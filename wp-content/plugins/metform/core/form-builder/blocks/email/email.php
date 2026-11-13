<?php
namespace MetForm\Core\FormBuilder\Blocks\Email;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Email extends Block_Base {
	public function get_name(): string {
		return 'email'; }
	public function get_title(): string {
		return __( 'Email', 'metform' ); }
	public function get_icon(): string {
		return 'Mail'; }
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
		return Input_Controls::default_props( __( 'Email', 'metform' ), __( 'you@example.com', 'metform' ) );
	}

	public function get_property_schema(): array {
		return Input_Controls::schema();
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content( __( 'Email', 'metform' ) ),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id               = $context->get_element_id();
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<input id="<?php echo esc_attr( $id ); ?>" type="email" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" value="<?php echo esc_attr( (string) ( $settings['defaultValue'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		return (string) ob_get_clean();
	}
}
