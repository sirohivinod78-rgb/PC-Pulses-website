<?php

/**
 * Fallback widget mapper for detecting and mapping arbitrary or unregistered Elementor widgets.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Widget
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Widget;

use MetForm\Core\FormBuilder\Migrator\Style_Extractor;
use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Heuristic fallback for arbitrary or unregistered widget identifiers.
 *
 * @since 4.1.9
 */
class Fallback_Widget_Mapper {

	use Singleton;

	/**
	 * Tier 3: Heuristic fallback for arbitrary or unregistered widget identifiers.
	 *
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array|null
	 */
	public function map( array $settings, string $node_id, string $block_id, ?string $parent_id ): ?array {
		if ( isset( $settings['mf_btn_text'] ) || isset( $settings['button_text'] ) ) {
			$align_raw = (string) Value_Unwrapper::unwrap( $settings['mf_btn_align'] ?? ( $settings['alignment'] ?? ( $settings['align'] ?? 'left' ) ) );
			$alignment = in_array( $align_raw, array( 'left', 'center', 'right', 'full' ), true ) ? $align_raw : ( 'justify' === $align_raw ? 'full' : 'left' );
			$styles    = Style_Extractor::instance()->map_button_styles( $settings );
			$props     = array(
				'text'       => (string) Value_Unwrapper::unwrap( $settings['mf_btn_text'] ?? ( $settings['button_text'] ?? esc_html__( 'Submit Button', 'metform' ) ) ),
				'buttonType' => 'submit',
				'alignment'  => $alignment,
			);
			if ( ! empty( $styles ) ) {
				$props['styles'] = $styles;
			}
			return array(
				'id'       => $block_id,
				'type'     => 'button',
				'parentId' => $parent_id,
				'props'    => $props,
			);
		}

		if ( ! empty( $settings['mf_input_list'] ) || ! empty( $settings['mf_input_options'] ) ) {
			return array(
				'id'       => $block_id,
				'type'     => 'select',
				'parentId' => $parent_id,
				'props'    => Choice_Widget_Mapper::instance()->map_option_props( $settings, $node_id ),
			);
		}

		if ( isset( $settings['mf_input_name'] ) || isset( $settings['input_name'] ) || isset( $settings['mf_input_label'] ) ) {
			return array(
				'id'       => $block_id,
				'type'     => 'input',
				'parentId' => $parent_id,
				'props'    => Input_Property_Mapper::instance()->map( $settings, $node_id ),
			);
		}

		if ( ! empty( $settings['editor'] ) || ! empty( $settings['html'] ) || ! empty( $settings['text'] ) ) {
			return Content_Widget_Mapper::instance()->map_paragraph( $settings, $node_id, $block_id, $parent_id );
		}

		return null;
	}
}
