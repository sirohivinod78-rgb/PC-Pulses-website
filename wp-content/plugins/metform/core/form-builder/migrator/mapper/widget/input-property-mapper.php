<?php
/**
 * Input property mapper for assembling standard form input field properties.
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
 * Maps standard input properties, validation rules, quiz metadata, and names.
 *
 * @since 4.1.9
 */
class Input_Property_Mapper {
	use Singleton;

	/**
	 * Maps common attributes for standard input fields.
	 *
	 * @param array  $settings      Widget settings.
	 * @param string $node_id       Node ID.
	 * @param string $default_label Default label text.
	 * @return array
	 */
	public function map( array $settings, string $node_id, string $default_label = 'Input' ): array {
		$label_pos = (string) Value_Unwrapper::unwrap( $settings['mf_input_label_display_property'] ?? 'block' ) === 'inline-block' ? 'left' : 'top';
		$required  = ( (string) Value_Unwrapper::unwrap( $settings['mf_input_required'] ?? 'no' ) ) === 'yes';

		$val_type = 'none';
		$raw_type = (string) Value_Unwrapper::unwrap( $settings['mf_input_validation_type'] ?? 'none' );
		if ( 'by_character_length' === $raw_type ) {
			$val_type = 'character';
		} elseif ( 'by_word_length' === $raw_type ) {
			$val_type = 'word';
		}

		$props = array(
			'label'         => (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ?? $default_label ),
			'showLabel'     => ( (string) Value_Unwrapper::unwrap( $settings['mf_input_label_status'] ?? 'yes' ) ) === 'yes',
			'labelPosition' => $label_pos,
			'name'          => $this->resolve_name( $settings, 'mf-input-' . $node_id ),
			'placeholder'   => (string) Value_Unwrapper::unwrap( $settings['mf_input_placeholder'] ?? '' ),
			'helpText'      => (string) Value_Unwrapper::unwrap( $settings['mf_input_help_text'] ?? '' ),
			'required'      => $required,
			'validation'    => array(
				'type' => $val_type,
				'min'  => Value_Unwrapper::unwrap( $settings['mf_input_min_value'] ?? '' ),
				'max'  => Value_Unwrapper::unwrap( $settings['mf_input_max_value'] ?? '' ),
			),
		);

		if ( ! empty( $settings['mf_input_validation_warning_message'] ) ) {
			$props['requiredMessage'] = (string) Value_Unwrapper::unwrap( $settings['mf_input_validation_warning_message'] );
		}

		// Quiz settings
		if ( isset( $settings['mf_quiz_point'] ) ) {
			$props['quizPoint'] = (float) Value_Unwrapper::unwrap( $settings['mf_quiz_point'] );
		}
		if ( isset( $settings['mf_quiz_question_answer'] ) && '' !== (string) $settings['mf_quiz_question_answer'] ) {
			$props['quizAddAnswer'] = true;
			$props['quizAnswer']    = (string) Value_Unwrapper::unwrap( $settings['mf_quiz_question_answer'] );
		}
		if ( isset( $settings['mf_quiz_negative_point_status'] ) ) {
			$props['quizAddNegativePoint'] = ( (string) Value_Unwrapper::unwrap( $settings['mf_quiz_negative_point_status'] ) ) === 'yes';
		}
		if ( isset( $settings['mf_quiz_negative_point'] ) ) {
			$props['quizNegativePoint'] = (float) Value_Unwrapper::unwrap( $settings['mf_quiz_negative_point'] );
		}

		$styles = Style_Extractor::instance()->map_field_styles( $settings );
		if ( ! empty( $styles ) ) {
			$props['styles'] = $styles;
		}

		$conditional_props = Conditional_Logic_Mapper::instance()->extract( $settings );
		if ( ! empty( $conditional_props ) ) {
			$props['conditional'] = $conditional_props['conditional'];
		}

		return $props;
	}

	/**
	 * Resolves a safe, non-empty field name.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $fallback Fallback name.
	 * @return string
	 */
	public function resolve_name( array $settings, string $fallback ): string {
		if ( isset( $settings['mf_input_name'] ) ) {
			$name = sanitize_key( (string) Value_Unwrapper::unwrap( $settings['mf_input_name'] ) );
			if ( '' !== $name ) {
				return $name;
			}
		}

		if ( isset( $settings['name'] ) || isset( $settings['input_name'] ) ) {
			$name = sanitize_key( (string) Value_Unwrapper::unwrap( $settings['name'] ?? $settings['input_name'] ) );
			if ( '' !== $name ) {
				return $name;
			}
		}

		if ( isset( $settings['mf_input_label'] ) ) {
			$label_slug = sanitize_key( str_replace( '-', '_', sanitize_title( (string) Value_Unwrapper::unwrap( $settings['mf_input_label'] ) ) ) );
			if ( '' !== $label_slug ) {
				return $label_slug;
			}
		}

		return sanitize_key( $fallback );
	}
}
