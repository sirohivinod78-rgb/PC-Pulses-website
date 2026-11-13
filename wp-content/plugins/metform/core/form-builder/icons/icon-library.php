<?php
/**
 * JSON-backed icon library registry.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for MetForm's bundled icon sets (Font Awesome today).
 *
 * A form stores only an icon *key* such as `fas-star` — never SVG markup.
 * This class resolves a key against its library's generated JSON file (see
 * `bin/build-font-awesome-icons.mjs`) and builds the `<svg>` from a fixed
 * template at render time, re-validating the definition on every render so
 * that nothing read from disk or cache is ever output unchecked.
 *
 * Libraries are registered through the {@see 'metform/icons/libraries'}
 * filter, so MetForm Pro or a future library can add icon sets without
 * changing this class. Each library is loaded lazily, the first time one of
 * its icons is looked up — a request that renders no icon never loads any.
 *
 * Caching, per library:
 * - Static: a library is decoded at most once per request.
 * - Persistent: the decoded data is written once as a PHP file returning
 *   the array, under `uploads/metform/cache/icons/`, so OPcache serves it
 *   from shared memory on later requests instead of re-decoding the JSON.
 *   The file name carries a fingerprint of the source files (path, size,
 *   modification time) plus the plugin version and cache format, so any
 *   change to the JSON — including a plugin update — makes the next
 *   request regenerate it. It is written atomically (temporary file +
 *   rename), and when it can't be written this class simply decodes the
 *   JSON each request instead.
 *
 * @since 4.4.0
 */
class Icon_Library {

	/**
	 * Shared class on every rendered library icon; carries Font Awesome's own
	 * `svg-with-js` sizing (see `blocks/shared/icon.scss`) so an SVG takes the
	 * expected inline dimensions and baseline alignment.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const SVG_CLASS = 'mf-svg-icon';

	/**
	 * Default library ID.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const DEFAULT_LIBRARY = 'font-awesome';

	/**
	 * The Font Awesome library's generated JSON, relative to the plugin root.
	 * Build output (`npm run build:assets`), not source — see
	 * `bin/build-font-awesome-icons.mjs`.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const FONT_AWESOME_FILE = 'build/assets/fonts/font-awesome/font-awesome.json';

	/**
	 * Icon key: a registered style prefix, a hyphen, then a kebab-case name.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	private const KEY_PATTERN = '/^([a-z][a-z0-9]{1,9})-([a-z0-9]+(?:-[a-z0-9]+)*)$/';

	/**
	 * Style prefix (e.g. `fas`) accepted from a library registration.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	private const PREFIX_PATTERN = '/^[a-z][a-z0-9]{1,9}$/';

	/**
	 * A viewBox: exactly four numbers, width and height non-negative.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	private const VIEWBOX_PATTERN = '/^-?\d+(?:\.\d+)? -?\d+(?:\.\d+)? \d+(?:\.\d+)? \d+(?:\.\d+)?$/';

	/**
	 * Path data: SVG path commands, numbers, and separators only.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	private const PATH_PATTERN = '/^[MmZzLlHhVvCcSsQqTtAa0-9eE.,+\-\s]+$/';

	/**
	 * A width/height render argument: a number with an optional CSS unit.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	private const DIMENSION_PATTERN = '/^\d+(?:\.\d+)?(?:px|em|rem|%)?$/';

	/**
	 * Bumped whenever the generated cache file's shape changes.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	private const CACHE_FORMAT = 2;

	/**
	 * Validated library registrations, keyed by library ID. Null until first needed.
	 *
	 * @since 4.4.0
	 * @var array<string, array>|null
	 */
	private static $libraries = null;

	/**
	 * Style prefix => [library ID, style name].
	 *
	 * @since 4.4.0
	 * @var array<string, array{0: string, 1: string}>
	 */
	private static $prefixes = array();

	/**
	 * Loaded library data, keyed by library ID: `['icons' => [...]]`.
	 *
	 * @since 4.4.0
	 * @var array<string, array>
	 */
	private static $data = array();

	/**
	 * Where each loaded library's data came from this request (`cache`, `json`, or `none`) — diagnostics/tests only.
	 *
	 * @since 4.4.0
	 * @var array<string, string>
	 */
	private static $sources = array();

