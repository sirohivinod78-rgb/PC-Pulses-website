<?php
/**
 * Registers the `metform-custom` icon collection from Media Library attachments.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * A custom icon is nothing more than a Media Library attachment
 * (`post_type = attachment`, `post_mime_type = image/svg+xml`) flagged with
 * `_metform_custom_icon = 1` and given a unique `_metform_icon_slug`. The
 * attachment ID is the single source of truth; this class never stores an
 * absolute filesystem path anywhere — it always resolves one on demand via
 * `get_attached_file()`.
 *
 * @since 4.3.0
 */
class Custom_Icons {

	/**
	 * Post meta key flagging an attachment as a MetForm custom icon.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public const META_FLAG = '_metform_custom_icon';

	/**
	 * Post meta key holding the icon's unqualified slug (used as the
	 * `metform-custom/<slug>` icon name).
	 *
	 * @since 4.3.0
	 * @var string
	 */
	public const META_SLUG = '_metform_icon_slug';

	/**
	 * Registers every valid custom-icon attachment with the Icon API.
	 *
	 * Safe to call on every `init` — re-registering from the current set of
	 * attachments is how a deleted attachment stops being served without any
	 * separate cleanup step.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public static function register_all(): void {
		if ( ! Icon_Api_Compat::is_available() ) {
			return;
		}

		foreach ( self::get_slug_map() as $slug => $attachment_id ) {
			$file_path = get_attached_file( $attachment_id );

			if ( ! is_string( $file_path ) || ! is_readable( $file_path ) ) {
				continue;
			}

			wp_register_icon(
				'metform-custom/' . $slug,
				array(
					'label'     => get_the_title( $attachment_id ),
					'file_path' => $file_path,
				)
			);
		}
	}

	/**
	 * Gets every registered custom icon's slug mapped to its attachment ID.
	 *
	 * @since 4.3.0
	 *
	 * @return array<string, int> Icon slug => attachment ID.
	 */
	public static function get_slug_map(): array {
		$attachment_ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'post_mime_type' => 'image/svg+xml',
				'meta_key'       => self::META_FLAG, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$map = array();

		foreach ( $attachment_ids as $attachment_id ) {
			$slug = get_post_meta( $attachment_id, self::META_SLUG, true );

			if ( is_string( $slug ) && '' !== $slug ) {
				$map[ $slug ] = (int) $attachment_id;
			}
		}

		return $map;
	}

	/**
	 * Builds a slug that does not already belong to another custom icon.
	 *
	 * @since 4.3.0
	 *
	 * @param string $base_slug Preferred slug (already run through `sanitize_title()`).
	 * @return string A unique slug, suffixed with `-2`, `-3`, etc. if needed.
	 */
	public static function get_unique_slug( string $base_slug ): string {
		$existing = self::get_slug_map();

		if ( ! isset( $existing[ $base_slug ] ) ) {
			return $base_slug;
		}

		$suffix = 2;
		while ( isset( $existing[ $base_slug . '-' . $suffix ] ) ) {
			++$suffix;
		}

		return $base_slug . '-' . $suffix;
	}
}
