<?php
/**
 * Shared icon-value resolver used by every icon-bearing block/control.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves an icon control's stored value into render-ready pieces, shared by
 * every block/control that has an `icon`-typed field (Button, Multistep step
 * icons, Radio/Checkbox option icons, ...), so they all resolve and enqueue
 * consistently instead of each hand-rolling their own dispatch.
 *
 * @since 4.3.0
 */
class Icon_Resolver {

	/**
	 * Class on every Icon API `<svg>` and every `<img>` icon: fills its
	 * wrapper's box (see `blocks/shared/icon.scss`), which each block sizes.
	 *
	 * @since 4.4.0
	 * @var string
	 */
	public const FILL_CLASS = 'mf-icon-fill';

	/**
	 * Resolves the Icon control's value into its escaped icon markup.
	 *
	 * Value shapes, in the order they're checked:
	 *
	 * - The normalized WordPress Icon API value `{type:'icon', collection,
	 *   name}` (`core`, `metform-custom`) — through the Icon API
	 *   ({@see resolve_icon_value()}).
	 * - An icon key ("fas-star"), what the picker saves for a bundled
	 *   library icon since 4.4.0 — inline SVG from {@see Icon_Library}.
	 * - A media library URL or a `data:image/...` URI — `<img>`.
	 *
	 * No saved value is rewritten; anything unrecognized (an unknown key, any
	 * other string) renders nothing. The library is only loaded when a value
	 * actually is a key.
	 *
	 * Every icon carries one generic class and is styled through its block's
	 * wrapper: library SVGs `Icon_Library::SVG_CLASS`, Icon API SVGs and
	 * images {@see FILL_CLASS}.
	 *
	 * @since 4.3.0
	 * @since 4.4.0 Icon keys and generic classes instead of per-block ones.
	 *
	 * @param array<string, mixed>|string $icon_raw Raw `icon` prop value.
	 * @return string `<svg>` or `<img>` markup, or '' when nothing renders.
	 */
	public static function resolve( $icon_raw ): string {
		if ( is_array( $icon_raw ) ) {
			return self::resolve_icon_value( $icon_raw );
		}

		$icon_raw = is_scalar( $icon_raw ) ? (string) $icon_raw : '';

		// Empty values, data URIs, and URLs are handled before anything
		// touches Icon_Library, so a form whose fields have no library icon
		// never even autoloads the class.
		if ( $icon_raw === '' ) {
			return '';
		}

		$is_data_uri = (bool) preg_match( '#^data:image/(svg\+xml|png|jpe?g|gif|webp);base64,#i', $icon_raw );
		$is_url      = ! $is_data_uri && (bool) preg_match( '#^(https?:)?//#i', $icon_raw );

		if ( $is_data_uri ) {
			return sprintf( '<img src="%s" alt="" class="%s">', esc_attr( $icon_raw ), esc_attr( self::FILL_CLASS ) );
		}

		if ( $is_url ) {
			return sprintf( '<img src="%s" alt="" class="%s">', esc_url( $icon_raw ), esc_attr( self::FILL_CLASS ) );
		}

		// Form Builder versions before 4.4.0 saved Font Awesome 5 class
		// strings. Resolve them through the JSON library without rewriting the
		// stored document, so existing forms keep rendering after the key-based
		// picker is introduced.
		if ( preg_match( '/^(fas|far|fab)\s+fa-([a-z0-9]+(?:-[a-z0-9]+)*)$/', trim( $icon_raw ), $matches ) ) {
			$icon_raw = $matches[1] . '-' . $matches[2];
		}

		if ( Icon_Library::is_key( $icon_raw ) ) {
			if ( ! Icon_Api_Compat::is_available() ) {
				return Icon_Library::render( $icon_raw );
			}

			return (string) wp_get_icon(
				Font_Awesome_Icons::qualified_name( $icon_raw ),
				array(
					'size'  => null,
					'class' => Icon_Library::SVG_CLASS,
				)
			);
		}

		return '';
	}

	/**
	 * Resolves a normalized `{type:'icon', collection, name}` Icon API
	 * value into its `<svg>` markup.
	 *
	 * Returns '' whenever the icon can't be rendered — the Icon API isn't
	 * available (a site downgraded below WordPress 7.1 after the icon was
	 * saved), the value is malformed, or the icon is no longer registered
	 * (e.g. its custom SVG attachment was deleted) — rather than erroring.
	 *
	 * @since 4.3.0
	 *
	 * @param array<string, mixed> $icon Normalized icon value.
	 * @return string
	 */
	private static function resolve_icon_value( array $icon ): string {
		if ( 'icon' !== ( $icon['type'] ?? '' ) || empty( $icon['collection'] ) || empty( $icon['name'] ) ) {
			return '';
		}

		if ( ! Icon_Api_Compat::is_available() ) {
			return '';
		}

		$qualified_name = sanitize_key( (string) $icon['collection'] ) . '/' . sanitize_key( (string) $icon['name'] );

		return (string) wp_get_icon( $qualified_name, array( 'class' => self::FILL_CLASS ) );
	}
}
