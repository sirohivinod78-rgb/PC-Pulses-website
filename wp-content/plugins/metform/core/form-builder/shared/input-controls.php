<?php
namespace MetForm\Core\FormBuilder\Shared;

defined( 'ABSPATH' ) || exit;

final class Input_Controls {
	private static function typography_selector( string $el ): string {
		return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
	}


	public static function content( string $label_default = 'Input', array $extra = array(), bool $url_prefill = true, ?string $locked_name_help = null, array $extra_top = array() ): array {
		return array_merge(
			array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
			),
			$extra_top,
			array(
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
					'default'   => $label_default,
					'inline'    => true,
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'labelPosition',
					'label'      => __( 'Label Position', 'metform' ),
					'default'    => 'top',
					'inline'     => true,
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
				$locked_name_help !== null
				? array(
					'type'     => 'text',
					'key'      => 'name',
					'label'    => __( 'Name', 'metform' ),
					'inline'   => true,
					'disabled' => true,
					'help'     => $locked_name_help,
				)
				: array(
					'type'   => 'text',
					'key'    => 'name',
					'label'  => __( 'Name', 'metform' ),
					'inline' => true,
					'help'   => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'   => 'text',
					'key'    => 'placeholder',
					'label'  => __( 'Placeholder', 'metform' ),
					'inline' => true,
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'defaultValue',
					'label' => __( 'Default Value', 'metform' ),
					'help'  => __( 'Pre-filled when the form first loads', 'metform' ),
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
			),
			$url_prefill ? array(
				array(
					'type'    => 'toggle',
					'key'     => 'getValueFromUrl',
					'label'   => __( 'Get Value From URL?', 'metform' ),
					'default' => false,
					'help'    => __( 'Prefills this field from a URL query parameter matching the Field Name.', 'metform' ),
				),
			) : array(),
			array(
				array(
					'type'        => 'section',
					'key'         => 'validation',
					'label'       => __( 'Validation', 'metform' ),
					'defaultOpen' => false,
				),
				array(
					'type'    => 'select',
					'key'     => 'validation.type',
					'label'   => __( 'Validate By', 'metform' ),
					'default' => 'none',
					'inline'  => true,
					'options' => array(
						array(
							'label' => __( 'None', 'metform' ),
							'value' => 'none',
						),
						array(
							'label' => __( 'By character length', 'metform' ),
							'value' => 'character',
						),
						array(
							'label' => __( 'By word length', 'metform' ),
							'value' => 'word',
						),
						array(
							'label' => __( 'By expression', 'metform' ),
							'value' => 'pattern',
						),
					),
				),
				array(
					'type'      => 'number',
					'key'       => 'validation.min',
					'label'     => __( 'Min', 'metform' ),
					'inline'    => true,
					'condition' => array( 'validation.type' => array( 'character', 'word' ) ),
				),
				array(
					'type'      => 'number',
					'key'       => 'validation.max',
					'label'     => __( 'Max', 'metform' ),
					'inline'    => true,
					'condition' => array( 'validation.type' => array( 'character', 'word' ) ),
				),
				array(
					'type'      => 'text',
					'key'       => 'validation.value',
					'label'     => __( 'Expression', 'metform' ),
					'help'      => __( 'Standard JavaScript regular expression.', 'metform' ),
					'condition' => array( 'validation.type' => 'pattern' ),
				),
				array(
					'type'      => 'text',
					'key'       => 'validation.message',
					'label'     => __( 'Error Message', 'metform' ),
					'help'      => __( 'Use {{min}} and {{max}} to insert the limits.', 'metform' ),
					'condition' => array( 'validation.type!' => 'none' ),
				),
			),
			$extra
		);
	}

