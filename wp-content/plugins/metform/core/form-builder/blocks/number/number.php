<?php
namespace MetForm\Core\FormBuilder\Blocks\Number;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Number extends Block_Base {
	public function get_name(): string {
		return 'number'; }
	public function get_title(): string {
		return __( 'Number', 'metform' ); }
	public function get_icon(): string {
		return 'Hash'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'input'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array_merge(
			Input_Controls::default_props( __( 'Number', 'metform' ), __( 'Enter a number', 'metform' ) ),
			array(
				'min'               => '',
				'max'               => '',
				'step'              => '',
				'prefix'            => '',
				'suffix'            => '',
				'showStepper'       => false,
				'decimalPlaces'     => '',
				'hideNativeSpinner' => false,
			)
		);
	}

	public function get_property_schema(): array {
		return array_merge(
			Input_Controls::schema(),
			array(
				'min'               => array( 'type' => 'number' ),
				'max'               => array( 'type' => 'number' ),
				'step'              => array( 'type' => 'number' ),
				'prefix'            => array( 'type' => 'string' ),
				'suffix'            => array( 'type' => 'string' ),
				'showStepper'       => array( 'type' => 'boolean' ),
				'decimalPlaces'     => array( 'type' => 'number' ),
				'hideNativeSpinner' => array( 'type' => 'boolean' ),
			)
		);
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content(
				__( 'Number', 'metform' ),
				array(
					// Divider: everything above this point (including the
					// "Validation" dropdown/Min/Max above) is the shared
					// text-input validation block — its minlength/maxlength/
					// pattern output don't apply to type="number" (see the
					// note in render() below). The min/max/step controls
					// below are Number's own, and are what actually validate
					// the value.
					array(
						'type'        => 'section',
						'label'       => __( 'Number Range', 'metform' ),
						'defaultOpen' => true,
					),
					array(
						'type'  => 'number',
						'key'   => 'min',
						'label' => __( 'Min Value', 'metform' ),
						'help'  => __( 'Lowest accepted value. Leave blank for no lower limit.', 'metform' ),
					),
					array(
						'type'  => 'number',
						'key'   => 'max',
						'label' => __( 'Max Value', 'metform' ),
						'help'  => __( 'Highest accepted value. Leave blank for no upper limit.', 'metform' ),
					),
					array(
						'type'  => 'number',
						'key'   => 'step',
						'label' => __( 'Step', 'metform' ),
						'help'  => __( 'Amount each stepper click or arrow key changes the value.', 'metform' ),
					),
					array(
						'type'        => 'text',
						'key'         => 'prefix',
						'label'       => __( 'Prefix', 'metform' ),
						'placeholder' => '$, €, #',
						'help'        => __( 'Static text shown to the left of the input (e.g. currency symbol).', 'metform' ),
					),
					array(
						'type'        => 'text',
						'key'         => 'suffix',
						'label'       => __( 'Suffix', 'metform' ),
						'placeholder' => 'kg, %, pts',
						'help'        => __( 'Static text shown to the right of the input.', 'metform' ),
					),
					array(
						'type'    => 'toggle',
						'key'     => 'showStepper',
						'label'   => __( 'Show Stepper Buttons', 'metform' ),
						'default' => false,
						'help'    => __( 'Adds − and + buttons on either side of the field. Long-press to accelerate.', 'metform' ),
					),
					array(
						'type'        => 'number',
						'key'         => 'decimalPlaces',
						'label'       => __( 'Decimal Places', 'metform' ),
						'placeholder' => __( 'Auto', 'metform' ),
						'help'        => __( 'Rounds and formats to this many decimal places on blur. Leave blank for no rounding.', 'metform' ),
					),
					array(
						'type'      => 'toggle',
						'key'       => 'hideNativeSpinner',
						'label'     => __( 'Hide Browser Spinner', 'metform' ),
						'default'   => false,
						'help'      => __( 'Removes the native up/down arrows. Enabled automatically when stepper buttons are on.', 'metform' ),
						'condition' => array( 'showStepper' => false ),
					),
				)
			),
			'style'   => Input_Controls::style(
				'input',
				array(
					array(
						'type'  => 'section',
						'label' => __( 'Prefix / Suffix', 'metform' ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.prefixColor',
						'label'    => __( 'Prefix Color', 'metform' ),
						'default'  => '#6b7280',
						'selector' => '{{WRAPPER}} .mfb-number__prefix, {{WRAPPER}} .mfb-number__suffix { color: {{VALUE}}; }',
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.prefixBg',
						'label'    => __( 'Prefix / Suffix Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#f3f4f6',
						),
						'selector' => '{{WRAPPER}} .mfb-number__prefix, {{WRAPPER}} .mfb-number__suffix { {{VALUE}} }',
					),
					array(
						'type'           => 'typography',
						'key'            => 'styles.prefixTypography',
						'label'          => __( 'Prefix / Suffix Typography', 'metform' ),
						'selector'       => '{{WRAPPER}} .mfb-number__prefix, {{WRAPPER}} .mfb-number__suffix { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }',
						'responsive'     => true,
						'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					),

					array(
						'type'      => 'section',
						'label'     => __( 'Stepper Buttons', 'metform' ),
						'condition' => array( 'showStepper' => true ),
					),
					array(
						'type'     => 'background',
						'key'      => 'styles.stepperBg',
						'label'    => __( 'Button Background', 'metform' ),
						'default'  => array(
							'type'  => 'classic',
							'color' => '#f9fafb',
						),
						'selector' => '{{WRAPPER}} .mfb-number__stepper-btn { {{VALUE}} }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.stepperColor',
						'label'    => __( 'Button Color', 'metform' ),
						'default'  => '#374151',
						'selector' => '{{WRAPPER}} .mfb-number__stepper-btn { color: {{VALUE}}; }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.stepperBorderColor',
						'label'    => __( 'Button Border Color', 'metform' ),
						'default'  => '#d1d5db',
						'selector' => '{{WRAPPER}} .mfb-number__stepper-btn { border-color: {{VALUE}}; }',
					),
					array(
						'type'       => 'range',
						'key'        => 'styles.stepperWidth',
						'label'      => __( 'Button Width', 'metform' ),
						'default'    => '2.25rem',
						'units'      => array( 'px', 'rem' ),
						'selector'   => '{{WRAPPER}} .mfb-number__stepper-btn { width: {{VALUE}}; }',
						'responsive' => true,
					),
				)
			),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id           = $context->get_element_id();
		$prefix       = (string) ( $settings['prefix'] ?? '' );
		$suffix       = (string) ( $settings['suffix'] ?? '' );
		$show_stepper = ! empty( $settings['showStepper'] );
		$hide_spinner = $show_stepper || ! empty( $settings['hideNativeSpinner'] );
		// Wired for consistency with every other block exposing this shared
		// control (telephone.php/email.php/textarea.php), but note: unlike
		// those, `minlength`/`maxlength`/`pattern` are not valid HTML5
		// attributes on `<input type="number">` at all — browsers silently
		// ignore all three here regardless. `min`/`max`/`step` (already
		// rendered below) remain the only validation that actually applies
		// to a number input; a real fix for `character`/`pattern`-type
		// validation would need switching to `type="text" inputmode=
		// "decimal"`, out of scope for this one-line wiring pass.
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<div class="mfb-number__input-wrap<?php echo $show_stepper ? ' mfb-number__input-wrap--has-stepper' : ''; ?>">
			<?php
			if ( $show_stepper ) :
				?>
				<button type="button" class="mfb-number__stepper-btn mfb-number__decrement" data-mf-number-step="-1" aria-label="<?php esc_attr_e( 'Decrease', 'metform' ); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path></svg></button><?php endif; ?>
			<?php
			if ( '' !== $prefix ) :
				?>
				<span class="mfb-number__prefix"><?php echo esc_html( $prefix ); ?></span><?php endif; ?>
			<input id="<?php echo esc_attr( $id ); ?>" type="number" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input<?php echo $hide_spinner ? ' mfb-number__input--no-spinner' : ''; ?>" value="<?php echo esc_attr( (string) ( $settings['defaultValue'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo '' !== (string) ( $settings['decimalPlaces'] ?? '' ) ? 'data-mf-decimal-places="' . esc_attr( $settings['decimalPlaces'] ) . '"' : ''; ?> <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			if ( '' !== $suffix ) :
				?>
				<span class="mfb-number__suffix"><?php echo esc_html( $suffix ); ?></span><?php endif; ?>
			<?php
			if ( $show_stepper ) :
				?>
				<button type="button" class="mfb-number__stepper-btn mfb-number__increment" data-mf-number-step="1" aria-label="<?php esc_attr_e( 'Increase', 'metform' ); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"></path><path d="M12 5v14"></path></svg></button><?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
