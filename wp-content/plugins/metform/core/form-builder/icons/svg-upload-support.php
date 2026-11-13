<?php
/**
 * Safe SVG upload support for the Form Builder's icon pickers.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Icons;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMText;
use DOMXPath;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a user with the `upload_files` capability upload SVG files through
 * `wp.media` — needed for the Form Builder's icon pickers (see
 * `IconPickerControl.jsx`'s "Upload SVG" button and `mediaLibrary.js`'s
 * `mimeType` option), which point the media modal at `image/svg+xml`
 * specifically.
 *
 * WordPress core deliberately excludes SVG from its default allowed mimes:
 * an SVG is XML and can carry a `<script>` or event-handler attribute, so
 * serving one back verbatim is a stored-XSS vector. Even once the mime is
 * allowed, `wp_check_filetype_and_ext()` still rejects the upload ("This
 * file cannot be processed by the web server") because its finfo-based
 * real-mime sniff doesn't recognize SVG's XML content as `image/svg+xml`.
 *
 * This class allows the mime for capable users, corrects that sniff, and —
 * since allowing the mime alone would reopen the XSS vector above — rebuilds
 * every uploaded `.svg` file from a strict whitelist of SVG elements and
 * attributes (see {@see sanitize_svg_markup()}) before WordPress finishes
 * processing it.
 *
 * @since 4.1.9
 */
class Svg_Upload_Support {
	use Singleton;

	/**
	 * Registers the upload hooks.
	 *
	 * @since 4.1.9
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'upload_mimes', array( $this, 'allow_svg_mime' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_svg_filetype' ), 10, 4 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );
	}

	/**
	 * Adds `image/svg+xml` to the allowed upload mimes for users who can
	 * already upload files.
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, string> $mimes Currently allowed mime types, keyed by file extension.
	 * @return array<string, string> Filtered mime types.
	 */
	public function allow_svg_mime( array $mimes ): array {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $mimes;
		}

		$mimes['svg'] = 'image/svg+xml';

