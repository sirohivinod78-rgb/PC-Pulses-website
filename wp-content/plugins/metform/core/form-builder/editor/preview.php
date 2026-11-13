<?php
/**
 * Standalone Form Builder preview controller.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder\Editor;

defined( 'ABSPATH' ) || exit;

use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Rendering\Form_Renderer;
use MetForm\Traits\Singleton;

/**
 * Generates and serves nonce-protected frontend form previews.
 *
 * @since 4.1.8
 */
class Preview {
	use Singleton;

	/**
	 * Registers the preview request handler.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'template_redirect', array( $this, 'handle_request' ), 0 );
	}

	/**
	 * Gets the standalone preview URL for a form.
	 *
	 * @since 4.1.8
	 *
	 * @param int $post_id Form post ID.
	 * @return string Nonce-protected preview URL.
	 */
	public static function get_url( $post_id ) {
		$post_id = absint( $post_id );

		return add_query_arg(
			array(
				'preview_id'    => $post_id,
				'preview_nonce' => wp_create_nonce( 'post_preview_' . $post_id ),
				'preview'       => 'true',
			),
			home_url( '/metform-builder-' . $post_id . '/' )
		);
	}

	/**
	 * Renders the preview when the current request matches its endpoint.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function handle_request() {
		if ( ! $this->is_request() ) {
			return;
		}

		$this->render();
	}

	/**
	 * Determines whether the current request targets a Form Builder preview.
	 *
	 * @since 4.1.8
	 *
	 * @return bool Whether this is a preview request.
	 */
	private function is_request(): bool {
		if ( ! isset( $_GET['preview'], $_GET['preview_id'] ) || 'true' !== sanitize_key( wp_unslash( $_GET['preview'] ) ) ) {
			return false;
		}

		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		return 1 === preg_match( '#/metform-builder-([0-9]+)/?$#', $path );
	}

	/**
	 * Validates and renders the standalone preview document.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	private function render() {
		$post_id = absint( $_GET['preview_id'] ?? 0 );
		$nonce   = sanitize_text_field( wp_unslash( $_GET['preview_nonce'] ?? '' ) );
		$path    = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );

		if ( 1 === preg_match( '#/metform-builder-([0-9]+)/?$#', $path, $matches ) ) {
			if ( ! $post_id || $post_id !== absint( $matches[1] ?? 0 ) ) {
				wp_die( esc_html__( 'Invalid form preview.', 'metform' ), 404 );
			}
		} elseif ( ! $post_id ) {
			wp_die( esc_html__( 'Invalid form preview.', 'metform' ), 404 );
		}
		if ( ! wp_verify_nonce( $nonce, 'post_preview_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to preview this form.', 'metform' ), 403 );
		}
		if ( 'metform-form' !== get_post_type( $post_id ) ) {
			wp_die( esc_html__( 'Invalid form.', 'metform' ), 404 );
		}

		add_action(
			'wp_enqueue_scripts',
			function () use ( $post_id ) {
				Canvas::enqueue_runtime_assets( $post_id );

				wp_add_inline_style(
					'metform-builder',
					'
                html, body { margin: 0; min-height: 100%; }
                body { overflow: auto; background: var(--bg1); }
                .metform-builder-preview__content { max-width: calc(100% - 20%); margin: auto !important; padding: 0 20px; }
                .metform-builder-preview__title { font-size: 32px; font-weight: 700; line-height: 1.3; margin-top: 32px; margin-bottom: 24px; color: var(--tx1, #1e293b); }
            '
				);

				// The preview loads the editor bundle, whose `#metform-builder-root`
				// prefixed base rules outrank the frontend-scoped form stylesheet -
				// re-emit the control CSS with the editor scope so it still wins.
				$document = Repository::get( $post_id );
				if ( $document ) {
					wp_add_inline_style( 'metform-builder', Style_Engine::instance()->build_document_css( $post_id, $document, true ) );
				}
			},
			PHP_INT_MAX
		);

		nocache_headers();
		status_header( 200 );
		?>
		<!doctype html>
			<html <?php language_attributes(); ?>>
				<head>
					<meta charset="<?php bloginfo( 'charset' ); ?>">
					<meta name="viewport" content="width=device-width, initial-scale=1">
					<title><?php echo esc_html( sprintf( __( '%s - Form Preview', 'metform' ), get_the_title( $post_id ) ) ); ?></title>
					<?php wp_head(); ?>
				</head>
				<body <?php body_class( 'metform-builder-canvas metform-builder-preview' ); ?> data-theme="light">
					<?php wp_body_open(); ?>
					<div id="metform-builder-root">
						<main class="metform-builder-preview__content">
							<h1 class="metform-builder-preview__title" style="font-size: 32px; font-weight: 700; margin-top: 32px; margin-bottom: 24px;"><?php echo esc_html( get_the_title( $post_id ) ); ?></h1>
							<?php echo Form_Renderer::instance()->render_saved_form( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</main>
					</div>
					<script>
						window.addEventListener('storage', function (event) {
							if (event.key === <?php echo wp_json_encode( 'metform-preview-refresh-' . $post_id ); ?> && event.newValue) {
								window.location.reload();
							}
						});
					</script>
					<?php wp_footer(); ?>
				</body>
			</html>
		<?php
		exit;
	}
}