	/** Matches inputControls.js's sharedInputStyle(el, { extra }). */
	public static function style( string $el = 'input', array $extra = array(), array $exclude = array() ): array {
		$base = array(
			array(
				'type'        => 'section',
				'label'       => __( 'Label', 'metform' ),
				'defaultOpen' => true,
				'condition'   => array( 'showLabel!' => false ),
			),
			array(
				'type'      => 'color',
				'key'       => 'styles.labelColor',
				'label'     => __( 'Label Color', 'metform' ),
				'default'   => '#374151',
				'selector'  => '{{WRAPPER}} .mfb-label { color: {{VALUE}}; }',
				'condition' => array( 'showLabel!' => false ),
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.labelTypography',
				'label'          => __( 'Label Typography', 'metform' ),
				'selector'       => self::typography_selector( '.mfb-label' ),
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				'condition'      => array( 'showLabel!' => false ),
			),
			array(
				'type'  => 'section',
				'label' => __( 'Help Text & Error', 'metform' ),
			),
			array(
				'type'     => 'color',
				'key'      => 'styles.helpTextColor',
				'label'    => __( 'Help Text Color', 'metform' ),
				'default'  => '#6b7280',
				'selector' => '{{WRAPPER}} .mfb-field__help { color: {{VALUE}}; }',
			),
			array(
				'type'     => 'color',
				'key'      => 'styles.errorColor',
				'label'    => __( 'Error Color', 'metform' ),
				'default'  => '#ef4444',
				'selector' => '{{WRAPPER}} .mfb-field__error { color: {{VALUE}}; }',
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.labelTypography',
				'label'          => __( 'Label Typography', 'metform' ),
				'selector'       => self::typography_selector( '.mfb-label' ),
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.labelMargin',
				'label'      => __( 'Label Margin', 'metform' ),
				'selector'   => '{{WRAPPER}} .mfb-label { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.labelPadding',
				'label'      => __( 'Label Padding', 'metform' ),
				'selector'   => '{{WRAPPER}} .mfb-label { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.helpTextTypography',
				'label'          => __( 'Help Text Typography', 'metform' ),
				'selector'       => self::typography_selector( '.mfb-field__help' ),
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.errorTypography',
				'label'          => __( 'Error Typography', 'metform' ),
				'selector'       => self::typography_selector( '.mfb-field__error' ),
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
			),

			array(
				'type'  => 'section',
				'label' => __( 'Colors', 'metform' ),
			),
			array(
				'type' => 'tabs',
				'tab'  => array( 'normal', 'focus' ),
			),
			array(
				'type'       => 'color',
				'key'        => 'styles.color',
				'label'      => __( 'Text Color', 'metform' ),
				'tab'        => 'normal',
				'default'    => '#111827',
				'selector'   => "{{WRAPPER}} {$el} { color: {{VALUE}}; }",
				'responsive' => true,
			),
			array(
				'type'     => 'background',
				'key'      => 'styles.background',
				'label'    => __( 'Background', 'metform' ),
				'tab'      => 'normal',
				'default'  => array(
					'type'  => 'classic',
					'color' => '#ffffff',
				),
				'selector' => "{{WRAPPER}} {$el} { {{VALUE}} }",
			),
			// The normal-state border color lives in the Border control
			// below ('styles.border' → color); forms saved with the old
			// standalone 'styles.borderColor' still render it through that
			// control's 'legacy' fallback.
			array(
				'type'     => 'color',
				'key'      => 'styles.focusColor',
				'label'    => __( 'Text Color', 'metform' ),
				'tab'      => 'focus',
				'default'  => '#111827',
				'selector' => "{{WRAPPER}} {$el}:focus { color: {{VALUE}}; }",
			),
			array(
				'type'     => 'background',
				'key'      => 'styles.focusBackground',
				'label'    => __( 'Background', 'metform' ),
				'tab'      => 'focus',
				'default'  => array(
					'type'  => 'classic',
					'color' => '#ffffff',
				),
				'selector' => "{{WRAPPER}} {$el}:focus { {{VALUE}} }",
			),
			array(
				'type'     => 'color',
				'key'      => 'styles.focusBorderColor',
				'label'    => __( 'Focus Border Color', 'metform' ),
				'tab'      => 'focus',
				'default'  => '#3b82f6',
				'selector' => "{{WRAPPER}} {$el}:focus { border-color: {{VALUE}}; outline-color: {{VALUE}}; }",
			),
			array(
				'type'     => 'color',
				'key'      => 'styles.placeholderColor',
				'label'    => __( 'Placeholder Color', 'metform' ),
				'default'  => '#9ca3af',
				'selector' => "{{WRAPPER}} {$el}::placeholder { color: {{VALUE}}; opacity: 1; }",
			),

			array(
				'type'  => 'section',
				'label' => __( 'Typography & Layout', 'metform' ),
			),
			array(
				'type'           => 'typography',
				'key'            => 'styles.typography',
				'label'          => __( 'Typography', 'metform' ),
				'selector'       => self::typography_selector( $el ),
				'responsive'     => true,
				'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
			),
			array(
				'type'       => 'range',
				'key'        => 'styles.width',
				'label'      => __( 'Width', 'metform' ),
				'default'    => '100%',
				'units'      => array( 'px', '%', 'em', 'rem' ),
				'selector'   => "{{WRAPPER}} {$el} { width: {{VALUE}}; }",
				'responsive' => true,
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.padding',
				'label'      => __( 'Padding', 'metform' ),
				'default'    => array(
					'top'    => '10px',
					'right'  => '16px',
					'bottom' => '10px',
					'left'   => '16px',
				),
				'selector'   => "{{WRAPPER}} {$el} { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }",
				'responsive' => true,
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.margin',
				'label'      => __( 'Margin', 'metform' ),
				'default'    => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '0px',
					'left'   => '0px',
				),
				'selector'   => '{{WRAPPER}} { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
			// The one place for the field's normal-state border: style, width,
			// color and radius (Css_Value_Formatter::border_to_css()). The
			// Colors section's "Focus Border Color" (`:focus`, one pseudo-class
			// more specific) overrides this color while focused.
			// 'widthFallback' matches the 1px base width, so picking a style
			// without a width still draws it. 'legacy' reads the old
			// standalone Border Color when this control has no color yet.
			array(
				'type'           => 'border',
				'key'            => 'styles.border',
				'label'          => __( 'Border', 'metform' ),
				'selector'       => "{{WRAPPER}} {$el} { {{VALUE}} }",
				'responsive'     => true,
				'responsiveKeys' => array( 'width', 'radius' ),
				'widthFallback'  => '1px',
				'legacy'         => array(
					'color' => array(
						'key'     => 'styles.borderColor',
						'default' => '#d1d5db',
					),
				),
			),
			array(
				'type'     => 'boxShadow',
				'key'      => 'styles.boxShadow',
				'label'    => __( 'Box Shadow', 'metform' ),
				'selector' => "{{WRAPPER}} {$el} { box-shadow: {{VALUE}}; }",
			),
		);

		if ( $exclude ) {
			$base = array_values(
				array_filter(
					$base,
					static fn ( array $control ): bool => empty( $control['key'] ) || ! in_array( $control['key'], $exclude, true )
				)
			);
		}

		return array_merge( $base, $extra );
	}