	/**
	 * Gets every icon across all registered libraries.
	 *
	 * @since 4.4.0
	 *
	 * @return array<string, array> Icon definitions, keyed by icon key.
	 */
	public static function get_all(): array {
		$all = array();
		foreach ( array_keys( self::libraries() ) as $library_id ) {
			$all += self::load( $library_id )['icons'];
		}
		return $all;
	}

	/**
	 * Gets every icon from one registered library.
	 *
	 * @since 4.4.0
	 *
	 * @param string $library Library ID.
	 * @return array<string, array> Icon definitions, keyed by icon key.
	 */
	public static function get_library_icons( string $library = self::DEFAULT_LIBRARY ): array {
		return isset( self::libraries()[ $library ] ) ? self::load( $library )['icons'] : array();
	}

	/**
	 * Gets one icon's definition.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $key Icon key, e.g. "fas-star".
	 * @return array|null `{label, style, viewBox, path, search_terms}`, or null for an invalid/unknown key.
	 */
	public static function get( $key ): ?array {
		$library_id = self::library_for_key( $key );
		if ( '' === $library_id ) {
			return null;
		}

		$icon = self::load( $library_id )['icons'][ $key ] ?? null;

		return is_array( $icon ) ? $icon : null;
	}

	/**
	 * Checks whether a key names a known icon.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $key Icon key.
	 * @return bool
	 */
	public static function exists( $key ): bool {
		return null !== self::get( $key );
	}

	/**
	 * Checks whether a value is shaped like an icon key with a registered
	 * prefix — without loading any library data. Lets callers tell a key
	 * apart from other stored value shapes cheaply.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $value Any value.
	 * @return bool
	 */
	public static function is_key( $value ): bool {
		return '' !== self::library_for_key( $value );
	}

	/**
	 * Renders an icon as inline SVG.
	 *
	 * The markup comes from a fixed template, never from stored markup: only
	 * the definition's validated viewBox and path data are interpolated, and
	 * every attribute is escaped.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $key  Icon key.
	 * @param array $args {
	 *     Optional. Only these keys are read; anything else is ignored.
	 *
	 *     @type string     $class      Extra CSS classes (added after {@see SVG_CLASS}).
	 *     @type string|int $width      Width attribute: a number with an optional px/em/rem/% unit.
	 *     @type string|int $height     Height attribute, same format.
	 *     @type string     $aria-label Accessible name. When set, the icon gets
	 *                                  `role="img"` instead of `aria-hidden="true"`.
	 *     @type string     $role       Role override (letters only); only used with an aria-label.
	 * }
	 * @return string SVG markup, or '' for an invalid/unknown key or an invalid definition.
	 */
	public static function render( $key, array $args = array() ): string {
		$icon = self::get( $key );
		if ( null === $icon || ! self::is_valid_definition( $icon ) ) {
			return '';
		}

		$classes = array( self::SVG_CLASS );
		if ( isset( $args['class'] ) && is_string( $args['class'] ) ) {
			foreach ( preg_split( '/\s+/', $args['class'], -1, PREG_SPLIT_NO_EMPTY ) as $class_name ) {
				$class_name = sanitize_html_class( $class_name );
				if ( '' !== $class_name ) {
					$classes[] = $class_name;
				}
			}
		}

		$attributes = array(
			'xmlns'   => 'http://www.w3.org/2000/svg',
			'viewBox' => $icon['viewBox'],
			'class'   => implode( ' ', array_unique( $classes ) ),
		);

		foreach ( array( 'width', 'height' ) as $dimension ) {
			if ( isset( $args[ $dimension ] ) && is_scalar( $args[ $dimension ] ) && preg_match( self::DIMENSION_PATTERN, (string) $args[ $dimension ] ) ) {
				$attributes[ $dimension ] = (string) $args[ $dimension ];
			}
		}

		$label = isset( $args['aria-label'] ) && is_string( $args['aria-label'] ) ? trim( $args['aria-label'] ) : '';
		if ( '' !== $label ) {
			$role                     = isset( $args['role'] ) && is_string( $args['role'] ) && preg_match( '/^[a-z]+$/', $args['role'] ) ? $args['role'] : 'img';
			$attributes['role']       = $role;
			$attributes['aria-label'] = $label;
		} else {
			$attributes['aria-hidden'] = 'true';
		}

		$attributes['focusable'] = 'false';
		$attributes['fill']      = 'currentColor';

		$html = '<svg';
		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . $name . '="' . esc_attr( $value ) . '"';
		}
		$html .= '>';
		foreach ( (array) $icon['path'] as $path ) {
			$html .= '<path d="' . esc_attr( $path ) . '"/>';
		}

