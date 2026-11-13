<?php
namespace MetForm\Core\FormBuilder\Blocks\SwitchField;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

final class SwitchField extends Block_Base {
	public function get_name(): string {
		return 'switch'; }
	public function get_title(): string {
		return __( 'Switch', 'metform' ); }
	public function get_icon(): string {
		return 'ToggleLeft'; }
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
		return array(
			'label'         => __( 'Switch', 'metform' ),
			'labelPosition' => 'top',
			'name'          => '',
			'helpText'      => '',
			'activeText'    => __( 'Yes', 'metform' ),
			'inactiveText'  => __( 'No', 'metform' ),
			'required'      => false,
			'showLabel'     => true,
			'styles'        => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'            => array( 'type' => 'field_name' ),
			'helpText'        => array( 'type' => 'string' ),
			'activeText'      => array( 'type' => 'string' ),
			'inactiveText'    => array( 'type' => 'string' ),
			'required'        => array( 'type' => 'boolean' ),
			'showLabel'       => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
		);
	}

	private function typography_selector( string $el ): string {
		return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
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
					'default'   => __( 'Switch', 'metform' ),
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
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'activeText',
					'label'   => __( 'Active Text', 'metform' ),
					'default' => __( 'Yes', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'inactiveText',
					'label'   => __( 'Inactive Text', 'metform' ),
					'default' => __( 'No', 'metform' ),
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
					'help'      => __( 'Shown when the field is left off.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Label & Spacing', 'metform' ),
					'defaultOpen' => true,
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
					'selector'       => $this->typography_selector( '.mfb-label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array( 'showLabel!' => false ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.helpTextTypography',
					'label'          => __( 'Help Text Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-field__help' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.errorTypography',
					'label'          => __( 'Error Typography', 'metform' ),
					'selector'       => $this->typography_selector( '.mfb-field__error' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				array(
					'type'  => 'section',
					'label' => __( 'Track & Thumb', 'metform' ),
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.trackWidth',
					'label'      => __( 'Track Width', 'metform' ),
					'default'    => '58px',
					'units'      => array( 'px' ),
					'min'        => 30,
					'max'        => 120,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-switch__toggle { --mfb-switch-track-w: {{VALUE}}; } {{WRAPPER}} .mfb-switch__track { width: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.trackHeight',
					'label'      => __( 'Track Height', 'metform' ),
					'default'    => '28px',
					'units'      => array( 'px' ),
					'min'        => 12,
					'max'        => 60,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-switch__toggle { --mfb-switch-track-h: {{VALUE}}; } {{WRAPPER}} .mfb-switch__track { height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.trackRadius',
					'label'      => __( 'Track Border Radius', 'metform' ),
					'default'    => '14px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 30,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-switch__track { border-radius: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.thumbSize',
					'label'      => __( 'Thumb Size', 'metform' ),
					'default'    => '22px',
					'units'      => array( 'px' ),
					'min'        => 10,
					'max'        => 50,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-switch__toggle { --mfb-switch-thumb-size: {{VALUE}}; } {{WRAPPER}} .mfb-switch__thumb { width: {{VALUE}}; height: {{VALUE}}; }',
					'responsive' => true,
				),

				array(
					'type' => 'tabs',
					'tab'  => array( 'inactive', 'active' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.inactiveTrackColor',
					'label'    => __( 'Track Color', 'metform' ),
					'tab'      => 'inactive',
					'default'  => '#d1d5db',
					'selector' => '{{WRAPPER}} .mfb-switch__track { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.inactiveThumbColor',
					'label'    => __( 'Thumb Color', 'metform' ),
					'tab'      => 'inactive',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-switch__thumb { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.inactiveTextColor',
					'label'    => __( 'Text Color', 'metform' ),
					'tab'      => 'inactive',
					'default'  => '#6b7280',
					'selector' => '{{WRAPPER}} .mfb-switch__track::before { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.inactiveTextTypography',
					'label'          => __( 'Typography', 'metform' ),
					'tab'            => 'inactive',
					'selector'       => $this->typography_selector( '.mfb-switch__track::before' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.activeTrackColor',
					'label'    => __( 'Track Color', 'metform' ),
					'tab'      => 'active',
					'default'  => '#2563eb',
					'selector' => '{{WRAPPER}} .mfb-switch__input-native:checked + .mfb-switch__track { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.activeThumbColor',
					'label'    => __( 'Thumb Color', 'metform' ),
					'tab'      => 'active',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-switch__input-native:checked + .mfb-switch__track .mfb-switch__thumb { background-color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.activeTextColor',
					'label'    => __( 'Text Color', 'metform' ),
					'tab'      => 'active',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-switch__input-native:checked + .mfb-switch__track::before { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.activeTextTypography',
					'label'          => __( 'Typography', 'metform' ),
					'tab'            => 'active',
					'selector'       => $this->typography_selector( '.mfb-switch__input-native:checked + .mfb-switch__track::before' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
			),
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id = $context->get_element_id();

		ob_start();
		?>
		<div class="mfb-switch__row">
			<span class="mfb-switch__toggle">
				<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $settings['name'] ); ?>" value="1" class="mfb-switch__input-native" <?php echo ! empty( $settings['required'] ) ? 'required' : ''; ?>>
				<span class="mfb-switch__track" data-active="<?php echo esc_attr( $settings['activeText'] ); ?>" data-inactive="<?php echo esc_attr( $settings['inactiveText'] ); ?>" aria-hidden="true"><span class="mfb-switch__thumb"></span></span>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
