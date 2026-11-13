<?php
namespace MetForm\Core\FormBuilder\Blocks\Input;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Input extends Block_Base {
	public function get_name(): string {
		return 'input'; }
	public function get_title(): string {
		return __( 'Text Input', 'metform' ); }
	public function get_icon(): string {
		return 'Type'; }
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
			Input_Controls::default_props( __( 'Input', 'metform' ) ),
			Input_Controls::quiz_default_props()
		);
	}

	public function get_property_schema(): array {
		return array_merge(
			Input_Controls::schema(),
			Input_Controls::quiz_schema()
		);
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content( __( 'Input', 'metform' ), Input_Controls::quiz_controls() ),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id               = $context->get_element_id();
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<input id="<?php echo esc_attr( $id ); ?>" type="text" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" value="<?php echo esc_attr( (string) ( $settings['defaultValue'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		return (string) ob_get_clean();
	}
}
