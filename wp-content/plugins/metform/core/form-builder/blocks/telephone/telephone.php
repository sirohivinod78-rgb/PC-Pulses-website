<?php
namespace MetForm\Core\FormBuilder\Blocks\Telephone;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Telephone extends Block_Base {
	public function get_name(): string {
		return 'telephone'; }
	public function get_title(): string {
		return __( 'Telephone', 'metform' ); }
	public function get_icon(): string {
		return 'Phone'; }
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
			Input_Controls::default_props( __( 'Telephone', 'metform' ), __( 'Enter your phone number', 'metform' ) ),
			array(
				'validation' => array(
					'type'    => 'pattern',
					'value'   => '^[0-9+()\-\s]{7,20}$',
					'message' => __( 'Please enter a valid phone number.', 'metform' ),
				),
			)
		);
	}

	public function get_property_schema(): array {
		return Input_Controls::schema();
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content( __( 'Telephone', 'metform' ) ),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id               = $context->get_element_id();
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<input id="<?php echo esc_attr( $id ); ?>" type="tel" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" value="<?php echo esc_attr( (string) ( $settings['defaultValue'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		return (string) ob_get_clean();
	}
}
