<?php
namespace MetForm\Core\FormBuilder\Blocks\Multistep;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Icons\Block_Icon;
use MetForm\Core\FormBuilder\Dynamic_Css\Css_Value_Formatter;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Advanced\Visibility;

defined( 'ABSPATH' ) || exit;


final class Multistep extends Block_Base {
	/** Typography control declarations (see Css_Value_Formatter::fill_typography()). */
	private const TYPOGRAPHY = '{ font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }';

	private const STEPPER_POSITIONS    = array( 'top', 'bottom', 'vertical', 'hidden' );
	private const PROGRESS_POSITIONS   = array( 'top', 'bottom', 'inline', 'hidden' );
	private const STEP_STATUS_DISPLAYS = array( 'none', 'text', 'icons', 'both' );
	private const STEP_STYLES          = array( 'default', 'chevron', 'connector', 'timeline', 'progress-line' );
	private const STEP_NUMBER_SHAPES   = array( 'circle', 'square', 'rounded' );

	public function get_name(): string {
		return 'multistep'; }
	public function get_title(): string {
		return __( 'Multi-Step Form', 'metform' ); }
	public function get_icon(): string {
		return 'Layers'; }
	public function get_category(): string {
		return 'Layout'; }

	public function get_group(): string {
		return 'other'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren'  => true,
			'isField'          => false,
			'cannotNestInside' => array( 'multistep', 'container' ),
		);
	}

	/**
	 * Builds the default `steps` repeater value.
	 *
	 * The default step icons are icon keys, which render as inline SVG from
	 * the bundled icon library on every WordPress version (no Font Awesome
	 * CSS enqueue) — see {@see Block_Icon::icon()}.
	 *
	 * @since 4.4.0
	 */
	private function get_default_steps(): array {
		$icon_defaults = array(
			'user'     => 'fas-user',
			'envelope' => 'fas-envelope',
			'check'    => 'fas-check',
		);

		return array(
			array(
				'id'          => 'step-1',
				'label'       => __( 'Step 1', 'metform' ),
				'description' => __( 'First step', 'metform' ),
				'icon'        => $icon_defaults['user'],
			),
			array(
				'id'          => 'step-2',
				'label'       => __( 'Step 2', 'metform' ),
				'description' => __( 'Second step', 'metform' ),
				'icon'        => $icon_defaults['envelope'],
			),
			array(
				'id'          => 'step-3',
				'label'       => __( 'Step 3', 'metform' ),
				'description' => __( 'Third step', 'metform' ),
				'icon'        => $icon_defaults['check'],
			),
		);
	}

	public function get_default_settings(): array {
		return array(
			'steps'                => $this->get_default_steps(),
			'currentStep'          => 0,
			'stepperPosition'      => 'top',
			'stepStyle'            => 'default',
			'stepStatusDisplay'    => 'none',
			'showStepNumbers'      => true,
			'stepNumberShape'      => 'circle',
			'showStepDescriptions' => true,
			'enablePreviousButton' => true,
			'prevButtonLabel'      => __( 'Previous', 'metform' ),
			'nextButtonLabel'      => __( 'Next', 'metform' ),
			'submitButtonLabel'    => __( 'Submit', 'metform' ),
			'completionMessage'    => __( 'Form Completed!', 'metform' ),
			'enableValidation'     => true,
			'showProgressBar'      => false,
			'progressPosition'     => 'top',
			'saveProgress'         => false,
			'__className'          => '',
			'styles'               => array(
				'activeStepColor'           => '#2563eb',
				'activeStepTextColor'       => '#ffffff',
				'inactiveStepColor'         => '#374151',
				'inactiveStepTextColor'     => '#d1d5db',
				'contentBackground'         => array(
					'type'  => 'classic',
					'color' => '#ffffff',
				),
				'contentTextColor'          => '#000000',
				'contentBorder'             => array(
					'borderType' => 'solid',
					'width'      => array(
						'top'    => '1px',
						'right'  => '1px',
						'bottom' => '1px',
						'left'   => '1px',
					),
					'color'      => '#374151',
					'radius'     => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
				),
				'contentPadding'            => array(
					'top'    => '16px',
					'right'  => '16px',
					'bottom' => '16px',
					'left'   => '16px',
				),
				'prevButtonBgColor'         => '#374151',
				'prevButtonTextColor'       => '#d1d5db',
				'nextButtonBgColor'         => '#2563eb',
				'nextButtonTextColor'       => '#ffffff',
				'stepNumberBgColor'         => '#374151',
				'stepNumberTextColor'       => '#d1d5db',
				'stepNumberBgColorActive'   => '#2563eb',
				'stepNumberTextColorActive' => '#ffffff',
				'stepNumberSize'            => '20px',
				'stepGap'                   => '12px',
				'stepButtonBorderRadius'    => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '0px',
					'left'   => '0px',
				),
				'navButtonBorderRadius'     => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '0px',
					'left'   => '0px',
				),
				'progressBarFillColor'      => '#2563eb',
				'progressBarBgColor'        => '#e5e7eb',
				'progressBarHeight'         => '6px',
				'progressBarRadius'         => '4px',
				'connectorColor'            => '#374151',
				'connectorCompletedColor'   => '#10b981',
				'connectorDotSize'          => '14px',
				'stepIconColor'             => '#ffffff',
			),
		);
	}

	public function get_property_schema(): array {
		return array(
			'steps'                => array( 'type' => 'object' ),
			'currentStep'          => array( 'type' => 'number' ),
			'stepperPosition'      => array(
				'type'       => 'string',
				'enum'       => self::STEPPER_POSITIONS,
				'responsive' => true,
			),
			'stepStyle'            => array(
				'type' => 'string',
				'enum' => self::STEP_STYLES,
			),
			'stepStatusDisplay'    => array(
				'type' => 'string',
				'enum' => self::STEP_STATUS_DISPLAYS,
			),
			'showStepNumbers'      => array( 'type' => 'boolean' ),
			'stepNumberShape'      => array(
				'type' => 'string',
				'enum' => self::STEP_NUMBER_SHAPES,
			),
			'showStepDescriptions' => array( 'type' => 'boolean' ),
			'enablePreviousButton' => array( 'type' => 'boolean' ),
			'prevButtonLabel'      => array( 'type' => 'string' ),
			'nextButtonLabel'      => array( 'type' => 'string' ),
			'submitButtonLabel'    => array( 'type' => 'string' ),
			'completionMessage'    => array( 'type' => 'string' ),
			'enableValidation'     => array( 'type' => 'boolean' ),
			'showProgressBar'      => array( 'type' => 'boolean' ),
			'progressPosition'     => array(
				'type' => 'string',
				'enum' => self::PROGRESS_POSITIONS,
			),
			'saveProgress'         => array( 'type' => 'boolean' ),
			'__className'          => array( 'type' => 'css_class' ),
		);
	}

	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Steps', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'           => 'repeater',
					'key'            => 'steps',
					'label'          => __( 'Manage Steps', 'metform' ),
					'min'            => 1,
					'addLabel'       => __( 'Add Step', 'metform' ),
					'titleField'     => 'label',
					'manageChildren' => array(
						'indexKey'       => 'stepIndex',
						'activeIndexKey' => 'currentStep',
					),
					'itemDefault'    => array(
						'label'       => __( 'New Step', 'metform' ),
						'description' => '',
						'icon'        => '',
					),
					'fields'         => array(
						array(
							'type'  => 'text',
							'key'   => 'label',
							'label' => __( 'Label', 'metform' ),
						),
						array(
							'type'  => 'text',
							'key'   => 'description',
							'label' => __( 'Description', 'metform' ),
						),
						array(
							'type'  => 'icon',
							'key'   => 'icon',
							'label' => __( 'Icon', 'metform' ),
							'help'  => __( 'Shown in place of the status glyph when Step Status Display is set to Icons or Text + Icons. Leave empty to use the default finished/active/pending glyph.', 'metform' ),
						),
					),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Stepper', 'metform' ),
				),
				array(
					'type'       => 'select',
					'key'        => 'stepperPosition',
					'label'      => __( 'Stepper Position', 'metform' ),
					'default'    => 'top',
					'options'    => array(
						array(
							'value' => 'top',
							'label' => __( 'Top (Horizontal)', 'metform' ),
						),
						array(
							'value' => 'bottom',
							'label' => __( 'Bottom (Horizontal)', 'metform' ),
						),
						array(
							'value' => 'vertical',
							'label' => __( 'Left (Vertical)', 'metform' ),
						),
						array(
							'value' => 'hidden',
							'label' => __( 'Hidden', 'metform' ),
						),
					),
					'responsive' => true,
					'help'       => __( 'Left puts the steps in a column beside the content (Default and Timeline styles; the others stay on top). Set Tablet/Mobile separately, e.g. Left on desktop and Top on mobile.', 'metform' ),
				),
				array(
					'type'      => 'select',
					'key'       => 'stepStyle',
					'label'     => __( 'Step Style', 'metform' ),
					'default'   => 'default',
					'options'   => array(
						array(
							'value' => 'default',
							'label' => __( 'Default (Simple Buttons)', 'metform' ),
						),
						array(
							'value' => 'chevron',
							'label' => __( 'Chevron/Arrow', 'metform' ),
						),
						array(
							'value' => 'connector',
							'label' => __( 'Connector Dots', 'metform' ),
						),
						array(
							'value' => 'timeline',
							'label' => __( 'Timeline', 'metform' ),
						),
						array(
							'value' => 'progress-line',
							'label' => __( 'Progress Line', 'metform' ),
						),
					),
					'help'      => __( 'Chevron, Connector Dots, Timeline and Progress Line use the Connector colors and Connector Dot Size below.', 'metform' ),
					'condition' => array( 'stepperPosition!' => 'hidden' ),
				),
				array(
					'type'      => 'select',
					'key'       => 'stepStatusDisplay',
					'label'     => __( 'Step Status Display', 'metform' ),
					'default'   => 'none',
					'help'      => __( 'Icons replace the step number; Text adds Finished / In Progress / Waiting under each step.', 'metform' ),
					'options'   => array(
						array(
							'value' => 'none',
							'label' => __( 'None', 'metform' ),
						),
						array(
							'value' => 'text',
							'label' => __( 'Text (Finished, In Progress, Waiting)', 'metform' ),
						),
						array(
							'value' => 'icons',
							'label' => __( 'Icons', 'metform' ),
						),
						array(
							'value' => 'both',
							'label' => __( 'Text + Icons', 'metform' ),
						),
					),
					'condition' => array( 'stepperPosition!' => 'hidden' ),
				),
				array(
					'type'      => 'toggle',
					'key'       => 'showStepNumbers',
					'label'     => __( 'Show Step Numbers', 'metform' ),
					'default'   => true,
					// Numbers show unless Status Display puts icons in their place
					// (Text + Icons keeps both only in the Default style).
					'condition' => array(
						'stepperPosition!' => 'hidden',
						'$or'              => array(
							array( 'stepStatusDisplay' => array( 'none', 'text' ) ),
							array(
								'stepStatusDisplay' => 'both',
								'stepStyle'         => 'default',
							),
						),
					),
				),
				array(
					'type'      => 'select',
					'key'       => 'stepNumberShape',
					'label'     => __( 'Number / Dot Shape', 'metform' ),
					'default'   => 'circle',
					// Numbers are hidden while Step Status Display shows icons only.
					'options'   => array(
						array(
							'value' => 'circle',
							'label' => __( 'Circle', 'metform' ),
						),
						array(
							'value' => 'square',
							'label' => __( 'Square', 'metform' ),
						),
						array(
							'value' => 'rounded',
							'label' => __( 'Rounded', 'metform' ),
						),
					),
					// The shape of the number badge (Default, Chevron) or of the step
					// dot (Connector Dots, Timeline, Progress Line — always shown).
					'condition' => array(
						'stepperPosition!' => 'hidden',
						'$or'              => array(
							array( 'stepStyle' => array( 'connector', 'timeline', 'progress-line' ) ),
							array(
								'stepStyle'          => 'default',
								'showStepNumbers!'   => false,
								'stepStatusDisplay!' => 'icons',
							),
							array(
								'stepStyle'         => 'chevron',
								'showStepNumbers!'  => false,
								'stepStatusDisplay' => array( 'none', 'text' ),
							),
						),
					),
				),
				array(
					'type'      => 'toggle',
					'key'       => 'showStepDescriptions',
					'label'     => __( 'Show Step Descriptions', 'metform' ),
					'default'   => true,
					'condition' => array(
						'stepperPosition!' => 'hidden',
						'stepStyle'        => array( 'default', 'timeline' ),
					),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Progress Bar', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showProgressBar',
					'label'   => __( 'Show Progress Bar', 'metform' ),
					'default' => false,
				),
				array(
					'type'      => 'select',
					'key'       => 'progressPosition',
					'label'     => __( 'Progress Bar Position', 'metform' ),
					'default'   => 'top',
					'condition' => array( 'showProgressBar' => true ),
					'options'   => array(
						array(
							'value' => 'top',
							'label' => __( 'Top (Above Stepper)', 'metform' ),
						),
						array(
							'value' => 'bottom',
							'label' => __( 'Bottom (Below Stepper)', 'metform' ),
						),
						array(
							'value' => 'inline',
							'label' => __( 'Inline (Within Stepper)', 'metform' ),
						),
						array(
							'value' => 'hidden',
							'label' => __( 'Hidden', 'metform' ),
						),
					),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Navigation', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'enablePreviousButton',
					'label'   => __( 'Enable Previous Button', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'prevButtonLabel',
					'label'     => __( 'Previous Button Label', 'metform' ),
					'default'   => __( 'Previous', 'metform' ),
					'condition' => array( 'enablePreviousButton' => true ),
				),
				array(
					'type'    => 'text',
					'key'     => 'nextButtonLabel',
					'label'   => __( 'Next Button Label', 'metform' ),
					'default' => __( 'Next', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'submitButtonLabel',
					'label'   => __( 'Submit Button Label', 'metform' ),
					'default' => __( 'Submit', 'metform' ),
					'help'    => __( 'Shown on the Next button in place of "Next" on the final step.', 'metform' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Validation', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'enableValidation',
					'label'   => __( 'Validate Each Step', 'metform' ),
					'default' => true,
					'help'    => __( 'Checks the current step\'s fields before moving to a later step.', 'metform' ),
				),

			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Steps', 'metform' ),
					'defaultOpen' => true,
					'condition'   => array( 'stepperPosition!' => 'hidden' ),
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'inactive', 'active' ),
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.inactiveStepColor',
					'label'   => __( 'Background', 'metform' ),
					'default' => '#374151',
					'tab'     => 'inactive',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.inactiveStepTextColor',
					'label'   => __( 'Text', 'metform' ),
					'default' => '#d1d5db',
					'tab'     => 'inactive',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.activeStepColor',
					'label'   => __( 'Background', 'metform' ),
					'default' => '#2563eb',
					'tab'     => 'active',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.activeStepTextColor',
					'label'   => __( 'Text', 'metform' ),
					'default' => '#ffffff',
					'tab'     => 'active',
				),
				array(
					'type'          => 'dimensions',
					'key'           => 'styles.stepButtonBorderRadius',
					'rendersMarkup' => true,
					'condition'     => array( 'stepStyle' => 'default' ),
					'label'         => __( 'Border Radius', 'metform' ),
					'default'       => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'      => '{{WRAPPER}} .mfb-stepper-default__button { border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive'    => true,
				),
				array(
					'type'          => 'range',
					'key'           => 'styles.stepGap',
					'rendersMarkup' => true,
					'condition'     => array( 'stepStyle' => 'default' ),
					'label'         => __( 'Step Gap', 'metform' ),
					'default'       => '12px',
					'units'         => array( 'px' ),
					'min'           => 0,
					'max'           => 64,
					'step'          => 1,
					'selector'      => '{{WRAPPER}} .mfb-stepper-default__list { gap: {{VALUE}}; }',
					'responsive'    => true,
				),
				array(
					'type'      => 'color',
					'key'       => 'styles.stepIconColor',
					'label'     => __( 'Status Icon Color', 'metform' ),
					'default'   => '#ffffff',
					'selector'  => '{{WRAPPER}} .mfb-stepper-default__status-icon, {{WRAPPER}} .mfb-stepper-default__status-icon-mr { color: {{VALUE}}; } {{WRAPPER}} .mfb-st { --mfb-st-icon: {{VALUE}}; }',
					// Icons sit in a dot (and take its text color) in the other styles.
					'condition' => array(
						'stepStatusDisplay' => array( 'icons', 'both' ),
						'stepStyle'         => array( 'default', 'chevron' ),
					),
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.stepIconSize',
					'label'      => __( 'Status Icon Size', 'metform' ),
					'help'       => __( 'The status icon (✓ / ● / custom step icon). In Connector, Timeline and Progress Line it sits inside the step dot.', 'metform' ),
					'units'      => array( 'px', 'em' ),
					'min'        => 8,
					'max'        => 64,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-stepper-default__status-icon, {{WRAPPER}} .mfb-stepper-default__status-icon-mr, {{WRAPPER}} .mfb-st__icon, {{WRAPPER}} .mfb-st__dot .mfb-st__by-state > span, {{WRAPPER}} .mfb-st__dot .mfb-st__dot-icon { width: {{VALUE}}; height: {{VALUE}}; font-size: {{VALUE}}; line-height: 1; } {{WRAPPER}} .mfb-st__dot svg, {{WRAPPER}} .mfb-st__dot img { max-width: 100%; max-height: 100%; }',
					'responsive' => true,
					'condition'  => array( 'stepStatusDisplay' => array( 'icons', 'both' ) ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.stepTitleTypography',
					'label'          => __( 'Label Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-stepper-default__title, {{WRAPPER}} .mfb-st__title ' . self::TYPOGRAPHY,
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'      => 'color',
					'key'       => 'styles.stepDescColor',
					'label'     => __( 'Description Color', 'metform' ),
					'selector'  => '{{WRAPPER}} .mfb-stepper-default__desc, {{WRAPPER}} .mfb-stepper-timeline__desc { color: {{VALUE}}; opacity: 1; }',
					'condition' => array(
						'showStepDescriptions!' => false,
						'stepStyle'             => array( 'default', 'timeline' ),
					),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.stepDescTypography',
					'label'          => __( 'Description Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-stepper-default__desc, {{WRAPPER}} .mfb-stepper-timeline__desc ' . self::TYPOGRAPHY,
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array(
						'showStepDescriptions!' => false,
						'stepStyle'             => array( 'default', 'timeline' ),
					),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.stepStatusTypography',
					'label'          => __( 'Status Text Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-stepper-default__status, {{WRAPPER}} .mfb-st__status, {{WRAPPER}} .mfb-stepper-timeline__status ' . self::TYPOGRAPHY,
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array( 'stepStatusDisplay' => array( 'text', 'both' ) ),
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.stepPadding',
					'label'      => __( 'Step Padding', 'metform' ),
					'selector'   => '{{WRAPPER}} .mfb-stepper-default__button, {{WRAPPER}} .mfb-stepper-chevron__button, {{WRAPPER}} .mfb-stepper-timeline__button { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
					'condition'  => array( 'stepStyle' => array( 'default', 'chevron', 'timeline' ) ),
				),
				array(
					'type'       => 'select',
					'key'        => 'styles.stepperAlign',
					'label'      => __( 'Alignment', 'metform' ),
					'help'       => __( 'Where the step buttons sit in their row.', 'metform' ),
					'options'    => array(
						array(
							'value' => 'flex-start',
							'label' => __( 'Start', 'metform' ),
						),
						array(
							'value' => 'center',
							'label' => __( 'Center', 'metform' ),
						),
						array(
							'value' => 'flex-end',
							'label' => __( 'End', 'metform' ),
						),
						array(
							'value' => 'space-between',
							'label' => __( 'Space Between', 'metform' ),
						),
					),
					'selector'   => '{{WRAPPER}} .mfb-stepper-default__list { justify-content: {{VALUE}}; }',
					'responsive' => true,
					'condition'  => array(
						'stepStyle'        => 'default',
						'stepperPosition!' => 'vertical',
					),
				),

				array(
					'type'      => 'section',
					'label'     => __( 'Stepper Box', 'metform' ),
					'condition' => array( 'stepperPosition!' => 'hidden' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.stepperBackground',
					'label'    => __( 'Background', 'metform' ),
					'selector' => '{{WRAPPER}} .mfb-multistep__stepper-wrap { {{VALUE}} }',
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.stepperPadding',
					'label'      => __( 'Padding', 'metform' ),
					'selector'   => '{{WRAPPER}} .mfb-multistep__stepper-wrap { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.stepperBorder',
					'label'          => __( 'Border', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-multistep__stepper-wrap { {{VALUE}} }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),

				array(
					'type'      => 'section',
					'label'     => __( 'Step Numbers', 'metform' ),
					// Only the number badge of the Default and Chevron styles.
					'condition' => array(
						'stepperPosition!' => 'hidden',
						'$or'              => array(
							array(
								'stepStyle'          => 'default',
								'showStepNumbers!'   => false,
								'stepStatusDisplay!' => 'icons',
							),
							array(
								'stepStyle'         => 'chevron',
								'showStepNumbers!'  => false,
								'stepStatusDisplay' => array( 'none', 'text' ),
							),
						),
					),
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'inactive', 'active' ),
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.stepNumberBgColor',
					'label'   => __( 'Background', 'metform' ),
					'default' => '#374151',
					'tab'     => 'inactive',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.stepNumberTextColor',
					'label'   => __( 'Text', 'metform' ),
					'default' => '#d1d5db',
					'tab'     => 'inactive',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.stepNumberBgColorActive',
					'label'   => __( 'Background', 'metform' ),
					'default' => '#2563eb',
					'tab'     => 'active',
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.stepNumberTextColorActive',
					'label'   => __( 'Text', 'metform' ),
					'default' => '#ffffff',
					'tab'     => 'active',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.stepNumberSize',
					'label'      => __( 'Number Size', 'metform' ),
					'default'    => '20px',
					'units'      => array( 'px' ),
					'min'        => 12,
					'max'        => 48,
					'step'       => 1,
					// The desktop size is also written inline by render() (and
					// swapped by the frontend script); this rule carries every
					// non-default size, per breakpoint, and wins over the
					// inline value.
					'selector'   => '{{WRAPPER}} .mfb-stepper-default__number, {{WRAPPER}} .mfb-st__num { width: {{VALUE}}; height: {{VALUE}}; font-size: calc({{VALUE}} * 0.6); }',
					'responsive' => true,
				),

				array(
					'type'      => 'section',
					'label'     => __( 'Completed Steps & Connectors', 'metform' ),
					'condition' => array(
						'stepperPosition!' => 'hidden',
						'stepStyle!'       => 'default',
					),
				),
				array(
					'type'    => 'color',
					'key'     => 'styles.connectorCompletedColor',
					'label'   => __( 'Completed Step Color', 'metform' ),
					'default' => '#10b981',
					'help'    => __( 'Finished steps (and the line up to them).', 'metform' ),
				),
				array(
					'type'      => 'color',
					'key'       => 'styles.connectorColor',
					'label'     => __( 'Connector Line Color', 'metform' ),
					'default'   => '#374151',
					'condition' => array( 'stepStyle' => 'connector' ),
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.connectorDotSize',
					'condition'  => array( 'stepStyle' => 'connector' ),
					'label'      => __( 'Connector Dot Size', 'metform' ),
					'default'    => '14px',
					'units'      => array( 'px' ),
					'min'        => 6,
					'max'        => 48,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-stepper-connector__dot { width: {{VALUE}}; height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.connectorLineWidth',
					'label'      => __( 'Connector Line Thickness', 'metform' ),
					'units'      => array( 'px' ),
					'min'        => 1,
					'max'        => 12,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-stepper-connector { --mfb-st-line-w: {{VALUE}}; }',
					'responsive' => true,
					'condition'  => array( 'stepStyle' => 'connector' ),
				),

				array(
					'type'      => 'section',
					'label'     => __( 'Progress Bar', 'metform' ),
					// The progress bar, or the Progress Line style's own bar.
					'condition' => array(
						'$or' => array(
							array(
								'showProgressBar'   => true,
								'progressPosition!' => 'hidden',
							),
							array(
								'stepStyle'        => 'progress-line',
								'stepperPosition!' => 'hidden',
							),
						),
					),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.progressBarFillColor',
					'label'    => __( 'Fill Color', 'metform' ),
					'help'     => __( 'This section styles the progress bar (Content → Progress Bar) and the bar of the Progress Line step style.', 'metform' ),
					'default'  => '#2563eb',
					'selector' => '{{WRAPPER}} .mfb-multistep__progress-fill, {{WRAPPER}} .mfb-stepper-progressline__fill { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.progressBarBgColor',
					'label'    => __( 'Background Color', 'metform' ),
					'default'  => '#e5e7eb',
					'selector' => '{{WRAPPER}} .mfb-multistep__progress-track, {{WRAPPER}} .mfb-stepper-progressline__track { background-color: {{VALUE}}; }',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.progressBarHeight',
					'label'      => __( 'Height', 'metform' ),
					'default'    => '6px',
					'units'      => array( 'px' ),
					'min'        => 2,
					'max'        => 32,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-multistep__progress-track, {{WRAPPER}} .mfb-stepper-progressline__track { height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.progressBarRadius',
					'label'      => __( 'Radius', 'metform' ),
					'default'    => '4px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 32,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-multistep__progress-track, {{WRAPPER}} .mfb-stepper-progressline__track { border-radius: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.progressLabelColor',
					'label'    => __( 'Percentage Color', 'metform' ),
					'selector' => '{{WRAPPER}} .mfb-multistep__progress-label, {{WRAPPER}} .mfb-stepper-progressline__percent { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.progressLabelTypography',
					'label'          => __( 'Percentage Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-multistep__progress-label, {{WRAPPER}} .mfb-stepper-progressline__percent ' . self::TYPOGRAPHY,
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Content Area', 'metform' ),
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'normal', 'hover' ),
				),
				array(
					'type'          => 'background',
					'key'           => 'styles.contentBackground',
					'rendersMarkup' => true,
					'label'         => __( 'Background', 'metform' ),
					'default'       => array(
						'type'  => 'classic',
						'color' => '#ffffff',
					),
					'selector'      => '{{WRAPPER}} .mfb-multistep__content { {{VALUE}} }',
					'tab'           => 'normal',
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.contentBackgroundHover',
					'label'    => __( 'Background', 'metform' ),
					'help'     => __( 'Applied on :hover, in addition to Background above.', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '',
					),
					'selector' => '{{WRAPPER}} .mfb-multistep__content:hover { {{VALUE}} }',
					'tab'      => 'hover',
				),
				array(
					'type'     => 'range',
					'key'      => 'styles.contentTransitionDuration',
					'label'    => __( 'Transition Duration', 'metform' ),
					'help'     => __( 'How smoothly Background/Border/Box Shadow hover changes animate.', 'metform' ),
					'default'  => '',
					'units'    => array( 'ms' ),
					'min'      => 0,
					'max'      => 2000,
					'step'     => 50,
					'selector' => '{{WRAPPER}} .mfb-multistep__content { transition-duration: {{VALUE}}; transition-property: background-color, background-image, border-color, border-width, border-radius, box-shadow; transition-timing-function: ease-in-out; }',
					'tab'      => 'hover',
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.contentTextColor',
					'rendersMarkup' => true,
					'label'         => __( 'Text Color', 'metform' ),
					'default'       => '#000000',
					'selector'      => '{{WRAPPER}} .mfb-multistep__content { color: {{VALUE}}; }',
				),
				array(
					'type'          => 'dimensions',
					'key'           => 'styles.contentPadding',
					'rendersMarkup' => true,
					'label'         => __( 'Padding', 'metform' ),
					'default'       => array(
						'top'    => '16px',
						'right'  => '16px',
						'bottom' => '16px',
						'left'   => '16px',
					),
					'selector'      => '{{WRAPPER}} .mfb-multistep__content { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive'    => true,
				),

				array(
					'type'  => 'section',
					'label' => __( 'Content Border', 'metform' ),
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'normal', 'hover' ),
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.contentBorder',
					'rendersMarkup'  => true,
					'label'          => __( 'Border', 'metform' ),
					'tab'            => 'normal',
					'default'        => array(
						'borderType' => 'solid',
						'width'      => array(
							'top'    => '1px',
							'right'  => '1px',
							'bottom' => '1px',
							'left'   => '1px',
						),
						'color'      => '#374151',
						'radius'     => array(
							'top'    => '0px',
							'right'  => '0px',
							'bottom' => '0px',
							'left'   => '0px',
						),
					),
					'selector'       => '{{WRAPPER}} .mfb-multistep__content { {{VALUE}} }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'     => 'boxShadow',
					'key'      => 'styles.contentBoxShadow',
					'label'    => __( 'Box Shadow', 'metform' ),
					'tab'      => 'normal',
					'selector' => '{{WRAPPER}} .mfb-multistep__content { box-shadow: {{VALUE}}; }',
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.contentBorderHover',
					'label'          => __( 'Border', 'metform' ),
					'tab'            => 'hover',
					'selector'       => '{{WRAPPER}} .mfb-multistep__content:hover { {{VALUE}} }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'     => 'boxShadow',
					'key'      => 'styles.contentBoxShadowHover',
					'label'    => __( 'Box Shadow', 'metform' ),
					'tab'      => 'hover',
					'selector' => '{{WRAPPER}} .mfb-multistep__content:hover { box-shadow: {{VALUE}}; }',
				),

				array(
					'type'  => 'section',
					'label' => __( 'Navigation Buttons', 'metform' ),
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'previous', 'next' ),
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.prevButtonBgColor',
					'rendersMarkup' => true,
					'label'         => __( 'Background', 'metform' ),
					'default'       => '#374151',
					'tab'           => 'previous',
					'selector'      => '{{WRAPPER}} .mfb-multistep__prev-button { background-color: {{VALUE}}; }',
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.prevButtonTextColor',
					'rendersMarkup' => true,
					'label'         => __( 'Text', 'metform' ),
					'default'       => '#d1d5db',
					'tab'           => 'previous',
					'selector'      => '{{WRAPPER}} .mfb-multistep__prev-button { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.prevButtonHoverBgColor',
					'label'    => __( 'Hover Background', 'metform' ),
					'tab'      => 'previous',
					'selector' => '{{WRAPPER}} .mfb-multistep__prev-button:not(:disabled):hover { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.prevButtonHoverTextColor',
					'label'    => __( 'Hover Text', 'metform' ),
					'tab'      => 'previous',
					'selector' => '{{WRAPPER}} .mfb-multistep__prev-button:not(:disabled):hover { color: {{VALUE}}; }',
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.nextButtonBgColor',
					'rendersMarkup' => true,
					'label'         => __( 'Background', 'metform' ),
					'default'       => '#2563eb',
					'tab'           => 'next',
					'selector'      => '{{WRAPPER}} .mfb-multistep__next-button { background-color: {{VALUE}}; }',
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.nextButtonTextColor',
					'rendersMarkup' => true,
					'label'         => __( 'Text', 'metform' ),
					'default'       => '#ffffff',
					'tab'           => 'next',
					'selector'      => '{{WRAPPER}} .mfb-multistep__next-button { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.nextButtonHoverBgColor',
					'label'    => __( 'Hover Background', 'metform' ),
					'tab'      => 'next',
					'selector' => '{{WRAPPER}} .mfb-multistep__next-button:hover { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.nextButtonHoverTextColor',
					'label'    => __( 'Hover Text', 'metform' ),
					'tab'      => 'next',
					'selector' => '{{WRAPPER}} .mfb-multistep__next-button:hover { color: {{VALUE}}; }',
				),
				// Outside the tab group — one radius for both Previous and
				// Next, same reasoning as Step Gap/Number Size above.
				array(
					'type'          => 'dimensions',
					'key'           => 'styles.navButtonBorderRadius',
					'rendersMarkup' => true,
					'label'         => __( 'Border Radius', 'metform' ),
					'default'       => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'      => '{{WRAPPER}} .mfb-multistep__prev-button, {{WRAPPER}} .mfb-multistep__next-button { border-radius: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive'    => true,
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.navButtonTypography',
					'label'          => __( 'Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-multistep__prev-button, {{WRAPPER}} .mfb-multistep__next-button ' . self::TYPOGRAPHY,
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.navButtonPadding',
					'label'      => __( 'Padding', 'metform' ),
					'selector'   => '{{WRAPPER}} .mfb-multistep__prev-button, {{WRAPPER}} .mfb-multistep__next-button { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.navButtonBorder',
					'label'          => __( 'Border', 'metform' ),
					'help'           => __( 'Border Radius above sets the corners.', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-multistep__prev-button, {{WRAPPER}} .mfb-multistep__next-button { {{VALUE}} }',
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'       => 'select',
					'key'        => 'styles.navAlign',
					'label'      => __( 'Alignment', 'metform' ),
					'options'    => array(
						array(
							'value' => 'space-between',
							'label' => __( 'Space Between', 'metform' ),
						),
						array(
							'value' => 'flex-start',
							'label' => __( 'Start', 'metform' ),
						),
						array(
							'value' => 'center',
							'label' => __( 'Center', 'metform' ),
						),
						array(
							'value' => 'flex-end',
							'label' => __( 'End', 'metform' ),
						),
					),
					// An explicit alignment also drops the Next button's
					// "push right" margin, which only exists for the default.
					'selector'   => '{{WRAPPER}} .mfb-multistep__nav { justify-content: {{VALUE}}; } {{WRAPPER}} .mfb-multistep__next-button { margin-left: 0; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.navButtonGap',
					'label'      => __( 'Gap Between Buttons', 'metform' ),
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 64,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-multistep__nav { gap: {{VALUE}}; }',
					'responsive' => true,
				),
			),
		);
	}

	/** Matches index.jsx's getStyle(): a saved styles.{key} value, or $default when unset/empty. */
	private function get_style( array $styles, string $key, $default ) {
		$value = array_key_exists( $key, $styles ) ? $styles[ $key ] : null;
		// Responsive (`{desktop,tablet,mobile}`) values: the inline markup
		// carries the desktop value; tablet/mobile come from the generated
		// stylesheet's media rules, which override it (`!important`).
		if ( Responsive_Value::is_responsive( $value ) ) {
			$value = Responsive_Value::resolve( $value, 'desktop', null );
		}
		return ( '' !== $value && null !== $value ) ? $value : $default;
	}

	/**
	 * Builds a `T R B L` value (e.g. for `border-radius:`/`padding:`) from a
	 * `type: 'dimensions'` style value (DimensionsControl's own shape:
	 * `{top,right,bottom,left}`, each already carrying its unit, e.g. "4px")
	 * — same per-corner control Container uses for its own Padding/Border
	 * Radius. Falls back to $default when the style is missing or malformed
	 * (legacy forms saved before this control existed).
	 *
	 * @since 4.1.11
	 */
	private function get_dimensions_css( array $styles, string $key, array $default = array(
		'top'    => '0px',
		'right'  => '0px',
		'bottom' => '0px',
		'left'   => '0px',
	) ): string {
		$val = $this->get_style( $styles, $key, $default );
		if ( ! is_array( $val ) ) {
			$val = $default;
		}
		return sprintf(
			'%1$s %2$s %3$s %4$s',
			esc_attr( $val['top'] ?? $default['top'] ),
			esc_attr( $val['right'] ?? $default['right'] ),
			esc_attr( $val['bottom'] ?? $default['bottom'] ),
			esc_attr( $val['left'] ?? $default['left'] )
		);
	}

	/**
	 * Builds a full `border-style:…; border-width:…; border-color:…;
	 * border-radius:…;` declaration from a `type: 'border'` style value
	 * (BorderControl's own shape: `{borderType, width:{...}, color,
	 * radius:{...}}`) — the same composite control Container/Button use for
	 * their own borders. Used for Content Area's *normal* border only —
	 * its Hover counterpart has no real default (see get_default_settings())
	 * and can only ever come from the generated stylesheet, since an inline
	 * `style="…"` attribute has no way to express `:hover`.
	 *
	 * @since 4.1.12
	 */
	private function get_border_css( array $styles, string $key, array $default ): string {
		$val    = $this->get_style( $styles, $key, $default );
		$val    = is_array( $val ) ? $val : $default;
		$width  = Responsive_Value::resolve( $val['width'] ?? null, 'desktop', null );
		$radius = Responsive_Value::resolve( $val['radius'] ?? null, 'desktop', null );
		$width  = is_array( $width ) ? $width : $default['width'];
		$radius = is_array( $radius ) ? $radius : $default['radius'];
		return sprintf(
			'border-style: %1$s; border-width: %2$s %3$s %4$s %5$s; border-color: %6$s; border-radius: %7$s %8$s %9$s %10$s;',
			esc_attr( $val['borderType'] ?? $default['borderType'] ),
			esc_attr( $width['top'] ?? $default['width']['top'] ),
			esc_attr( $width['right'] ?? $default['width']['right'] ),
			esc_attr( $width['bottom'] ?? $default['width']['bottom'] ),
			esc_attr( $width['left'] ?? $default['width']['left'] ),
			esc_attr( $val['color'] ?? $default['color'] ),
			esc_attr( $radius['top'] ?? $default['radius']['top'] ),
			esc_attr( $radius['right'] ?? $default['radius']['right'] ),
			esc_attr( $radius['bottom'] ?? $default['radius']['bottom'] ),
			esc_attr( $radius['left'] ?? $default['radius']['left'] )
		);
	}

	/**
	 * Renders one step's status icon markup — a per-step custom icon (set via
	 * the "Icon" field on Manage Steps: the normalized Icon API value, a
	 * Font Awesome class string, a media URL, or a data: URL for an uploaded
	 * SVG/PNG — see {@see Block_Icon::icon()} for the full shape
	 * rundown) when the step has one, otherwise the old finished/active/
	 * pending glyph (✓ / ● / step number) as a safe fallback so existing
	 * forms with no icon set keep rendering exactly as before.
	 *
	 * @since 4.1.11
	 * @since 4.4.0 Delegates to the shared Icon_Resolver (previously only
	 *              understood Font Awesome class strings and data: URLs,
	 *              and never enqueued their CSS).
	 *
	 * @param mixed $custom_icon The step's stored `icon` value.
	 */
	private function render_step_status_icon( $custom_icon, bool $is_completed, bool $is_active, int $step_index ): string {
		$icon_html = Block_Icon::icon( $custom_icon );
		if ( '' !== $icon_html ) {
			return $icon_html;
		}

		return esc_html( $this->get_icon_for_status( $is_completed, $is_active, $step_index ) );
	}

	private function get_icon_for_status( bool $is_completed, bool $is_active, int $step_index ) {
		if ( $is_completed ) {
			return '✓';
		}
		return $is_active ? '●' : (string) ( $step_index + 1 );
	}

	/**
	 * Renders the stepper in the chosen Step Style.
	 *
	 * @since 4.4.1
	 */
	private function render_stepper( array $steps, int $current_step, array $settings, array $styles, string $stepper_position ): string {
		$style = in_array( $settings['stepStyle'] ?? 'default', self::STEP_STYLES, true ) ? $settings['stepStyle'] : 'default';
		if ( 'default' === $style ) {
			return $this->render_stepper_default( $steps, $current_step, $settings, $styles, $stepper_position );
		}
		return $this->render_stepper_styled( $style, $steps, $current_step, $settings, $styles, $stepper_position );
	}

	/**
	 * Chevron / Connector Dots / Timeline / Progress Line steppers (the
	 * styles the old React stepper had — StepperChevron.jsx etc., removed in
	 * the PHP migration — restored server-side).
	 *
	 * Step state is a class on each `[data-mf-step-item]` (is-done /
	 * is-active / is-wait) and each `[data-mf-step-line]`, which the
	 * frontend script toggles on navigation; colors are CSS custom
	 * properties on the stepper root, so no inline style has to be swapped.
	 * MultistepCanvas.jsx renders the same markup in the editor.
	 *
	 * @since 4.4.1
	 */
	private function render_stepper_styled( string $style, array $steps, int $current_step, array $settings, array $styles, string $stepper_position ): string {
		$show_numbers   = (bool) ( $settings['showStepNumbers'] ?? true );
		$show_desc      = (bool) ( $settings['showStepDescriptions'] ?? true );
		$status_display = in_array( $settings['stepStatusDisplay'] ?? 'none', self::STEP_STATUS_DISPLAYS, true ) ? $settings['stepStatusDisplay'] : 'none';
		$show_icons     = in_array( $status_display, array( 'icons', 'both' ), true );
		$inactive_step  = (string) $this->get_style( $styles, 'inactiveStepColor', '#374151' );
		$count          = count( $steps );
		$slug           = 'progress-line' === $style ? 'progressline' : $style; // CSS block name.

		$vars   = sprintf(
			'--mfb-st-active: %1$s; --mfb-st-active-text: %2$s; --mfb-st-done: %3$s; --mfb-st-line: %4$s; --mfb-st-inactive: %5$s; --mfb-st-inactive-text: %6$s; --mfb-st-icon: %7$s; --mfb-st-num-bg: %8$s; --mfb-st-num-text: %9$s; --mfb-st-dot: %10$s; --mfb-st-num-bg-active: %11$s; --mfb-st-num-text-active: %12$s;',
			$this->get_style( $styles, 'activeStepColor', '#2563eb' ),
			$this->get_style( $styles, 'activeStepTextColor', '#ffffff' ),
			$this->get_style( $styles, 'connectorCompletedColor', '#10b981' ),
			$this->get_style( $styles, 'connectorColor', $inactive_step ),
			$inactive_step,
			$this->get_style( $styles, 'inactiveStepTextColor', '#d1d5db' ),
			$this->get_style( $styles, 'stepIconColor', '#ffffff' ),
			$this->get_style( $styles, 'stepNumberBgColor', '#374151' ),
			$this->get_style( $styles, 'stepNumberTextColor', '#d1d5db' ),
			$this->get_style( $styles, 'connectorDotSize', '14px' ),
			$this->get_style( $styles, 'stepNumberBgColorActive', $this->get_style( $styles, 'activeStepColor', '#2563eb' ) ),
			$this->get_style( $styles, 'stepNumberTextColorActive', $this->get_style( $styles, 'activeStepTextColor', '#ffffff' ) )
		);
		$vars  .= ' --mfb-st-shape: ' . self::number_radius( $settings ) . ';';
		$status = in_array( $status_display, array( 'text', 'both' ), true ) ? '<span class="mfb-st__status">' . self::status_text_variants() . '</span>' : '';

		$items = '';
		foreach ( $steps as $index => $step ) {
			$state     = $index < $current_step ? 'is-done' : ( $index === $current_step ? 'is-active' : 'is-wait' );
			$label     = esc_html( (string) ( $step['label'] ?? '' ) );
			$indicator = $this->styled_indicator( $step, $index, $show_icons, $show_numbers );
			$item_attr = sprintf( 'class="mfb-st__item mfb-stepper-%1$s__item %2$s%3$s" data-mf-step-item="%4$d"', $slug, $state, $index === $count - 1 ? ' is-last' : '', $index );
			$button    = static function ( string $inner, string $class ) use ( $index ): string {
				return sprintf( '<button type="button" class="%1$s" data-mf-step-index="%2$d">%3$s</button>', $class, $index, $inner );
			};

			if ( 'chevron' === $style ) {
				$number = ( $show_numbers && ! $show_icons ) ? '<span class="mfb-st__num">' . ( $index + 1 ) . '</span>' : '';
				$icon   = $show_icons ? '<span class="mfb-st__icon">' . $indicator . '</span>' : '';
				$items .= "<div {$item_attr}>" . $button( '<span class="mfb-stepper-chevron__inner">' . $icon . $number . '<span class="mfb-stepper-chevron__text"><span class="mfb-st__title">' . $label . '</span>' . $status . '</span></span>', 'mfb-stepper-chevron__button' ) . '</div>';
			} elseif ( 'connector' === $style ) {
				$items .= "<div {$item_attr}>" . $button( '<span class="mfb-stepper-connector__dot mfb-st__dot">' . $indicator . '</span>', 'mfb-stepper-connector__button' )
					. '<span class="mfb-stepper-connector__label"><span class="mfb-st__title">' . $label . '</span>' . $status . '</span></div>';
				if ( $index < $count - 1 ) {
					$items .= sprintf( '<div class="mfb-stepper-connector__line%1$s" data-mf-step-line="%2$d" aria-hidden="true"></div>', $index < $current_step ? ' is-done' : '', $index );
				}
			} elseif ( 'timeline' === $style ) {
				$desc      = ( $show_desc && ! empty( $step['description'] ) ) ? '<span class="mfb-stepper-timeline__desc">' . esc_html( (string) $step['description'] ) . '</span>' : '';
				$tl_status = '' !== $status ? '<span class="mfb-stepper-timeline__status">' . self::status_text_variants() . '</span>' : '';
				$items    .= "<div {$item_attr}>" . $button(
					'<span class="mfb-stepper-timeline__dot mfb-st__dot">' . $indicator . '</span><span class="mfb-stepper-timeline__content"><span class="mfb-stepper-timeline__title mfb-st__title">' . $label . '</span>' . $desc . $tl_status . '</span>',
					'mfb-stepper-timeline__button'
				) . '</div>';
			} else { // progress-line.
				$items .= "<div {$item_attr}>" . $button( '<span class="mfb-stepper-progressline__dot mfb-st__dot">' . $indicator . '</span>', 'mfb-stepper-progressline__button' )
					. '<span class="mfb-stepper-progressline__label"><span class="mfb-st__title">' . $label . '</span>' . $status . '</span></div>';
			}
		}

		$list_class = 'mfb-stepper-' . $slug . '__list';
		if ( 'timeline' === $style ) {
			$list_class .= 'vertical' === $stepper_position ? ' mfb-stepper-timeline__list--vertical' : ' mfb-stepper-timeline__list--horizontal';
		}
		$html = '<div class="' . esc_attr( $list_class ) . '">' . $items . '</div>';

		if ( 'progress-line' === $style ) {
			$percent = $count > 0 ? (int) round( ( ( $current_step + 1 ) / $count ) * 100 ) : 0;
			$html   .= sprintf(
				'<div class="mfb-stepper-progressline__progress"><div class="mfb-stepper-progressline__track"><div class="mfb-stepper-progressline__fill" style="width: %1$d%%;"></div></div><div class="mfb-stepper-progressline__percent" data-mf-complete-label="%2$s">%1$d%% %2$s</div></div>',
				$percent,
				esc_attr__( 'Complete', 'metform' )
			);
		}

		return sprintf( '<div class="mfb-st mfb-stepper-%1$s" style="%2$s">%3$s</div>', esc_attr( $slug ), esc_attr( $vars ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * A styled stepper's dot/indicator content: the status icon (custom
	 * step icon, else ✓ / ● / number — one variant per state, CSS shows the
	 * current one) when Step Status Display shows icons, else the number.
	 *
	 * @since 4.4.1
	 */
	private function styled_indicator( array $step, int $index, bool $show_icons, bool $show_numbers ): string {
		if ( $show_icons ) {
			return $this->status_icon_variants( $step['icon'] ?? '', $index );
		}
		return $show_numbers ? (string) ( $index + 1 ) : '';
	}

	/**
	 * The status icon in all three states (custom step icon, else ✓ / ● /
	 * number); CSS shows the one matching the step item's state class.
	 *
	 * @since 4.4.1
	 */
	private function status_icon_variants( $icon, int $index, bool $number_shown = false ): string {
		// With the number badge already showing, the "waiting" glyph would
		// repeat the step number — use an empty circle instead.
		$wait = $this->render_step_status_icon( $icon, false, false, $index );
		if ( $number_shown && (string) ( $index + 1 ) === $wait ) {
			$wait = '○';
		}
		return '<span class="mfb-st__by-state">'
			. '<span class="mfb-st__done">' . $this->render_step_status_icon( $icon, true, false, $index ) . '</span>'
			. '<span class="mfb-st__active">' . $this->render_step_status_icon( $icon, false, true, $index ) . '</span>'
			. '<span class="mfb-st__wait">' . $wait . '</span>'
			. '</span>';
	}

	/** "Finished / In Progress / Waiting" in all three states (see status_icon_variants()). */
	private static function status_text_variants(): string {
		return '<span class="mfb-st__by-state">'
			. '<span class="mfb-st__done">' . esc_html__( 'Finished', 'metform' ) . '</span>'
			. '<span class="mfb-st__active">' . esc_html__( 'In Progress', 'metform' ) . '</span>'
			. '<span class="mfb-st__wait">' . esc_html__( 'Waiting', 'metform' ) . '</span>'
			. '</span>';
	}

	/** Border radius for the Step Number Shape setting. */
	private static function number_radius( array $settings ): string {
		$shape = in_array( $settings['stepNumberShape'] ?? 'circle', self::STEP_NUMBER_SHAPES, true ) ? $settings['stepNumberShape'] : 'circle';
		return array(
			'circle'  => '50%',
			'square'  => '0',
			'rounded' => '6px',
		)[ $shape ];
	}

	/** Matches the old StepperDefault.jsx. */
	private function render_stepper_default( array $steps, int $current_step, array $settings, array $styles, string $stepper_position ): string {
		$show_numbers   = (bool) ( $settings['showStepNumbers'] ?? true );
		$show_desc      = (bool) ( $settings['showStepDescriptions'] ?? true );
		$status_display = in_array( $settings['stepStatusDisplay'] ?? 'none', self::STEP_STATUS_DISPLAYS, true ) ? $settings['stepStatusDisplay'] : 'none';
		$is_vertical    = 'vertical' === $stepper_position;
		$step_gap       = max( 0, (int) $this->get_style( $styles, 'stepGap', 12 ) );

		$step_button_radius = $this->get_dimensions_css( $styles, 'stepButtonBorderRadius' );
		$active_style       = sprintf( 'background-color: %1$s; color: %2$s; border-radius: %3$s;', esc_attr( $this->get_style( $styles, 'activeStepColor', '#2563eb' ) ), esc_attr( $this->get_style( $styles, 'activeStepTextColor', '#ffffff' ) ), $step_button_radius );
		$inactive_style     = sprintf( 'background-color: %1$s; color: %2$s; border-radius: %3$s;', esc_attr( $this->get_style( $styles, 'inactiveStepColor', '#374151' ) ), esc_attr( $this->get_style( $styles, 'inactiveStepTextColor', '#d1d5db' ) ), $step_button_radius );
		$number_size        = max( 1, (int) $this->get_style( $styles, 'stepNumberSize', 20 ) );
		$number_shape       = in_array( $settings['stepNumberShape'] ?? 'circle', self::STEP_NUMBER_SHAPES, true ) ? $settings['stepNumberShape'] : 'circle';

		// shape included.
		$number_border_radius  = array(
			'circle'  => '50%',
			'square'  => '0',
			'rounded' => '6px',
		)[ $number_shape ];
		$number_style_base     = 'width: %1$dpx; height: %1$dpx; display: flex; align-items: center; justify-content: center; border-radius: %5$s; font-size: %2$dpx; font-weight: 600; background-color: %3$s; color: %4$s;';
		$number_font_size      = (int) floor( $number_size * 0.6 );
		$number_style_active   = sprintf(
			$number_style_base,
			$number_size,
			$number_font_size,
			esc_attr( $this->get_style( $styles, 'stepNumberBgColorActive', $this->get_style( $styles, 'activeStepColor', '#2563eb' ) ) ),
			esc_attr( $this->get_style( $styles, 'stepNumberTextColorActive', $this->get_style( $styles, 'activeStepTextColor', '#ffffff' ) ) ),
			$number_border_radius
		);
		$number_style_inactive = sprintf(
			$number_style_base,
			$number_size,
			$number_font_size,
			esc_attr( $this->get_style( $styles, 'stepNumberBgColor', '#374151' ) ),
			esc_attr( $this->get_style( $styles, 'stepNumberTextColor', '#d1d5db' ) ),
			$number_border_radius
		);

		$items = '';
		foreach ( $steps as $index => $step ) {
			$label       = (string) ( $step['label'] ?? '' );
			$description = (string) ( $step['description'] ?? '' );
			$is_active   = $index === $current_step;
			$is_finished = $index < $current_step;
			$number_html = ( $show_numbers && 'icons' !== $status_display )
				? sprintf(
					'<span class="mfb-stepper-default__number" data-mf-active-style="%1$s" data-mf-inactive-style="%2$s" style="%3$s">%4$d</span>',
					esc_attr( $number_style_active ),
					esc_attr( $number_style_inactive ),
					$is_active ? $number_style_active : $number_style_inactive,
					$index + 1
				)
				: '';

			// Status text/icon: one variant per state, the item's state class
			// (toggled by index.js on navigation) shows the current one — so
			// it doesn't stay frozen at the first-render step.
			$status_html = '';
			if ( 'none' !== $status_display ) {
				$icon_style = sprintf( ' style="color: %s;"', esc_attr( $this->get_style( $styles, 'stepIconColor', '#ffffff' ) ) );
				$icon_html  = '';
				$text_html  = '';
				if ( 'text' !== $status_display ) {
					$icon_html = '<span class="' . ( 'icons' === $status_display ? 'mfb-stepper-default__status-icon' : 'mfb-stepper-default__status-icon-mr' ) . '"' . $icon_style . '>' . $this->status_icon_variants( $step['icon'] ?? '', $index, '' !== $number_html ) . '</span>';
				}
				if ( 'icons' !== $status_display ) {
					$text_html = self::status_text_variants();
				}
				$status_html = '<div class="mfb-stepper-default__status">' . $icon_html . $text_html . '</div>';
			}
			$state = $is_finished ? 'is-done' : ( $is_active ? 'is-active' : 'is-wait' );

			// correctly there.
			$items .= sprintf(
				'<div class="mfb-st__item mfb-stepper-default__item %10$s%1$s" data-mf-step-item="%2$d"><button type="button" data-mf-step-index="%2$d" data-mf-active-style="%3$s" data-mf-inactive-style="%4$s" class="mfb-stepper-default__button" style="%5$s">%6$s<div class="mfb-stepper-default__text"><div class="mfb-stepper-default__title">%7$s</div>%8$s%9$s</div></button></div>',
				$is_vertical ? ' mfb-stepper-default__item--vertical' : '',
				$index,
				$active_style,
				$inactive_style,
				$is_active ? $active_style : $inactive_style,
				$number_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $label ),
				( $show_desc && $description ) ? '<div class="mfb-stepper-default__desc">' . esc_html( $description ) . '</div>' : '',
				$status_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$state
			);
		}

		return sprintf(
			'<div class="mfb-stepper-default__list%1$s" style="gap: %2$dpx;">%3$s</div>',
			$is_vertical ? ' mfb-stepper-default__list--vertical' : '',
			$step_gap,
			$items // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	private function render_progress_bar( int $total, int $current_step, array $styles ): string {
		$fill    = $this->get_style( $styles, 'progressBarFillColor', $this->get_style( $styles, 'activeStepColor', '#2563eb' ) );
		$bg      = $this->get_style( $styles, 'progressBarBgColor', '#e5e7eb' );
		$height  = max( 1, (int) $this->get_style( $styles, 'progressBarHeight', 6 ) );
		$radius  = max( 0, (int) $this->get_style( $styles, 'progressBarRadius', 4 ) );
		$percent = $total > 0 ? (int) round( ( ( $current_step + 1 ) / $total ) * 100 ) : 0;

		return sprintf(
			'<div class="mfb-multistep__progress"><div class="mfb-multistep__progress-track" style="background-color: %1$s; border-radius: %2$dpx; height: %3$dpx;"><div class="mfb-multistep__progress-fill" style="width: %4$d%%; background-color: %5$s; height: 100%%;"></div></div><div class="mfb-multistep__progress-label">%4$d%%</div></div>',
			esc_attr( $bg ),
			$radius,
			$height,
			$percent,
			esc_attr( $fill )
		);
	}

	/**
	 * Stepper Position resolved at a breakpoint slot.
	 *
	 * @since 4.4.1
	 */
	private static function stepper_position_at( $setting, string $slot, string $style = 'default' ): string {
		$value = (string) Responsive_Value::resolve( $setting, $slot, 'top' );
		$value = in_array( $value, self::STEPPER_POSITIONS, true ) ? $value : 'top';
		// Only Default and Timeline have a vertical layout; the horizontal
		// styles (Chevron, Connector Dots, Progress Line) stay on top.
		if ( 'vertical' === $value && ! in_array( $style, array( 'default', 'timeline' ), true ) ) {
			$value = 'top';
		}
		return $value;
	}

	/** The saved Step Style, limited to the known ones. */
	private static function step_style( array $settings ): string {
		return in_array( $settings['stepStyle'] ?? 'default', self::STEP_STYLES, true ) ? $settings['stepStyle'] : 'default';
	}

	/** Whether a per-breakpoint Stepper Position shows the stepper on any breakpoint. */
	private static function stepper_shown_anywhere( $setting ): bool {
		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $slot ) {
			if ( 'hidden' !== self::stepper_position_at( $setting, $slot ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds the per-breakpoint Stepper Position rules to the control CSS.
	 *
	 * Only when Stepper Position is responsive: the block root becomes a
	 * flex column so the stepper can move above/below the content with
	 * `order`, switch between the horizontal and vertical list, or hide.
	 * Scoped to the page and the preview — the editor canvas renders its
	 * own markup for the active breakpoint (MultistepCanvas.jsx).
	 *
	 * @since 4.4.1
	 *
	 * @param array<string, mixed> $settings   Block settings.
	 * @param string               $class_name Scoped selector.
	 * @return string CSS.
	 */
	public function build_dynamic_css( array $settings, string $class_name ): string {
		$css     = parent::build_dynamic_css( $settings, $class_name );
		$setting = $settings['stepperPosition'] ?? 'top';
		if ( ! Responsive_Value::is_responsive( $setting ) || ! self::stepper_shown_anywhere( $setting ) ) {
			return $css;
		}

		// The canvas renders the active breakpoint's markup itself.
		if ( ! empty( $settings['__isCanvas'] ) ) {
			return $css;
		}
		// Preview passes its own scope; the saved file guards against the
		// canvas (which also loads it), like `{{LAYOUT_WRAPPER}}` rules.
		$root     = ! empty( $settings['__layoutSelector'] )
			? '.' . $class_name
			: sprintf( 'body:not(.%1$s) .%3$s, body.%2$s .%3$s', Visibility::CANVAS_BODY_CLASS, Visibility::PREVIEW_BODY_CLASS, $class_name );
		$scope    = static function ( string $suffix ) use ( $root ): string {
			return implode( ', ', array_map( static fn( $part ) => trim( $part ) . $suffix, explode( ',', $root ) ) );
		};
		$progress = in_array( $settings['progressPosition'] ?? 'top', self::PROGRESS_POSITIONS, true ) ? $settings['progressPosition'] : 'top';
		$style    = self::step_style( $settings );

		$out = Css_Value_Formatter::force_important( self::stepper_slot_rules( $scope, self::stepper_position_at( $setting, 'desktop', $style ), $progress ) );
		foreach ( Responsive_Value::get_breakpoints() as $slot => $media ) {
			if ( in_array( $slot, Responsive_Value::overridden_slots( $setting ), true ) ) {
				$out .= $media . ' { ' . Css_Value_Formatter::force_important( self::stepper_slot_rules( $scope, self::stepper_position_at( $setting, $slot, $style ), $progress ) ) . ' }';
			}
		}

		return $css . $out;
	}

	/**
	 * The block layout for one Stepper Position value: a two-column grid
	 * (steps left, everything else right) for Left, a column with the
	 * stepper ordered above/below the content for Top/Bottom, or the
	 * stepper hidden. Every rule is set for every value so a breakpoint
	 * can switch from one to another.
	 */
	private static function stepper_slot_rules( callable $scope, string $position, string $progress ): string {
		$root     = $scope( '' );
		$children = $scope( ' > *' );
		$wrap     = $scope( ' > .mfb-multistep__stepper-wrap' );
		$vertical = 'vertical' === $position;

		if ( $vertical ) {
			$css = "{$root} { display: grid; grid-template-columns: minmax(160px, 240px) minmax(0, 1fr); grid-template-rows: auto auto 1fr; column-gap: 1.5rem; row-gap: 0.5rem; align-items: start; }"
				. "{$children} { grid-column: 2; grid-row: auto; order: 0; margin-top: 0; }"
				. "{$wrap} { display: block; grid-column: 1; grid-row: 1 / span 3; }";
		} else {
			$css = "{$root} { display: flex; flex-direction: column; gap: 0.5rem; }"
				. "{$children} { grid-column: auto; grid-row: auto; order: 2; margin-top: 0; }"
				. $scope( ' > .mfb-multistep__progress' ) . ' { order: ' . ( 'bottom' === $progress ? 4 : 0 ) . '; }'
				. $scope( ' > .mfb-multistep__nav' ) . ' { order: 5; }'
				. "{$wrap} { display: " . ( 'hidden' === $position ? 'none' : 'block' ) . '; grid-column: auto; grid-row: auto; order: ' . ( 'bottom' === $position ? 3 : 1 ) . '; }';
		}
		if ( 'hidden' === $position ) {
			return $css;
		}

		return $css
			. $scope( ' .mfb-stepper-default__list' ) . ' { flex-direction: ' . ( $vertical ? 'column' : 'row' ) . '; align-items: ' . ( $vertical ? 'stretch' : 'center' ) . '; }'
			. $scope( ' .mfb-stepper-default__item' ) . ' { width: ' . ( $vertical ? '100%' : 'auto' ) . '; }'
			// Timeline is the only other step style with a vertical layout.
			. $scope( ' .mfb-stepper-timeline__list' ) . ' { flex-direction: ' . ( $vertical ? 'column' : 'row' ) . '; gap: ' . ( $vertical ? '0.75rem' : '1.5rem' ) . '; }'
			. $scope( ' .mfb-stepper-timeline__item' ) . ' { flex: ' . ( $vertical ? 'none' : '1 1 0' ) . '; width: ' . ( $vertical ? '100%' : 'auto' ) . '; min-width: ' . ( $vertical ? '0' : '200px' ) . '; }'
			. $scope( ' .mfb-stepper-timeline__content' ) . ' { text-align: ' . ( $vertical ? 'left' : 'center' ) . '; }';
	}

	public function render( array $settings, Render_Context $context ): string {
		$steps        = is_array( $settings['steps'] ?? null ) ? array_values( $settings['steps'] ) : array();
		$step_count   = count( $steps );
		$current_step = $step_count > 0 ? max( 0, min( $step_count - 1, (int) ( $settings['currentStep'] ?? 0 ) ) ) : 0;

		// Stepper Position can differ per breakpoint. The markup follows the
		// desktop value; with per-breakpoint values the stepper is always
		// rendered (unless hidden everywhere) and build_dynamic_css() moves /
		// re-orients / hides it per breakpoint.
		$position_setting    = $settings['stepperPosition'] ?? 'top';
		$stepper_position    = self::stepper_position_at( $position_setting, 'desktop', self::step_style( $settings ) );
		$responsive_position = Responsive_Value::is_responsive( $position_setting );
		if ( $responsive_position && 'hidden' === $stepper_position && self::stepper_shown_anywhere( $position_setting ) ) {
			$stepper_position = 'top';
		}
		$progress_position = in_array( $settings['progressPosition'] ?? 'top', self::PROGRESS_POSITIONS, true ) ? $settings['progressPosition'] : 'top';
		$show_progress     = (bool) ( $settings['showProgressBar'] ?? false );
		// "Inline" lives inside the stepper: with the stepper hidden it
		// would never show, so it falls back to the top.
		if ( 'inline' === $progress_position && 'hidden' === $stepper_position ) {
			$progress_position = 'top';
		}
		$enable_prev       = (bool) ( $settings['enablePreviousButton'] ?? true );
		$enable_validation = (bool) ( $settings['enableValidation'] ?? true );
		$styles            = is_array( $settings['styles'] ?? null ) ? $settings['styles'] : array();

		// Group children by stepIndex (a cross-cutting prop, see
		// Document_Sanitizer).
		$by_step = array_fill( 0, max( 1, $step_count ), array() );
		foreach ( $context->get_children() as $child ) {
			$index = (int) ( $child['props']['stepIndex'] ?? 0 );
			if ( $index < 0 || $index >= count( $by_step ) ) {
				$index = 0;
			}
			$by_step[ $index ][] = $child;
		}

		$stepper_html = ( 'hidden' !== $stepper_position && $step_count > 0 )
			? '<div class="mfb-multistep__stepper-wrap">' . $this->render_stepper( $steps, $current_step, $settings, $styles, $stepper_position )
				. ( 'inline' === $progress_position && $show_progress ? $this->render_progress_bar( $step_count, $current_step, $styles ) : '' )
				. '</div>'
			: '';

		$progress_html = ( $show_progress && $step_count > 0 ) ? $this->render_progress_bar( $step_count, $current_step, $styles ) : '';

		$content_html = '';
		foreach ( $steps as $i => $step ) {
			$inner = '';
			foreach ( ( $by_step[ $i ] ?? array() ) as $child ) {
				$inner .= $context->render_child( $child['id'] );
			}
			$content_html .= sprintf( '<div class="mfb-multistep__step" data-step="%1$d"%2$s>%3$s</div>', $i, $i === $current_step ? '' : ' hidden', $inner ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		$content_background_default = array(
			'type'  => 'classic',
			'color' => '#ffffff',
		);
		$content_border_default     = array(
			'borderType' => 'solid',
			'width'      => array(
				'top'    => '1px',
				'right'  => '1px',
				'bottom' => '1px',
				'left'   => '1px',
			),
			'color'      => '#374151',
			'radius'     => array(
				'top'    => '0px',
				'right'  => '0px',
				'bottom' => '0px',
				'left'   => '0px',
			),
		);
		$content_padding_default    = array(
			'top'    => '16px',
			'right'  => '16px',
			'bottom' => '16px',
			'left'   => '16px',
		);
		$content_background         = $this->get_style( $styles, 'contentBackground', $content_background_default );
		$content_background         = is_array( $content_background ) ? $content_background : $content_background_default;
		$content_style              = sprintf(
			'background-color: %1$s; color: %2$s; %3$s padding: %4$s;',
			esc_attr( $content_background['color'] ?? $content_background_default['color'] ),
			esc_attr( $this->get_style( $styles, 'contentTextColor', '#000000' ) ),
			$this->get_border_css( $styles, 'contentBorder', $content_border_default ),
			$this->get_dimensions_css( $styles, 'contentPadding', $content_padding_default )
		);

		$is_last_step      = $current_step === $step_count - 1;
		$nav_button_radius = $this->get_dimensions_css( $styles, 'navButtonBorderRadius' );
		$prev_style        = sprintf( 'background-color: %1$s; color: %2$s; border-radius: %3$s;', esc_attr( $this->get_style( $styles, 'prevButtonBgColor', '#374151' ) ), esc_attr( $this->get_style( $styles, 'prevButtonTextColor', '#d1d5db' ) ), $nav_button_radius );
		$next_style        = sprintf( 'background-color: %1$s; color: %2$s; border-radius: %3$s;', esc_attr( $this->get_style( $styles, 'nextButtonBgColor', '#2563eb' ) ), esc_attr( $this->get_style( $styles, 'nextButtonTextColor', '#ffffff' ) ), $nav_button_radius );

		$prev_label = (string) ( $settings['prevButtonLabel'] ?? '' );
		$prev_label = '' !== $prev_label ? $prev_label : __( 'Previous', 'metform' );

		$nav_html = '<div class="mfb-multistep__nav">';
		if ( $enable_prev ) {
			$nav_html .= sprintf(
				'<button type="button" data-direction="prev" class="mfb-multistep__prev-button" style="%1$s" %2$s>%3$s</button>',
				$prev_style,
				0 === $current_step ? 'disabled' : '',
				esc_html( $prev_label )
			);
		}

		$next_label_raw   = (string) ( $settings['nextButtonLabel'] ?? '' );
		$submit_label_raw = (string) ( $settings['submitButtonLabel'] ?? '' );
		$next_label       = esc_html( '' !== $next_label_raw ? $next_label_raw : __( 'Next', 'metform' ) );
		$submit_label     = esc_html( '' !== $submit_label_raw ? $submit_label_raw : __( 'Submit', 'metform' ) );
		$nav_html        .= sprintf(
			'<button type="button" data-direction="next" data-mf-next-label="%1$s" data-mf-submit-label="%2$s" class="mfb-multistep__next-button" style="%3$s">%4$s</button>',
			$next_label,
			$submit_label,
			$next_style,
			$is_last_step ? $submit_label : $next_label
		);
		$nav_html        .= '</div>';

		$style_class = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();

		$top_stepper     = in_array( $stepper_position, array( 'top', 'vertical' ), true ) ? $stepper_html : '';
		$bottom_stepper  = 'bottom' === $stepper_position ? $stepper_html : '';
		$top_progress    = 'top' === $progress_position ? $progress_html : '';
		$bottom_progress = 'bottom' === $progress_position ? $progress_html : '';

		ob_start();
		?>
<div class="<?php echo esc_attr( $style_class ); ?> mfb-multistep<?php echo 'vertical' === $stepper_position ? ' mfb-multistep--stepper-left' : ''; ?>" data-mf-block-id="<?php echo esc_attr( $context->get_block_id() ); ?>" data-mf-current-step="<?php echo (int) $current_step; ?>" data-mf-step-count="<?php echo (int) $step_count; ?>" data-mf-enable-validation="<?php echo $enable_validation ? '1' : '0'; ?>">
		<?php echo $top_progress; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo $top_stepper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<div class="mfb-multistep__content" style="<?php echo esc_attr( $content_style ); ?>">
		<?php echo $content_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
		<?php echo $bottom_stepper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo $bottom_progress; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php echo $nav_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
		<?php
		return (string) ob_get_clean();
	}
}
