<?php
namespace MetForm\Core\FormBuilder\Editor;

use MetForm\Base\Assets_Enqueue;
use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\Forms\Builder as Forms_Builder;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/** Owns the standalone editor lifecycle and request handlers. */
class Controller {
	use Singleton;

	/** Registers editor-specific hooks. */
	public function init(): void {
		add_action( 'admin_action_metform-builder', array( $this, 'render_form_builder_page' ) );
		add_action( 'admin_action_metform-new-builder', array( $this, 'create_and_open_new_builder' ) );
		add_action( 'template_redirect', array( $this, 'handle_canvas_request' ), 0 );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_preview_assets' ) );
	}

	public function enqueue_preview_assets(): void {
		Assets_Enqueue::get_script( 'metform-builder-frontend' );
	}

	/**
	 * Gets the standalone builder URL for a form.
	 *
	 * @since 4.1.8
	 *
	 * @param int $post_id Form post ID.
	 * @return string Standalone builder URL.
	 */
	public static function get_builder_url( $post_id ) {
		return add_query_arg(
			array(
				'post'   => absint( $post_id ),
				'action' => 'metform-builder',
			),
			admin_url( 'post.php' )
		);
	}

	/**
	 * Gets the nonce-protected URL that creates a new standalone form.
	 *
	 * @since 4.3.4
	 *
	 * @return string New form URL (HTML-escaped by wp_nonce_url()).
	 */
	public static function get_new_builder_url() {
		return wp_nonce_url(
			admin_url( 'admin.php?action=metform-new-builder' ),
			'metform-new-builder'
		);
	}

	/**
	 * Gets the WordPress-rendered canvas document URL.
	 *
	 * @since 4.1.8
	 *
	 * @param int $post_id Form post ID.
	 * @return string Canvas document URL.
	 */
	public static function get_canvas_url( $post_id ) {
		return add_query_arg(
			array( 'metform_builder_canvas' => absint( $post_id ) ),
			home_url( '/' )
		);
	}

	/**
	 * Gets the correct editor URL for a form, standalone builder or Elementor.
	 *
	 * @since 4.1.8
	 *
	 * @param int $post_id Form post ID.
	 * @return string Editor URL.
	 */
	public static function get_editor_url( $post_id ) {
		return Repository::is_builder_form( $post_id )
			? self::get_builder_url( $post_id )
			: admin_url( 'post.php?post=' . absint( $post_id ) . '&action=elementor' );
	}

	/**
	 * Renders the canvas when its frontend endpoint is requested.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function handle_canvas_request() {
		Canvas::instance()->handle_request();
	}

	/**
	 * Renders the builder without WordPress admin chrome.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function render_form_builder_page() {
		Canvas::instance()->render_form_builder_page();
	}

	/**
	 * Creates a new standalone form and redirects directly to the standalone builder.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function create_and_open_new_builder() {
		// This GET request creates a post, so it must carry a nonce to stop
		// cross-site requests from creating forms.
		check_admin_referer( 'metform-new-builder' );

		// Forms are published on creation, so require the form post type's own
		// capabilities instead of the generic `edit_posts` a Contributor has.
		if ( ! Forms_Builder::can_create_forms() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to create forms.', 'metform' ), 403 );
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
		if ( $post_id && 'metform-form' === get_post_type( $post_id ) && current_user_can( 'edit_post', $post_id ) ) {
			wp_safe_redirect( self::get_builder_url( $post_id ) );
			exit;
		}

		$form_id = Forms_Builder::instance()->create_form(
			'',
			0,
			array( 'builder_type' => 'metform' )
		);

		if ( ! $form_id || is_wp_error( $form_id ) ) {
			wp_die( esc_html__( 'Failed to create form.', 'metform' ), 500 );
		}

		$builder_url = self::get_builder_url( $form_id );
		wp_safe_redirect( $builder_url );
		exit;
	}

	/**
	 * Render the canvas document and its WordPress-managed asset queues.
	 *
	 * WordPress/theme/plugin frontend assets are enqueued first. The custom
	 * hook runs afterwards so consumers can enqueue new handles or remove
	 * existing handles before the queues are printed.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function render_canvas_page() {
		Canvas::instance()->render_canvas_page();
	}
}
