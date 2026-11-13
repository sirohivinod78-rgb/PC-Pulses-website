<?php
namespace MetForm\Core\FormBuilder\Editor;

use Elementor\Plugin as Elementor_Plugin;
use MetForm\Base\Assets_Enqueue;
use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Icons\Icon_Api_Compat;
use MetForm\Core\FormBuilder\Icons\Icon_Library;
use MetForm\Core\Forms\Action as Form_Action;
use MetForm\Plugin as MetForm_Plugin;
use MetForm\Traits\Singleton;
use MetForm_Pro\Base\Package as Pro_Package;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * The two full-page (no theme chrome) documents the standalone builder
 * needs: the wp-admin-free editor page, and the WordPress/theme-managed
 * canvas document used for the live preview iframe.
 *
 * Extracted from Base — see PHP_RENDERING_MIGRATION_PLAN.md §3.1.
 */
class Canvas {
	use Singleton;

	/**
	 * Enqueues the runtime assets shared by canvas and preview pages.
	 *
	 * Reads the saved document through `Block_Registry`, so only assets for
	 * block types used by this form are enqueued — unless `$all_blocks` is
	 * set, in which case every registered block's assets are enqueued
	 * regardless of what's currently saved.
	 *
	 * @param int  $post_id    Current MetForm form ID.
	 * @param bool $all_blocks Enqueue every registered block's assets instead
	 *                         of only the ones used in the saved document.
	 * @return void
	 */
	public static function enqueue_runtime_assets( int $post_id, bool $all_blocks = false ): void {
		Assets_Enqueue::get_style( 'metform-builder' );
		Assets_Enqueue::get_style( 'metform-builder-frontend' );
		Assets_Enqueue::get_script( 'metform-builder-frontend' );

		$document = Repository::get( $post_id );
		$blocks   = $all_blocks
			? Block_Registry::instance()->all()
			: Block_Registry::instance()->used_in_document( is_array( $document ) ? $document : array() );

		foreach ( $blocks as $block ) {
			foreach ( $block->get_frontend_assets() as $handle ) {
				wp_enqueue_style( $handle );
				wp_enqueue_script( $handle );
			}
		}

		if ( is_array( $document ) ) {
			Style_Engine::instance()->enqueue_or_create_form_css( $post_id, $document );
		}

		do_action( 'metform/form_builder/editor/enqueue_scripts', $post_id, $all_blocks );
	}

	/**
	 * Validates the requested form and the current user's access.
	 *
	 * @return WP_Post
	 */
	private function get_requested_form(): WP_Post {
		$post_id = isset( $_GET['metform_builder_canvas'] )
			? absint( $_GET['metform_builder_canvas'] )
			: ( isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0 );
		$post    = get_post( $post_id );

		if ( ! $post || 'metform-form' !== $post->post_type ) {
			wp_die( esc_html__( 'Invalid form.', 'metform' ), 404 );
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to edit this form.', 'metform' ), 403 );
		}

		return $post;
	}

	/**
	 * Renders the canvas only when its frontend endpoint is requested.
	 *
	 * @return void
	 */
	public function handle_request(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing param, no state change.
		if ( ! isset( $_GET['metform_builder_canvas'] ) ) {
			return;
		}

		$this->render_canvas_page();
	}

	/**
	 * Temporarily removes Elementor's AI media-library extension from
	 * `wp_enqueue_media()`.
	 *
	 * @return array{0: object|null, 1: int|false} [module instance, removed hook priority].
	 */
	private function suppress_elementor_ai_media_library(): array {
		if ( ! did_action( 'elementor/loaded' ) || ! isset( Elementor_Plugin::$instance->modules_manager ) ) {
			return array( null, false );
		}

		$module = Elementor_Plugin::$instance->modules_manager->get_modules( 'ai' );
		if ( ! $module ) {
			return array( null, false );
		}

		$priority = has_action( 'wp_enqueue_media', array( $module, 'enqueue_ai_media_library' ) );
		if ( false !== $priority ) {
			remove_action( 'wp_enqueue_media', array( $module, 'enqueue_ai_media_library' ), $priority );
		}

		return array( $module, $priority );
	}

