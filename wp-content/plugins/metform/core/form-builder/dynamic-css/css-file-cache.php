<?php
/**
 * On-disk generated CSS file cache for form-builder documents.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 1.0.0
 */

namespace MetForm\Core\FormBuilder\Dynamic_Css;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the on-disk generated CSS file for a form: its location, writing
 * it, enqueueing it, checking whether the cache is stale, and deleting
 * it.
 *
 * Contains no CSS-formatting logic itself — that lives in
 * Css_Value_Formatter, orchestrated by Style_Engine::build_document_css().
 * Isolating the filesystem/WP-hook side effects here keeps this the only
 * class that needs mocking to test cache behaviour.
 *
 * Bump GENERATED_CSS_HEADER's version suffix whenever the CSS *output*
 * shape changes (new control type, bugfix in a Css_Value_Formatter
 * method, etc.). The header is the only thing that marks a cached file as
 * current, so an unbumped version means old cached files keep serving
 * stale CSS until their form is re-saved.
 *
 * @since 1.0.0
 */
final class Css_File_Cache {
	use Singleton;

	/**
	 * Marker written at the top of every generated CSS file, used to
	 * detect whether a cached file matches the current generator version.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	// Bump the version whenever generated CSS changes for the same saved
	// document: files with an older header are treated as stale and rebuilt
	// on the next render (v3: container `--mfb-width` for flex containers; v5: grid
	// container track sizes via `--mfb-grid-cols` / `--mfb-grid-rows`; v6:
	// container stacking rules moved to the stylesheet, fill items share a row).
	private const GENERATED_CSS_HEADER = '/* MetForm generated CSS v6 */';

	/**
	 * Returns the generated CSS file's directory, path, and URL for a
	 * form.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return array{directory: string, path: string, url: string}|null
	 *               The file location info, or null if the uploads
	 *               directory is unavailable.
	 */
	public function get_location( int $form_id ): ?array {
		$uploads = wp_upload_dir();
		if ( ! empty( $uploads['error'] ) ) {
			return null;
		}

		$relative = 'metform/css/form-' . $form_id . '.css';

		return array(
			'directory' => trailingslashit( $uploads['basedir'] ) . 'metform/css',
			'path'      => trailingslashit( $uploads['basedir'] ) . $relative,
			'url'       => trailingslashit( $uploads['baseurl'] ) . $relative,
		);
	}

	/**
	 * Checks whether a cached CSS file exists for this form and its
	 * header matches the current generator version.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return bool True when a readable, up-to-date cache file exists.
	 */
	public function is_current( int $form_id ): bool {
		$location = $this->get_location( $form_id );
		if ( ! $location || ! is_readable( $location['path'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a small generated cache file header.
		$header = file_get_contents( $location['path'], false, null, 0, strlen( self::GENERATED_CSS_HEADER ) );
		return self::GENERATED_CSS_HEADER === $header;
	}

	/**
	 * Writes generated CSS, prefixed with the cache header, to the form's
	 * cache file. Creates the target directory if it doesn't exist yet.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $form_id The form's post ID.
	 * @param string $css     The generated CSS body (without the header).
	 * @return bool True on success, false if the uploads directory is
	 *              unavailable, the target directory couldn't be created,
	 *              or the write failed.
	 */
	public function write( int $form_id, string $css ): bool {
		$location = $this->get_location( $form_id );
		if ( ! $location || ( ! is_dir( $location['directory'] ) && ! wp_mkdir_p( $location['directory'] ) ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Generated CSS cache in uploads.
		$contents = self::GENERATED_CSS_HEADER . "\n" . $css;
		return false !== file_put_contents( $location['path'], $contents, LOCK_EX );
	}

	/**
	 * Enqueues a previously generated form CSS file, if it exists and is
	 * readable.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return bool True if the stylesheet was enqueued, false if no
	 *              readable cache file exists for this form.
	 */
	public function enqueue( int $form_id ): bool {
		$location = $this->get_location( $form_id );
		if ( ! $location || ! is_readable( $location['path'] ) ) {
			return false;
		}

		$modified = filemtime( $location['path'] );
		wp_enqueue_style(
			'metform-builder-form-' . $form_id,
			$location['url'],
			array( 'metform-builder-frontend' ),
			false !== $modified ? (string) $modified : null
		);

		return true;
	}

	/**
	 * Removes a form's generated CSS file, typically called after the
	 * form itself is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return void
	 */
	public function delete( int $form_id ): void {
		$location = $this->get_location( $form_id );
		if ( $location && is_file( $location['path'] ) ) {
			wp_delete_file( $location['path'] );
		}
	}
}
