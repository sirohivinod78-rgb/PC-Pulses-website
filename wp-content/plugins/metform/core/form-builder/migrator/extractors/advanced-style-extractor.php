<?php
/**
 * Advanced layout & spacing style extractor for converting Elementor settings to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts Advanced-tab layout, spacing, background and responsive controls.
 *
 * @since 4.1.9
 */
class Advanced_Style_Extractor {
	use Singleton;

	/**
	 * Extracts Advanced-tab layout & spacing styles from Elementor widget settings.
	 *
	 * @param array $settings Elementor settings array.
	 * @return array
	 */
	public function extract( array $settings ): array {
		$advanced_styles     = array();
		$dimension_extractor = Dimension_Extractor::instance();

		// Margin (desktop, tablet, mobile)
		$m_desktop = $dimension_extractor->extract_dimensions( $settings['_margin'] ?? ( $settings['margin'] ?? null ) );
		$m_tablet  = $dimension_extractor->extract_dimensions( $settings['_margin_tablet'] ?? ( $settings['margin_tablet'] ?? null ) );
		$m_mobile  = $dimension_extractor->extract_dimensions( $settings['_margin_mobile'] ?? ( $settings['margin_mobile'] ?? null ) );

		if ( $m_desktop || $m_tablet || $m_mobile ) {
			if ( $m_tablet || $m_mobile ) {
				$advanced_styles['margin'] = array_filter(
					array(
						'desktop' => $m_desktop,
						'tablet'  => $m_tablet,
						'mobile'  => $m_mobile,
					)
				);
			} else {
				$advanced_styles['margin'] = $m_desktop;
			}
		}

		// Padding (desktop, tablet, mobile)
		$p_desktop = $dimension_extractor->extract_dimensions( $settings['_padding'] ?? ( $settings['padding'] ?? null ) );
		$p_tablet  = $dimension_extractor->extract_dimensions( $settings['_padding_tablet'] ?? ( $settings['padding_tablet'] ?? null ) );
		$p_mobile  = $dimension_extractor->extract_dimensions( $settings['_padding_mobile'] ?? ( $settings['padding_mobile'] ?? null ) );

		if ( $p_desktop || $p_tablet || $p_mobile ) {
			if ( $p_tablet || $p_mobile ) {
				$advanced_styles['padding'] = array_filter(
					array(
						'desktop' => $p_desktop,
						'tablet'  => $p_tablet,
						'mobile'  => $p_mobile,
					)
				);
			} else {
				$advanced_styles['padding'] = $p_desktop;
			}
		}

		// Width & Custom Width (desktop, tablet, mobile)
		$cw_desktop = $dimension_extractor->extract_size_value( $settings['_element_custom_width'] ?? ( $settings['_custom_width'] ?? null ) );
		$cw_tablet  = $dimension_extractor->extract_size_value( $settings['_element_custom_width_tablet'] ?? ( $settings['_custom_width_tablet'] ?? null ) );
		$cw_mobile  = $dimension_extractor->extract_size_value( $settings['_element_custom_width_mobile'] ?? ( $settings['_custom_width_mobile'] ?? null ) );

		if ( $cw_desktop || $cw_tablet || $cw_mobile ) {
			$advanced_styles['widthType'] = 'custom';
			if ( $cw_tablet || $cw_mobile ) {
				$advanced_styles['customWidth'] = array_filter(
					array(
						'desktop' => $cw_desktop,
						'tablet'  => $cw_tablet,
						'mobile'  => $cw_mobile,
					)
				);
			} else {
				$advanced_styles['customWidth'] = $cw_desktop;
			}
		} elseif ( ! empty( $settings['_element_width'] ) ) {
			$w = (string) Value_Unwrapper::unwrap( $settings['_element_width'] );
			if ( 'auto' === $w || 'inline' === $w ) {
				$advanced_styles['widthType'] = 'inline';
			} elseif ( 'full' === $w || '100%' === $w ) {
				$advanced_styles['widthType'] = 'full';
			}
		}

		// Z-Index
		if ( isset( $settings['_z_index'] ) && '' !== (string) $settings['_z_index'] ) {
			$advanced_styles['zIndex'] = (int) Value_Unwrapper::unwrap( $settings['_z_index'] );
		}

		// Background (Advanced tab normal)
		$bg = Background_Extractor::instance()->extract( $settings, '_background_' );
		if ( $bg ) {
			$advanced_styles['background'] = $bg;
		}

		// Background Hover (Advanced tab hover)
		$bg_hover = Background_Extractor::instance()->extract( $settings, '_background_hover_' );
		if ( $bg_hover ) {
			$advanced_styles['backgroundHover'] = $bg_hover;
		}

		// Transition duration
		if ( isset( $settings['_background_hover_transition'] ) ) {
			$trans = $dimension_extractor->extract_transition_duration( $settings['_background_hover_transition'] );
			if ( $trans ) {
				$advanced_styles['transitionDuration'] = $trans;
			}
		}

		return $advanced_styles;
	}
}