	/** Matches inputControls.js's sharedInputDefaultProps(labelDefault). */
	public static function default_props( string $label_default = 'Input', string $placeholder_default = '' ): array {
		return array(
			'label'           => $label_default,
			'labelPosition'   => 'top',
			'name'            => '',
			'placeholder'     => '',
			'required'        => false,
			'showLabel'       => true,
			'getValueFromUrl' => false,
			'styles'          => array(),
		);
	}

	public static function validation_attributes( array $settings ): string {
		$validation = is_array( $settings['validation'] ?? null ) ? $settings['validation'] : array();
		$attrs      = array();

		if ( ! empty( $settings['required'] ) ) {
			$attrs[] = 'required';
			if ( ! empty( $settings['requiredMessage'] ) ) {
				$attrs[] = 'data-mf-required-msg="' . esc_attr( (string) $settings['requiredMessage'] ) . '"';
			}
		}

		switch ( $validation['type'] ?? 'none' ) {
			case 'character':
				if ( isset( $validation['min'] ) && '' !== (string) $validation['min'] ) {
					$attrs[] = 'minlength="' . (int) $validation['min'] . '"';
				}
				if ( isset( $validation['max'] ) && '' !== (string) $validation['max'] ) {
					$attrs[] = 'maxlength="' . (int) $validation['max'] . '"';
				}
				break;
			case 'word':
				if ( isset( $validation['min'] ) && '' !== (string) $validation['min'] ) {
					$attrs[] = 'data-mf-word-min="' . (int) $validation['min'] . '"';
				}
				if ( isset( $validation['max'] ) && '' !== (string) $validation['max'] ) {
					$attrs[] = 'data-mf-word-max="' . (int) $validation['max'] . '"';
				}
				break;
			case 'pattern':
				if ( ! empty( $validation['value'] ) ) {
					$attrs[] = 'pattern="' . esc_attr( $validation['value'] ) . '"';
				}
				break;
		}

		if ( isset( $settings['min'] ) && '' !== (string) $settings['min'] ) {
			$attrs[] = 'min="' . esc_attr( (string) $settings['min'] ) . '"';
		}
		if ( isset( $settings['max'] ) && '' !== (string) $settings['max'] ) {
			$attrs[] = 'max="' . esc_attr( (string) $settings['max'] ) . '"';
		}
		if ( isset( $settings['step'] ) && '' !== (string) $settings['step'] ) {
			$attrs[] = 'step="' . esc_attr( (string) $settings['step'] ) . '"';
		}

		return implode( ' ', $attrs );
	}

