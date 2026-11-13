<?php
/**
 * Button style mapper for converting Elementor button widget styles to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maps visual styles for buttons into props['styles'].
 *
 * @since 4.1.9
 */
class Button_Style_Mapper {
	use Singleton;

	/**
	 * Maps visual styles for buttons into props['styles'].
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

		$btn_color = $settings['mf_btn_color'] ?? ( $settings['button_text_color'] ?? ( $settings['color'] ?? null ) );
		if ( ! empty( $btn_color ) ) {
			$styles['color'] = (string) Value_Unwrapper::unwrap( $btn_color );
		}

		$extracted_bg = $background_extractor->extract( $settings, 'mf_btn_bg_color_' );
		if ( $extracted_bg ) {
			$styles['background'] = $extracted_bg;
		} else {
			$btn_bg = $settings['mf_btn_bg_color_color'] ?? ( $settings['mf_btn_bg_color'] ?? ( $settings['mf_btn_color_bg'] ?? ( $settings['background_color'] ?? ( $settings['button_background_color'] ?? null ) ) ) );
			if ( ! empty( $btn_bg ) ) {
				$btn_bg_val           = (string) Value_Unwrapper::unwrap( $btn_bg );
				$styles['background'] = array(
					'type'  => 'classic',
					'color' => $btn_bg_val,
				);
			}
		}

		$btn_hover_color = $settings['mf_btn_hover_color'] ?? ( $settings['hover_color'] ?? null );
		if ( ! empty( $btn_hover_color ) ) {
			$styles['hoverColor'] = (string) Value_Unwrapper::unwrap( $btn_hover_color );
		}

		$extracted_hover_bg = $background_extractor->extract( $settings, 'mf_btn_bg_hover_color_' ) ?? $background_extractor->extract( $settings, 'mf_btn_hover_bg_color_' );
		if ( $extracted_hover_bg ) {
			$styles['hoverBackground'] = $extracted_hover_bg;
		} else {
			$btn_hover_bg = $settings['mf_btn_hover_bg_color_color'] ?? ( $settings['mf_btn_hover_bg_color'] ?? ( $settings['mf_btn_bg_hover_color_color'] ?? ( $settings['button_background_hover_color'] ?? ( $settings['hover_background_color'] ?? null ) ) ) );
			if ( ! empty( $btn_hover_bg ) ) {
				$bg_hover                  = (string) Value_Unwrapper::unwrap( $btn_hover_bg );
				$styles['hoverBackground'] = array(
					'type'  => 'classic',
					'color' => $bg_hover,
				);
			}
		}

		// Border & Radius
		$extracted_border = $border_extractor->extract( $settings, 'mf_btn_border_' ) ?? $border_extractor->extract( $settings, 'border_' );
		if ( $extracted_border ) {
			$styles['border'] = $extracted_border;
		} else {
			$rad_source = $settings['mf_btn_border_radius'] ?? ( $settings['border_radius'] ?? ( $settings['_border_radius'] ?? null ) );
			if ( ! empty( $rad_source ) ) {
				$rad_val = Value_Unwrapper::unwrap( $rad_source );
				if ( is_array( $rad_val ) ) {
					if ( isset( $rad_val['size'] ) && '' !== (string) $rad_val['size'] ) {
						$unit             = $rad_val['unit'] ?? 'px';
						$r                = $rad_val['size'] . $unit;
						$styles['border'] = array(
							'borderType' => 'solid',
							'radius'     => array(
								'top'    => $r,
								'right'  => $r,
								'bottom' => $r,
								'left'   => $r,
							),
						);
					} else {
						$rad_dims = $dimension_extractor->extract_dimensions( $rad_val );
						if ( $rad_dims ) {
							$styles['border'] = array(
								'borderType' => 'solid',
								'radius'     => $rad_dims,
							);
						}
					}
				} elseif ( is_numeric( $rad_val ) ) {
					$r                = $rad_val . 'px';
					$styles['border'] = array(
						'borderType' => 'solid',
						'radius'     => array(
							'top'    => $r,
							'right'  => $r,
							'bottom' => $r,
							'left'   => $r,
						),
					);
				}
			}
		}

		$pad_source = $settings['mf_btn_text_padding'] ?? ( $settings['mf_btn_padding'] ?? ( $settings['text_padding'] ?? null ) );
		if ( ! empty( $pad_source ) ) {
			$pad = $dimension_extractor->extract_dimensions( $pad_source );
			if ( $pad ) {
				$styles['padding'] = $pad;
			}
		}

		// Only use mf_btn_margin for the button inner margin.
		// Never fall back to _margin (wrapper margin, handled in extract_advanced_styles).
		$margin_source = $settings['mf_btn_margin'] ?? null;
		if ( ! empty( $margin_source ) ) {
			$margin = $dimension_extractor->extract_dimensions( $margin_source );
			if ( $margin ) {
				$styles['margin'] = $margin;
			}
		}

		$shadow_source = $settings['mf_btn_box_shadow_group_box_shadow'] ?? ( $settings['mf_btn_box_shadow_box_shadow'] ?? ( $settings['_box_shadow_box_shadow'] ?? null ) );
		if ( ! empty( $shadow_source ) ) {
			$bs = $box_shadow_extractor->extract( $shadow_source );
			if ( $bs ) {
				$styles['boxShadow'] = $bs;
			}
		}

		$button_typography = $typography_extractor->extract( $settings, 'mf_btn_typography_' ) ?? $typography_extractor->extract( $settings, 'typography_' );
		if ( $button_typography ) {
			$styles['typography'] = $button_typography;
		}

		// Sensible defaults when colors/border are not explicitly provided
		if ( empty( $styles['background'] ) ) {
			$styles['background'] = array(
				'type'  => 'classic',
				'color' => '#2563eb',
			);
		}
		if ( empty( $styles['color'] ) ) {
			$styles['color'] = '#ffffff';
		}
		if ( empty( $styles['hoverBackground'] ) ) {
			$styles['hoverBackground'] = array(
				'type'  => 'classic',
				'color' => '#1d4ed8',
			);
		}
		if ( empty( $styles['hoverColor'] ) ) {
			$styles['hoverColor'] = '#ffffff';
		}
		if ( empty( $styles['border'] ) ) {
			$styles['border'] = array(
				'borderType' => 'solid',
				'width'      => array(
					'top'    => '1px',
					'right'  => '1px',
					'bottom' => '1px',
					'left'   => '1px',
				),
				'color'      => '#2563eb',
				'radius'     => array(
					'top'    => '4px',
					'right'  => '4px',
					'bottom' => '4px',
					'left'   => '4px',
				),
			);
		}

		// Advanced styles (_margin, _padding, widths, z-index)
		$advanced_styles = $advanced_extractor->extract( $settings );
		if ( ! empty( $advanced_styles ) ) {
			$styles['adv'] = $advanced_styles;
		}

		return $styles;
	}
}
