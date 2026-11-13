<?php
/**
 * Template import coordinator.
 *
 * Sits between the REST controller and the pieces that do the real work: the
 * Elementor source that fetches and processes remote content, the access rules
 * that decide whether the site may use it, and the form builder that persists
 * the result. Keeping this orchestration out of `Api` means the import flow can
 * be reused by WP-CLI, tests, or the Elementor editor without a REST request.
 *
 * @package MetForm\Core\Forms\Template_Library
 * @since 4.1.6
 */

namespace MetForm\Core\Forms\Template_Library;

use MetForm\Core\Forms\Builder;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a remote template ID into a local `metform-form` post.
 *
 * @since 4.1.6
 */
class Import {

	use \MetForm\Traits\Singleton;

	/**
	 * Form type used when the caller does not specify one.
	 *
	 * @since 4.1.6
	 * @var string
	 */
	const DEFAULT_FORM_TYPE = 'general-form';

	/**
	 * Form type reserved for quiz templates, which require Pro.
	 *
	 * @since 4.1.6
	 * @var string
	 */
	const QUIZ_FORM_TYPE = 'quiz-form';

	/**
	 * Import a remote template, or create a blank form when no template is given.
	 *
	 * @since 4.1.6
	 *
	 * @param array $args {
	 *     Import arguments.
	 *
	 *     @type int    $template_id Remote template ID. `0` creates a blank form.
	 *     @type string $title       Desired form title.
	 *     @type string $form_type   MetForm form type.
	 * }
	 * @return int|\WP_Error Created form post ID, or an error on failure.
	 */
	public function run( array $args ) {
		$template_id  = isset( $args['template_id'] ) ? absint( $args['template_id'] ) : 0;
		$title        = isset( $args['title'] ) ? sanitize_text_field( $args['title'] ) : '';
		$form_type    = $this->resolve_form_type( isset( $args['form_type'] ) ? $args['form_type'] : '' );
		$builder_type = isset( $args['builder_type'] ) ? sanitize_text_field( $args['builder_type'] ) : 'elementor';

		if ( is_wp_error( $form_type ) ) {
			return $form_type;
		}

		if ( 0 === $template_id ) {
			return Builder::instance()->create_form(
				$title,
				0,
				[
					'form_type'    => $form_type,
					'builder_type' => $builder_type,
				]
			);
		}

		if ( 'metform' === $builder_type ) {
			$template = $this->get_raw_template_data( $template_id );
		} else {
			// `Source` extends an Elementor class, so it must not even be autoloaded
			// when Elementor is missing.
			if ( ! class_exists( '\Elementor\TemplateLibrary\Source_Base' ) ) {
				return new \WP_Error(
					'metform_elementor_missing',
					esc_html__( 'Elementor must be active to import a form template.', 'metform' ),
					[ 'status' => 409 ]
				);
			}

			$template = Source::instance()->get_form_data( $template_id );
		}

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		if ( '' === $title ) {
			$title = $template['title'];
		}

		$form_id = Builder::instance()->create_form(
			$title,
			$template_id,
			[
				'form_type'         => $form_type,
				'builder_type'      => $builder_type,
				'template_content'  => $template['content'],
				'template_settings' => $template['settings'],
			]
		);

		if ( is_wp_error( $form_id ) ) {
			return $form_id;
		}

		if ( 'metform' !== $builder_type ) {
			$this->normalize_content( $form_id, $template['content'] );
		}

		return $form_id;
	}

	/**
	 * Fetch remote template and extract raw form payload without Elementor dependency.
	 *
	 * @since 4.1.9
	 *
	 * @param int $template_id Remote template ID.
	 * @return array|\WP_Error
	 */
	public function get_raw_template_data( $template_id ) {
		$template = Remote_Api::get_template( $template_id );

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		$metadata = isset( $template['metadata'] ) ? (array) $template['metadata'] : [];
		$package  = isset( $metadata['package'] ) ? (string) $metadata['package'] : Access::PACKAGE_FREE;

		if ( ! Access::can_import( $package ) ) {
			return Access::denied_error();
		}

		$forms = isset( $template['metform_content']['forms'] ) ? (array) $template['metform_content']['forms'] : [];
		if ( empty( $forms ) ) {
			return new \WP_Error(
				'metform_template_empty',
				esc_html__( 'The selected template does not contain a form.', 'metform' ),
				[ 'status' => 422 ]
			);
		}

		$candidates = ( array_key_exists( 'elementor_data', $forms ) || array_key_exists( 'form_setting', $forms ) )
			? [ $forms ]
			: $forms;

		foreach ( $candidates as $form ) {
			$form    = (array) $form;
			$content = isset( $form['elementor_data'] ) ? $form['elementor_data'] : null;
			if ( is_string( $content ) ) {
				$content = json_decode( $content, true );
			}

			if ( empty( $content ) || ! is_array( $content ) ) {
				continue;
			}

			$fallback_title = isset( $template['metadata']['title'] ) ? (string) $template['metadata']['title'] : '';

			return [
				'content'  => $content,
				'settings' => ( isset( $form['form_setting'] ) && is_array( $form['form_setting'] ) ) ? $form['form_setting'] : [],
				'title'    => ! empty( $form['title'] ) ? (string) $form['title'] : $fallback_title,
				'package'  => $package,
			];
		}

		return new \WP_Error(
			'metform_template_empty',
			esc_html__( 'The selected template does not contain a form.', 'metform' ),
			[ 'status' => 422 ]
		);
	}

	/**
	 * Re-save the imported content once the Elementor document exists.
	 *
	 * @since 4.1.6
	 *
	 * @param int   $form_id Created form post ID.
	 * @param array $content Processed element tree.
	 * @return void
	 */
	private function normalize_content( $form_id, array $content ) {
		$normalized = Source::instance()->normalize_document_content( $form_id, $content );

		if ( $normalized !== $content ) {
			Builder::instance()->save_form_content( $form_id, $normalized );
		}
	}

	/**
	 * Validate the requested form type against the active package.
	 *
	 * @since 4.1.6
	 *
	 * @param string $form_type Requested form type.
	 * @return string|\WP_Error Sanitized form type, or an error when unavailable.
	 */
	private function resolve_form_type( $form_type ) {
		$form_type = sanitize_key( $form_type );

		if ( '' === $form_type ) {
			return self::DEFAULT_FORM_TYPE;
		}

		if ( self::QUIZ_FORM_TYPE === $form_type && ! Access::has_quiz() ) {
			return new \WP_Error(
				'metform_quiz_requires_pro',
				esc_html__( 'Quiz forms require MetForm Pro.', 'metform' ),
				[ 'status' => 402 ]
			);
		}

		return $form_type;
	}
}
