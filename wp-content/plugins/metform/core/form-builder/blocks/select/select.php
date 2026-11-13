<?php
namespace MetForm\Core\FormBuilder\Blocks\Select;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Select extends Block_Base {
	public function get_name(): string {
		return 'select'; }
	public function get_title(): string {
		return __( 'Select', 'metform' ); }
	public function get_icon(): string {
		return 'List'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'choice'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array_merge(
			Input_Controls::default_props( __( 'Select', 'metform' ), __( 'Select an option', 'metform' ) ),
			array(
				'options'  => array(
					array(
						'label' => 'Option 1',
						'value' => 'option-1',
					),
					array(
						'label' => 'Option 2',
						'value' => 'option-2',
					),
					array(
						'label' => 'Option 3',
						'value' => 'option-3',
					),
				),
				'multiple' => false,
			)
		);
	}

	public function get_property_schema(): array {
		return array_merge(
			Input_Controls::schema(),
			array(
				'multiple' => array( 'type' => 'boolean' ),
				'options'  => array( 'type' => 'object' ),
			)
		);
	}

	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showLabel',
					'label'   => __( 'Show Label', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'Select', 'metform' ),
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'labelPosition',
					'label'      => __( 'Label Position', 'metform' ),
					'default'    => 'top',
					'options'    => array(
						array(
							'label' => __( 'Top', 'metform' ),
							'value' => 'top',
						),
						array(
							'label' => __( 'Left', 'metform' ),
							'value' => 'left',
						),
					),
					'condition'  => array( 'showLabel!' => false ),
					'responsive' => true,
				),
				array(
					'type'  => 'text',
					'key'   => 'name',
					'label' => __( 'Name', 'metform' ),
					'help'  => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'placeholder',
					'label' => __( 'Placeholder', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'multiple',
					'label'   => __( 'Multiple Select', 'metform' ),
					'default' => false,
				),
				array(
					'type'       => 'repeater',
					'key'        => 'options',
					'label'      => __( 'Options', 'metform' ),
					'default'    => array(
						array(
							'label' => 'Option 1',
							'value' => 'option-1',
						),
						array(
							'label' => 'Option 2',
							'value' => 'option-2',
						),
						array(
							'label' => 'Option 3',
							'value' => 'option-3',
						),
					),
					'titleField' => 'label',
					'addLabel'   => __( 'ADD OPTION', 'metform' ),
					'min'        => 1,
					'fields'     => array(
						array(
							'type'  => 'text',
							'key'   => 'label',
							'label' => __( 'Label', 'metform' ),
						),
						array(
							'type'  => 'text',
							'key'   => 'value',
							'label' => __( 'Value', 'metform' ),
						),
						array(
							'type'  => 'toggle',
							'key'   => 'disabled',
							'label' => __( 'Disabled', 'metform' ),
						),
						array(
							'type'  => 'toggle',
							'key'   => 'isDefault',
							'label' => __( 'Default Selected', 'metform' ),
						),
						array(
							'type'  => 'toggle',
							'key'   => 'isQuizAnswer',
							'label' => __( 'Correct Answer', 'metform' ),
						),
					),
				),

				array(
					'type'        => 'section',
					'key'         => 'settings',
					'label'       => __( 'Settings', 'metform' ),
					'defaultOpen' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'required',
					'label'   => __( 'Required', 'metform' ),
					'default' => false,
				),
				array(
					'type'      => 'text',
					'key'       => 'requiredMessage',
					'label'     => __( 'Required Message', 'metform' ),
					'help'      => __( 'Shown when the field is left empty.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'getValueFromUrl',
					'label'   => __( 'Get Value From URL?', 'metform' ),
					'default' => false,
					'help'    => __( 'Prefills this field from a URL query parameter matching the Field Name.', 'metform' ),
				),
				array(
					'type'        => 'section',
					'key'         => 'quiz',
					'label'       => __( 'Quiz Settings', 'metform' ),
					'defaultOpen' => false,
					'condition'   => array( '$form.formType' => 'quiz-form' ),
				),
				array(
					'type'      => 'number',
					'key'       => 'quizPoint',
					'label'     => __( 'Points', 'metform' ),
					'default'   => 0,
					'min'       => 0,
					'help'      => __( "Note: if the point and the negative point both are set at zero, we won't consider it a question.", 'metform' ),
					'condition' => array( '$form.formType' => 'quiz-form' ),
				),
				array(
					'type'      => 'toggle',
					'key'       => 'quizAddNegativePoint',
					'label'     => __( 'Deduct Points for Wrong Answer', 'metform' ),
					'default'   => false,
					'condition' => array( '$form.formType' => 'quiz-form' ),
				),
				array(
					'type'      => 'number',
					'key'       => 'quizNegativePoint',
					'label'     => __( 'Points to Deduct', 'metform' ),
					'default'   => 0,
					'min'       => 0,
					'step'      => 0.5,
					'condition' => array(
						'$form.formType'       => 'quiz-form',
						'quizAddNegativePoint' => true,
					),
				),
			),

			'style'   => Input_Controls::style(
				'.mfb-field__input',
				array(
					array(
						'type'      => 'section',
						'label'     => __( 'Single Select', 'metform' ),
						'condition' => array( 'multiple!' => true ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.placeholderColor',
						'label'    => __( 'Placeholder / Disabled Color', 'metform' ),
						'default'  => '#9ca3af',
						'help'     => __( 'The disabled-option/placeholder color of the native dropdown.', 'metform' ),
						'selector' => '{{WRAPPER}} select option:disabled { color: {{VALUE}}; }',
					),
					array(
						'type'      => 'section',
						'label'     => __( 'Multiple Select — Tags', 'metform' ),
						'condition' => array( 'multiple' => true ),
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.tagsWrapBackground',
						'label'    => __( 'Wrapper Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#ffffff',
						),
						'selector' => '{{WRAPPER}} .mfb-select__tags { {{VALUE}} }',
					),
					array(
						'type'           => 'border',
						'key'            => 'styles.tagsWrapBorder',
						'label'          => __( 'Wrapper Border', 'metform' ),
						'default'        => array(
							'borderType' => 'solid',
							'width'      => array(
								'top'    => '1px',
								'right'  => '1px',
								'bottom' => '1px',
								'left'   => '1px',
							),
							'color'      => '#d1d5db',
							'radius'     => array(
								'top'    => '6px',
								'right'  => '6px',
								'bottom' => '6px',
								'left'   => '6px',
							),
						),
						'selector'       => '{{WRAPPER}} .mfb-select__tags { {{VALUE}} }',
						'responsive'     => true,
						'responsiveKeys' => array( 'width', 'radius' ),
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.tagBackground',
						'label'    => __( 'Tag Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#f3f4f6',
						),
						'selector' => '{{WRAPPER}} .mfb-select__tag { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.tagColor',
						'label'    => __( 'Tag Text Color', 'metform' ),
						'default'  => '#374151',
						'selector' => '{{WRAPPER}} .mfb-select__tag { color: {{VALUE}}; }',
					),
					array(
						'type'           => 'border',
						'key'            => 'styles.tagBorder',
						'label'          => __( 'Tag Border', 'metform' ),
						'default'        => array(
							'borderType' => 'solid',
							'width'      => array(
								'top'    => '1px',
								'right'  => '1px',
								'bottom' => '1px',
								'left'   => '1px',
							),
							'color'      => '#d1d5db',
							'radius'     => array(
								'top'    => '4px',
								'right'  => '4px',
								'bottom' => '4px',
								'left'   => '4px',
							),
						),
						'selector'       => '{{WRAPPER}} .mfb-select__tag { {{VALUE}} }',
						'responsive'     => true,
						'responsiveKeys' => array( 'width', 'radius' ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.tagRemoveColor',
						'label'    => __( 'Remove Icon Color', 'metform' ),
						'default'  => '#6b7280',
						'selector' => '{{WRAPPER}} .mfb-select__tag-remove { color: {{VALUE}}; }',
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.addButtonBackground',
						'label'    => __( 'Add Button Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#ffffff',
						),
						'selector' => '{{WRAPPER}} .mfb-select__add-btn { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.addButtonColor',
						'label'    => __( 'Add Button Icon Color', 'metform' ),
						'default'  => '#6b7280',
						'selector' => '{{WRAPPER}} .mfb-select__add-btn { color: {{VALUE}}; }',
					),

					array(
						'type'      => 'section',
						'label'     => __( 'Multiple Select — Dropdown', 'metform' ),
						'condition' => array( 'multiple' => true ),
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.dropdownBackground',
						'label'    => __( 'Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#ffffff',
						),
						'selector' => '{{WRAPPER}} .mfb-select__dropdown { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.dropdownBorderColor',
						'label'    => __( 'Border Color', 'metform' ),
						'default'  => '#d1d5db',
						'selector' => '{{WRAPPER}} .mfb-select__dropdown { border-color: {{VALUE}}; }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.dropdownOptionColor',
						'label'    => __( 'Option Text Color', 'metform' ),
						'default'  => '#111827',
						'selector' => '{{WRAPPER}} .mfb-select__dropdown-option { color: {{VALUE}}; }',
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.dropdownHoverBackground',
						'label'    => __( 'Option Hover Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#f3f4f6',
						),
						'selector' => '{{WRAPPER}} .mfb-select__dropdown-option:hover { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.dropdownDisabledColor',
						'label'    => __( 'Disabled Option Color', 'metform' ),
						'default'  => '#9ca3af',
						'selector' => '{{WRAPPER}} .mfb-select__dropdown-option.mfb-select__dropdown-option--disabled { color: {{VALUE}}; }',
					),
				),
				array(
					'styles.placeholderColor',
				)
			),
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$options     = is_array( $settings['options'] ?? null ) ? $settings['options'] : array();
		$multiple    = ! empty( $settings['multiple'] );
		$placeholder = (string) ( $settings['placeholder'] ?? '' );

		// Single-select's placeholder is a real disabled <option> above the first real option
		$has_default = false;
		foreach ( $options as $opt ) {
			if ( is_array( $opt ) && ! empty( $opt['isDefault'] ) ) {
				$has_default = true;
				break;
			}
		}

		$option_html = '';
		if ( ! $multiple && $placeholder ) {
			$option_html .= sprintf( '<option value="" disabled %s>%s</option>', $has_default ? '' : 'selected', esc_html( $placeholder ) );
		}
		foreach ( $options as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			$is_quiz_answer = ! empty( $opt['isQuizAnswer'] );
			$option_html   .= sprintf(
				'<option value="%1$s" %2$s %3$s %4$s>%5$s</option>',
				esc_attr( (string) ( $opt['value'] ?? '' ) ),
				! empty( $opt['disabled'] ) ? 'disabled' : '',
				! empty( $opt['isDefault'] ) ? 'selected' : '',
				$is_quiz_answer ? 'data-quiz-answer="true"' : '',
				esc_html( (string) ( $opt['label'] ?? '' ) )
			);
		}

		$pre_rendered_tags  = '';
		$select_classes     = 'mfb-field__input';
		$select_extra_attrs = '';

		if ( $multiple ) {
			$select_classes    .= ' mfb-select__native-hidden';
			$select_extra_attrs = 'style="display:none;" tabindex="-1" aria-hidden="true"';

			$tags_inner     = '';
			$selected_count = 0;
			foreach ( $options as $opt ) {
				if ( is_array( $opt ) && ! empty( $opt['isDefault'] ) ) {
					++$selected_count;
					$tags_inner .= '<span class="mfb-select__tag"><span class="mfb-select__tag-label">' . esc_html( (string) ( $opt['label'] ?? '' ) ) . '</span></span>';
				}
			}
			if ( 0 === $selected_count && $placeholder ) {
				$tags_inner = '<span class="mfb-select__placeholder">' . esc_html( $placeholder ) . '</span>';
			}
			$pre_rendered_tags = '<div class="mfb-select__tags">' . $tags_inner . '</div>';
		}

		return sprintf(
			'<select id="%1$s" name="%2$s" class="%3$s" %4$s %5$s %6$s %7$s>%8$s</select>%9$s',
			esc_attr( $context->get_element_id() ),
			esc_attr( $multiple ? $settings['name'] . '[]' : $settings['name'] ),
			esc_attr( $select_classes ),
			$multiple ? 'multiple' : '',
			! empty( $settings['required'] ) ? 'required' : '',
			( $multiple && $placeholder ) ? sprintf( 'data-mf-select-placeholder="%s"', esc_attr( $placeholder ) ) : '',
			$select_extra_attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$option_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$pre_rendered_tags // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
