<?php
namespace MetForm\Core\FormBuilder\Blocks\Recaptcha;

use MetForm\Core\Admin\Base as Admin_Base;
use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

/**
 * PHP block for Google reCAPTCHA in Form Builder.
 *
 * Reuses MetForm's existing reCAPTCHA settings (v2 / v3) configured under
 * MetForm -> Settings -> General Options, and connects to MetForm's
 * submission verification pipeline in action.php.
 *
 * @since 4.1.9
 */
final class Recaptcha extends Block_Base {
	public function get_name(): string {
		return 'recaptcha';
	}

	public function get_title(): string {
		return __( 'reCAPTCHA', 'metform' );
	}

	public function get_icon(): string {
		return 'ShieldCheck';
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
			'name'            => 'mf-recaptcha',
			'customClassName' => '',
		);
	}

	public function get_property_schema(): array {
		return array(
			'name'            => array( 'type' => 'field_name' ),
			'label'           => array( 'type' => 'string' ),
			'customClassName' => array( 'type' => 'string' ),
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
					'type'  => 'notice',
					'key'   => 'recaptcha_notice',
					'label' => __( 'reCAPTCHA Configuration', 'metform' ),
					'help'  => __( 'Configure Site Key and Secret Key in MetForm -> Settings -> General Options.', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'customClassName',
					'label' => __( 'Extra Class Name', 'metform' ),
				),
			),
			'style'   => array(),
		);
	}

	public function get_frontend_assets(): array {
		$recaptcha_setting = Admin_Base::instance()->get_settings_option();
		$recaptcha_type    = ! empty( $recaptcha_setting['mf_recaptcha_version'] ) ? $recaptcha_setting['mf_recaptcha_version'] : 'recaptcha-v2';
		return array( $recaptcha_type );
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id                = $context->get_element_id();
		$recaptcha_setting = Admin_Base::instance()->get_settings_option();
		$recaptcha_type    = ! empty( $recaptcha_setting['mf_recaptcha_version'] ) ? $recaptcha_setting['mf_recaptcha_version'] : 'recaptcha-v2';
		$site_key_v2       = $recaptcha_setting['mf_recaptcha_site_key'] ?? '';
		$site_key_v3       = $recaptcha_setting['mf_recaptcha_site_key_v3'] ?? '';
		$extra_class       = sanitize_html_class( (string) ( $settings['customClassName'] ?? '' ) );

		// In Builder editor mode: render a placeholder preview badge
		if ( $context->is_editor() ) {
			return sprintf(
				'<div class="mfb-recaptcha__editor-preview"><span class="mfb-recaptcha__badge-icon">&#x1F6E1;</span> <span>%1$s (%2$s)</span><small>%3$s</small></div>',
				esc_html__( 'Google reCAPTCHA', 'metform' ),
				esc_html( $recaptcha_type ),
				esc_html__( 'Active on preview & live page', 'metform' )
			);
		}

		// On Frontend / Live
		ob_start();
		if ( 'recaptcha-v2' === $recaptcha_type ) {
			if ( empty( $site_key_v2 ) && current_user_can( 'manage_options' ) ) {
				echo '<div class="mfb-recaptcha__warning" style="padding: 10px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; font-size: 13px;">' . esc_html__( 'reCAPTCHA v2 Site Key is missing. Please configure it in MetForm -> Settings -> General Options.', 'metform' ) . '</div>';
			}
			wp_enqueue_script( 'recaptcha-v2' );
			?>
			<div class="mf-input-wrapper mfb-recaptcha__wrapper">
				<div class="g-recaptcha <?php echo esc_attr( $extra_class ); ?>" id="g-recaptcha-<?php echo esc_attr( $id ); ?>" data-sitekey="<?php echo esc_attr( $site_key_v2 ); ?>"></div>
				<input type="hidden" name="g-recaptcha-response" class="mf-input" />
			</div>
			<?php
		} elseif ( 'recaptcha-v3' === $recaptcha_type ) {
			if ( empty( $site_key_v3 ) && current_user_can( 'manage_options' ) ) {
				echo '<div class="mfb-recaptcha__warning" style="padding: 10px; background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 4px; font-size: 13px;">' . esc_html__( 'reCAPTCHA v3 Site Key is missing. Please configure it in MetForm -> Settings -> General Options.', 'metform' ) . '</div>';
			}
			if ( ! wp_script_is( 'recaptcha-v3', 'registered' ) && ! empty( $site_key_v3 ) ) {
				wp_register_script( 'recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . esc_attr( $site_key_v3 ), array(), null, true );
			}
			wp_enqueue_script( 'recaptcha-v3' );
			?>
			<div class="mf-input-wrapper mfb-recaptcha__wrapper">
				<div id="recaptcha_site_key_v3_<?php echo esc_attr( $id ); ?>" data-sitekey="<?php echo esc_attr( $site_key_v3 ); ?>" class="recaptcha_site_key_v3 <?php echo esc_attr( $extra_class ); ?>">
					<input type="hidden" class="g-recaptcha-response-v3" name="g-recaptcha-response-v3" />
				</div>
			</div>
			<?php
		}
		return (string) ob_get_clean();
	}
}