	/**
	 * Renders the builder without WordPress admin chrome.
	 *
	 * @return void
	 */
	public function render_form_builder_page(): void {
		$post    = $this->get_requested_form();
		$post_id = $post->ID;

		$plugin = MetForm_Plugin::instance();
		if ( ! file_exists( $plugin->plugin_dir() . 'build/form-builder/index.asset.php' ) ) {
			wp_die( esc_html__( 'The form builder assets are unavailable.', 'metform' ), 500 );
		}

		[$ai_module, $ai_priority] = $this->suppress_elementor_ai_media_library();
		wp_enqueue_media();
		if ( null !== $ai_module && false !== $ai_priority ) {
			add_action( 'wp_enqueue_media', array( $ai_module, 'enqueue_ai_media_library' ), $ai_priority );
		}

		Assets_Enqueue::get( 'metform-builder' );

		$rest_nonce    = wp_create_nonce( 'wp_rest' );
		$document_url  = add_query_arg( 'formId', $post_id, rest_url( 'metform/v1/form-builder/document' ) );
		$form_settings = Form_Action::instance()->get_all_data( $post_id );
		$form_type     = ! empty( $form_settings['form_type'] ) ? $form_settings['form_type'] : 'general-form';
		$quiz_summary  = ! empty( $form_settings['quiz_summery'] );
		$is_pro        = class_exists( Pro_Package::class );

		wp_localize_script(
			'metform-builder',
			'metformFormBuilder',
			array(
				'pluginUrl'          => $plugin->plugin_url(),
				'adminUrl'           => admin_url(),
				'nonce'              => $rest_nonce,
				'postId'             => $post_id,
				'formType'           => $form_type,
				'formTitle'          => get_the_title( $post_id ),
				'postStatus'         => get_post_status( $post_id ),
				'quizSummary'        => $quiz_summary,
				'isPro'              => $is_pro,
				'canvasUrl'          => Controller::get_canvas_url( $post_id ),
				'previewUrl'         => Preview::get_url( $post_id ),
				'renderUrl'          => rest_url( 'metform/v1/form-builder/render' ),
				'saveUrl'            => rest_url( 'metform/v1/form-builder/document' ),
				'revisionsUrl'       => rest_url( 'metform/v1/form-builder/revisions' ),
				'revisionUrl'        => rest_url( 'metform/v1/form-builder/revision' ),
				'saveTitleUrl'       => rest_url( 'metform/v1/form-builder/title' ),
				'documentUrl'        => $document_url,
				'inspectDocumentUrl' => add_query_arg( '_wpnonce', $rest_nonce, $document_url ),
				'blocks'             => Block_Registry::instance()->get_public_definitions(),
				'iconApiAvailable'   => Icon_Api_Compat::is_available(),
				'iconsUrl'           => rest_url( 'wp/v2/icons' ),
				'iconUploadUrl'      => rest_url( 'metform/v1/icons/custom' ),
				'iconLibraryUrl'     => rest_url( 'metform/v1/icons' ),
				'iconLibraries'      => Icon_Library::describe_libraries(),
			)
		);

		remove_action( 'admin_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		nocache_headers();
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<title><?php echo esc_html( sprintf( __( 'Edit %s - Form Builder', 'metform' ), get_the_title( $post_id ) ) ); ?></title>
				<?php
				wp_print_styles();
				wp_print_head_scripts();
				?>
			</head>
			<body class="metform-builder-standalone" style="margin: 0;" data-post-id="<?php echo esc_attr( $post_id ); ?>">
				<div id="metform-builder-root"></div>
				<?php
				wp_print_media_templates();
				wp_print_footer_scripts();
				?>
			</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Enqueues canvas runtime assets and inline styles on `wp_enqueue_scripts`.
	 *
	 * Runs after normal frontend enqueue callbacks but before wp_head()
	 * prints the queues, so dequeues/deregistrations made here are final.
	 *
	 * @param int $post_id Current MetForm form ID.
	 * @return void
	 */
	private function enqueue_canvas_assets( int $post_id ): void {
		self::enqueue_runtime_assets( $post_id, true );

		wp_add_inline_style(
			'metform-builder',
			'
			html, body { margin: 0; min-height: 100%; }
			body { overflow: auto; background: var(--bg1); }
			#canvas-root { min-height: 100vh; }
			'
		);

		/**
		 * Fires after canvas runtime assets are enqueued.
		 *
		 * Allows integrations to enqueue or remove assets for the form-builder
		 * editor canvas before the style/script queues are printed.
		 *
		 * @param int $post_id Current MetForm form ID.
		 */
		do_action( 'metform/form_builder/canvas/enqueue_scripts', $post_id );
	}

	/**
	 * Renders the canvas document and its WordPress-managed asset queues.
	 *
	 * WordPress/theme/plugin frontend assets are enqueued first. The custom
	 * hook runs afterwards so consumers can enqueue new handles or remove
	 * existing handles before the queues are printed.
	 *
	 * @return void
	 */
	public function render_canvas_page(): void {
		$post = $this->get_requested_form();

		show_admin_bar( false );
		remove_action( 'wp_head', 'wp_admin_bar_header' );
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		add_action(
			'wp_enqueue_scripts',
			function () use ( $post ) {
				wp_dequeue_style( 'admin-bar' );
				wp_deregister_style( 'admin-bar' );
				wp_dequeue_script( 'admin-bar' );
				wp_deregister_script( 'admin-bar' );

				$this->enqueue_canvas_assets( $post->ID );
			},
			PHP_INT_MAX
		);

		nocache_headers();
		?>
		<!doctype html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>">
				<meta name="viewport" content="width=device-width, initial-scale=1">
				<?php wp_head(); ?>
			</head>
			<body <?php body_class( 'metform-builder-canvas' ); ?> data-theme="light">
				<?php wp_body_open(); ?>
				<div id="metform-builder-root" class="<?php echo esc_attr( Style_Engine::get_form_class( $post->ID ) ); ?>"><div id="canvas-root"></div></div>
				<?php wp_footer(); ?>
			</body>
		</html>
		<?php
		exit;
	}
}
