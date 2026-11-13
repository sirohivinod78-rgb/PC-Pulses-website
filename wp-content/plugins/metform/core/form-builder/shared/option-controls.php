<?php
namespace MetForm\Core\FormBuilder\Shared;

use MetForm\Core\FormBuilder\Icons\Block_Icon;

defined( 'ABSPATH' ) || exit;

/**
 * Shared field controls/schema/defaults for Wave 2's option-group blocks
 * (Checkbox, Radio, and — via their own `content()`/style overrides —
 * Select/ImageSelect/LikeDislike/Optin/GdprConsent/ToggleSelect), ported
 * 1:1 from src/form-builder/blocks/shared/optionControls.js.
 *
 * `content()`/`style()` mirror sharedOptionContent()/sharedOptionStyle()
 * exactly (same keys, same defaults, same selectors) — with ONE deliberate
 * deviation, called out where it happens below: the default "Marker" style
 * selectors target `:checked` directly against the real native input
 * instead of a JavaScript-managed checked-state class. React's checkbox
 * and radio group inputs can add an editor-only state modifier themselves,
 * at render time, computed from React state — nothing does that in a
 * zero-JS PHP render, so a selector keyed off that state is silently
 * inert (same bug class fixed for Switch in the Post-Wave-1 bug-fix pass —
 * see PHP_RENDERING_MIGRATION_PLAN.md's Progress log). `:checked` is real
 * browser state on the real `<input>`, so it works with zero JS.
 *
 * `schema()` has no JS equivalent — it's this class's one PHP-only
 * addition, declaring the sanitizer type for every key `content()`
 * introduces.
 */
final class Option_Controls {
	private static function typography_selector( string $el ): string {
		return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
	}

