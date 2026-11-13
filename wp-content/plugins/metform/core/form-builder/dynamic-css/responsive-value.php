<?php
/**
 * Responsive setting value helpers for the form builder.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.1
 */

namespace MetForm\Core\FormBuilder\Dynamic_Css;

defined( 'ABSPATH' ) || exit;

/**
 * Detects and resolves values stored per responsive breakpoint.
 */
final class Responsive_Value {

	/** Default media queries keyed by their stored responsive slot. */
	private const DEFAULT_BREAKPOINTS = array(
		'tablet' => '@media (max-width: 768px)',
		'mobile' => '@media (max-width: 480px)',
	);

	/**
	 * Reads a nested setting using a dot-delimited path.
	 *
	 * @param array  $settings Settings to read.
	 * @param string $path     Dot-delimited path, e.g. "border.width.top".
	 * @return mixed The value, or null when a path segment is missing.
	 */
	public static function get( array $settings, string $path ) {
		$value = $settings;

		foreach ( explode( '.', $path ) as $key ) {
			if ( ! is_array( $value ) || ! array_key_exists( $key, $value ) ) {
				return null;
			}
			$value = $value[ $key ];
		}

		return $value;
	}

	/**
	 * Returns the filterable media queries used by responsive controls.
	 *
	 * @return array<string,string>
	 */
	public static function get_breakpoints(): array {
		return apply_filters( 'metform_style_breakpoints', self::DEFAULT_BREAKPOINTS );
	}

	/**
	 * Whether a value is stored as a responsive breakpoint map.
	 *
	 * @param mixed $value Stored setting value.
	 */
	public static function is_responsive( $value ): bool {
		return is_array( $value )
			&& ! array_is_list( $value )
			&& ( array_key_exists( 'desktop', $value ) || array_key_exists( 'tablet', $value ) || array_key_exists( 'mobile', $value ) );
	}

	/**
	 * Resolves a slot using mobile -> tablet -> desktop inheritance.
	 *
	 * @param mixed  $value    Plain or responsive setting value.
	 * @param string $slot     Requested breakpoint slot.
	 * @param mixed  $fallback Value returned when no usable value exists.
	 * @return mixed
	 */
	public static function resolve( $value, string $slot = 'desktop', $fallback = null ) {
		if ( ! self::is_responsive( $value ) ) {
			return ( null === $value || '' === $value ) ? $fallback : $value;
		}

		$chain = array( 'desktop' );
		if ( 'tablet' === $slot ) {
			$chain = array( 'tablet', 'desktop' );
		} elseif ( 'mobile' === $slot ) {
			$chain = array( 'mobile', 'tablet', 'desktop' );
		}

		foreach ( $chain as $key ) {
			if ( array_key_exists( $key, $value ) && self::is_set( $value[ $key ] ) ) {
				return $value[ $key ];
			}
		}

		return $fallback;
	}

	/**
	 * Returns non-desktop slots carrying an explicit override.
	 *
	 * @param mixed $value Possibly responsive setting value.
	 * @return string[]
	 */
	public static function overridden_slots( $value ): array {
		if ( ! self::is_responsive( $value ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_keys( self::get_breakpoints() ),
				static function ( $slot ) use ( $value ) {
					return array_key_exists( $slot, $value ) && self::is_set( $value[ $slot ] );
				}
			)
		);
	}

	/** Whether a stored slot value is explicit rather than inherited. */
	private static function is_set( $value ): bool {
		return null !== $value && '' !== $value && array() !== $value;
	}
}
