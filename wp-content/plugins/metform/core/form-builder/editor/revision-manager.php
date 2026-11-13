<?php
/**
 * Revision Manager for Standalone Form Builder.
 *
 * @package MetForm\Core\FormBuilder
 * @since 4.1.8
 */

namespace MetForm\Core\FormBuilder\Editor;

use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Handles saving, restoring, and querying WordPress post revisions
 * for MetForm standalone form-builder documents.
 */
class Revision_Manager {
	use Singleton;

	/**
	 * Meta key used to store form-builder document JSON.
	 */
	const META_KEY = '_metform_builder_document';

	/**
	 * Maximum number of revisions to display in the editor.
	 */
	const MAX_REVISIONS = 50;

	/**
	 * Initialize hooks for revisions management.
	 *
	 * @return void
	 */
	public function init() {
		add_action( '_wp_put_post_revision', array( $this, 'save_revision' ) );
		add_action( 'wp_restore_post_revision', array( $this, 'restore_revision' ), 10, 2 );
	}

	/**
	 * Copies form builder meta to the revision post whenever WordPress creates a revision.
	 *
	 * @param int $revision_id The ID of the revision post.
	 * @return void
	 */
	public function save_revision( $revision_id ) {
		$parent_id = wp_is_post_revision( $revision_id );

		if ( ! $parent_id || 'metform-form' !== get_post_type( $parent_id ) ) {
			return;
		}

		$document = get_post_meta( $parent_id, self::META_KEY, true );

		if ( ! empty( $document ) ) {
			// update_metadata bypasses core revision meta constraints. wp_slash
			// because it unslashes, which would strip regex backslashes.
			update_metadata( 'post', $revision_id, self::META_KEY, wp_slash( $document ) );
		}
	}

	/**
	 * Restores form builder meta from a revision back to the active parent form.
	 *
	 * @param int $parent_id   The active form ID.
	 * @param int $revision_id The historical revision ID being restored.
	 * @return void
	 */
	public function restore_revision( $parent_id, $revision_id ) {
		if ( 'metform-form' !== get_post_type( $parent_id ) ) {
			return;
		}

		$document = get_metadata( 'post', $revision_id, self::META_KEY, true );

		if ( ! empty( $document ) ) {
			update_post_meta( $parent_id, self::META_KEY, wp_slash( $document ) );
			clean_post_cache( $parent_id );

			// Keep the generated frontend stylesheet in sync with the restored
			// document. Delete a stale file when regeneration fails so the normal
			// render path can recreate it on the next request.
			if ( ! Style_Engine::instance()->save_form_css( $parent_id, $document ) ) {
				Style_Engine::instance()->delete_form_css( $parent_id );
			}
		}
	}

	/**
	 * Retrieves formatted revisions for a given form ID.
	 *
	 * @param int $form_id The form post ID.
	 * @return array List of formatted revision objects.
	 */
	public function get_revisions( $form_id ) {
		if ( ! $form_id || 'metform-form' !== get_post_type( $form_id ) ) {
			return array();
		}

		$revisions = wp_get_post_revisions(
			$form_id,
			array(
				'posts_per_page' => self::MAX_REVISIONS,
				'order'          => 'DESC',
			)
		);

		if ( empty( $revisions ) ) {
			return array();
		}

		$formatted    = array();
		$current_time = current_time( 'timestamp' );
		$is_first     = true;

		foreach ( $revisions as $revision ) {
			$author_id   = (int) $revision->post_author;
			$author_name = get_the_author_meta( 'display_name', $author_id );
			$avatar_url  = get_avatar_url( $author_id, array( 'size' => 48 ) );

			$modified_time = strtotime( $revision->post_modified );
			$human_diff    = human_time_diff( $modified_time, $current_time );
			$date_label    = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $modified_time );
			$date_label    = preg_replace_callback(
				'/\b(am|pm)\b/i',
				function ( $matches ) {
					return strtoupper( $matches[0] );
				},
				$date_label
			);

			$has_builder_data = ! empty( get_metadata( 'post', $revision->ID, self::META_KEY, true ) );

			$formatted[] = array(
				'id'             => (int) $revision->ID,
				'author'         => $author_name ? $author_name : __( 'System', 'metform' ),
				'avatar'         => $avatar_url,
				'date'           => $date_label,
				'relativeTime'   => sprintf( __( '%s ago', 'metform' ), $human_diff ),
				'timestamp'      => $modified_time,
				'isLatest'       => $is_first,
				'hasBuilderData' => $has_builder_data,
			);

			$is_first = false;
		}

		return $formatted;
	}

	/**
	 * Retrieves document JSON for a specific revision ID.
	 *
	 * @param int $form_id     Parent form post ID.
	 * @param int $revision_id Revision post ID.
	 * @return array|null Document array or null if not found.
	 */
	public function get_revision_document( $form_id, $revision_id ) {
		$parent_id = wp_is_post_revision( $revision_id );

		if ( ! $parent_id || (int) $parent_id !== (int) $form_id ) {
			return null;
		}

		$document = get_metadata( 'post', $revision_id, self::META_KEY, true );

		if ( empty( $document ) || ! is_array( $document ) ) {
			return null;
		}

		return $document;
	}
}
