<?php
/**
 * Choice widget mapper for converting Elementor choice controls (select, radio, checkbox, etc.) to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Widget
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Widget;

use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maps choice and selection widgets and their option lists.
 *
 * @since 4.1.9
 */
class Choice_Widget_Mapper {
	use Singleton;

	/**
	 * Maps choice/option widgets (select, radio, checkbox, etc.).
	 *
	 * @param string      $type      Normalized widget type.
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array
	 */
	public function map( string $type, array $settings, string $node_id, string $block_id, ?string $parent_id ): array {
		$block_type = 'select';
		$label      = 'Select';
		$extra      = array();

		if ( in_array( $type, array( 'multi-select', 'multiselect' ), true ) ) {
			$label             = 'Multi Select';
			$extra['multiple'] = true;
		} elseif ( in_array( $type, array( 'radio', 'radio-button', 'radio-group' ), true ) ) {
			$block_type      = 'radio';
			$label           = 'Radio';
			$extra['layout'] = ( Value_Unwrapper::unwrap( $settings['mf_input_display_type'] ?? '' ) ) === 'inline-block' ? 'horizontal' : 'vertical';
		} elseif ( in_array( $type, array( 'checkbox', 'checkbox-group' ), true ) ) {
			$block_type      = 'checkbox';
			$label           = 'Checkbox';
			$extra['layout'] = ( Value_Unwrapper::unwrap( $settings['mf_input_display_type'] ?? '' ) ) === 'inline-block' ? 'horizontal' : 'vertical';
		} elseif ( 'toggle-select' === $type ) {
			$is_pro     = null !== Block_Registry::instance()->get( 'toggleselect' );
			$block_type = $is_pro ? 'toggleselect' : 'select';
			$label      = 'Toggle Select';
		} elseif ( 'image-select' === $type ) {
			$is_pro     = null !== Block_Registry::instance()->get( 'imageselect' );
			$block_type = $is_pro ? 'imageselect' : 'radio';
			$label      = 'Image Select';
		}

		$props = array_merge( $this->map_option_props( $settings, $node_id, esc_html__( $label, 'metform' ) ), $extra );

		return array(
			'id'       => $block_id,
			'type'     => $block_type,
			'parentId' => $parent_id,
			'props'    => $props,
		);
	}

	/**
	 * Maps option fields (select, radio, checkbox, image-select) and normalizes repeaters.
	 *
	 * @param array  $settings      Widget settings.
	 * @param string $node_id       Node ID.
	 * @param string $default_label Default label text.
	 * @return array
	 */
	public function map_option_props( array $settings, string $node_id, string $default_label = 'Options' ): array {
		$props = Input_Property_Mapper::instance()->map( $settings, $node_id, $default_label );

		$raw_options = $settings['mf_input_list'] ?? ( $settings['mf_input_options'] ?? array() );
		$options     = array();

		if ( is_array( $raw_options ) ) {
			foreach ( $raw_options as $option ) {
				if ( ! is_array( $option ) ) {
					continue;
				}

				$image_title  = (string) Value_Unwrapper::unwrap( $option['mf_image_select_title'] ?? '' );
				$raw_text     = $option['mf_input_option_text'] ?? ( $option['label'] ?? ( $option['option_text'] ?? '' ) );
				$option_image = '';
				if ( is_array( $raw_text ) ) {
					$option_label = '' !== $image_title ? $image_title : ( $raw_text['alt'] ?? ( ! empty( $raw_text['id'] ) ? 'Image #' . $raw_text['id'] : 'Image' ) );
					$option_image = $raw_text['url'] ?? '';
				} else {
					$option_label = '' !== $image_title ? $image_title : (string) Value_Unwrapper::unwrap( $raw_text );
				}

				$option_value = (string) Value_Unwrapper::unwrap( $option['mf_input_option_value'] ?? ( $option['value'] ?? ( $option['option_value'] ?? sanitize_title( $option_label ) ) ) );

				$entry = array(
					'label' => '' !== $option_label ? $option_label : esc_html__( 'Option', 'metform' ),
					'value' => $option_value,
				);

				if ( ( (string) Value_Unwrapper::unwrap( $option['mf_quiz_question_answer'] ?? 'no' ) ) === 'yes' ) {
					$entry['isQuizAnswer'] = true;
				}

				if ( '' !== $option_image ) {
					$entry['imageUrl'] = esc_url_raw( $option_image );
				}

				$options[] = $entry;
			}
		}

		if ( empty( $options ) ) {
			$options = array(
				array(
					'label' => esc_html__( 'Option 1', 'metform' ),
					'value' => 'option-1',
				),
				array(
					'label' => esc_html__( 'Option 2', 'metform' ),
					'value' => 'option-2',
				),
			);
		}

		$props['options'] = $options;
		return $props;
	}
}