	/** Matches optionControls.js's sharedOptionContent({ labelDefault, requiredHelp, extra }). */
	public static function content( string $label_default = 'Options', string $required_help = 'Shown when no option is selected.', array $extra = array(), string $default_layout = 'vertical' ): array {
		return array_merge(
			array(
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
				array(
					'type'   => 'text',
					'key'    => 'name',
					'label'  => __( 'Name', 'metform' ),
					'inline' => true,
					'help'   => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),

				array(
					'type'       => 'select',
					'key'        => 'layout',
					'label'      => __( 'Layout', 'metform' ),
					'default'    => $default_layout,
					'options'    => array(
						array(
							'label' => __( 'Vertical', 'metform' ),
							'value' => 'vertical',
						),
						array(
							'label' => __( 'Horizontal', 'metform' ),
							'value' => 'horizontal',
						),
					),
					'responsive' => true,
				),
				array(
					'type'    => 'select',
					'key'     => 'textPosition',
					'label'   => __( 'Options Text Position', 'metform' ),
					'default' => 'after',
					'options' => array(
						array(
							'label' => __( 'Before option', 'metform' ),
							'value' => 'before',
						),
						array(
							'label' => __( 'After option', 'metform' ),
							'value' => 'after',
						),
					),
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
					'addLabel'   => __( 'ADD ITEM', 'metform' ),
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
							'type'  => 'icon',
							'key'   => 'icon',
							'label' => __( 'Icon', 'metform' ),
							'help'  => __( 'Optional icon shown next to this option.', 'metform' ),
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
					'help'      => $required_help,
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
			$extra
		);
	}

	/**
	 * Matches optionControls.js's sharedOptionStyle({ extra, marker }).
	 * $marker overrides the three "Marker" section selectors (checked_bg,
	 * check_color, checked_border) — Radio passes its own (native
	 * `:checked::after` on the real `<input>`, matching optionControls.js's
	 * radio override exactly). Checkbox uses the class default below.
	 */
	public static function style( array $extra = array(), array $marker = array() ): array {
		$marker_selectors = array_merge(
			array(
				// Default (Checkbox): the real native checkbox is the immediate
				// previous sibling of .mfb-option__input (see fieldBlock.css's
				// .mfb-option__box-wrap structure) — `:checked +` reads real
				// browser state with zero JS. See this file's class doc for why
				// this deviates from optionControls.js's editor-only checked state.
				'checked_bg'     => '{{WRAPPER}} .mfb-option__input-native:checked + .mfb-option__input { {{VALUE}} }',
				'check_color'    => '{{WRAPPER}} .mfb-option__check-icon { color: {{VALUE}}; }',
				'checked_border' => '{{WRAPPER}} .mfb-option__input-native:checked + .mfb-option__input { border-color: {{VALUE}}; }',
			),
			$marker
		);

		return array_merge(
			array(
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
					'label' => __( 'Options', 'metform' ),
				),
				array(
					'type'       => 'color',
					'key'        => 'styles.color',
					'label'      => __( 'Text Color', 'metform' ),
					'default'    => '#111827',
					'selector'   => '{{WRAPPER}} .mfb-option__label { color: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.accentColor',
					'label'    => __( 'Accent Color', 'metform' ),
					'default'  => '#2563eb',
					'selector' => '{{WRAPPER}} .mfb-option__input { border-color: {{VALUE}}; }',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.optionSize',
					'label'      => __( 'Option Size', 'metform' ),
					'default'    => '16px',
					'units'      => array( 'px' ),
					'min'        => 12,
					'max'        => 32,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-option__input { width: {{VALUE}}; height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.typography',
					'label'          => __( 'Typography', 'metform' ),
					'selector'       => self::typography_selector( '.mfb-option__label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Marker', 'metform' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.checkedBackground',
					'label'    => __( 'Checked Background', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '#2563eb',
					),
					'selector' => $marker_selectors['checked_bg'],
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.checkColor',
					'label'    => __( 'Check Mark Color', 'metform' ),
					'default'  => '#ffffff',
					'selector' => $marker_selectors['check_color'],
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.checkedBorderColor',
					'label'    => __( 'Checked Border Color', 'metform' ),
					'default'  => '#2563eb',
					'selector' => $marker_selectors['checked_border'],
				),

				array(
					'type'  => 'section',
					'label' => __( 'Spacing', 'metform' ),
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.optionPadding',
					'label'      => __( 'Option Padding', 'metform' ),
					'default'    => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} .mfb-option { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.markerGap',
					'label'      => __( 'Space After Marker', 'metform' ),
					'default'    => '8px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 40,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-option { gap: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.optionGap',
					'label'      => __( 'Gap Between Options', 'metform' ),
					'default'    => '8px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 40,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-option { margin-bottom: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.inlineGap',
					'label'      => __( 'Inline Gap', 'metform' ),
					'default'    => '16px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 40,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-options--horizontal { gap: {{VALUE}}; }',
					'responsive' => true,
					'condition'  => array( 'layout' => 'horizontal' ),
				),
			),
			$extra
		);
	}

	/** Matches optionControls.js's sharedOptionDefaultProps(labelDefault). */
	public static function default_props( string $label_default = 'Options', string $default_layout = 'vertical' ): array {
		return array(
			'label'                => $label_default,
			'labelPosition'        => 'top',
			'name'                 => '',
			'layout'               => $default_layout,
			'textPosition'         => 'after',
			'options'              => array(
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
			'required'             => false,
			'showLabel'            => true,
			'getValueFromUrl'      => false,
			'quizPoint'            => 0,
			'quizAddNegativePoint' => false,
			'quizNegativePoint'    => 0,
			'styles'               => array(),
		);
	}

	/** PHP-only: the get_property_schema() entries for every key content() can introduce. */
	public static function schema(): array {
		return array(
			'showLabel'            => array( 'type' => 'boolean' ),
			'label'                => array( 'type' => 'string' ),
			'labelPosition'        => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'                 => array( 'type' => 'field_name' ),
			'helpText'             => array( 'type' => 'string' ),
			'layout'               => array(
				'type'       => 'string',
				'enum'       => array( 'vertical', 'horizontal' ),
				'responsive' => true,
			),
			'textPosition'         => array(
				'type' => 'string',
				'enum' => array( 'before', 'after' ),
			),
			// 'array' (Document\Sanitizer::sanitize_value()) drops every
			// non-scalar item via array_filter('is_scalar') — wrong for a
			// repeater of {label,value,icon,disabled} objects, it would
			// silently zero out every saved option. 'object' recurses
			// instead (map_deep), keeping the nested shape. (The same bug
			// existed on Colorpicker's presetColors — fixed alongside this.)
			'options'              => array( 'type' => 'object' ),
			'required'             => array( 'type' => 'boolean' ),
			'requiredMessage'      => array( 'type' => 'string' ),
			'getValueFromUrl'      => array( 'type' => 'boolean' ),
			'quizPoint'            => array( 'type' => 'number' ),
			'quizAddNegativePoint' => array( 'type' => 'boolean' ),
			'quizNegativePoint'    => array( 'type' => 'number' ),
		);
	}

	/**
	 * PHP-only: renders one <label> option row shared by Checkbox/Radio's
	 * per-option markup shape (`<label class="mfb-option mfb-field-option">`
	 * wrapping the control + text span, ordered by textPosition). $input_html
	 * is the already-built control (native input, or native input + visual
	 * span for Checkbox) — kept block-specific since Checkbox/Radio's inner
	 * shape differs (see checkbox.php/radio.php). The option's `label` and
	 * `icon` are read from $opt; the icon only resolves when it's set.
	 *
	 * @param string               $input_html    The option's control markup.
	 * @param array<string, mixed> $opt           Options repeater item.
	 * @param string               $text_position 'before' or 'after' the control.
	 */
	public static function render_option_label( string $input_html, array $opt, string $text_position ): string {
		$icon  = Block_Icon::icon( $opt['icon'] ?? '' );
		$text  = sprintf(
			'<span class="mfb-option__label mfb-field-option__label">%s%s</span>',
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			esc_html( (string) ( $opt['label'] ?? '' ) )
		);
		$inner = 'before' === $text_position ? ( $text . $input_html ) : ( $input_html . $text );
		return '<label class="mfb-option mfb-field-option">' . $inner . '</label>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
