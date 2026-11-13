<?php
namespace MetForm\Core\FormBuilder\Advanced;

use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;

defined( 'ABSPATH' ) || exit;

/**
 * Advanced tab › Responsive: "Show on Desktop/Tablet/Mobile".
 *
 * - sanitize()  — Document_Sanitizer keeps the prop on save.
 * - build_css() — per-instance hide media queries (via Css_Builder).
 *
 * @since 4.2.0
 */
final class Visibility {

	/** Responsive visibility slots. */
	public const DEVICES = array( 'desktop', 'tablet', 'mobile' );

	/** Body class of the editor canvas iframe (see Editor\Canvas). */
	public const CANVAS_BODY_CLASS = 'metform-builder-canvas';

	/** Body class added on top of the canvas class in the preview iframe (see Editor\Preview). */
	public const PREVIEW_BODY_CLASS = 'metform-builder-preview';

	/**
	 * Sanitizes the Advanced behaviour props present in a raw props array.
	 *
	 * @param array<string, mixed> $props Raw element props.
	 * @return array<string, mixed> Sanitized subset.
	 */
	public static function sanitize( array $props ): array {
		$clean = array();

		if ( isset( $props['responsiveVisibility'] ) && is_array( $props['responsiveVisibility'] ) ) {
			$clean['responsiveVisibility'] = array();
			foreach ( self::DEVICES as $device ) {
				$clean['responsiveVisibility'][ $device ] = self::to_bool( $props['responsiveVisibility'][ $device ] ?? true );
			}
		}

		return $clean;
	}

	/**
	 * Per-instance CSS for responsive visibility.
	 *
	 * Hides the element on the live site and in the preview; the editor
	 * canvas keeps it visible (dimmed by withFormBlock.jsx, with a "Hidden
	 * on <device>" badge) so it stays selectable, same as Elementor.
	 *
	 * @param array<string, mixed> $settings   Merged block settings.
	 * @param string               $class_name Scoped selector without the leading dot
	 *                                         (e.g. 'metform-12 .metform-element-abc').
	 * @return string Raw CSS.
	 */
	public static function build_css( array $settings, string $class_name ): string {
		$hidden = self::get_hidden_devices( $settings );
		if ( '' === $class_name || ! $hidden ) {
			return '';
		}

		$selector = '.' . $class_name;
		$live     = sprintf(
			'body:not(.%1$s) %3$s, body.%2$s %3$s',
			self::CANVAS_BODY_CLASS,
			self::PREVIEW_BODY_CLASS,
			$selector
		);
		$queries  = self::get_device_media_queries();
		$css      = '';

		foreach ( $hidden as $device ) {
			$css .= sprintf( '%1$s { %2$s { display: none !important; } }', $queries[ $device ], $live );
		}

		return $css;
	}

	/**
	 * Non-overlapping media queries for each device, derived from the
	 * (filterable) responsive breakpoints so "Hide on Desktop" never also
	 * hides on tablet, unlike the cascading max-width queries style
	 * controls use.
	 *
	 * @return array<string, string> Media query keyed by device.
	 */
	public static function get_device_media_queries(): array {
		$breakpoints = Responsive_Value::get_breakpoints();
		$tablet_max  = self::extract_max_width( $breakpoints['tablet'] ?? '', 768 );
		$mobile_max  = self::extract_max_width( $breakpoints['mobile'] ?? '', 480 );

		return array(
			'desktop' => sprintf( '@media (min-width: %dpx)', $tablet_max + 1 ),
			'tablet'  => sprintf( '@media (min-width: %dpx) and (max-width: %dpx)', $mobile_max + 1, $tablet_max ),
			'mobile'  => sprintf( '@media (max-width: %dpx)', $mobile_max ),
		);
	}

	/**
	 * Devices the block is hidden on.
	 *
	 * @param array<string, mixed> $settings Merged block settings.
	 * @return string[] Subset of self::DEVICES.
	 */
	private static function get_hidden_devices( array $settings ): array {
		$visibility = $settings['responsiveVisibility'] ?? null;
		if ( ! is_array( $visibility ) ) {
			return array();
		}

		$hidden = array();
		foreach ( self::DEVICES as $device ) {
			if ( array_key_exists( $device, $visibility ) && ! self::to_bool( $visibility[ $device ] ) ) {
				$hidden[] = $device;
			}
		}

		return $hidden;
	}

	/**
	 * Casts a saved flag to bool, treating the strings 'false'/'0'/'' as false.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private static function to_bool( $value ): bool {
		if ( is_string( $value ) ) {
			return ! in_array( strtolower( trim( $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
		}
		return (bool) $value;
	}

	/**
	 * Reads the px value out of a `@media (max-width: 768px)` string.
	 *
	 * @param string $media    Media query string.
	 * @param int    $fallback Value used when no px max-width is found.
	 * @return int
	 */
	private static function extract_max_width( string $media, int $fallback ): int {
		if ( preg_match( '/max-width\s*:\s*(\d+(?:\.\d+)?)px/i', $media, $m ) ) {
			return (int) floor( (float) $m[1] );
		}
		return $fallback;
	}
}