		return $mimes;
	}

	/**
	 * Corrects `wp_check_filetype_and_ext()`'s real-mime sniff for `.svg`
	 * files, which otherwise flags every SVG as an extension/content
	 * mismatch and blocks the upload with "This file cannot be processed
	 * by the web server."
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $data     {ext, type, proper_filename} as resolved so far.
	 * @param string               $file     Full path to the uploaded/sideloaded file.
	 * @param string               $filename The file's original name.
	 * @param array<string,string> $mimes    Allowed mime types.
	 * @return array<string, mixed> Filtered filetype data.
	 */
	public function fix_svg_filetype( $data, $file, $filename, $mimes ) {
		if ( ! current_user_can( 'upload_files' ) ) {
			return $data;
		}

		if ( '.svg' === strtolower( substr( (string) $filename, -4 ) ) ) {
			$data['ext']             = 'svg';
			$data['type']            = 'image/svg+xml';
			$data['proper_filename'] = $filename;
		}

		return $data;
	}

	/**
	 * Strips script-capable constructs from an uploaded SVG's markup
	 * before WordPress stores it, and rejects files carrying an XML
	 * external entity declaration outright (a class of attack no amount
	 * of tag/attribute stripping below reliably closes).
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $file One entry from `$_FILES`, as passed through `wp_handle_upload_prefilter`.
	 * @return array<string, mixed> The same entry, with `error` set when the file is rejected.
	 */
	public function sanitize_svg_upload( array $file ): array {
		// The multipart MIME type is supplied by the client and cannot decide
		// whether sanitization runs. WordPress allows the upload by extension,
		// and fix_svg_filetype() likewise recognizes .svg by filename, so use
		// that same server-controlled criterion here.
		$filename = isset( $file['name'] ) && is_scalar( $file['name'] ) ? (string) $file['name'] : '';
		if ( 'svg' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			return $file;
		}

		$path = $file['tmp_name'] ?? '';
		if ( ! $path || ! is_readable( $path ) ) {
			$file['error'] = __( 'The SVG file could not be read.', 'metform' );
			return $file;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading an uploaded SVG (already on local disk as a WP upload tmp file) to sanitize it, not fetching remote/arbitrary content.
		$svg = file_get_contents( $path );
		if ( false === $svg ) {
			$file['error'] = __( 'The SVG file could not be read.', 'metform' );
			return $file;
		}

		$clean = self::sanitize_svg_markup( $svg );
		if ( null === $clean ) {
			$file['error'] = __( 'This SVG file was rejected for security reasons.', 'metform' );
			return $file;
		}

		if ( $clean !== $svg ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing back the sanitized copy of the file WordPress itself just accepted as an upload, at its own tmp path.
			$written = file_put_contents( $path, $clean, LOCK_EX );
			if ( false === $written || strlen( $clean ) !== $written ) {
				$file['error'] = __( 'The sanitized SVG file could not be saved.', 'metform' );
				return $file;
			}
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort cache clear after rewriting the tmp file's size; failure here doesn't affect the sanitized content already written.
			clearstatcache( true, $path );
		}

		return $file;
	}

	/**
	 * SVG elements an uploaded icon may keep (compared lowercased). Anything
	 * else — `script`, `foreignObject`, `style`, `image`, `a`, the animation
	 * elements (`animate`, `set`, ... can rewrite attributes into
	 * `javascript:` URLs or event handlers), elements from other namespaces —
	 * is removed along with its content.
	 *
	 * @since 4.4.0
	 * @var string[]
	 */
	private const ALLOWED_ELEMENTS = array(
		'svg',
		'g',
		'path',
		'circle',
		'ellipse',
		'line',
		'polyline',
		'polygon',
		'rect',
		'defs',
		'title',
		'desc',
		'lineargradient',
		'radialgradient',
		'stop',
		'pattern',
		'clippath',
		'mask',
		'symbol',
		'use',
		'text',
		'tspan',
	);

	/**
	 * Attributes an allowed element may keep (compared lowercased, with any
	 * namespace prefix). No event handlers, no `style`.
	 *
	 * @since 4.4.0
	 * @var string[]
	 */
	private const ALLOWED_ATTRIBUTES = array(
		'id',
		'class',
		'xmlns',
		'xmlns:xlink',
		'version',
		'viewbox',
		'preserveaspectratio',
		'width',
		'height',
		'x',
		'y',
		'x1',
		'y1',
		'x2',
		'y2',
		'cx',
		'cy',
		'r',
		'rx',
		'ry',
		'fx',
		'fy',
		'd',
		'points',
		'transform',
		'fill',
		'fill-rule',
		'fill-opacity',
		'stroke',
		'stroke-width',
		'stroke-linecap',
		'stroke-linejoin',
		'stroke-miterlimit',
		'stroke-dasharray',
		'stroke-dashoffset',
		'stroke-opacity',
		'opacity',
		'color',
		'clip-path',
		'clip-rule',
		'mask',
		'offset',
		'stop-color',
		'stop-opacity',
		'gradientunits',
		'gradienttransform',
		'spreadmethod',
		'patternunits',
		'patterncontentunits',
		'patterntransform',
		'clippathunits',
		'maskunits',
		'maskcontentunits',
		'display',
		'visibility',
		'overflow',
		'vector-effect',
		'shape-rendering',
		'font-family',
		'font-size',
		'font-weight',
		'text-anchor',
		'dominant-baseline',
		'aria-hidden',
		'aria-label',
		'role',
		'focusable',
		'href',
		'xlink:href',
		'xml:space',
	);

	/**
	 * Sanitizes raw SVG markup against a strict whitelist.
	 *
	 * Shared by the classic `wp.media` upload path above (via
	 * `wp_handle_upload_prefilter`) and `Custom_Icon_Upload`'s REST upload
	 * endpoint, so there is exactly one SVG sanitizer for this feature.
	 *
	 * The markup is parsed as XML (network access off, entities never
	 * expanded) and rebuilt from what survives: only elements in the SVG
	 * namespace listed in {@see ALLOWED_ELEMENTS}, only attributes listed in
	 * {@see ALLOWED_ATTRIBUTES}, `href`/`xlink:href` only as a same-document
	 * `#id` reference, and `url(...)` values only as `url(#id)`. Comments,
	 * processing instructions, and the DOCTYPE are dropped. Because the
	 * checks run on parsed, entity-decoded values, encodings such as
	 * `jav&#x61;script:` can't slip through the way they can past a regex.
	 *
	 * `<style>` elements and `style` attributes never survive, but their
	 * safe declarations do, including gradient/pattern fills: see
	 * {@see inline_styles()}.
	 *
	 * Rejected outright (null): a DOCTYPE declaring an ENTITY (XXE / billion
	 * laughs), markup that doesn't parse as XML, a root element that isn't
	 * `<svg>`, or a server without PHP's DOM extension.
	 *
	 * @since 4.3.0
	 * @since 4.4.0 Whitelist-based (was a script/foreignObject/on*=/javascript: blacklist);
	 *              CSS colors and strokes are kept as presentation attributes.
	 *
	 * @param string $svg Raw SVG markup.
	 * @return string|null Sanitized markup, or `null` when the markup is rejected outright.
	 */
	public static function sanitize_svg_markup( string $svg ): ?string {
		if ( false !== stripos( $svg, '<!doctype' ) && false !== stripos( $svg, 'entity' ) ) {
			return null;
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			return null;
		}

		$dom            = new DOMDocument();
		$previous_state = libxml_use_internal_errors( true );
		// LIBXML_NONET: never fetch anything. Deliberately no LIBXML_NOENT
		// (which would *expand* entities) and no DTD loading.
		$loaded = $dom->loadXML( $svg, LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_state );

		$root = $dom->documentElement;
		if ( ! $loaded || ! $root || 'svg' !== strtolower( $root->localName ) || ! self::is_svg_namespace( $root ) ) {
			return null;
		}

		self::inline_styles( $root );
		self::sanitize_node( $root );

		// Nothing from another namespace survives, so drop their now-unused
		// declarations (sodipodi:, inkscape:, ...) too.
		foreach ( ( new DOMXPath( $dom ) )->query( 'namespace::*', $root ) as $namespace ) {
			if ( ! in_array( $namespace->prefix, array( '', 'xml', 'xlink' ), true ) ) {
				$root->removeAttributeNS( $namespace->namespaceURI, $namespace->prefix );
			}
		}

		$clean = $dom->saveXML( $root );
		return false === $clean ? null : $clean;
	}

	/**
	 * CSS properties {@see inline_styles()} may turn into presentation
	 * attributes, each mapped to the pattern its value must match. Keywords
	 * and named colors are letters only; nothing here admits a backslash
	 * escape or a quote. A `paint` is a color, or a `url(#id)` reference to a
	 * gradient or pattern in the same file (see {@see safe_style_value()}).
	 *
	 * @since 4.4.0
	 * @var array<string, string>
	 */
	private const STYLE_PROPERTIES = array(
		'fill'            => 'paint',
		'stroke'          => 'paint',
		'stop-color'      => 'color',
		'stop-opacity'    => '/^(?:\d*\.?\d+%?|inherit)$/',
		'fill-opacity'    => '/^(?:\d*\.?\d+%?|inherit)$/',
		'stroke-opacity'  => '/^(?:\d*\.?\d+%?|inherit)$/',
		'opacity'         => '/^(?:\d*\.?\d+%?|inherit)$/',
		'stroke-width'    => '/^(?:\d*\.?\d+(?:px|em|ex|rem|pt|pc|mm|cm|in|%)?|inherit)$/',
		'fill-rule'       => '/^(?:nonzero|evenodd|inherit)$/',
		'stroke-linecap'  => '/^(?:butt|round|square|inherit)$/',
		'stroke-linejoin' => '/^(?:miter|miter-clip|round|bevel|arcs|inherit)$/',
		'display'         => '/^(?:none|inline|block|inline-block|inherit)$/',
		'visibility'      => '/^(?:visible|hidden|collapse|inherit)$/',
	);

	/**
	 * Turns `<style>` rules and `style` attributes into presentation attributes.
	 *
	 * Design tools (Illustrator, Figma, Inkscape, ...) often color an SVG
	 * through CSS — `<style>.cls-1{fill:#e30}</style>` or
	 * `style="fill:#e30"` — and neither survives {@see sanitize_node()}, so
	 * without this such an icon would render black. Only the properties in
	 * {@see STYLE_PROPERTIES} with a value matching its pattern are
	 * converted; everything else (at-rules such as `@import`/`@media`,
	 * selectors with combinators, pseudo-classes or attribute matches,
	 * any `url(...)` but a local gradient/pattern reference, `expression`,
	 * `javascript:`, CSS escapes, other properties) is dropped.
	 *
	 * Supported selectors are simple ones — `tag`, `.class`, `#id`, and
	 * compounds like `path.cls-1` — in comma lists. The CSS cascade is kept:
	 * `!important` beats normal, the `style` attribute beats rules, higher
	 * specificity then later source order wins, and any result overrides an
	 * existing presentation attribute (which CSS would too).
	 *
	 * @since 4.4.0
	 *
	 * @param DOMElement $root The `<svg>` root.
	 * @return void
	 */
	private static function inline_styles( DOMElement $root ): void {
		$elements = array( $root );
		foreach ( $root->getElementsByTagName( '*' ) as $element ) {
			$elements[] = $element;
		}

		$paint_servers = self::paint_server_ids( $elements );

		$rules = array();
		foreach ( $elements as $element ) {
			if ( 'style' === strtolower( $element->localName ) && self::is_svg_namespace( $element ) ) {
				$rules = array_merge( $rules, self::parse_css_rules( $element->textContent, count( $rules ), $paint_servers ) );
			}
		}

		foreach ( $elements as $element ) {
			if ( ! self::is_svg_namespace( $element ) ) {
				continue;
			}

			// property => array( weight, value ); weight = array( important, inline, specificity, order ).
			$winners = array();
			$apply   = static function ( array $declarations, array $weight ) use ( &$winners ): void {
				foreach ( $declarations as $property => list( $value, $important ) ) {
					$candidate = array( $important ? 1 : 0, $weight[0], $weight[1], $weight[2] );
					if ( ! isset( $winners[ $property ] ) || $candidate >= $winners[ $property ][0] ) {
						$winners[ $property ] = array( $candidate, $value );
					}
				}
			};

			foreach ( $rules as $rule ) {
				if ( self::selector_matches( $rule['selector'], $element ) ) {
					$apply( $rule['declarations'], array( 0, $rule['specificity'], $rule['order'] ) );
				}
			}

			if ( $element->hasAttribute( 'style' ) ) {
				$apply( self::parse_css_declarations( $element->getAttribute( 'style' ), $paint_servers ), array( 1, 0, 0 ) );
				$element->removeAttribute( 'style' );
			}

			foreach ( $winners as $property => $winner ) {
				$element->setAttribute( $property, $winner[1] );
			}
		}
	}

	/**
	 * Collects the ids a CSS `url(#id)` paint may reference.
	 *
	 * An id qualifies when the first element carrying it (the one a browser
	 * resolves) is a `linearGradient`, `radialGradient` or `pattern` that
	 * survives {@see sanitize_node()}: it and every ancestor are whitelisted
	 * SVG elements.
	 *
	 * @since 4.4.0
	 *
	 * @param DOMElement[] $elements The `<svg>` root and its descendants, in document order.
	 * @return array<string, true> Qualifying ids.
	 */
	private static function paint_server_ids( array $elements ): array {
		$seen = array();
		$ids  = array();

		foreach ( $elements as $element ) {
			$id = $element->getAttribute( 'id' );
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;

			if ( ! in_array( strtolower( $element->localName ), array( 'lineargradient', 'radialgradient', 'pattern' ), true ) ) {
				continue;
			}

			for ( $node = $element; $node instanceof DOMElement; $node = $node->parentNode ) {
				if ( ! in_array( strtolower( $node->localName ), self::ALLOWED_ELEMENTS, true ) || ! self::is_svg_namespace( $node ) ) {
					continue 2;
				}
			}
			$ids[ $id ] = true;
		}

		return $ids;
	}

	/**
	 * Parses a stylesheet into simple-selector rules, skipping at-rules and anything else unsupported.
	 *
	 * @since 4.4.0
	 *
	 * @param string              $css           Stylesheet text.
	 * @param int                 $order_start   Source-order offset (rules from earlier `<style>` elements).
	 * @param array<string, true> $paint_servers Ids a `url(#id)` paint may reference.
	 * @return array<int, array{selector: array, specificity: int, order: int, declarations: array}>
	 */
	private static function parse_css_rules( string $css, int $order_start, array $paint_servers ): array {
		$css    = (string) preg_replace( '#/\*.*?(?:\*/|$)#s', '', $css );
		$css    = str_replace( array( '<!--', '-->' ), ' ', $css );
		$length = strlen( $css );
		$rules  = array();
		$order  = $order_start;
		$pos    = 0;

		while ( $pos < $length ) {
			$open = strpos( $css, '{', $pos );
			$semi = strpos( $css, ';', $pos );
			$head = trim( substr( $css, $pos, ( false === $open ? $length : $open ) - $pos ) );

			// Statement at-rule (`@import ...;`, `@charset ...;`): skip to its `;`.
			if ( '' !== $head && '@' === $head[0] && false !== $semi && ( false === $open || $semi < $open ) ) {
				$pos = $semi + 1;
				continue;
			}
			if ( false === $open ) {
				break;
			}

			// Find the matching `}` so a block at-rule (`@media { ... }`) is skipped whole.
			$depth = 0;
			$end   = $open;
			for ( ; $end < $length; $end++ ) {
				if ( '{' === $css[ $end ] ) {
					++$depth;
				} elseif ( '}' === $css[ $end ] && 0 === --$depth ) {
					break;
				}
			}
			$body = substr( $css, $open + 1, $end - $open - 1 );
			$pos  = $end + 1;

			if ( '@' === ( $head[0] ?? '@' ) || false !== strpos( $body, '{' ) ) {
				continue;
			}

			$declarations = self::parse_css_declarations( $body, $paint_servers );
			if ( ! $declarations ) {
				continue;
			}

			foreach ( explode( ',', $head ) as $selector_text ) {
				$selector = self::parse_selector( trim( $selector_text ) );
				if ( null !== $selector ) {
					$rules[] = array(
						'selector'     => $selector,
						'specificity'  => 100 * count( $selector['ids'] ) + 10 * count( $selector['classes'] ) + ( '' === $selector['tag'] ? 0 : 1 ),
						'order'        => $order++,
						'declarations' => $declarations,
					);
				}
			}
		}

		return $rules;
	}

	/**
	 * Parses `prop: value; ...` into the whitelisted, validated declarations.
	 *
	 * @since 4.4.0
	 *
	 * @param string              $css           Declaration block (no braces).
	 * @param array<string, true> $paint_servers Ids a `url(#id)` paint may reference.
	 * @return array<string, array{0: string, 1: bool}> property => array( value, important ).
	 */
	private static function parse_css_declarations( string $css, array $paint_servers ): array {
		$declarations = array();

		foreach ( explode( ';', $css ) as $declaration ) {
			$colon = strpos( $declaration, ':' );
			if ( false === $colon ) {
				continue;
			}

			$property = strtolower( trim( substr( $declaration, 0, $colon ) ) );
			$value    = trim( substr( $declaration, $colon + 1 ) );
			if ( ! isset( self::STYLE_PROPERTIES[ $property ] ) ) {
				continue;
			}

			$important = (bool) preg_match( '/!\s*important$/i', $value );
			if ( $important ) {
				$value = rtrim( (string) preg_replace( '/!\s*important$/i', '', $value ) );
			}

			$value = self::safe_style_value( $property, $value, $paint_servers );
			if ( null !== $value && ( $important || empty( $declarations[ $property ][1] ) ) ) {
				$declarations[ $property ] = array( $value, $important );
			}
		}

		return $declarations;
	}

	/**
	 * Validates one CSS value for {@see STYLE_PROPERTIES}.
	 *
	 * A `paint` value may also be exactly `url(#id)` — a local fragment, no
	 * quotes, paths, protocols or escapes — naming one of `$paint_servers`.
	 * Any other `url(` is rejected.
	 *
	 * @since 4.4.0
	 *
	 * @param string              $property      Whitelisted property name.
	 * @param string              $value         Trimmed value, without `!important`.
	 * @param array<string, true> $paint_servers Ids a `url(#id)` paint may reference.
	 * @return string|null The value to store, or null when it's rejected.
	 */
	private static function safe_style_value( string $property, string $value, array $paint_servers ): ?string {
		$type = self::STYLE_PROPERTIES[ $property ];

		if ( 'paint' === $type && preg_match( '/^url\(\s*#([A-Za-z][A-Za-z0-9_.:-]*)\s*\)$/', $value, $match ) ) {
			return isset( $paint_servers[ $match[1] ] ) ? "url(#{$match[1]})" : null;
		}

		$lower = strtolower( $value );
		foreach ( array( 'url(', 'expression', '@import', 'javascript:', '\\' ) as $forbidden ) {
			if ( false !== strpos( $lower, $forbidden ) ) {
				return null;
			}
		}

		if ( 'paint' !== $type && 'color' !== $type ) {
			return preg_match( $type, $lower ) ? $value : null;
		}

		return preg_match( '/^(?:#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})|[a-z]{3,20}|(?:rgba?|hsla?)\((?:[0-9.,%\s\/+-]|deg)*\))$/', $lower ) ? $value : null;
	}

	/**
	 * Parses a simple selector (`tag`, `.class`, `#id`, or a compound of them).
	 *
	 * @since 4.4.0
	 *
	 * @param string $selector Selector text.
	 * @return array{tag: string, ids: string[], classes: string[]}|null Null when unsupported.
	 */
	private static function parse_selector( string $selector ): ?array {
		if ( ! preg_match( '/^([a-z][a-z0-9-]*)?((?:[.#]-?[_a-z][\w-]*)*)$/i', $selector, $match ) || '' === $selector ) {
			return null;
		}

		preg_match_all( '/([.#])(-?[_a-z][\w-]*)/i', $match[2], $parts, PREG_SET_ORDER );
		$parsed = array(
			'tag'     => strtolower( $match[1] ?? '' ),
			'ids'     => array(),
			'classes' => array(),
		);
		foreach ( $parts as $part ) {
			$parsed[ '#' === $part[1] ? 'ids' : 'classes' ][] = $part[2];
		}

		return $parsed;
	}

	/**
	 * Checks whether a parsed simple selector matches an element.
	 *
	 * @since 4.4.0
	 *
	 * @param array{tag: string, ids: string[], classes: string[]} $selector Parsed selector.
	 * @param DOMElement                                           $element  Element.
	 * @return bool
	 */
	private static function selector_matches( array $selector, DOMElement $element ): bool {
		if ( '' !== $selector['tag'] && strtolower( $element->localName ) !== $selector['tag'] ) {
			return false;
		}

		foreach ( $selector['ids'] as $id ) {
			if ( $element->getAttribute( 'id' ) !== $id ) {
				return false;
			}
		}

		$classes = preg_split( '/\s+/', trim( $element->getAttribute( 'class' ) ) );
		foreach ( $selector['classes'] as $class ) {
			if ( ! in_array( $class, $classes, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Recursively strips disallowed children, attributes, and non-element nodes.
	 *
	 * @since 4.4.0
	 *
	 * @param DOMElement $element Element whose attributes and subtree are sanitized.
	 * @return void
	 */
	private static function sanitize_node( DOMElement $element ): void {
		$attributes = array();
		foreach ( $element->attributes as $attribute ) {
			$attributes[] = $attribute;
		}
		foreach ( $attributes as $attribute ) {
			if ( ! self::is_allowed_attribute( $attribute ) ) {
				$element->removeAttributeNode( $attribute );
			}
		}

		$children = array();
		foreach ( $element->childNodes as $child ) {
			$children[] = $child;
		}
		foreach ( $children as $child ) {
			if ( $child instanceof DOMElement ) {
				if ( in_array( strtolower( $child->localName ), self::ALLOWED_ELEMENTS, true ) && self::is_svg_namespace( $child ) ) {
					self::sanitize_node( $child );
				} else {
					$element->removeChild( $child );
				}
			} elseif ( ! ( $child instanceof DOMText ) ) {
				// Comments, processing instructions, entity references, ...
				// (DOMCdataSection extends DOMText and is kept as text.)
				$element->removeChild( $child );
			}
		}
	}

	/**
	 * Checks one attribute against the whitelist and its value against URL/URI rules.
	 *
	 * @since 4.4.0
	 *
	 * @param DOMAttr $attribute Attribute.
	 * @return bool
	 */
	private static function is_allowed_attribute( DOMAttr $attribute ): bool {
		$name = strtolower( $attribute->nodeName );
		if ( ! in_array( $name, self::ALLOWED_ATTRIBUTES, true ) ) {
			return false;
		}

		// Parsed values are already entity-decoded; drop control/whitespace
		// characters a browser would ignore inside a URL scheme.
		$value = strtolower( (string) preg_replace( '/[\x00-\x20]+/', '', $attribute->value ) );

		if ( 'href' === $name || 'xlink:href' === $name ) {
			return (bool) preg_match( '/^#[a-z0-9_.:-]+$/', $value );
		}

		if ( false !== strpos( $value, 'url(' ) ) {
			return (bool) preg_match( '/^url\(["\']?#[a-z0-9_.:-]+["\']?\)$/', $value );
		}

		return false === strpos( $value, 'javascript:' ) && false === strpos( $value, 'data:' ) && false === strpos( $value, 'expression(' );
	}

	/**
	 * Checks an element is in the SVG namespace (or has none, for bare-markup SVGs).
	 *
	 * @since 4.4.0
	 *
	 * @param DOMElement $element Element.
	 * @return bool
	 */
	private static function is_svg_namespace( DOMElement $element ): bool {
		return null === $element->namespaceURI || '' === $element->namespaceURI || 'http://www.w3.org/2000/svg' === $element->namespaceURI;
	}
}
