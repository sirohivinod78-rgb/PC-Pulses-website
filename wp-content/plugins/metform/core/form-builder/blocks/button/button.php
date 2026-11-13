<?php

namespace MetForm\Core\FormBuilder\Blocks\Button;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Icons\Block_Icon;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

final class Button extends Block_Base {
	public function get_name(): string {
		return 'button';
	}
	public function get_title(): string {
		return __( 'Button', 'metform' );
	}
	public function get_icon(): string {
		return 'Square';
	}
	public function get_category(): string {
		return 'Basic';
	}

	public function get_group(): string {
		return 'content';
	}

	public function get_default_settings(): array {
		return array(
			'text'           => __( 'Submit', 'metform' ),
			'submittingText' => __( 'Submitting...', 'metform' ),
			'buttonType'     => 'submit',
			'alignment'      => 'left',
			'icon'           => '',
			'iconPosition'   => 'before',
			'disabled'       => false,
			'styles'         => array(),
			'__className'    => '',
		);
	}

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => false,
		);
	}

	public function get_property_schema(): array {
		return array(
			'text'           => array( 'type' => 'string' ),
			'submittingText' => array( 'type' => 'string' ),
			'buttonType'     => array(
				'type' => 'string',
				'enum' => array( 'button', 'submit', 'reset' ),
			),
			'alignment'      => array(
				'type'       => 'string',
				'enum'       => array( 'left', 'center', 'right', 'full' ),
				'responsive' => true,
			),
			'icon'           => array( 'type' => 'icon_value' ),
			'iconPosition'   => array(
				'type' => 'string',
				'enum' => array( 'before', 'after' ),
			),
			'disabled'       => array( 'type' => 'boolean' ),
			'styles'         => array( 'type' => 'object' ),
			'__className'    => array( 'type' => 'css_class' ),
		);
	}

	private function border_default( string $color ): array {
		return array(
			'borderType' => 'solid',
			'width'      => array(
				'top'    => '1px',
				'right'  => '1px',
				'bottom' => '1px',
				'left'   => '1px',
			),
			'color'      => $color,
			'radius'     => array(
				'top'    => '4px',
				'right'  => '4px',
				'bottom' => '4px',
				'left'   => '4px',
			),
		);
	}

	private function border_selector( string $state = '' ): string {
		return '{{WRAPPER}} .mfb-button' . $state . ' { {{VALUE}} }';
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
					'type'    => 'text',
					'key'     => 'text',
					'label'   => __( 'Button Text', 'metform' ),
					'default' => __( 'Submit', 'metform' ),
				),
				array(
					'type'      => 'text',
					'key'       => 'submittingText',
					'label'     => __( 'Submitting Text', 'metform' ),
					'default'   => __( 'Submitting...', 'metform' ),
					'condition' => array( 'buttonType' => 'submit' ),
				),
				array(
					'type'    => 'select',
					'key'     => 'buttonType',
					'label'   => __( 'Button Type', 'metform' ),
					'default' => 'submit',
					'options' => array(
						array(
							'label' => __( 'Submit', 'metform' ),
							'value' => 'submit',
						),
						array(
							'label' => __( 'Button', 'metform' ),
							'value' => 'button',
						),
						array(
							'label' => __( 'Reset', 'metform' ),
							'value' => 'reset',
						),
					),
				),
				array(
					'type'       => 'alignment',
					'key'        => 'alignment',
					'label'      => __( 'Alignment', 'metform' ),
					'default'    => 'left',
					'inline'     => true,
					'options'    => array(
						array(
							'label' => __( 'Left', 'metform' ),
							'value' => 'left',
						),
						array(
							'label' => __( 'Center', 'metform' ),
							'value' => 'center',
						),
						array(
							'label' => __( 'Right', 'metform' ),
							'value' => 'right',
						),
						array(
							'label' => __( 'Full Width', 'metform' ),
							'value' => 'full',
						),
					),
					'responsive' => true,
				),
				array(
					'type'  => 'icon',
					'key'   => 'icon',
					'label' => __( 'Icon', 'metform' ),
					'help'  => __( 'Optional icon shown next to the button text.', 'metform' ),
				),
				array(
					'type'      => 'select',
					'key'       => 'iconPosition',
					'label'     => __( 'Icon Position', 'metform' ),
					'default'   => 'before',
					'options'   => array(
						array(
							'label' => __( 'Before', 'metform' ),
							'value' => 'before',
						),
						array(
							'label' => __( 'After', 'metform' ),
							'value' => 'after',
						),
					),
					'condition' => array( 'icon!' => '' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'disabled',
					'label'   => __( 'Disabled', 'metform' ),
					'default' => false,
				),
			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Colors', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type' => 'tabs',
					'tab'  => array( 'normal', 'hover', 'focus' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.background',
					'label'    => __( 'Background', 'metform' ),
					'tab'      => 'normal',
					'default'  => array(
						'type'  => 'classic',
						'color' => '#3b82f6',
					),
					'selector' => '{{WRAPPER}} .mfb-button { {{VALUE}} }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.color',
					'label'    => __( 'Text Color', 'metform' ),
					'tab'      => 'normal',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-button { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.border',
					'label'          => __( 'Border', 'metform' ),
					'tab'            => 'normal',
					'default'        => $this->border_default( '#3b82f6' ),
					'selector'       => $this->border_selector(),
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.hoverBackground',
					'label'    => __( 'Background', 'metform' ),
					'tab'      => 'hover',
					'default'  => array(
						'type'  => 'classic',
						'color' => '#2563eb',
					),
					'selector' => '{{WRAPPER}} .mfb-button:hover { {{VALUE}} }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.hoverColor',
					'label'    => __( 'Text Color', 'metform' ),
					'tab'      => 'hover',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-button:hover { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.hoverBorder',
					'label'          => __( 'Border', 'metform' ),
					'tab'            => 'hover',
					'default'        => $this->border_default( '#2563eb' ),
					'selector'       => $this->border_selector( ':hover' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.focusBackground',
					'label'    => __( 'Background', 'metform' ),
					'tab'      => 'focus',
					'default'  => array(
						'type'  => 'classic',
						'color' => '#3b82f6',
					),
					'selector' => '{{WRAPPER}} .mfb-button:focus { {{VALUE}} }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.focusColor',
					'label'    => __( 'Text Color', 'metform' ),
					'tab'      => 'focus',
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-button:focus { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'border',
					'key'            => 'styles.focusBorder',
					'label'          => __( 'Border', 'metform' ),
					'tab'            => 'focus',
					'default'        => $this->border_default( '#3b82f6' ),
					'selector'       => $this->border_selector( ':focus' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'width', 'radius' ),
				),
				array(
					'type'  => 'section',
					'label' => __( 'Appearance', 'metform' ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.typography',
					'label'          => __( 'Typography', 'metform' ),
					'selector'       => '{{WRAPPER}} .mfb-button { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }',
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.iconColor',
					'label'    => __( 'Icon Color', 'metform' ),
					'default'  => '#ffffff',
					'selector' => '{{WRAPPER}} .mfb-button__icon { color: {{VALUE}}; }',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.iconSize',
					'label'      => __( 'Icon Size', 'metform' ),
					'default'    => '16px',
					'units'      => array( 'px' ),
					'min'        => 12,
					'max'        => 64,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-button__icon { width: {{VALUE}}; height: {{VALUE}}; font-size: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.iconGap',
					'label'      => __( 'Icon Gap', 'metform' ),
					'default'    => '8px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 40,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-button__content { gap: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.padding',
					'label'      => __( 'Padding', 'metform' ),
					'default'    => array(
						'top'    => '10px',
						'right'  => '20px',
						'bottom' => '10px',
						'left'   => '20px',
					),
					'selector'   => '{{WRAPPER}} .mfb-button { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
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
					'selector'   => '{{WRAPPER}} .mfb-button { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'     => 'boxShadow',
					'key'      => 'styles.boxShadow',
					'label'    => __( 'Box Shadow', 'metform' ),
					'selector' => '{{WRAPPER}} .mfb-button { box-shadow: {{VALUE}}; }',
				),
			),
		);
	}

	/**
	 * Per-breakpoint values of alignment (applied through a markup class
	 * on desktop). See Block_Base::get_responsive_markup_rules().
	 *
	 * @since 4.4.1
	 */
	protected function get_responsive_markup_rules(): array {
		return array_merge(
			parent::get_responsive_markup_rules(),
			array(
				'alignment' => array(
					'left'   => '{{WRAPPER}} { display: flex; justify-content: flex-start; } {{WRAPPER}} .mfb-button { width: auto; }',
					'center' => '{{WRAPPER}} { display: flex; justify-content: center; } {{WRAPPER}} .mfb-button { width: auto; }',
					'right'  => '{{WRAPPER}} { display: flex; justify-content: flex-end; } {{WRAPPER}} .mfb-button { width: auto; }',
					'full'   => '{{WRAPPER}} { display: block; } {{WRAPPER}} .mfb-button { width: 100%; }',
				),
			)
		);
	}

	public function render( array $settings, Render_Context $context ): string {
		$type = in_array( $settings['buttonType'], array( 'button', 'submit', 'reset' ), true ) ? $settings['buttonType'] : 'button';
		// Desktop value; tablet/mobile come from build_responsive_markup_css().
		$alignment     = (string) Responsive_Value::resolve( $settings['alignment'] ?? 'left', 'desktop', 'left' );
		$alignment     = in_array( $alignment, array( 'left', 'center', 'right', 'full' ), true ) ? $alignment : 'left';
		$icon_position = in_array( $settings['iconPosition'], array( 'before', 'after' ), true ) ? $settings['iconPosition'] : 'before';
		$icon_html     = Block_Icon::icon(
			$settings['icon'] ?? '',
			'mfb-button__icon',
			array( 'aria-hidden' => 'true' ),
			'span'
		);
		// The block class sits on the outer wrapper, the block root that the
		// Advanced tab styles; Style rules target the <button> inside it
		// (`{{WRAPPER}} .mfb-button`). It must always be present, even with
		// no persisted __className, or no generated rule would match.
		$style_class     = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		$submitting_text = (string) ( $settings['submittingText'] ?? __( 'Submitting...', 'metform' ) );
		$submitting_attr = 'submit' === $type ? sprintf( ' data-submitting-text="%s"', esc_attr( $submitting_text ) ) : '';
		ob_start();
		?>
		<div class="mfb-button__wrapper mfb-align--<?php echo esc_attr( $alignment ); ?> <?php echo esc_attr( $style_class ); ?>" data-mf-block-id="<?php echo esc_attr( $context->get_block_id() ); ?>">
			<button type="<?php echo esc_attr( $type ); ?>" class="mfb-button"<?php echo $submitting_attr; ?><?php echo ! empty( $settings['disabled'] ) ? ' disabled' : ''; ?>>
				<span class="mfb-button__content">
					<?php echo 'before' === $icon_position ? $icon_html : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="mfb-button__text"><?php echo esc_html( $settings['text'] ); ?></span>
					<?php echo 'after' === $icon_position ? $icon_html : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
