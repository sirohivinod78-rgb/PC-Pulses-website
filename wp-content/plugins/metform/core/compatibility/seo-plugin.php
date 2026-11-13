<?php
/**
 * SEO plugin compatibility.
 *
 * @package MetForm\Core\Compatibility
 */

namespace MetForm\Core\Compatibility;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Prevent SEO plugins from processing MetForm internal post types.
 */
class Seo_Plugin {

	use Singleton;

	private const POST_TYPES = [ 'metform-form', 'metform-entry' ];

	/**
	 * Register SEO compatibility hooks.
	 *
	 * @return void
	 */
	public function init() {

		// AIOSEO.
		add_filter( 'aioseo_public_post_types', [ $this, 'exclude_post_types' ] );
		add_filter( 'aioseo_page_builder_integration_disable', [ $this, 'disable_aioseo_page_builder' ], 10, 2 );

		// Rank Math.
		add_filter( 'rank_math/excluded_post_types', [ $this, 'exclude_post_types' ] );

		// SEOPress.
		add_filter( 'seopress_post_types', [ $this, 'exclude_post_types' ] );
		add_filter( 'seopress_metaboxe_seo', [ $this, 'exclude_post_types' ] );
		add_filter( 'seopress_metaboxe_content_analysis', [ $this, 'exclude_post_types' ] );

		// Yoast SEO.
		add_filter( 'wpseo_enable_editor_features_metform-form', '__return_false' );
		add_filter( 'wpseo_enable_editor_features_metform-entry', '__return_false' );
		add_filter( 'wpseo_indexable_excluded_post_types', [ $this, 'add_excluded_post_types' ] );
		add_filter( 'wpseo_sitemap_exclude_post_type', [ $this, 'exclude_yoast_sitemap' ], 10, 2 );

		// Remove AIOSEO admin columns.
		add_filter( 'manage_metform-form_posts_columns', [ $this, 'remove_aioseo_columns' ], 10, 1 );
		add_filter( 'manage_metform-entry_posts_columns', [ $this, 'remove_aioseo_columns' ], 10, 1 );
	}

	/**
	 * Disable AIOSEO's page-builder integration for MetForm's internal post types.
	 *
	 * AIOSEO initializes its Elementor integration on every request and may try to
	 * read page-builder settings for these excluded post types. Besides being
	 * unnecessary, that can trigger a warning when no dynamic settings group was
	 * created for the post type.
	 *
	 * @param bool   $disabled         Whether the integration is disabled.
	 * @param string $integration_slug Page-builder integration slug.
	 * @return bool
	 */
	public function disable_aioseo_page_builder( $disabled, $integration_slug ) {

		if ( $disabled || 'elementor' !== $integration_slug ) {
			return $disabled;
		}

		$post_type = '';

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['post_type'] ) ) {
			$post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
		}

		if ( ! $post_type ) {
			foreach ( [ 'post', 'post_id', 'id', 'metform_builder_canvas', 'preview_id' ] as $key ) {
				if ( empty( $_GET[ $key ] ) ) {
					continue;
				}

				$post_type = get_post_type( absint( wp_unslash( $_GET[ $key ] ) ) );
				break;
			}
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $post_type ) {
			$post_type = get_post_type();
		}

		return in_array( $post_type, self::POST_TYPES, true );
	}

	/**
	 * Remove internal post types from supported lists.
	 *
	 * @param mixed $post_types Supported post types.
	 * @return mixed
	 */
	public function exclude_post_types( $post_types ) {

		if ( ! is_array( $post_types ) ) {
			return $post_types;
		}

		$is_list = [] === $post_types || array_keys( $post_types ) === range( 0, count( $post_types ) - 1 );

		foreach ( $post_types as $key => $post_type ) {
			$post_type_name = is_string( $post_type ) ? $post_type : '';

			if ( is_object( $post_type ) && isset( $post_type->name ) && is_string( $post_type->name ) ) {
				$post_type_name = $post_type->name;
			} elseif ( is_array( $post_type ) && isset( $post_type['name'] ) && is_string( $post_type['name'] ) ) {
				$post_type_name = $post_type['name'];
			}

			if ( in_array( $key, self::POST_TYPES, true ) || in_array( $post_type_name, self::POST_TYPES, true ) ) {
				unset( $post_types[ $key ] );
			}
		}

		return $is_list ? array_values( $post_types ) : $post_types;
	}

	/**
	 * Add internal post types to exclusion lists.
	 *
	 * @param mixed $post_types Excluded post types.
	 * @return array
	 */
	public function add_excluded_post_types( $post_types ) {

		return array_values(
			array_unique(
				array_merge(
					(array) $post_types,
					self::POST_TYPES
				)
			)
		);
	}

	/**
	 * Exclude MetForm post types from Yoast sitemap.
	 *
	 * @param bool   $exclude   Whether excluded.
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public function exclude_yoast_sitemap( $exclude, $post_type ) {

		if ( in_array( $post_type, self::POST_TYPES, true ) ) {
			return true;
		}

		return $exclude;
	}

	/**
	 * Remove AIOSEO-generated admin columns.
	 *
	 * @param mixed $columns Admin columns.
	 * @return mixed
	 */
	public function remove_aioseo_columns( $columns ) {

		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		foreach ( array_keys( $columns ) as $key ) {
			if (
				0 === strpos( $key, 'aioseo-' ) ||
				0 === strpos( $key, 'aioseo_' )
			) {
				unset( $columns[ $key ] );
			}
		}

		return $columns;
	}
}
