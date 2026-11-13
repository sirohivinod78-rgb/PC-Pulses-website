<?php
/**
 * Container style mapper: handles container-specific box styles, responsive padding/margins,
 * normal/hover backgrounds, overlays, borders, shadows, and z-index.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Container
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Container;

use MetForm\Core\FormBuilder\Migrator\Extractors\Dimension_Extractor;
use MetForm\Core\FormBuilder\Migrator\Style_Extractor;
use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts and normalizes container styling properties.
 *
 * @since 4.1.9
 */
class Container_Style_Mapper {
	use Singleton;

	/**
	 * Maps container box styles including advanced tab properties and background overlay.
	 *
	 * @param array  $settings Container settings.
	 * @param string $width    Resolved width string.
	 * @param string $col_gap  Column gap string.
	 * @param string $row_gap  Row gap string.
	 * @return array Mapped styles dictionary.
	 */
	public function map( array $settings, string $width, string $col_gap, string $row_gap ): array {
		$box_styles = array();
		$adv        = $this->extract_advanced_styles( $settings );

		if ( ! empty( $adv ) ) {
			$box_styles['adv'] = $adv;
		}

		// Background Overlay (Container-specific Style tab control)
		$bg_overlay = Style_Extractor::instance()->extract_background( $settings, 'background_overlay_' );
		if ( $bg_overlay ) {
			$box_styles['backgroundOverlay'] = $bg_overlay;
		}

		$box_styles['width']     = $width;
		$box_styles['columnGap'] = $col_gap;
		$box_styles['rowGap']    = $row_gap;

		return $box_styles;
	}

	/**
	 * Extracts advanced tab layout, spacing, background, border, and shadow properties.
	 *
	 * @param array $settings Container settings.
	 * @return array Advanced styles dictionary.
	 */
	public function extract_advanced_styles( array $settings ): array {
		$adv           = array();
		$dim_extractor = Dimension_Extractor::instance();
		$extractor     = Style_Extractor::instance();

		// Responsive Padding (desktop, tablet, mobile)
		$p_desktop = $dim_extractor->extract_dimensions( $settings['padding'] ?? ( $settings['_padding'] ?? null ) );
		$p_tablet  = $dim_extractor->extract_dimensions( $settings['padding_tablet'] ?? ( $settings['_padding_tablet'] ?? null ) );
		$p_mobile  = $dim_extractor->extract_dimensions( $settings['padding_mobile'] ?? ( $settings['_padding_mobile'] ?? null ) );
		if ( $p_desktop || $p_tablet || $p_mobile ) {
			$adv['padding'] = ( $p_tablet || $p_mobile )
				? array_filter(
					array(
						'desktop' => $p_desktop,
						'tablet'  => $p_tablet,
						'mobile'  => $p_mobile,
					)
				)
				: $p_desktop;
		}

		// Responsive Margin (desktop, tablet, mobile)
		$m_desktop = $dim_extractor->extract_dimensions( $settings['margin'] ?? ( $settings['_margin'] ?? null ) );
		$m_tablet  = $dim_extractor->extract_dimensions( $settings['margin_tablet'] ?? ( $settings['_margin_tablet'] ?? null ) );
		$m_mobile  = $dim_extractor->extract_dimensions( $settings['margin_mobile'] ?? ( $settings['_margin_mobile'] ?? null ) );
		if ( $m_desktop || $m_tablet || $m_mobile ) {
			$adv['margin'] = ( $m_tablet || $m_mobile )
				? array_filter(
					array(
						'desktop' => $m_desktop,
						'tablet'  => $m_tablet,
						'mobile'  => $m_mobile,
					)
				)
				: $m_desktop;
		}

		// Background (Normal & Hover)
		$bg = $extractor->extract_background( $settings, 'background_' ) ?? $extractor->extract_background( $settings, '_background_' );
		if ( $bg ) {
			$adv['background'] = $bg;
		}

		$bg_hover = $extractor->extract_background( $settings, 'background_hover_' ) ?? $extractor->extract_background( $settings, '_background_hover_' );
		if ( $bg_hover ) {
			$adv['backgroundHover'] = $bg_hover;
		}

		// Transition duration for hover
		if ( isset( $settings['background_hover_transition'] ) || isset( $settings['_background_hover_transition'] ) ) {
			$trans = $dim_extractor->extract_transition_duration( $settings['background_hover_transition'] ?? $settings['_background_hover_transition'] );
			if ( $trans ) {
				$adv['transitionDuration'] = $trans;
			}
		}

		// Box Shadow (Normal & Hover)
		$shadow_val = $settings['box_shadow_box_shadow'] ?? ( $settings['_box_shadow_box_shadow'] ?? null );
		if ( ! empty( $shadow_val ) ) {
			$shadow_css = $extractor->extract_box_shadow( $shadow_val );
			if ( $shadow_css ) {
				$adv['boxShadow'] = $shadow_css;
			}
		}

		$shadow_hover_val = $settings['box_shadow_hover_box_shadow'] ?? ( $settings['_box_shadow_hover_box_shadow'] ?? null );
		if ( ! empty( $shadow_hover_val ) ) {
			$shadow_hover_css = $extractor->extract_box_shadow( $shadow_hover_val );
			if ( $shadow_hover_css ) {
				$adv['boxShadowHover'] = $shadow_hover_css;
			}
		}

		// Border (Normal & Hover)
		$border = $extractor->extract_border( $settings, 'border_' ) ?? $extractor->extract_border( $settings, '_border_' );
		if ( $border ) {
			$adv['border'] = $border;
		}

		$border_hover = $extractor->extract_border( $settings, 'border_hover_' ) ?? $extractor->extract_border( $settings, '_border_hover_' );
		if ( $border_hover ) {
			$adv['borderHover'] = $border_hover;
		}

		// Z-Index
		$z_index_val = $settings['_z_index'] ?? ( $settings['z_index'] ?? null );
		if ( null !== $z_index_val && '' !== (string) $z_index_val ) {
			$adv['zIndex'] = (int) Value_Unwrapper::unwrap( $z_index_val );
		}

		return $adv;
	}
}
