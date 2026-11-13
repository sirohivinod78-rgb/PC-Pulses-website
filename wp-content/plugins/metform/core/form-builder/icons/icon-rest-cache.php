<?php
/**
 * Caches responses from WordPress's native icon REST routes.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

use WP_REST_Request;
use MetForm\Traits\Singleton;
defined( 'ABSPATH' ) || exit;

/**
 * `WP_REST_Icons_Controller::get_items()` always loads and sanitizes every
 * registered icon across every collection before filtering the result down
 * to the requested `collection`/`search`, and the icons REST routes apply no
 * pagination at all. With MetForm's Font Awesome collection registering
 * roughly 1,600 icons, an unfiltered `GET /wp/v2/icons` would read and
 * `wp_kses()`-sanitize every one of those SVG files from disk on every
 * single request. That cost lives in WordPress core, not in MetForm, so
 * rather than trying to patch core's controller, this class puts a short
 * transient cache in front of it.
 *
 * Cached per exact route + query-string combination, so a `collection`- or
 * `search`-scoped request only ever incurs core's full-registry sanitization
 * pass once per cache window. {@see flush()} busts every cached entry at
 * once (via a version counter, not by enumerating query combinations)
 * whenever a custom icon is uploaded or deleted.
 *
 * @since 4.3.0
 */
class Icon_Rest_Cache {
	use Singleton;

	/**
	 * How long a cached icon REST response is kept.
	 *
	 * @since 4.3.0
	 * @var int
	 */
	private const TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * Option name for the cache-busting version counter.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	private const VERSION_OPTION = 'metform_icon_cache_version';

	/**
	 * Bumped when what the icon routes return changes shape or content for
	 * reasons the version counter doesn't track. Version 3 introduces the
	 * native `metform-font-awesome` collection.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	private const SCHEMA = 3;

	/**
	 * Matches `/wp/v2/icons`, `/wp/v2/icons/...`, `/wp/v2/icon-collections`,
	 * and `/wp/v2/icon-collections/...`.
	 *
	 * @since 4.3.0
	 * @var string
	 */
	private const ROUTE_PATTERN = '#^/wp/v2/icon(?:s|-collections)(?:/|$)#';

	/**
	 * Registers the caching hooks.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		if ( ! Icon_Api_Compat::is_available() ) {
			return;
		}

		add_filter( 'rest_dispatch_request', array( $this, 'serve_cached' ), 10, 4 );
		add_filter( 'rest_request_after_callbacks', array( $this, 'cache_response' ), 10, 3 );
	}

	/**
	 * Short-circuits the icon REST routes with a cached response when available.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed           $dispatch_result Dispatch result so far; non-null already short-circuits.
	 * @param WP_REST_Request $request         Current REST request.
	 * @param string          $route           Matched route.
	 * @param array           $handler         Route handler.
	 * @return mixed Cached response data, or `$dispatch_result` unchanged.
	 */
	public function serve_cached( $dispatch_result, $request, $route, $handler ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ( null !== $dispatch_result || ! self::is_cacheable( $request, $route ) ) {
			return $dispatch_result;
		}

		$cached = get_transient( self::cache_key( $request, $route ) );

		return false === $cached ? null : rest_ensure_response( $cached );
	}

	/**
	 * Caches a successful icon REST response.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed           $response Response about to be sent.
	 * @param array           $handler  Route handler.
	 * @param WP_REST_Request $request  Current REST request.
	 * @return mixed `$response`, unchanged.
	 */
	public function cache_response( $response, $handler, $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$route = $request->get_route();

		if ( is_wp_error( $response ) || ! self::is_cacheable( $request, $route ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );
		$data     = self::enrich_custom_icons( $response->get_data() );
		$response->set_data( $data );

		set_transient( self::cache_key( $request, $route ), $data, self::TTL );

		return $response;
	}

	/**
	 * Adds the owning Media Library attachment ID to any `metform-custom`
	 * icon in a `/wp/v2/icons` response.
	 *
	 * The native Icon API response schema (`name`/`label`/`content`/
	 * `collection`) has no attachment reference, but MetForm's saved icon
	 * value needs to retain it for custom icons (see `Document_Sanitizer`'s
	 * `icon_value` type) so the Media Library attachment stays the primary
	 * reference, per {@see Custom_Icons}.
	 *
	 * @since 4.3.0
	 *
	 * @param mixed $data Response data — a single icon array (`get_item()`), or a list of them (`get_items()`).
	 * @return mixed Enriched response data, in the same shape as `$data`.
	 */
	private static function enrich_custom_icons( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$is_list = isset( $data[0] ) && is_array( $data[0] );
		$items   = $is_list ? $data : array( $data );

		$has_custom_icon = false;
		foreach ( $items as $item ) {
			if ( is_array( $item ) && 'metform-custom' === ( $item['collection'] ?? '' ) ) {
				$has_custom_icon = true;
				break;
			}
		}

		if ( ! $has_custom_icon ) {
			return $data;
		}

		$slug_map = Custom_Icons::get_slug_map();

		foreach ( $items as $index => $item ) {
			if ( ! is_array( $item ) || 'metform-custom' !== ( $item['collection'] ?? '' ) || empty( $item['name'] ) ) {
				continue;
			}

			$slug = preg_replace( '#^metform-custom/#', '', $item['name'] );

			if ( isset( $slug_map[ $slug ] ) ) {
				$items[ $index ]['attachmentId'] = $slug_map[ $slug ];
			}
		}

		return $is_list ? $items : $items[0];
	}

	/**
	 * Invalidates every cached icon REST response.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public static function flush(): void {
		update_option( self::VERSION_OPTION, self::get_version() + 1, false );
	}

	/**
	 * Checks whether a request targets a cacheable icon REST route.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request Current REST request.
	 * @param string          $route   Matched route.
	 * @return bool True when the request is a cacheable icon-listing GET.
	 */
	private static function is_cacheable( WP_REST_Request $request, string $route ): bool {
		return 'GET' === $request->get_method() && (bool) preg_match( self::ROUTE_PATTERN, $route );
	}

	/**
	 * Builds the transient key for a request.
	 *
	 * @since 4.3.0
	 *
	 * @param WP_REST_Request $request Current REST request.
	 * @param string          $route   Matched route.
	 * @return string Transient key.
	 */
	private static function cache_key( WP_REST_Request $request, string $route ): string {
		$params = $request->get_query_params();
		ksort( $params );

		return 'mf_icon_rest_' . md5( $route . wp_json_encode( $params ) . '_v' . self::get_version() . '_s' . self::SCHEMA );
	}

	/**
	 * Gets the current cache-busting version counter.
	 *
	 * @since 4.3.0
	 *
	 * @return int Current version.
	 */
	private static function get_version(): int {
		return (int) get_option( self::VERSION_OPTION, 1 );
	}
}
