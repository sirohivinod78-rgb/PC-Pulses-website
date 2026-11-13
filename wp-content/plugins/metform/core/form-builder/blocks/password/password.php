<?php
namespace MetForm\Core\FormBuilder\Blocks\Password;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

final class Password extends Block_Base {
	public function get_name(): string {
		return 'password'; }
	public function get_title(): string {
		return __( 'Password', 'metform' ); }
	public function get_icon(): string {
		return 'Lock'; }
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
			Input_Controls::default_props( __( 'Password', 'metform' ), __( 'Enter your password', 'metform' ) ),
			array(
				'showToggle' => true,
			)
		);
	}

	public function get_property_schema(): array {
		return array_merge(
			Input_Controls::schema(),
			array(
				'showToggle' => array( 'type' => 'boolean' ),
			)
		);
	}

	public function register_controls(): array {
		return array(
			// urlPrefill = false: a password value should never round-trip
			// through a visible query string, so this block opts out of the
			// shared "Get Value From URL?" control (matches the manifest).
			'content' => Input_Controls::content(
				__( 'Password', 'metform' ),
				array(
					array(
						'type'    => 'toggle',
						'key'     => 'showToggle',
						'label'   => __( 'Show Password', 'metform' ),
						'default' => true,
						'help'    => __( 'Adds an eye icon so users can reveal the password.', 'metform' ),
					),
				),
				false
			),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	protected function render_markup( array $settings, Render_Context $context ): string {
		$id               = $context->get_element_id();
		$validation_attrs = Input_Controls::validation_attributes( $settings );
		$show_toggle      = ! empty( $settings['showToggle'] );

		ob_start();
		?>
		<div class="mfb-password__input-wrap">
			<input id="<?php echo esc_attr( $id ); ?>" type="password" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( $show_toggle ) : ?>
			<button type="button" class="mfb-password__toggle" data-mf-password-toggle aria-pressed="false" aria-label="<?php esc_attr_e( 'Show password', 'metform' ); ?>">
				<svg class="mfb-password__icon mfb-password__icon-eye" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"></path><circle cx="12" cy="12" r="3"></circle></svg>
				<svg class="mfb-password__icon mfb-password__icon-eye-off" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path fill="currentColor" opacity=".1" d="M6.295 7.876 5 9.172c-1.333 1.333-2 2-2 2.828 0 .829.667 1.495 2 2.829l2.121 2.12a7 7 0 0 0 9.9 0l.411-.41-2.927-2.277a3.25 3.25 0 0 1-5.1-3.967z"></path><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m3.171 5.13 18 14"></path><path stroke="currentColor" stroke-width="2" d="M14.365 13.846a3 3 0 0 1-4.767-3.643"></path><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 5.627a7 7 0 0 1 7.85 1.423l3.001 3.002c.497.497.745.745.885 1.008a2 2 0 0 1 0 1.88c-.14.263-.388.511-.885 1.008L19.8 14"></path><path fill="currentColor" d="M7.016 8.398a1 1 0 1 0-1.414-1.414zm.64 7.845-2.12-2.122-1.415 1.415 2.122 2.12zm8.486 0a6 6 0 0 1-8.485 0l-1.414 1.414a8 8 0 0 0 11.313 0zM5.536 9.879l1.48-1.48-1.414-1.415-1.48 1.48zm11.21 5.76-.604.604 1.414 1.414.605-.604zM5.537 14.12c-.687-.686-1.13-1.132-1.413-1.503-.265-.348-.295-.51-.295-.618h-2c0 .72.304 1.306.705 1.832.384.502.942 1.057 1.588 1.704zM4.12 8.464c-.646.647-1.204 1.202-1.588 1.705-.4.525-.705 1.11-.705 1.831h2c0-.108.03-.27.295-.618.284-.371.726-.817 1.413-1.503z"></path></svg>
			</button>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