		return $html . '</svg>';
	}

	/**
	 * Searches a library's icons, for the editor's icon picker.
	 *
	 * Every whitespace-separated term must match the icon's name, label, or
	 * one of its search terms. Results are ranked exact name, then name
	 * prefix, then name/label substring, then search-term-only matches, and
	 * otherwise keep the library's own order.
	 *
	 * @since 4.4.0
	 *
	 * @param string $query    Search text; '' lists everything.
	 * @param string $style    Style name (e.g. 'solid') or 'all'.
	 * @param int    $page     1-based page number.
	 * @param int    $per_page Page size (clamped to 1-500).
	 * @param string $library  Library ID.
	 * @return array{items: array<string, array>, total: int, page: int, per_page: int, pages: int}
	 */
	public static function search( string $query = '', string $style = 'all', int $page = 1, int $per_page = 200, string $library = self::DEFAULT_LIBRARY ): array {
		$per_page = max( 1, min( 500, $per_page ) );
		$page     = max( 1, $page );
		$terms    = preg_split( '/\s+/', strtolower( trim( $query ) ), -1, PREG_SPLIT_NO_EMPTY );
		$icons    = isset( self::libraries()[ $library ] ) ? self::load( $library )['icons'] : array();

		$ranked = array();
		$order  = 0;
		foreach ( $icons as $key => $icon ) {
			++$order;
			if ( 'all' !== $style && ( $icon['style'] ?? '' ) !== $style ) {
				continue;
			}

			$rank = 0;
			if ( $terms ) {
				$rank = self::match_rank( substr( $key, strpos( $key, '-' ) + 1 ), strtolower( (string) ( $icon['label'] ?? '' ) ), (array) ( $icon['search_terms'] ?? array() ), $terms );
				if ( null === $rank ) {
					continue;
				}
			}
			$ranked[ $key ] = array( $rank, $order );
		}

		if ( $terms ) {
			uasort(
				$ranked,
				static function ( $a, $b ) {
					return $a <=> $b;
				}
			);
		}

		$total = count( $ranked );
		$keys  = array_slice( array_keys( $ranked ), ( $page - 1 ) * $per_page, $per_page );

		$items = array();
		foreach ( $keys as $key ) {
			$items[ $key ] = $icons[ $key ];
		}

		return array(
			'items'    => $items,
			'total'    => $total,
			'page'     => $page,
			'per_page' => $per_page,
			'pages'    => (int) ceil( $total / $per_page ),
		);
	}

	/**
	 * Counts a library's icons per style, plus an `all` total.
	 *
	 * @since 4.4.0
	 *
	 * @param string $library Library ID.
	 * @return array<string, int>
	 */
	public static function count_by_style( string $library = self::DEFAULT_LIBRARY ): array {
		$counts = array( 'all' => 0 );
		foreach ( array_keys( self::libraries()[ $library ]['styles'] ?? array() ) as $style ) {
			$counts[ $style ] = 0;
		}
		if ( ! isset( self::libraries()[ $library ] ) ) {
			return $counts;
		}
		foreach ( self::load( $library )['icons'] as $icon ) {
			$style = $icon['style'] ?? '';
			if ( isset( $counts[ $style ] ) ) {
				++$counts[ $style ];
			}
			++$counts['all'];
		}
		return $counts;
	}

	/**
	 * Gets the registered libraries, validated.
	 *
	 * @since 4.4.0
	 *
	 * @return array<string, array{label: string, file: string, styles: array<string, array{prefix: string, label: string}>}>
	 */
	public static function libraries(): array {
		if ( null !== self::$libraries ) {
			return self::$libraries;
		}

		/**
		 * Filters the icon libraries MetForm can render.
		 *
		 * Each library is keyed by a unique ID and needs a generated JSON
		 * file in the same shape as `font-awesome.json` (an object keyed by
		 * `<prefix>-<name>`) and at least one style with a unique prefix.
		 * Invalid registrations, and prefixes already claimed by an earlier
		 * library, are ignored.
		 *
		 * @since 4.4.0
		 *
		 * @param array<string, array> $libraries {
		 *     @type string $label          Display label.
		 *     @type string $file           Absolute path to the library's icon JSON.
		 *     @type array  $styles         Style name => `['prefix' => 'fas', 'label' => 'Solid']`.
		 * }
		 */
		$registered = apply_filters(
			'metform/icons/libraries',
			array(
				self::DEFAULT_LIBRARY => array(
					'label'  => __( 'Font Awesome', 'metform' ),
					'file'   => METFORM_FREE_PATH . self::FONT_AWESOME_FILE,
					'styles' => array(
						'solid'   => array(
							'prefix' => 'fas',
							'label'  => __( 'Solid', 'metform' ),
						),
						'regular' => array(
							'prefix' => 'far',
							'label'  => __( 'Regular', 'metform' ),
						),
						'brands'  => array(
							'prefix' => 'fab',
							'label'  => __( 'Brands', 'metform' ),
						),
					),
				),
			)
		);

		self::$libraries = array();
		self::$prefixes  = array();

		foreach ( is_array( $registered ) ? $registered : array() as $library_id => $library ) {
			$library_id = sanitize_key( (string) $library_id );
			if ( '' === $library_id || ! is_array( $library ) || empty( $library['file'] ) || ! is_string( $library['file'] ) || empty( $library['styles'] ) || ! is_array( $library['styles'] ) ) {
				continue;
			}

			$styles = array();
			foreach ( $library['styles'] as $style => $style_config ) {
				$prefix = is_array( $style_config ) ? (string) ( $style_config['prefix'] ?? '' ) : '';
				$style  = sanitize_key( (string) $style );
				if ( '' === $style || 'all' === $style || ! preg_match( self::PREFIX_PATTERN, $prefix ) || isset( self::$prefixes[ $prefix ] ) ) {
					continue;
				}
				self::$prefixes[ $prefix ] = array( $library_id, $style );
				$styles[ $style ]          = array(
					'prefix' => $prefix,
					'label'  => (string) ( $style_config['label'] ?? $style ),
				);
			}

			if ( ! $styles ) {
				continue;
			}

			self::$libraries[ $library_id ] = array(
				'label'  => (string) ( $library['label'] ?? $library_id ),
				'file'   => $library['file'],
				'styles' => $styles,
			);
		}

		return self::$libraries;
	}

	/**
	 * Describes the registered libraries for the editor — IDs, labels, and
	 * styles with their key prefixes; no icon data, so it never loads a library.
	 *
	 * @since 4.4.0
	 *
	 * @return array<int, array{id: string, label: string, styles: array<int, array{key: string, label: string, prefix: string}>}>
	 */
	public static function describe_libraries(): array {
		$described = array();
		foreach ( self::libraries() as $library_id => $library ) {
			$styles = array();
			foreach ( $library['styles'] as $style => $style_config ) {
				$styles[] = array(
					'key'    => $style,
					'label'  => $style_config['label'],
					'prefix' => $style_config['prefix'],
				);
			}
			$described[] = array(
				'id'     => $library_id,
				'label'  => $library['label'],
				'styles' => $styles,
			);
		}
		return $described;
	}

	/**
	 * Gets where each library loaded this request came from — for tests and performance diagnostics.
	 *
	 * @since 4.4.0
	 *
	 * @return array<string, string> Library ID => 'cache' | 'json' | 'none'.
	 */
	public static function get_load_sources(): array {
		return self::$sources;
	}

	/**
	 * Clears the static (per-request) cache. Tests only — also drops library registrations.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$libraries = null;
		self::$prefixes  = array();
		self::$data      = array();
		self::$sources   = array();
	}

	/**
	 * Gets the persistent cache directory (no trailing slash), or '' when
	 * uploads are unavailable.
	 *
	 * On multisite every site shares the main site's directory: the icon
	 * data is identical network-wide, so one cache file per library version
	 * serves every site (and one OPcache entry), instead of one copy per site.
	 *
	 * @since 4.4.0
	 *
	 * @return string
	 */
	public static function get_cache_dir(): string {
		$uploads = wp_upload_dir( null, false );
		$basedir = empty( $uploads['error'] ) ? (string) ( $uploads['basedir'] ?? '' ) : '';

		if ( '' !== $basedir && is_multisite() && ! is_main_site() ) {
			$basedir = self::network_basedir( $basedir, get_current_blog_id() );
		}

		$dir = '' !== $basedir ? wp_normalize_path( $basedir ) . '/metform/cache/icons' : '';

		/**
		 * Filters the icon library cache directory. Return '' to disable the persistent cache.
		 *
		 * @since 4.4.0
		 *
		 * @param string $dir Absolute directory path, no trailing slash.
		 */
		return untrailingslashit( (string) apply_filters( 'metform/icons/cache_dir', $dir ) );
	}

	/**
	 * Maps a multisite sub-site's uploads basedir ("<main basedir>/sites/<blog id>")
	 * to the shared, network-level one. Any other layout (e.g. the legacy
	 * `blogs.dir` one) is returned unchanged, i.e. stays per-site.
	 *
	 * @since 4.4.0
	 *
	 * @param string $basedir Sub-site uploads basedir.
	 * @param int    $blog_id Current blog ID.
	 * @return string
	 */
	private static function network_basedir( string $basedir, int $blog_id ): string {
		return (string) preg_replace( '#[/\\\\]sites[/\\\\]' . $blog_id . '$#', '', $basedir );
	}

	/**
	 * Resolves the library a key's prefix belongs to, or '' when the value
	 * isn't a well-formed key with a registered prefix.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $key Candidate icon key.
	 * @return string Library ID, or ''.
	 */
	private static function library_for_key( $key ): string {
		if ( ! is_string( $key ) || strlen( $key ) > 100 || ! preg_match( self::KEY_PATTERN, $key, $matches ) ) {
			return '';
		}

		self::libraries();

		return self::$prefixes[ $matches[1] ][0] ?? '';
	}

	/**
	 * Validates a definition before it is rendered.
	 *
	 * @since 4.4.0
	 *
	 * @param array $icon Icon definition.
	 * @return bool
	 */
	private static function is_valid_definition( array $icon ): bool {
		if ( ! isset( $icon['viewBox'] ) || ! is_string( $icon['viewBox'] ) || ! preg_match( self::VIEWBOX_PATTERN, $icon['viewBox'] ) ) {
			return false;
		}

		$paths = (array) ( $icon['path'] ?? array() );
		if ( ! $paths ) {
			return false;
		}
		foreach ( $paths as $path ) {
			if ( ! is_string( $path ) || '' === $path || ! preg_match( self::PATH_PATTERN, $path ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Ranks how well an icon matches every search term (lower is better), or null when a term doesn't match.
	 *
	 * @since 4.4.0
	 *
	 * @param string   $name         Icon name (key without prefix).
	 * @param string   $label        Lowercased label.
	 * @param string[] $search_terms Icon search terms.
	 * @param string[] $terms        Lowercased query terms.
	 * @return int|null
	 */
	private static function match_rank( string $name, string $label, array $search_terms, array $terms ): ?int {
		$worst = 0;
		foreach ( $terms as $term ) {
			if ( $name === $term ) {
				$rank = 0;
			} elseif ( 0 === strpos( $name, $term ) ) {
				$rank = 1;
			} elseif ( false !== strpos( $name, $term ) || false !== strpos( $label, $term ) ) {
				$rank = 2;
			} else {
				$rank = null;
				foreach ( $search_terms as $search_term ) {
					if ( false !== strpos( (string) $search_term, $term ) ) {
						$rank = 3;
						break;
					}
				}
				if ( null === $rank ) {
					return null;
				}
			}
			$worst = max( $worst, $rank );
		}
		return $worst;
	}

	/**
	 * Loads a library's icons: static cache, then the persistent PHP cache
	 * file, then the source JSON (writing the cache file for next time).
	 *
	 * @since 4.4.0
	 *
	 * @param string $library_id Library ID.
	 * @return array{icons: array<string, array>}
	 */
	private static function load( string $library_id ): array {
		if ( isset( self::$data[ $library_id ] ) ) {
			return self::$data[ $library_id ];
		}

		$empty   = array( 'icons' => array() );
		$library = self::libraries()[ $library_id ] ?? null;
		if ( null === $library || ! is_file( $library['file'] ) ) {
			if ( null !== $library && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-mode only notice for developers.
				error_log( sprintf( 'MetForm: the "%1$s" icon library file "%2$s" is missing, so its icons will not render. Run `npm run build:assets` to generate it.', $library_id, $library['file'] ) );
			}
			self::$sources[ $library_id ]     = 'none';
			return self::$data[ $library_id ] = $empty; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
		}

		$cache_file = self::cache_file( $library_id, $library );
		if ( '' !== $cache_file && is_file( $cache_file ) ) {
			$cached = include $cache_file;
			if ( is_array( $cached ) && isset( $cached['icons'] ) && is_array( $cached['icons'] ) ) {
				self::$sources[ $library_id ]     = 'cache';
				return self::$data[ $library_id ] = $cached; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
			}
		}

		$data = array( 'icons' => self::read_json( $library['file'] ) );

		self::$sources[ $library_id ] = 'json';
		if ( '' !== $cache_file && $data['icons'] ) {
			self::write_cache_file( $cache_file, $library_id, $data );
		}

		return self::$data[ $library_id ] = $data; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found
	}

	/**
	 * Decodes a JSON file into an array ('' / invalid JSON -> empty array).
	 *
	 * @since 4.4.0
	 *
	 * @param string $file Absolute path.
	 * @return array
	 */
	private static function read_json( string $file ): array {
		if ( ! is_readable( $file ) ) {
			return array();
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local, bundled plugin file.
		$data = json_decode( (string) file_get_contents( $file ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * Builds a library's cache file path, or '' when caching is disabled.
	 *
	 * @since 4.4.0
	 *
	 * @param string $library_id Library ID.
	 * @param array  $library    Library registration.
	 * @return string
	 */
	private static function cache_file( string $library_id, array $library ): string {
		$dir = self::get_cache_dir();
		if ( '' === $dir ) {
			return '';
		}

		$file        = $library['file'];
		$fingerprint = array(
			self::CACHE_FORMAT,
			defined( 'METFORM_VERSION' ) ? METFORM_VERSION : '',
			$file . '|' . (int) filesize( $file ) . '|' . (int) filemtime( $file ),
		);

		return $dir . '/' . $library_id . '-' . substr( md5( implode( "\n", $fingerprint ) ), 0, 16 ) . '.php';
	}

	/**
	 * Writes a library's cache file atomically and removes its stale versions.
	 * Failures are silent: the caller already has the data, and the next
	 * request simply tries again.
	 *
	 * @since 4.4.0
	 *
	 * @param string $cache_file Target path.
	 * @param string $library_id Library ID.
	 * @param array  $data       Library data.
	 * @return void
	 */
	private static function write_cache_file( string $cache_file, string $library_id, array $data ): void {
		$dir = dirname( $cache_file );
		if ( ! self::prepare_cache_dir( $dir ) ) {
			return;
		}

		$php = "<?php\n// Generated by MetForm from the \"{$library_id}\" icon library. Do not edit: regenerated automatically when the library changes.\ndefined( 'ABSPATH' ) || exit;\nreturn " . var_export( $data, true ) . ";\n"; // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_export

		// Write to a unique temporary file in the same directory, then rename
		// it into place, so a concurrent request never includes a half-written file.
		$tmp = $cache_file . '.' . uniqid( '', true ) . '.tmp';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Cache file in MetForm's own uploads subdirectory.
		if ( false === @file_put_contents( $tmp, $php, LOCK_EX ) || ! @rename( $tmp, $cache_file ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $tmp );
			return;
		}

		foreach ( (array) glob( $dir . '/' . $library_id . '-*.php' ) as $stale ) {
			if ( is_string( $stale ) && wp_normalize_path( $stale ) !== wp_normalize_path( $cache_file ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				@unlink( $stale );
			}
		}
	}

	/**
	 * Creates the cache directory, protected from direct web access, if needed.
	 *
	 * @since 4.4.0
	 *
	 * @param string $dir Cache directory.
	 * @return bool Whether the directory exists and is writable.
	 */
	private static function prepare_cache_dir( string $dir ): bool {
		if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
			return false;
		}

		// Apache honors .htaccess; on any server the cache files themselves
		// exit before output when requested directly (they check ABSPATH).
		$guards = array(
			'index.php' => "<?php\n// Silence is golden.\n",
			'.htaccess' => "# Generated by MetForm. Blocks direct access to the icon cache.\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n\tDeny from all\n</IfModule>\n",
		);
		foreach ( $guards as $name => $contents ) {
			if ( ! file_exists( $dir . '/' . $name ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.NoSilencedErrors.Discouraged
				@file_put_contents( $dir . '/' . $name, $contents );
			}
		}

		return wp_is_writable( $dir );
	}
}
