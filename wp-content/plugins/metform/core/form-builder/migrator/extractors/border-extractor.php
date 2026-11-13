<?php
/**
 * Border extractor for converting Elementor border controls to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts border properties from Elementor border controls.
 *
 * @since 4.1.9
 */
class Border_Extractor {
	use Singleton;

	/**
	 * Extracts border properties from Elementor border controls.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'mf_input_border_' or 'border_').
	 * @return array|null
	 */
	public function extract( array $settings, string $prefix ): ?array {
		$border = array();

		if ( ! empty( $settings[ $prefix . 'border' ] ) || ! empty( $settings[ $prefix . 'style' ] ) ) {
			$border['borderType'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'border' ] ?? $settings[ $prefix . 'style' ] );
		}
		if ( ! empty( $settings[ $prefix . 'color' ] ) ) {
			$border['color'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'color' ] );
		}
		if ( ! empty( $settings[ $prefix . 'width' ] ) ) {
			$width_dims = Dimension_Extractor::instance()->extract_dimensions( $settings[ $prefix . 'width' ] );
			if ( $width_dims ) {
				$border['width'] = $width_dims;
			}
		}

		$rad_key = isset( $settings[ $prefix . 'radius' ] ) ? $prefix . 'radius' : ( isset( $settings[ $prefix . 'border_radius' ] ) ? $prefix . 'border_radius' : null );
		if ( $rad_key ) {
			$rad_val = Value_Unwrapper::unwrap( $settings[ $rad_key ] );
			if ( is_array( $rad_val ) ) {
				if ( isset( $rad_val['size'] ) && '' !== (string) $rad_val['size'] ) {
					$unit             = $rad_val['unit'] ?? 'px';
					$r                = $rad_val['size'] . $unit;
					$border['radius'] = array(
						'top'    => $r,
						'right'  => $r,
						'bottom' => $r,
						'left'   => $r,
					);
				} else {
					$radius_dims = Dimension_Extractor::instance()->extract_dimensions( $rad_val );
					if ( $radius_dims ) {
						$border['radius'] = $radius_dims;
					}
				}
			} elseif ( is_numeric( $rad_val ) ) {
				$r                = $rad_val . 'px';
				$border['radius'] = array(
					'top'    => $r,
					'right'  => $r,
					'bottom' => $r,
					'left'   => $r,
				);
			} elseif ( is_string( $rad_val ) && '' !== $rad_val ) {
				$r                = preg_match( '/[a-z%]+$/i', $rad_val ) ? $rad_val : $rad_val . 'px';
				$border['radius'] = array(
					'top'    => $r,
					'right'  => $r,
					'bottom' => $r,
					'left'   => $r,
				);
			}
		}

		// If width or color or radius is set without an explicit borderType, default to 'solid'
		if ( ! empty( $border ) && empty( $border['borderType'] ) && ( ! empty( $border['width'] ) || ! empty( $border['color'] ) ) ) {
			$border['borderType'] = 'solid';
		}

		return ! empty( $border ) ? $border : null;
	}
}
