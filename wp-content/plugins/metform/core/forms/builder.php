<?php
/**
 * Form creation and builder redirection.
 *
 * @package MetForm\Core\Forms
 */

namespace MetForm\Core\Forms;

use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Editor\Controller as Editor_Controller;
use MetForm\Core\FormBuilder\Migrator\Migrator;
use MetForm\Core\FormBuilder\Rendering\Form_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Creates `metform-form` posts and hands them to the correct editor
 * (standalone Form Builder or Elementor).
 */
class Builder {

	use \MetForm\Traits\Singleton;

	/**
	 * Meta key holding the Elementor element tree.
	 *
	 * @since 4.1.6
	 * @var string
	 */
	const META_ELEMENTOR_DATA = '_elementor_data';

	/**
	 * Meta key recording which remote template a form was cloned from.
	 *
	 * @since 4.1.6
	 * @var string
	 */
	const META_CLONED_ID = '_metform_cloned_id';

	/**
	 * Whether a user may create and publish forms.
	 *
	 * Forms are registered with `capability_type => 'page'`, so this resolves
	 * to `edit_pages` + `publish_pages` (Editor and Administrator by default).
	 * Contributors and Authors only have the post-level capabilities and are
	 * refused.
	 *
	 * @since 4.3.4
	 *
	 * @param int|null $user_id Optional. User ID to check. Defaults to the current user.
	 * @return bool True when the user may create forms.
	 */
	public static function can_create_forms( $user_id = null ) {
		$post_type = get_post_type_object( Base::instance()->form->get_name() );
		$caps      = $post_type
			? [ $post_type->cap->create_posts, $post_type->cap->publish_posts ]
			: [ 'edit_pages', 'publish_pages' ];

		foreach ( $caps as $cap ) {
			$allowed = null === $user_id
				? current_user_can( $cap )
				: user_can( (int) $user_id, $cap );

			if ( ! $allowed ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Redirect to a form's editor.
	 *
	 * @param int $form_id Form post ID.
	 * @return void
	 */
	public function get_editor( $form_id ) {
		$form = get_post( $form_id );

		if ( ! $form ) {
			wp_die( esc_html__( 'The requested form could not be found.', 'metform' ) );
		}

		wp_safe_redirect( $this->get_editor_url( $form->ID ) );
		exit;
	}

	/**
	 * Build the editor URL for a form.
	 *
	 * Standalone vs. Elementor editor routing lives in FormBuilder\Editor\Controller.
	 *
	 * @since 4.1.6
	 *
	 * @param int $form_id Form post ID.
	 * @return string Editor URL.
	 */
	public function get_editor_url( $form_id ) {
		return Editor_Controller::get_editor_url( absint( $form_id ) );
	}

	/**
	 * Gets a form's rendered markup, standalone builder or Elementor.
	 *
	 * Backs Api::get_form_content() (GET forms/form_content/{id}). Not
	 * currently called from any admin/frontend JS — fixed here so the route
	 * doesn't fatal ("call to undefined method") if it's ever hit, using the
	 * same builder-vs-Elementor split as get_editor().
	 *
	 * @param int $form_id ID of the form.
	 * @return string Rendered form markup.
	 */
	public function get_form_content( $form_id ) {
		if ( Repository::is_builder_form( $form_id ) ) {
			return Form_Renderer::instance()->render_saved_form( $form_id );
		}

		return \MetForm\Utils\Util::render_form_content( $form_id, $form_id, true );
	}

	/**
	 * Create a `metform-form` post, optionally seeded from an imported template.
	 *
	 * @param string     $title       Form title. Falls back to a generated name.
	 * @param int|string $template_id Remote template ID, or `0` for a blank form.
	 * @param array      $data {
	 *     Optional. Imported template payload.
	 *
	 *     @type string $form_type         MetForm form type.
	 *     @type string $builder_type      'metform' for the standalone Form Builder,
	 *                                     'elementor' (default) for the classic editor.
	 *     @type array  $template_content  Processed Elementor element tree.
	 *     @type array  $template_settings MetForm settings from the template.
	 * }
	 * @return int|\WP_Error Created form post ID, or an error when the user may
	 *                       not create forms or insertion fails.
	 */
	public function create_form( $title, $template_id = 0, $data = [] ) {
		// Forms are inserted as published, so every caller must be allowed to
		// publish this post type. Checked here so no entry point can skip it.
		if ( ! self::can_create_forms() ) {
			return new \WP_Error(
				'metform_forbidden',
				esc_html__( 'You are not allowed to create forms.', 'metform' ),
				[ 'status' => 403 ]
			);
		}

		$data              = is_array( $data ) ? $data : [];
		$template_content  = isset( $data['template_content'] ) && is_array( $data['template_content'] ) ? $data['template_content'] : null;
		$template_settings = isset( $data['template_settings'] ) && is_array( $data['template_settings'] ) ? $data['template_settings'] : [];
		$title             = '' === trim( (string) $title ) ? 'New Form # ' . time() : $title;

		unset( $data['template_content'], $data['template_settings'] );

		// Older Pro/custom integrations may still provide the legacy template
		// repository. Never reference it in Free or when the class is absent.
		if (
			null === $template_content &&
			class_exists( '\\MetForm_Pro\\Base\\Package' ) &&
			class_exists( '\\MetForm\\Templates\\Base' )
		) {
			$legacy_template_id = 'template-' . absint( $template_id );
			$template_content   = \MetForm\Templates\Base::instance()->get_template_contents( $legacy_template_id );
		}

		$form_id = wp_insert_post(
			[
				'post_author'  => get_current_user_id(),
				'post_content' => '',
				'post_title'   => $title,
				'post_status'  => 'publish',
				'post_type'    => Base::instance()->form->get_name(),
			],
			true
		);

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		if ( ! $form_id ) {
			return new \WP_Error(
				'metform_form_not_created',
				esc_html__( 'The form could not be created.', 'metform' ),
				[ 'status' => 500 ]
			);
		}

		$this->save_form_settings( $form_id, $title, $template_settings, $data );

		$builder_type = isset( $data['builder_type'] ) ? $data['builder_type'] : 'elementor';

		if ( 'metform' === $builder_type ) {
			// --- Standalone Builder Configuration ---
			if ( ! empty( $template_content ) && is_array( $template_content ) ) {
				$migrated_doc = Migrator::instance()->migrate( $template_content );
				update_post_meta( $form_id, '_metform_editor_mode', 'metform' );
				update_post_meta( $form_id, Repository::META_KEY, wp_slash( $migrated_doc ) );
				Style_Engine::instance()->save_form_css( $form_id, $migrated_doc );
				if ( $template_id ) {
					update_post_meta( $form_id, self::META_CLONED_ID, $template_id );
				}
			} else {
				Repository::initialize( $form_id );
			}
		} else {
			// --- Classic Elementor Configuration ---
			$this->save_editor_meta( $form_id, $template_id );

			if ( null !== $template_content ) {
				$this->save_form_content( $form_id, $template_content );
			}
		}

		return (int) $form_id;
	}

	/**
	 * Store the MetForm settings meta for a newly created form.
	 *
	 * Imported settings are layered over MetForm's own defaults, so every
	 * declared field is present even when the template omits it. Keys the
	 * template carries but this version does not declare are kept rather than
	 * filtered out: Pro builds and future releases add settings that a Free
	 * install cannot enumerate, and dropping them would quietly break the
	 * imported form.
	 *
	 * @since 4.1.6
	 *
	 * @param int    $form_id           Form post ID.
	 * @param string $title             Form title.
	 * @param array  $template_settings Settings imported from the template.
	 * @param array  $data              Full creation payload.
	 * @return void
	 */
	private function save_form_settings( $form_id, $title, array $template_settings, array $data ) {
		$fields = Base::instance()->form->get_form_settings_fields();

		$settings = array_map(
			function () {
				return '';
			},
			$fields
		);

		$settings = array_merge( $settings, $template_settings );

		$settings['success_message'] = esc_html__( 'Thank you! Form submitted successfully.', 'metform' );
		$settings['store_entries']   = '1';
		$settings['form_title']      = $title;

		if ( ! empty( $data['form_type'] ) ) {
			$settings['form_type'] = sanitize_key( $data['form_type'] );
		}

		update_post_meta( $form_id, Base::instance()->form->get_key_form_settings(), $settings );
	}

	/**
	 * Store the Elementor editor metadata for a newly created form.
	 *
	 * @since 4.1.6
	 *
	 * @param int        $form_id     Form post ID.
	 * @param int|string $template_id Remote template ID, or `0` for a blank form.
	 * @return void
	 */
	private function save_editor_meta( $form_id, $template_id ) {
		update_post_meta( $form_id, '_wp_page_template', 'elementor_canvas' );
		update_post_meta( $form_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $form_id, self::META_CLONED_ID, 'template-' . absint( $template_id ) );
	}

	/**
	 * Persist an Elementor element tree on a form.
	 *
	 * Elementor reads `_elementor_data` as a slashed JSON string, so the value is
	 * encoded and slashed the same way Elementor's own document save does.
	 *
	 * @since 4.1.6
	 *
	 * @param int   $form_id Form post ID.
	 * @param array $content Element tree.
	 * @return bool True when the meta was written.
	 */
	public function save_form_content( $form_id, array $content ) {
		$encoded = wp_json_encode( $content );

		if ( false === $encoded ) {
			return false;
		}

		return (bool) update_post_meta( $form_id, self::META_ELEMENTOR_DATA, wp_slash( $encoded ) );
	}
}
