<?php
/**
 * Typography extractor for converting Elementor typography controls to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts typography properties from Elementor typography controls.
 *
 * @since 4.1.9
 */
class Typography_Extractor {
	use Singleton;

	/**
	 * Extracts typography properties from Elementor typography controls.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'mf_input_label_typography_' or 'typography_').
	 * @return array|null
	 */
	public function extract( array $settings, string $prefix ): ?array {
		$typography = array();

		if ( ! empty( $settings[ $prefix . 'font_family' ] ) ) {
			$typography['fontFamily'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'font_family' ] );
		}
		if ( isset( $settings[ $prefix . 'font_size' ] ) ) {
			$size = Value_Unwrapper::unwrap( $settings[ $prefix . 'font_size' ] );
			if ( is_array( $size ) && isset( $size['size'] ) && '' !== (string) $size['size'] ) {
				$unit                   = $size['unit'] ?? 'px';
				$typography['fontSize'] = $size['size'] . $unit;
			} elseif ( is_numeric( $size ) ) {
				$typography['fontSize'] = $size . 'px';
			} elseif ( is_string( $size ) && '' !== $size ) {
				$typography['fontSize'] = $size;
			}
		}
		if ( ! empty( $settings[ $prefix . 'font_weight' ] ) ) {
			$typography['fontWeight'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'font_weight' ] );
		}
		if ( ! empty( $settings[ $prefix . 'font_style' ] ) ) {
			$typography['fontStyle'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'font_style' ] );
		}
		if ( ! empty( $settings[ $prefix . 'text_transform' ] ) ) {
			$typography['textTransform'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'text_transform' ] );
		}
		if ( ! empty( $settings[ $prefix . 'text_decoration' ] ) ) {
			$typography['textDecoration'] = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'text_decoration' ] );
		}
		if ( isset( $settings[ $prefix . 'line_height' ] ) ) {
			$line_height = Value_Unwrapper::unwrap( $settings[ $prefix . 'line_height' ] );
			if ( is_array( $line_height ) && isset( $line_height['size'] ) && '' !== (string) $line_height['size'] ) {
				$unit                     = $line_height['unit'] ?? 'px';
				$typography['lineHeight'] = $line_height['size'] . $unit;
			} elseif ( is_numeric( $line_height ) ) {
				$typography['lineHeight'] = $line_height . 'px';
			} elseif ( is_string( $line_height ) && '' !== $line_height ) {
				$typography['lineHeight'] = $line_height;
			}
		}
		if ( isset( $settings[ $prefix . 'letter_spacing' ] ) ) {
			$letter_spacing = Value_Unwrapper::unwrap( $settings[ $prefix . 'letter_spacing' ] );
			if ( is_array( $letter_spacing ) && isset( $letter_spacing['size'] ) && '' !== (string) $letter_spacing['size'] ) {
				$unit                        = $letter_spacing['unit'] ?? 'px';
				$typography['letterSpacing'] = $letter_spacing['size'] . $unit;
			} elseif ( is_numeric( $letter_spacing ) ) {
				$typography['letterSpacing'] = $letter_spacing . 'px';
			} elseif ( is_string( $letter_spacing ) && '' !== $letter_spacing ) {
				$typography['letterSpacing'] = $letter_spacing;
			}
		}

		return ! empty( $typography ) ? $typography : null;
	}
}
