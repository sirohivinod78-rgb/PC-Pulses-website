<?php
namespace MetForm\Core\FormBuilder\Blocks\Checkbox;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Option_Controls;

defined( 'ABSPATH' ) || exit;

final class Checkbox extends Block_Base {
	public function get_name(): string {
		return 'checkbox'; }
	public function get_title(): string {
		return __( 'Checkbox', 'metform' ); }
	public function get_icon(): string {
		return 'CheckSquare'; }
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
		return Option_Controls::default_props( __( 'Checkbox', 'metform' ), 'horizontal' );
	}

	public function get_property_schema(): array {
		return Option_Controls::schema();
	}

	public function register_controls(): array {
		return array(
			'content' => Option_Controls::content( __( 'Checkbox', 'metform' ), __( 'Shown when no option is checked.', 'metform' ), array(), 'horizontal' ),
			'style'   => Option_Controls::style(),
		);
	}

	/**
	 * Per-option markup matches CheckboxGroupInput.jsx exactly:
	 *   label.mfb-option.mfb-field-option
	 *     span.mfb-option__box-wrap
	 *       input[type=checkbox].mfb-option__input-native   (real, submits)
	 *       span.mfb-option__input.mfb-field-option__input-checkbox[aria-hidden]
	 *         svg.mfb-option__check-icon                    (CheckIcon.jsx, hand-drawn)
	 *     span.mfb-option__label.mfb-field-option__label
	 *
	 * `name` deliberately differs from React's `checkbox-{key}` (React
	 * manages the checked-values array in JS state; the native `name` is
	 * irrelevant there). For a zero-JS PHP submission every checked box in
	 * the group must survive as an array — `{field_name}[]` does that via
	 * PHP's own $_POST array-bracket convention.
	 */
	private function render_option( array $opt, string $name, string $text_position ): string {
		$value          = (string) ( $opt['value'] ?? '' );
		$is_quiz_answer = ! empty( $opt['isQuizAnswer'] );

		ob_start();
		?>
<span class="mfb-option__box-wrap">
	<input type="checkbox" name="<?php echo esc_attr( $name ); ?>[]" value="<?php echo esc_attr( $value ); ?>" class="mfb-option__input-native" <?php echo ! empty( $opt['disabled'] ) ? 'disabled' : ''; ?> <?php echo ! empty( $opt['isDefault'] ) ? 'checked' : ''; ?> <?php echo $is_quiz_answer ? 'data-quiz-answer="true"' : ''; ?>>
	<span class="mfb-option__input mfb-field-option__input-checkbox" aria-hidden="true">
		<svg class="mfb-option__check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
	</span>
</span>
		<?php
		$input_html = (string) ob_get_clean();

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
			$rows .= $this->render_option( $opt, $name, $text_position );
		}

		// Unlike Radio/Toggleselect/Imageselect/Likedislike, a checkbox
		// group has no native "at least one of N checked" HTML5 primitive
		// — `required` on an individual checkbox means THAT checkbox must
		// be checked, not "any one of the group". `data-mf-required`/
		// `data-mf-required-message` mark the group for
		// src/frontend/form-builder/blocks/checkbox/index.js's submit-time
		// custom-validity check instead (same class of fix as Signature's
		// `data-mf-required` guard).
		return sprintf(
			'<div class="%1$s" %2$s %3$s>%4$s</div>',
			esc_attr( $group_classes ),
			$required ? 'data-mf-required="1"' : '',
			$required && ! empty( $settings['requiredMessage'] ) ? 'data-mf-required-message="' . esc_attr( $settings['requiredMessage'] ) . '"' : '',
			$rows // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
