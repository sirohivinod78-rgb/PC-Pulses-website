<?php
/**
 * Elementor to Standalone Form Builder Conversion Controller.
 *
 * Handles admin-side AJAX requests and modal rendering to convert legacy
 * Elementor forms into the Standalone Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.2.0
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Editor\Controller as Editor_Controller;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

class Conversion_Controller {
	use Singleton;

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_metform_convert_to_builder', array( $this, 'convert_elementor_to_builder' ) );
		add_action( 'admin_footer', array( $this, 'render_modal' ) );
	}

	/**
	 * Render confirmation modal in the admin forms list footer.
	 *
	 * @return void
	 */
	public function render_modal() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'edit-metform-form', 'metform_page_mt-form-settings' ), true ) ) {
			return;
		}

		$modal_file = __DIR__ . '/views/modal-convert-form.php';
		if ( file_exists( $modal_file ) ) {
			include_once $modal_file;
		}
	}

	/**
	 * Convert an Elementor-based form to the Standalone Form Builder.
	 *
	 * @return void
	 */
	public function convert_elementor_to_builder() {
		// 1. Verify Nonce
		if ( ! check_ajax_referer( 'metform_convert_action', 'security', false ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Security check failed. Please refresh and try again.', 'metform' ),
				)
			);
			wp_die();
		}

		// 2. Validate Form ID
		$form_id = absint( $_POST['form_id'] ?? 0 );
		if ( ! $form_id ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Invalid form ID provided.', 'metform' ),
				)
			);
			wp_die();
		}

		// 3. Capability Check
		if ( ! current_user_can( 'edit_post', $form_id ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'You do not have permission to edit this form.', 'metform' ),
				)
			);
			wp_die();
		}

		// 4. Post Type Check
		$post = get_post( $form_id );
		if ( ! $post || 'metform-form' !== $post->post_type ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'The requested form could not be found.', 'metform' ),
				)
			);
			wp_die();
		}

		// 5. Already Converted Check
		if ( Repository::is_builder_form( $form_id ) ) {
			wp_send_json_success(
				array(
					'message'     => esc_html__( 'This form is already using the Form Builder.', 'metform' ),
					'builder_url' => Editor_Controller::get_builder_url( $form_id ),
				)
			);
			wp_die();
		}

		// 6. Retrieve Elementor data
		$raw_elementor  = get_post_meta( $form_id, '_elementor_data', true );
		$elementor_tree = array();

		if ( ! empty( $raw_elementor ) ) {
			$elementor_tree = is_string( $raw_elementor ) ? json_decode( $raw_elementor, true ) : $raw_elementor;
		}

		if ( ! is_array( $elementor_tree ) ) {
			$elementor_tree = array();
		}

		// 7. Run Migration
		if ( ! empty( $elementor_tree ) && class_exists( Migrator::class ) ) {
			$migrated_doc = Migrator::instance()->migrate( $elementor_tree );
		} else {
			$migrated_doc = Repository::get_default();
		}

		// 8. Safety Backup (Store original Elementor data)
		update_post_meta(
			$form_id,
			'_metform_elementor_backup',
			array(
				'converted_at'   => current_time( 'mysql' ),
				'elementor_data' => $raw_elementor,
				'edit_mode'      => get_post_meta( $form_id, '_elementor_edit_mode', true ),
			)
		);

		// 9. Update Form Builder Document and Mode
		update_post_meta( $form_id, '_metform_editor_mode', 'metform' );
		update_post_meta( $form_id, '_metform_builder_document', wp_slash( $migrated_doc ) );

		// 10. Generate and write compiled CSS file
		if ( class_exists( Style_Engine::class ) ) {
			Style_Engine::instance()->save_form_css( $form_id, $migrated_doc );
		}

		// 11. Clear WordPress cache for this post
		clean_post_cache( $form_id );

		// 12. Send Success Response
		wp_send_json_success(
			array(
				'message'     => esc_html__( 'Form successfully converted to Form Builder.', 'metform' ),
				'builder_url' => Editor_Controller::get_builder_url( $form_id ),
			)
		);
		wp_die();
	}
}
