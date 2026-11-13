<?php
/**
 * Custom SVG icon upload REST endpoint and Media Library wiring.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

use MetForm\Traits\Singleton;
use WP_Error;
use WP_Icons_Registry;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * Turns an uploaded SVG into a Media Library attachment usable as a MetForm
 * custom icon: `POST metform/v1/icons/custom` validates the request,
 * sanitizes the SVG (reusing `Svg_Upload_Support::sanitize_svg_markup()` —
 * one sanitizer, not two), sideloads it, and creates the attachment with the
 * `_metform_custom_icon` / `_metform_icon_slug` metadata that
 * {@see Custom_Icons} looks for.
 *
 * The native Icon API REST routes (`/wp/v2/icons`, `/wp/v2/icon-collections`)
 * are read-only, so uploads need this separate, capability-gated MetForm
 * route. Cookie-authenticated REST requests are nonce-checked by WordPress
 * itself (`rest_cookie_check_errors`) before any route callback runs; this
 * class is responsible for the capability and file-content checks on top of
 * that.
 *
 * @since 4.3.0
 */
class Custom_Icon_Upload {
	use Singleton;

	/**
	 * Registers the REST route and Media Library cleanup hook.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'delete_attachment', array( $this, 'handle_attachment_deleted' ) );
	}

	/**
	 * Registers the custom icon upload REST route.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'metform/v1',
			'/icons/custom',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'permission_callback' => array( $this, 'can_upload_icons' ),
				'callback'            => array( $this, 'upload_custom_icon' ),
			)
		);
	}

	/**
	 * Checks whether the current user can upload custom icons.
	 *
	 * @since 4.3.0
	 *
	 * @return bool True when the current user has the `upload_files` capability.
	 */
	public function can_upload_icons(): bool {
		return current_user_can( 'upload_files' );
	}