	/** PHP-only: the get_property_schema() entries for every key content() can introduce. */
	public static function schema(): array {
		return array(
			'showLabel'       => array( 'type' => 'boolean' ),
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'            => array( 'type' => 'field_name' ),
			'placeholder'     => array( 'type' => 'string' ),
			'helpText'        => array( 'type' => 'string' ),
			'defaultValue'    => array( 'type' => 'string' ),
			'required'        => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
			'getValueFromUrl' => array( 'type' => 'boolean' ),
			'validation'      => array( 'type' => 'object' ),
		);
	}

	/**
	 * Quiz-specific content controls for text-based question inputs (Text Input only).
	 * Legacy MetForm Elementor widgets only exposed quiz controls on 'mf-text'.
	 */
	public static function quiz_controls(): array {
		return array(
			array(
				'type'        => 'section',
				'key'         => 'quiz',
				'label'       => __( 'Quiz Settings', 'metform' ),
				'defaultOpen' => true,
				'condition'   => array( '$form.formType' => 'quiz-form' ),
			),
			array(
				'type'      => 'number',
				'key'       => 'quizPoint',
				'label'     => __( 'Points', 'metform' ),
				'default'   => 0,
				'min'       => 0,
				'step'      => 0.5,
				'condition' => array( '$form.formType' => 'quiz-form' ),
			),
			array(
				'type'      => 'toggle',
				'key'       => 'quizAddAnswer',
				'label'     => __( 'Enable Correct Answer', 'metform' ),
				'default'   => false,
				'condition' => array( '$form.formType' => 'quiz-form' ),
			),
			array(
				'type'      => 'text',
				'key'       => 'quizAnswer',
				'label'     => __( 'Answer', 'metform' ),
				'condition' => array(
					'$form.formType' => 'quiz-form',
					'quizAddAnswer'  => true,
				),
			),
			array(
				'type'      => 'toggle',
				'key'       => 'quizAddNegativePoint',
				'label'     => __( 'Deduct Points for Wrong Answer', 'metform' ),
				'default'   => false,
				'condition' => array(
					'$form.formType' => 'quiz-form',
					'quizAddAnswer'  => true,
				),
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
					'quizAddAnswer'        => true,
					'quizAddNegativePoint' => true,
				),
			),
		);
	}

	public static function quiz_default_props(): array {
		return array(
			'quizPoint'            => 0,
			'quizAddAnswer'        => false,
			'quizAnswer'           => '',
			'quizAddNegativePoint' => false,
			'quizNegativePoint'    => 0,
		);
	}

	public static function quiz_schema(): array {
		return array(
			'quizPoint'            => array( 'type' => 'number' ),
			'quizAddAnswer'        => array( 'type' => 'boolean' ),
			'quizAnswer'           => array( 'type' => 'string' ),
			'quizAddNegativePoint' => array( 'type' => 'boolean' ),
			'quizNegativePoint'    => array( 'type' => 'number' ),
		);
	}
}
