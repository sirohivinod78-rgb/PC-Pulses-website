<?php
/**
 * Field style mapper for converting Elementor input widget styles to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maps visual styles for standard input fields into props['styles'].
 *
 * @since 4.1.9
 */
class Field_Style_Mapper {
	use Singleton;

	/**
	 * Maps visual styles for standard input fields into props['styles'].
	 *
	 * @param array $settings Widget settings.
	 * @return array
	 */
	public function map( array $settings ): array {
		$styles               = array();
		$dimension_extractor  = Dimension_Extractor::instance();
		$border_extractor     = Border_Extractor::instance();
		$background_extractor = Background_Extractor::instance();
		$box_shadow_extractor = Box_Shadow_Extractor::instance();
		$typography_extractor = Typography_Extractor::instance();
		$advanced_extractor   = Advanced_Style_Extractor::instance();

		if ( ! empty( $settings['mf_input_label_color'] ) ) {
			$styles['labelColor'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_label_color'] );
		}
		if ( ! empty( $settings['mf_input_help_text_color'] ) ) {
			$styles['helpTextColor'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_help_text_color'] );
		}
		if ( ! empty( $settings['mf_input_label_margin'] ) ) {
			$label_margin = $dimension_extractor->extract_dimensions( $settings['mf_input_label_margin'] );
			if ( $label_margin ) {
				$styles['labelMargin'] = $label_margin;
			}
		}
		if ( ! empty( $settings['mf_input_label_padding'] ) ) {
			$label_padding = $dimension_extractor->extract_dimensions( $settings['mf_input_label_padding'] );
			if ( $label_padding ) {
				$styles['labelPadding'] = $label_padding;
			}
		}

		$placeholder_color = $settings['mf_input_placeholder_color'] ?? ( $settings['mf_input_place_holder_color'] ?? null );
		if ( ! empty( $placeholder_color ) ) {
			$styles['placeholderColor'] = (string) Value_Unwrapper::unwrap( $placeholder_color );
		}
		$color_source = $settings['mf_input_color'] ?? ( $settings['mf_input_option_color'] ?? ( $settings['mf_input_options_color'] ?? null ) );
		if ( ! empty( $color_source ) ) {
			$styles['color'] = (string) Value_Unwrapper::unwrap( $color_source );
		}
		$extracted_bg = $background_extractor->extract( $settings, 'mf_input_background_' );
		if ( $extracted_bg ) {
			$styles['background'] = $extracted_bg;
		} else {
			$bg_source = $settings['mf_input_bg_color'] ?? ( $settings['mf_input_background_color'] ?? null );
			if ( ! empty( $bg_source ) ) {
				$styles['background'] = array(
					'type'  => 'classic',
					'color' => (string) Value_Unwrapper::unwrap( $bg_source ),
				);
			}
		}
		if ( ! empty( $settings['mf_input_border_color'] ) ) {
			$styles['borderColor'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_border_color'] );
		}

		// Border & Radius
		$extracted_border = $border_extractor->extract( $settings, 'mf_input_border_' );
		if ( $extracted_border ) {
			$styles['border'] = $extracted_border;
		} elseif ( ! empty( $settings['mf_input_border_radius'] ) ) {
			$rad_val = Value_Unwrapper::unwrap( $settings['mf_input_border_radius'] );
			if ( is_array( $rad_val ) && isset( $rad_val['size'] ) && '' !== (string) $rad_val['size'] ) {
				$radius           = $rad_val['size'] . ( $rad_val['unit'] ?? 'px' );
				$styles['border'] = array(
					'borderType' => 'solid',
					'radius'     => array(
						'top'    => $radius,
						'right'  => $radius,
						'bottom' => $radius,
						'left'   => $radius,
					),
				);
			} elseif ( is_numeric( $rad_val ) ) {
				$radius           = $rad_val . 'px';
				$styles['border'] = array(
					'borderType' => 'solid',
					'radius'     => array(
						'top'    => $radius,
						'right'  => $radius,
						'bottom' => $radius,
						'left'   => $radius,
					),
				);
			}
		}

		// Padding & Margin
		// Only use mf_input_padding for the actual input field padding.
		// Never fall back to _padding (wrapper padding) or mf_option_padding (dropdown items).
		$pad_source = $settings['mf_input_padding'] ?? null;
		if ( ! empty( $pad_source ) ) {
			$padding = $dimension_extractor->extract_dimensions( $pad_source );
			if ( $padding ) {
				$styles['padding'] = $padding;
			}
		}
		// Only use mf_input_margin for the actual input field margin.
		// Never fall back to _margin (wrapper margin, handled in extract_advanced_styles).
		$margin_source = $settings['mf_input_margin'] ?? null;
		if ( ! empty( $margin_source ) ) {
			$margin = $dimension_extractor->extract_dimensions( $margin_source );
			if ( $margin ) {
				$styles['margin'] = $margin;
			}
		}

		// Option Spacing (for radio, checkbox, etc.)
		$opt_gap = $dimension_extractor->extract_size_value( $settings['mf_input_option_space_between'] ?? null );
		if ( null !== $opt_gap ) {
			$styles['optionGap'] = $opt_gap;
		}
		$opt_pad_source = $settings['mf_input_option_padding'] ?? null;
		if ( ! empty( $opt_pad_source ) ) {
			$opt_pad = $dimension_extractor->extract_dimensions( $opt_pad_source );
			if ( $opt_pad ) {
				$styles['optionPadding'] = $opt_pad;
			}
		}
		$opt_marker_gap = $dimension_extractor->extract_size_value( $settings['mf_input_option_space_after_icon'] ?? null );
		if ( null !== $opt_marker_gap ) {
			$styles['markerGap'] = $opt_marker_gap;
		}

		// Box Shadow
		$shadow_source = $settings['mf_input_box_shadow_box_shadow'] ?? ( $settings['box_shadow_box_shadow'] ?? null );
		if ( ! empty( $shadow_source ) ) {
			$box_shadow = $box_shadow_extractor->extract( $shadow_source );
			if ( $box_shadow ) {
				$styles['boxShadow'] = $box_shadow;
			}
		}

		// Typography
		$label_typography = $typography_extractor->extract( $settings, 'mf_input_label_typography_' ) ?? $typography_extractor->extract( $settings, 'mf_input_label_typgraphy_' );
		if ( $label_typography ) {
			$styles['labelTypography'] = $label_typography;
		}
		$help_typography = $typography_extractor->extract( $settings, 'mf_input_help_text_typography_' );
		if ( $help_typography ) {
			$styles['helpTextTypography'] = $help_typography;
		}
		$error_typography = $typography_extractor->extract( $settings, 'mf_input_warning_text_typography_' );
		if ( $error_typography ) {
			$styles['errorTypography'] = $error_typography;
		}

		$input_typography = $typography_extractor->extract( $settings, 'mf_input_typography_' )
			?? $typography_extractor->extract( $settings, 'mf_input_typgraphy_' )
			?? $typography_extractor->extract( $settings, 'mf_input_typgraphy_text_' )
			?? $typography_extractor->extract( $settings, 'mf_input_typography_text_' )
			?? $typography_extractor->extract( $settings, 'mf_input_option_typography_' )
			?? $typography_extractor->extract( $settings, 'mf_input_place_holder_typography_' )
			?? $typography_extractor->extract( $settings, 'mf_input_placeholder_typography_' );
		if ( $input_typography ) {
			$styles['typography'] = $input_typography;
		}

		// Advanced styles (_margin, _padding, widths, z-index)
		$advanced_styles = $advanced_extractor->extract( $settings );
		if ( ! empty( $advanced_styles ) ) {
			$styles['adv'] = $advanced_styles;
		}

		return $styles;
	}
}
