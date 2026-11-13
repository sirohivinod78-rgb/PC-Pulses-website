<?php
/**
 * Dimension and unit extractor for converting Elementor values to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts dimension boxes, sizes, opacity, and transition durations.
 *
 * @since 4.1.9
 */
class Dimension_Extractor {
	use Singleton;

	/**
	 * Extracts dimension values from an Elementor setting into FormBuilder format.
	 * e.g. [ 'unit' => 'px', 'top' => '10', 'right' => '20', ... ] -> [ 'top' => '10px', 'right' => '20px', ... ]
	 *
	 * @param mixed $setting Dimension setting.
	 * @return array|null
	 */
	public function extract_dimensions( $setting ): ?array {
		if ( ! is_array( $setting ) ) {
			return null;
		}

		$unit      = isset( $setting['unit'] ) ? (string) $setting['unit'] : 'px';
		$dims      = array();
		$has_value = false;

		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( isset( $setting[ $side ] ) && '' !== (string) $setting[ $side ] ) {
				$val           = (string) Value_Unwrapper::unwrap( $setting[ $side ] );
				$dims[ $side ] = preg_match( '/[a-z%]+$/i', $val ) ? $val : $val . $unit;
				$has_value     = true;
			}
		}

		if ( $has_value ) {
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				if ( ! isset( $dims[ $side ] ) || '' === $dims[ $side ] ) {
					$dims[ $side ] = '0' . $unit;
				}
			}
			return $dims;
		}

		return null;
	}

	/**
	 * Extracts a CSS size string (e.g. '21px', '1.5em') from an Elementor range/size setting.
	 *
	 * @param mixed $setting Size setting (array with size & unit, numeric, or string).
	 * @return string|null
	 */
	public function extract_size_value( $setting ): ?string {
		if ( empty( $setting ) ) {
			return null;
		}
		$val = Value_Unwrapper::unwrap( $setting );
		if ( is_array( $val ) && isset( $val['size'] ) && '' !== (string) $val['size'] ) {
			$unit = $val['unit'] ?? 'px';
			return $val['size'] . $unit;
		} elseif ( is_numeric( $val ) ) {
			return $val . 'px';
		} elseif ( is_string( $val ) && '' !== $val ) {
			return preg_match( '/[a-z%]+$/i', $val ) ? $val : $val . 'px';
		}
		return null;
	}

	/**
	 * Extracts an opacity float value (0.0 - 1.0) from Elementor slider/numeric setting.
	 *
	 * @param mixed $setting Opacity setting value.
	 * @return float|null
	 */
	public function extract_opacity_value( $setting ): ?float {
		if ( null === $setting || '' === $setting ) {
			return null;
		}
		if ( is_array( $setting ) ) {
			if ( isset( $setting['size'] ) && '' !== (string) $setting['size'] && is_numeric( $setting['size'] ) ) {
				$num = (float) $setting['size'];
				return $num > 1 ? round( $num / 100, 2 ) : $num;
			}
			if ( isset( $setting['value'] ) ) {
				return $this->extract_opacity_value( $setting['value'] );
			}
		}

		$val = Value_Unwrapper::unwrap( $setting );
		if ( is_numeric( $val ) ) {
			$num = (float) $val;
			return $num > 1 ? round( $num / 100, 2 ) : $num;
		}
		if ( is_string( $val ) && '' !== trim( $val ) ) {
			if ( preg_match( '/^([0-9.]+)\s*(%|px)?$/i', trim( $val ), $m ) ) {
				$num = (float) $m[1];
				if ( ( isset( $m[2] ) && '%' === $m[2] ) || $num > 1 ) {
					return round( $num / 100, 2 );
				}
				return $num;
			}
		}
		return null;
	}

	/**
	 * Extracts a transition duration in milliseconds (e.g. '300ms') from Elementor transition setting.
	 *
	 * @param mixed $setting Transition setting (e.g. 0.3, '0.3s', 300, ['size' => 0.3, 'unit' => 's']).
	 * @return string|null
	 */
	public function extract_transition_duration( $setting ): ?string {
		if ( null === $setting || '' === $setting ) {
			return null;
		}
		$val = Value_Unwrapper::unwrap( $setting );
		if ( is_array( $val ) ) {
			if ( isset( $val['size'] ) && '' !== (string) $val['size'] && is_numeric( $val['size'] ) ) {
				$unit = $val['unit'] ?? 's';
				$num  = (float) $val['size'];
				return 'ms' === $unit ? ( (int) $num ) . 'ms' : ( (int) round( $num * 1000 ) ) . 'ms';
			}
			return null;
		}
		if ( is_numeric( $val ) ) {
			$num = (float) $val;
			return $num <= 10 ? ( (int) round( $num * 1000 ) ) . 'ms' : ( (int) $num ) . 'ms';
		}
		if ( is_string( $val ) && '' !== $val ) {
			if ( preg_match( '/^([0-9.]+)\s*s$/i', trim( $val ), $m ) ) {
				return ( (int) round( (float) $m[1] * 1000 ) ) . 'ms';
			}
			if ( preg_match( '/^([0-9.]+)\s*ms$/i', trim( $val ), $m ) ) {
				return ( (int) $m[1] ) . 'ms';
			}
		}
		return null;
	}
}
