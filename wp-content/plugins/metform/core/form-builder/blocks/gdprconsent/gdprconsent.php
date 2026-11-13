<?php
namespace MetForm\Core\FormBuilder\Blocks\Gdprconsent;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Link_Placeholders;
use MetForm\Core\FormBuilder\Shared\Option_Controls;

defined( 'ABSPATH' ) || exit;

/**
 * GDPR Consent checkbox block for Form Builder.
 * Uses Field_Renderer::wrap() via Block_Base's Template Method Pattern,
 * passing label_tag => 'span' via get_wrap_options().
 */
final class Gdprconsent extends Block_Base {
	public function get_name(): string {
		return 'gdprconsent'; }
	public function get_title(): string {
		return __( 'GDPR Consent', 'metform' ); }
	public function get_icon(): string {
		return 'ShieldCheck'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'file-verify'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'                 => __( 'GDPR Consent', 'metform' ),
			'labelPosition'         => 'top',
			'layout'                => 'horizontal',
			'textPosition'          => 'after',
			'consentText'           => __( 'I agree to the {privacy_policy} and {terms}.', 'metform' ),
			'privacyPolicyLinkText' => __( 'privacy policy', 'metform' ),
			'privacyPolicyUrl'      => '',
			'termsLinkText'         => __( 'terms of service', 'metform' ),
			'termsUrl'              => '',
			'openInNewTab'          => true,
			'name'                  => '',
			'helpText'              => '',
			'required'              => true,
			'showLabel'             => true,
			'styles'                => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'label'                 => array( 'type' => 'string' ),
			'labelPosition'         => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'layout'                => array(
				'type'       => 'string',
				'enum'       => array( 'vertical', 'horizontal' ),
				'responsive' => true,
			),
			'textPosition'          => array(
				'type' => 'string',
				'enum' => array( 'before', 'after' ),
			),
			'consentText'           => array( 'type' => 'string' ),
			'privacyPolicyLinkText' => array( 'type' => 'string' ),
			'privacyPolicyUrl'      => array( 'type' => 'url' ),
			'termsLinkText'         => array( 'type' => 'string' ),
			'termsUrl'              => array( 'type' => 'url' ),
			'openInNewTab'          => array( 'type' => 'boolean' ),
			'name'                  => array( 'type' => 'field_name' ),
			'helpText'              => array( 'type' => 'string' ),
			'showLabel'             => array( 'type' => 'boolean' ),
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
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'GDPR Consent', 'metform' ),
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'layout',
					'label'      => __( 'Option Display', 'metform' ),
					'default'    => 'horizontal',
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
					'label'   => __( 'Text Position', 'metform' ),
					'default' => 'after',
					'options' => array(
						array(
							'label' => __( 'Before checkbox', 'metform' ),
							'value' => 'before',
						),
						array(
							'label' => __( 'After checkbox', 'metform' ),
							'value' => 'after',
						),
					),
				),
				array(
					'type'    => 'textarea',
					'key'     => 'consentText',
					'label'   => __( 'Checkbox Option Text', 'metform' ),
					'default' => __( 'I agree to the {privacy_policy} and {terms}.', 'metform' ),
					'help'    => __( 'Use {privacy_policy} and {terms} as placeholders for clickable links.', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'privacyPolicyLinkText',
					'label'   => __( 'Privacy Policy Link Text', 'metform' ),
					'default' => __( 'privacy policy', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'privacyPolicyUrl',
					'label' => __( 'Privacy Policy URL', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'termsLinkText',
					'label'   => __( 'Terms Link Text', 'metform' ),
					'default' => __( 'terms of service', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'termsUrl',
					'label' => __( 'Terms of Service URL', 'metform' ),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'openInNewTab',
					'label'   => __( 'Open Links in New Tab', 'metform' ),
					'default' => true,
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
				),
			),
			'style'   => Option_Controls::style(
				array(
					array(
						'type'  => 'section',
						'label' => __( 'Consent Links', 'metform' ),
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.consentLinkColor',
						'label'    => __( 'Link Color', 'metform' ),
						'default'  => '#2563eb',
						'selector' => '{{WRAPPER}} .mfb-gdpr__consent-option a { color: {{VALUE}}; }',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.consentLinkHoverColor',
						'label'    => __( 'Link Hover Color', 'metform' ),
						'default'  => '#1d4ed8',
						'selector' => '{{WRAPPER}} .mfb-gdpr__consent-option a:hover { color: {{VALUE}}; }',
					),
				)
			),
		);
	}

	/** Matches gdprconsent/index.jsx's renderConsentText(): splits on {privacy_policy}/{terms} placeholders, each becoming a real <a> when its URL is set, or a plain non-link span otherwise. */
	private function render_consent_text( array $settings ): string {
		return Link_Placeholders::render(
			(string) ( $settings['consentText'] ?? '' ),
			array(
				'{privacy_policy}' => array(
					'url'  => $settings['privacyPolicyUrl'] ?? '',
					'text' => $settings['privacyPolicyLinkText'] ?? __( 'privacy policy', 'metform' ),
				),
				'{terms}'          => array(
					'url'  => $settings['termsUrl'] ?? '',
					'text' => $settings['termsLinkText'] ?? __( 'terms of service', 'metform' ),
				),
			),
			! empty( $settings['openInNewTab'] ),
			'mfb-gdpr__link-placeholder'
		);
	}

	/**
	 * Per-breakpoint values of layout (applied through a markup class
	 * on desktop). See Block_Base::get_responsive_markup_rules().
	 *
	 * @since 4.4.1
	 */
	protected function get_responsive_markup_rules(): array {
		return array_merge(
			parent::get_responsive_markup_rules(),
			array(
				'layout' => array(
					'vertical'   => '{{WRAPPER}} .mfb-gdpr__consent-option { flex-direction: column; align-items: flex-start; }',
					'horizontal' => '{{WRAPPER}} .mfb-gdpr__consent-option { flex-direction: row; align-items: flex-start; }',
				),
			)
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id             = $context->get_element_id();
		$input_id       = 'gdpr-' . $id;
		$name           = (string) ( $settings['name'] ?? '' );
		$checked_input  = sprintf(
			'<span class="mfb-option__box-wrap"><input type="checkbox" id="%1$s" name="%2$s" value="1" class="mfb-option__input-native" required><span class="mfb-option__input mfb-field-option__input-checkbox" aria-hidden="true"><svg class="mfb-option__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></span>',
			esc_attr( $input_id ),
			esc_attr( $name )
		);
		$label_html     = sprintf( '<label for="%1$s" class="mfb-option__label">%2$s</label>', esc_attr( $input_id ), $this->render_consent_text( $settings ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$text_position  = ( $settings['textPosition'] ?? 'after' ) === 'before' ? 'before' : 'after';
		$inner          = 'before' === $text_position ? ( $label_html . $checked_input ) : ( $checked_input . $label_html );
		$option_classes = 'mfb-option mfb-gdpr__consent-option ' . ( 'vertical' === Responsive_Value::resolve( $settings['layout'] ?? 'horizontal', 'desktop', 'horizontal' ) ? 'mfb-options--vertical' : 'mfb-options--horizontal' );

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( $option_classes ),
			$inner
		);
	}

	protected function get_wrap_options( array $settings, Render_Context $context ): array {
		$opts = parent::get_wrap_options( $settings, $context );

		$opts['label_tag'] = 'span';

		return $opts;
	}
}
