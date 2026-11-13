<?php
namespace MetForm\Core\FormBuilder\Rest;

use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Document\Sanitizer as Document_Sanitizer;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Editor\Revision_Manager;
use MetForm\Core\FormBuilder\Rendering\Form_Renderer;
use MetForm\Core\Forms\Base as Forms_Base;
use MetForm\Traits\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/** Owns Form Builder REST routes and their callbacks. */
class Controller {
	use Singleton;

	/** Registers the REST API bootstrap hook. */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Registers Form Builder document and rendering REST routes.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'metform/v1',
			'/form-builder/render',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'form_builder_render' ),
			)
		);

		register_rest_route(
			'metform/v1',
			'/form-builder/document',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'save_form_builder_document' ),
			)
		);

		register_rest_route(
			'metform/v1',
			'/form-builder/document',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'get_form_builder_document' ),
			)
		);

		register_rest_route(
			'metform/v1',
			'/form-builder/title',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'save_form_builder_title' ),
			)
		);

		register_rest_route(
			'metform/v1',
			'/form-builder/revisions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'get_form_builder_revisions' ),
			)
		);

		register_rest_route(
			'metform/v1',
			'/form-builder/revision',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'can_edit_form_builder' ),
				'callback'            => array( $this, 'get_form_builder_revision' ),
			)
		);
	}

	/**
	 * Checks whether the current user can edit the requested form.
	 *
	 * Used as the shared permission callback for all authenticated Form
	 * Builder REST routes.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing the form ID.
	 * @return bool True when the current user can edit the form, otherwise false.
	 */
	public function can_edit_form_builder( WP_REST_Request $request ): bool {
		return current_user_can( 'edit_post', absint( $request->get_param( 'formId' ) ) );
	}

	/**
	 * Renders a complete Form Builder document or batched block fragments.
	 *
	 * The request may contain a `fragments` flag. When enabled, the response
	 * contains markup and PHP-generated dynamic CSS keyed by element ID;
	 * otherwise it contains complete form or single-fragment markup in the
	 * `html` property.
	 *
	 * A fragments request may also set `cssOnly`. The editor sends it when
	 * every changed prop belongs to a selector-backed Style control, so the
	 * block's markup cannot have changed: markup rendering is skipped and
	 * `fragments` comes back empty, while `styles` is built as usual. Without
	 * the flag the response is unchanged.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing the form ID and document.
	 * @return WP_REST_Response REST response containing rendered markup.
	 */
	public function form_builder_render( WP_REST_Request $request ) {
		$payload  = $request->get_json_params();
		$document = isset( $payload['document'] ) && is_array( $payload['document'] ) ? $payload['document'] : array();
		$document = $this->sanitize_document( $document );
		$form_id  = absint( $payload['formId'] ?? 0 );

		if ( ! empty( $payload['fragments'] ) ) {
			$css_only  = ! empty( $payload['cssOnly'] );
			$fragments = array();
			$styles    = array();
			foreach ( ( $document['elements'] ?? array() ) as $element ) {
				$element_id = sanitize_key( $element['id'] ?? '' );
				if ( $element_id ) {
					$fragment_document = array(
						'version'  => '1.0.0',
						'elements' => array( $element ),
					);
					if ( ! $css_only ) {
						$fragments[ $element_id ] = Form_Renderer::instance()->render_fragment(
							$fragment_document,
							$form_id,
							true
						);
					}
					$styles[ $element_id ] = Style_Engine::instance()->build_document_css(
						$form_id,
						$fragment_document,
						true,
						true
					);
				}
			}

			return rest_ensure_response(
				array(
					'fragments' => $fragments,
					'styles'    => $styles,
				)
			);
		}

		return rest_ensure_response(
			array(
				'html' => ! empty( $payload['fragment'] )
					? Form_Renderer::instance()->render_fragment( $document, $form_id, true )
					: Form_Renderer::instance()->render_document( $document, $form_id, true ),
			)
		);
	}

	/**
	 * Sanitizes and saves a Form Builder document.
	 *
	 * The saved document is written to the `_metform_builder_document` post
	 * meta key and read back to verify that persistence succeeded.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing the form ID and document.
	 * @return WP_REST_Response|WP_Error Save response on success, or an error on failure.
	 */
	public function save_form_builder_document( WP_REST_Request $request ) {
		$payload  = $request->get_json_params();
		$form_id  = absint( $payload['formId'] ?? 0 );
		$document = isset( $payload['document'] ) && is_array( $payload['document'] ) ? $payload['document'] : array();

		if ( ! $form_id || 'metform-form' !== get_post_type( $form_id ) ) {
			return new WP_Error(
				'metform_invalid_form',
				__( 'A valid MetForm form ID is required.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		// Update form title and status if provided in payload.
		$post_update = array( 'ID' => $form_id );
		if ( isset( $payload['title'] ) ) {
			$title = sanitize_text_field( $payload['title'] );
			if ( '' !== $title ) {
				$post_update['post_title'] = $title;

				$settings_key  = Forms_Base::instance()->form->get_key_form_settings();
				$form_settings = get_post_meta( $form_id, $settings_key, true );
				if ( is_array( $form_settings ) ) {
					$form_settings['form_title'] = $title;
					update_post_meta( $form_id, $settings_key, $form_settings );
				}
			}
		}

		if ( isset( $payload['status'] ) && in_array( $payload['status'], array( 'publish', 'draft' ), true ) ) {
			$post_update['post_status'] = sanitize_key( $payload['status'] );
		}

		if ( count( $post_update ) > 1 ) {
			wp_update_post( $post_update );
		}

		// This two-block proof stores only the supported, inert data
		// structure. Each PHP renderer escapes its values by context.
		$document = $this->sanitize_document( $document );
		update_post_meta( $form_id, '_metform_builder_document', wp_slash( $document ) );

		// Force WordPress to record a post revision for this document save.
		add_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		wp_update_post(
			array(
				'ID'                => $form_id,
				'post_modified'     => current_time( 'mysql' ),
				'post_modified_gmt' => current_time( 'mysql', 1 ),
			)
		);
		remove_filter( 'wp_save_post_revision_check_for_changes', '__return_false' );
		clean_post_cache( $form_id );

		$saved_document = Repository::get( $form_id );
		if ( ! is_array( $saved_document ) || $saved_document !== $document ) {
			return new WP_Error(
				'metform_document_save_failed',
				__( 'WordPress could not save the form-builder document.', 'metform' ),
				array( 'status' => 500 )
			);
		}

		if ( ! Style_Engine::instance()->save_form_css( $form_id, $saved_document ) ) {
			Style_Engine::instance()->delete_form_css( $form_id );
			return new WP_Error(
				'metform_css_save_failed',
				__( 'WordPress saved the form, but could not write its generated CSS file.', 'metform' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'saved'        => true,
				'formId'       => $form_id,
				'title'        => get_the_title( $form_id ),
				'postStatus'   => get_post_status( $form_id ),
				'metaKey'      => '_metform_builder_document',
				'elementCount' => count( $saved_document['elements'] ?? array() ),
				'document'     => $saved_document,
			)
		);
	}

	/**
	 * Saves the Form Builder form title.
	 *
	 * Updates both WordPress post_title and the form_title setting meta.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing formId and title.
	 * @return WP_REST_Response|WP_Error Response on success, or an error on failure.
	 */
	public function save_form_builder_title( WP_REST_Request $request ) {
		$payload = $request->get_json_params();
		$form_id = absint( $payload['formId'] ?? 0 );
		$title   = sanitize_text_field( $payload['title'] ?? '' );

		if ( ! $form_id || 'metform-form' !== get_post_type( $form_id ) ) {
			return new WP_Error(
				'metform_invalid_form',
				__( 'A valid MetForm form ID is required.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		if ( '' === $title ) {
			return new WP_Error(
				'metform_invalid_title',
				__( 'Form title cannot be empty.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		wp_update_post(
			array(
				'ID'         => $form_id,
				'post_title' => $title,
			)
		);

		$settings_key  = Forms_Base::instance()->form->get_key_form_settings();
		$form_settings = get_post_meta( $form_id, $settings_key, true );
		if ( is_array( $form_settings ) ) {
			$form_settings['form_title'] = $title;
			update_post_meta( $form_id, $settings_key, $form_settings );
		}

		clean_post_cache( $form_id );

		return rest_ensure_response(
			array(
				'saved'  => true,
				'formId' => $form_id,
				'title'  => get_the_title( $form_id ),
			)
		);
	}

	/**
	 * Gets the saved Form Builder document for a form.
	 *
	 * Returns an empty versioned document when no saved document exists.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing the form ID.
	 * @return WP_REST_Response REST response containing the saved document.
	 */
	public function get_form_builder_document( WP_REST_Request $request ) {
		$form_id  = absint( $request->get_param( 'formId' ) );
		$document = Repository::get( $form_id );

		return rest_ensure_response(
			array(
				'title'      => get_the_title( $form_id ),
				'postStatus' => get_post_status( $form_id ),
				'document'   => is_array( $document )
				? $document
				: array(
					'version'  => '1.0.0',
					'elements' => array(),
				),
			)
		);
	}

	/**
	 * Sanitizes a supported Form Builder document.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $document Form Builder document.
	 * @return array<string, mixed> Sanitized Form Builder document.
	 */
	private function sanitize_document( array $document ): array {
		return Document_Sanitizer::instance()->sanitize( $document );
	}

	/**
	 * Gets WordPress revisions list for a Form Builder form.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing the form ID.
	 * @return WP_REST_Response REST response containing revisions.
	 */
	public function get_form_builder_revisions( WP_REST_Request $request ) {
		$form_id   = absint( $request->get_param( 'formId' ) );
		$revisions = Revision_Manager::instance()->get_revisions( $form_id );

		return rest_ensure_response( array( 'revisions' => $revisions ) );
	}

	/**
	 * Gets the Form Builder document for a specific WordPress revision.
	 *
	 * @since 4.1.8
	 *
	 * @param WP_REST_Request $request REST request containing form ID and revision ID.
	 * @return WP_REST_Response|WP_Error REST response containing the revision document or 404 error.
	 */
	public function get_form_builder_revision( WP_REST_Request $request ) {
		$form_id     = absint( $request->get_param( 'formId' ) );
		$revision_id = absint( $request->get_param( 'revisionId' ) );
		$document    = Revision_Manager::instance()->get_revision_document( $form_id, $revision_id );

		if ( null === $document ) {
			return new WP_Error(
				'metform_revision_not_found',
				__( 'Revision document not found.', 'metform' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response( array( 'document' => $document ) );
	}
}
