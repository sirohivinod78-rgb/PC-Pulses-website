<?php

namespace MetForm\Core\FormBuilder\Blocks\Optin;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Link_Placeholders;
use MetForm\Core\FormBuilder\Shared\Option_Controls;

defined( 'ABSPATH' ) || exit;

/**
 * Opt-in checkbox block for Form Builder.
 * Uses Field_Renderer::wrap() via Block_Base's Template Method Pattern,
 * providing the custom badge via get_wrap_options(); the privacy note is
 * rendered in render_markup() (under the option text, or below the option
 * row when the text comes before the checkbox).
 */
final class Optin extends Block_Base {

	public function get_name(): string {
		return 'optin';
	}
	public function get_title(): string {
		return __( 'Opt-in', 'metform' );
	}
	public function get_icon(): string {
		return 'Mail';
	}
	public function get_category(): string {
		return 'Basic';
	}

	public function get_group(): string {
		return 'file-verify';
	}

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'                 => __( 'Newsletter Opt-in', 'metform' ),
			'labelPosition'         => 'top',
			'layout'                => 'horizontal',
			'textPosition'          => 'after',
			'optinText'             => __( 'Yes, I want to receive newsletters and updates.', 'metform' ),
			'privacyNote'           => '',
			'privacyPolicyLinkText' => __( 'privacy policy', 'metform' ),
			'privacyPolicyUrl'      => '',
			'openInNewTab'          => true,
			'helpText'              => '',
			'required'              => false,
			'requiredMessage'       => '',
			'showBadge'             => false,
			'badgeText'             => __( 'Free', 'metform' ),
			'name'                  => '',
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
			'optinText'             => array( 'type' => 'string' ),
			'privacyNote'           => array( 'type' => 'string' ),
			'privacyPolicyLinkText' => array( 'type' => 'string' ),
			'privacyPolicyUrl'      => array( 'type' => 'url' ),
			'openInNewTab'          => array( 'type' => 'boolean' ),
			'helpText'              => array( 'type' => 'string' ),
			'required'              => array( 'type' => 'boolean' ),
			'requiredMessage'       => array( 'type' => 'string' ),
			'showBadge'             => array( 'type' => 'boolean' ),
			'badgeText'             => array( 'type' => 'string' ),
			'name'                  => array( 'type' => 'field_name' ),
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
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'Newsletter Opt-in', 'metform' ),
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
					'type'    => 'toggle',
					'key'     => 'showBadge',
					'label'   => __( 'Show Badge', 'metform' ),
					'default' => false,
				),
				array(
					'type'      => 'text',
					'key'       => 'badgeText',
					'label'     => __( 'Badge Text', 'metform' ),
					'default'   => __( 'Free', 'metform' ),
					'condition' => array( 'showBadge' => true ),
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
					'key'     => 'optinText',
					'label'   => __( 'Checkbox Option Text', 'metform' ),
					'default' => __( 'Yes, I want to receive newsletters and updates.', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'privacyNote',
					'label'   => __( 'Privacy Note', 'metform' ),
					'default' => '',
					'help'    => __( 'Shown to visitors below the checkbox, e.g. how you\'ll use their email. The privacy policy link is added at the end automatically, or where you place {privacy_policy}.', 'metform' ),
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
					'type'    => 'toggle',
					'key'     => 'openInNewTab',
					'label'   => __( 'Open Links in New Tab', 'metform' ),
					'default' => true,
				),
				// No Help Text control: the Privacy Note above is this block's
				// text below the checkbox — two separate small notes there only
				// confused. A Help Text already saved on an existing form still
				// renders (helpText stays in the defaults/schema).

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
					'help'      => __( 'Shown when the checkbox is not checked.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
			),
			'style'   => self::without_help_text_styles(
				Option_Controls::style(
					array(
						array(
							'type'  => 'section',
							'label' => __( 'Privacy Note & Badge', 'metform' ),
						),
						array(
							'type'     => 'color',
							'key'      => 'styles.privacyNoteColor',
							'label'    => __( 'Note Color', 'metform' ),
							'default'  => '#9ca3af',
							'selector' => '{{WRAPPER}} .mfb-optin__privacy-note { color: {{VALUE}}; }',
						),
						array(
							'type'           => 'typography',
							'key'            => 'styles.privacyNoteTypography',
							'label'          => __( 'Note Typography', 'metform' ),
							'selector'       => '{{WRAPPER}} .mfb-optin__privacy-note { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; }',
							'responsive'     => true,
							'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
						),
						array(
							'type'     => 'background',
							'key'      => 'styles.badgeBg',
							'label'    => __( 'Badge Background', 'metform' ),
							'default'  => array(
								'type'  => 'classic',
								'color' => '#dbeafe',
							),
							'selector' => '{{WRAPPER}} .mfb-optin__badge { {{VALUE}} }',
						),
						array(
							'type'     => 'color',
							'key'      => 'styles.badgeColor',
							'label'    => __( 'Badge Text Color', 'metform' ),
							'default'  => '#1d4ed8',
							'selector' => '{{WRAPPER}} .mfb-optin__badge { color: {{VALUE}}; }',
						),
					)
				),
			),
		);
	}

	/**
	 * Drops the shared Help Text style controls (color, typography) from
	 * Option_Controls::style() — this block has no Help Text control, so
	 * styling it would only add dead options to the Style tab.
	 *
	 * @param array $controls Style controls.
	 * @return array Controls without the Help Text ones.
	 */
	private static function without_help_text_styles( array $controls ): array {
		$help_keys = array( 'styles.helpTextColor', 'styles.helpTextTypography' );

		return array_values(
			array_filter(
				$controls,
				static fn( array $control ): bool => empty( $control['key'] ) || ! in_array( $control['key'], $help_keys, true )
			)
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
					'vertical'   => '{{WRAPPER}} .mfb-optin__option { flex-direction: column; align-items: flex-start; gap: 8px; }',
					'horizontal' => '{{WRAPPER}} .mfb-optin__option { flex-direction: row; align-items: flex-start; gap: 12px; }',
				),
			)
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id            = $context->get_element_id();
		$input_id      = 'optin-' . $id;
		$name          = (string) ( $settings['name'] ?? '' );
		$checked_input = sprintf(
			'<span class="mfb-option__box-wrap"><input type="checkbox" id="%1$s" name="%2$s" value="1" class="mfb-option__input-native" %3$s><span class="mfb-option__input mfb-field-option__input-checkbox" aria-hidden="true"><svg class="mfb-option__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg></span></span>',
			esc_attr( $input_id ),
			esc_attr( $name ),
			! empty( $settings['required'] ) ? 'required' : ''
		);
		$label_html    = sprintf(
			'<label for="%1$s" class="mfb-option__label">%2$s</label>',
			esc_attr( $input_id ),
			esc_html( (string) ( $settings['optinText'] ?? '' ) )
		);
		$text_position = ( $settings['textPosition'] ?? 'after' ) === 'before' ? 'before' : 'after';
		$note_html     = $this->render_privacy_note( $settings );
		$note_after    = '';

		if ( '' !== $note_html ) {
			if ( 'after' === $text_position ) {
				// Share a column with the option text so the note lines up with
				// it for any checkbox size or gap.
				$label_html = sprintf( '<div class="mfb-optin__text">%1$s%2$s</div>', $label_html, $note_html );
			} else {
				// The text already starts at the left edge; keeping the note
				// out of the row stops it from pushing the checkbox right.
				$note_after = $note_html;
			}
		}

		$inner          = 'before' === $text_position ? ( $label_html . $checked_input ) : ( $checked_input . $label_html );
		$option_classes = 'mfb-option mfb-optin__option' . ( 'vertical' === Responsive_Value::resolve( $settings['layout'] ?? 'horizontal', 'desktop', 'horizontal' ) ? '' : ' mfb-options--horizontal' );

		return sprintf(
			'<div class="%1$s">%2$s</div>%3$s',
			esc_attr( $option_classes ),
			$inner,
			$note_after
		);
	}

	/**
	 * Privacy note paragraph with {privacy_policy} rendered as a link (see
	 * Link_Placeholders), or '' when there's neither a note nor a URL. With a
	 * URL but no placeholder in the note, the link is appended to the note.
	 */
	private function render_privacy_note( array $settings ): string {
		$note = empty( $settings['privacyNote'] ) ? '' : (string) $settings['privacyNote'];

		if ( ! empty( $settings['privacyPolicyUrl'] ) && false === strpos( $note, '{privacy_policy}' ) ) {
			$note = ltrim( rtrim( $note ) . ' {privacy_policy}' );
		}
		if ( '' === $note ) {
			return '';
		}

		$note = Link_Placeholders::render(
			$note,
			array(
				'{privacy_policy}' => array(
					'url'  => $settings['privacyPolicyUrl'] ?? '',
					'text' => $settings['privacyPolicyLinkText'] ?? __( 'privacy policy', 'metform' ),
				),
			),
			! empty( $settings['openInNewTab'] ),
			'mfb-optin__link-placeholder'
		);

		return '<p class="mfb-optin__privacy-note">' . $note . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by Link_Placeholders::render().
	}

	protected function get_wrap_options( array $settings, Render_Context $context ): array {
		$opts = parent::get_wrap_options( $settings, $context );

		$opts['label_tag']       = 'span';
		$opts['label_row_class'] = 'mfb-optin__label-row';

		if ( ! empty( $settings['showBadge'] ) && ! empty( $settings['badgeText'] ) ) {
			$opts['label_extra'] = sprintf( '<span class="mfb-optin__badge">%s</span>', esc_html( $settings['badgeText'] ) );
		}

		return $opts;
	}
}