	/**
	 * Validates, sanitizes, and stores an uploaded SVG as a custom icon.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request REST request carrying the uploaded file.
	 * @return WP_REST_Response|WP_Error Icon data on success, or an error on failure.
	 */
	public function upload_custom_icon( WP_REST_Request $request ) {
		$file = $request->get_file_params()['file'] ?? null;

		if ( ! is_array( $file ) || ! isset( $file['error'] ) ) {
			return new WP_Error(
				'metform_icon_missing_file',
				__( 'No SVG file was uploaded.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			return new WP_Error(
				'metform_icon_upload_error',
				__( 'The SVG file could not be uploaded.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		// $_FILES entries (unlike $_GET/$_POST) are never magic-quote-slashed
		// by WordPress, so this is `sanitize_file_name()` alone, not `wp_unslash()`
		// then `sanitize_file_name()` — see `WP_REST_Server::serve_request()`,
		// which unslashes `$_POST` but passes `$_FILES` through as-is.
		$filename  = sanitize_file_name( $file['name'] ?? '' );
		$extension = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );

		if ( 'svg' !== $extension ) {
			return new WP_Error(
				'metform_icon_invalid_extension',
				__( 'Custom icons must be an SVG file.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		$tmp_name = $file['tmp_name'] ?? '';
		if ( ! $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
			return new WP_Error(
				'metform_icon_invalid_upload',
				__( 'The uploaded file could not be found on the server.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		/** Filters the maximum allowed custom icon SVG file size, in bytes. */
		$max_size = (int) apply_filters( 'metform/icon/max_upload_size', 100 * KB_IN_BYTES );
		if ( (int) $file['size'] > $max_size ) {
			return new WP_Error(
				'metform_icon_file_too_large',
				sprintf(
					/* translators: %s: Maximum allowed file size, formatted for display. */
					__( 'The SVG file is too large. Maximum allowed size is %s.', 'metform' ),
					size_format( $max_size )
				),
				array( 'status' => 400 )
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading an uploaded SVG (already on local disk as a WP upload tmp file) to sanitize it, not fetching remote/arbitrary content.
		$svg = file_get_contents( $tmp_name );
		if ( false === $svg || false === stripos( $svg, '<svg' ) ) {
			return new WP_Error(
				'metform_icon_invalid_svg',
				__( 'This file does not contain valid SVG markup.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		$sanitized = Svg_Upload_Support::sanitize_svg_markup( $svg );
		if ( null === $sanitized ) {
			return new WP_Error(
				'metform_icon_unsafe_svg',
				__( 'This SVG file was rejected for security reasons.', 'metform' ),
				array( 'status' => 400 )
			);
		}

		if ( $sanitized !== $svg ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Writing back the sanitized copy of the file WordPress itself just accepted as an upload, at its own tmp path.
			$written = file_put_contents( $tmp_name, $sanitized, LOCK_EX );
			if ( false === $written || strlen( $sanitized ) !== $written ) {
				return new WP_Error(
					'metform_icon_sanitize_write_failed',
					__( 'The sanitized SVG file could not be saved.', 'metform' ),
					array( 'status' => 500 )
				);
			}
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Best-effort cache clear after rewriting the tmp file's size; failure here doesn't affect the sanitized content already written.
			clearstatcache( true, $tmp_name );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$sideload_file = array(
			'name'     => $filename,
			'type'     => 'image/svg+xml',
			'tmp_name' => $tmp_name,
			'error'    => 0,
			'size'     => filesize( $tmp_name ),
		);

		$sideloaded = wp_handle_sideload(
			$sideload_file,
			array(
				'test_form' => false,
				'mimes'     => array( 'svg' => 'image/svg+xml' ),
			)
		);

		if ( isset( $sideloaded['error'] ) ) {
			return new WP_Error(
				'metform_icon_sideload_failed',
				$sideloaded['error'],
				array( 'status' => 500 )
			);
		}

		$label     = sanitize_text_field( $request->get_param( 'label' ) ?? '' );
		$label     = '' !== $label ? $label : preg_replace( '/\.svg$/i', '', $filename );
		$base_slug = sanitize_title( $label );
		$slug      = Custom_Icons::get_unique_slug( $base_slug );

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/svg+xml',
				'post_title'     => $label,
				'post_content'   => '',
				'post_status'    => 'inherit',
			),
			$sideloaded['file']
		);

		if ( ! $attachment_id || is_wp_error( $attachment_id ) ) {
			wp_delete_file( $sideloaded['file'] );

			return new WP_Error(
				'metform_icon_attachment_failed',
				__( 'The SVG was uploaded, but WordPress could not create its Media Library attachment.', 'metform' ),
				array( 'status' => 500 )
			);
		}

		update_post_meta( $attachment_id, Custom_Icons::META_FLAG, 1 );
		update_post_meta( $attachment_id, Custom_Icons::META_SLUG, $slug );

		$icon_name = 'metform-custom/' . $slug;

		if ( Icon_Api_Compat::is_available() ) {
			wp_register_icon(
				$icon_name,
				array(
					'label'     => $label,
					'file_path' => $sideloaded['file'],
				)
			);
		}

		Icon_Rest_Cache::flush();

		return rest_ensure_response(
			array(
				'attachmentId' => $attachment_id,
				'collection'   => 'metform-custom',
				'name'         => $slug,
				'iconName'     => $icon_name,
				'label'        => $label,
				'url'          => wp_get_attachment_url( $attachment_id ),
			)
		);
	}

	/**
	 * Unregisters a deleted attachment's icon so it stops being served for
	 * the rest of the current request.
	 *
	 * The registry is always rebuilt from the current set of attachments on
	 * the next `init` anyway (see `Custom_Icons::register_all()`), so this
	 * only matters for same-request consistency right after a delete.
	 *
	 * @since 4.3.0
	 *
	 * @param int $attachment_id Deleted attachment ID.
	 * @return void
	 */
	public function handle_attachment_deleted( $attachment_id ): void {
		if ( ! get_post_meta( $attachment_id, Custom_Icons::META_FLAG, true ) ) {
			return;
		}

		if ( Icon_Api_Compat::is_available() ) {
			$slug      = get_post_meta( $attachment_id, Custom_Icons::META_SLUG, true );
			$icon_name = 'metform-custom/' . $slug;

			if ( $slug && WP_Icons_Registry::get_instance()->is_registered( $icon_name ) ) {
				wp_unregister_icon( $icon_name );
			}
		}

		Icon_Rest_Cache::flush();
	}
}
