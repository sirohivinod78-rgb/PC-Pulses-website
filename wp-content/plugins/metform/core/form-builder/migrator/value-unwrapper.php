<?php
/**
 * Value unwrapper utility for Elementor dynamic values and dimensions.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles unwrapping dynamic Elementor $$type object wrappers and primitive values.
 *
 * @since 4.1.9
 */
class Value_Unwrapper {

	/**
	 * Unwraps dynamic Elementor $$type object wrappers or returns primitive values.
	 * Recursively unrolls nested wrappers and reconstructs dimension units.
	 *
	 * @param mixed $val     Raw value from settings or styles.
	 * @param mixed $default Fallback value.
	 * @return mixed Primitive scalar value or unwrapped array.
	 */
	public static function unwrap( $val, $default = '' ) {
		if ( null === $val ) {
			return $default;
		}

		while ( is_array( $val ) ) {
			if ( isset( $val['$$type'] ) && array_key_exists( 'value', $val ) ) {
				// Dimension object wrapped in $$type: size
				if ( 'size' === $val['$$type'] && is_array( $val['value'] ) && isset( $val['value']['size'] ) ) {
					$size = $val['value']['size'];
					$unit = $val['value']['unit'] ?? '';
					return '' !== (string) $unit ? "{$size}{$unit}" : $size;
				}
				$val = $val['value'];
				continue;
			}

			// Dimension object without $$type
			if ( isset( $val['unit'], $val['size'] ) ) {
				$size = $val['size'];
				$unit = $val['unit'];
				return '' !== (string) $unit ? "{$size}{$unit}" : $size;
			}

			// Single-element wrapper with 'value'
			if ( 1 === count( $val ) && array_key_exists( 'value', $val ) ) {
				$val = $val['value'];
				continue;
			}

			break;
		}

		if ( is_array( $val ) ) {
			$unwrapped = array();
			foreach ( $val as $k => $item ) {
				$unwrapped[ $k ] = self::unwrap( $item, '' );
			}
			return $unwrapped;
		}

		return '' !== (string) $val ? $val : $default;
	}
}
