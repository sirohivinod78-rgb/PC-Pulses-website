<?php
namespace MetForm\Core\FormBuilder\Blocks\Radio;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Option_Controls;

defined( 'ABSPATH' ) || exit;

final class Radio extends Block_Base {
	public function get_name(): string {
		return 'radio'; }
	public function get_title(): string {
		return __( 'Radio', 'metform' ); }
	public function get_icon(): string {
		return 'Circle'; }
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
		return Option_Controls::default_props( __( 'Radio', 'metform' ) );
	}

	public function get_property_schema(): array {
		return Option_Controls::schema();
	}

	public function register_controls(): array {
		return array(
			'content' => Option_Controls::content( __( 'Radio', 'metform' ), __( 'Shown when no option is selected.', 'metform' ) ),
			// Radio's marker is drawn entirely via `::after` on the real
			// `<input>` itself (RadioGroupInput.jsx doesn't use the
			// box-wrap/visual-span split Checkbox does) — `:checked` is
			// native browser state on that real element, so — unlike
			// Checkbox's shared default — this one already worked with zero
			// JS in the original React control declarations; ported as-is.
			'style'   => Option_Controls::style(
				array(),
				array(
					'checked_bg'     => '{{WRAPPER}} .mfb-field-option__input-radio:checked::after { {{VALUE}} }',
					'check_color'    => '{{WRAPPER}} .mfb-field-option__input-radio:checked::after { background-color: {{VALUE}}; }',
					'checked_border' => '{{WRAPPER}} .mfb-field-option__input-radio:checked { border-color: {{VALUE}}; }',
				)
			),
		);
	}

	/**
	 * Per-option markup matches RadioGroupInput.jsx exactly — no wrapping
	 * span (unlike Checkbox), the real `<input>` carries both option-marker
	 * classes directly:
	 *   label.mfb-option.mfb-field-option
	 *     input[type=radio].mfb-option__input.mfb-field-option__input-radio
	 *     span.mfb-option__label.mfb-field-option__label
	 *
	 * `name` uses the real field name for every option in the group (plain
	 * native radio semantics — only the checked one's value posts), unlike
	 * Checkbox which needs the `[]` array-name adaptation.
	 */
	/**
	 * A bare `required` on just one radio in a named group is enough for
	 * the browser to require "at least one checked" across the whole group
	 * — cheap and free, unlike Checkbox's any-of-N case (no native
	 * equivalent there). `$required` is only ever true for the first
	 * option rendered.
	 */
	private function render_option( array $opt, string $name, string $text_position, bool $required = false ): string {
		$value          = (string) ( $opt['value'] ?? '' );
		$is_quiz_answer = ! empty( $opt['isQuizAnswer'] );

		$input_html = sprintf(
			'<input type="radio" name="%1$s" value="%2$s" class="mfb-option__input mfb-field-option__input-radio" %3$s %4$s %5$s %6$s>',
			esc_attr( $name ),
			esc_attr( $value ),
			! empty( $opt['disabled'] ) ? 'disabled' : '',
			$required ? 'required' : '',
			! empty( $opt['isDefault'] ) ? 'checked' : '',
			$is_quiz_answer ? 'data-quiz-answer="true"' : ''
		);

		return Option_Controls::render_option_label( $input_html, $opt, $text_position );
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
					'vertical'   => '{{WRAPPER}} .mfb-field-option__group { flex-direction: column; flex-wrap: nowrap; }',
					'horizontal' => '{{WRAPPER}} .mfb-field-option__group { flex-direction: row; flex-wrap: wrap; }',
				),
			)
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$name          = (string) ( $settings['name'] ?? '' );
		$options       = is_array( $settings['options'] ?? null ) ? $settings['options'] : array();
		$text_position = in_array( $settings['textPosition'] ?? 'after', array( 'before', 'after' ), true ) ? $settings['textPosition'] : 'after';
		$group_classes = 'mfb-field-option__group' . ( 'horizontal' === Responsive_Value::resolve( $settings['layout'] ?? 'vertical', 'desktop', 'vertical' ) ? ' mfb-options--horizontal' : '' );
		$required      = ! empty( $settings['required'] );

		$rows = '';
		foreach ( $options as $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			$rows    .= $this->render_option( $opt, $name, $text_position, $required );
			$required = false; // only the first rendered option carries it
		}

		return sprintf( '<div class="%1$s">%2$s</div>', esc_attr( $group_classes ), $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
