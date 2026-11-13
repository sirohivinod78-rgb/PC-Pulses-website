<?php
/**
 * REST routes for browsing the JSON-backed icon libraries.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.0
 */

namespace MetForm\Core\FormBuilder\Icons;

use MetForm\Traits\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

defined( 'ABSPATH' ) || exit;

/**
 * `GET metform/v1/icons` (search/paginate a library, for the Form Builder's
 * icon picker) and `GET metform/v1/icons/<key>` (one icon, for a saved
 * value's preview). The Form Builder modal uses these paginated routes for
 * Font Awesome so it never has to download the native collection's entire
 * icon set in one `/wp/v2/icons` response.
 *
 * Responses carry only what the picker draws — `key`, `label`, `style`,
 * `viewBox`, `path` — never a library's server-side search terms. Only
 * this class touches {@see Icon_Library}, and only inside a route
 * callback, so registering the routes never loads icon data.
 *
 * @since 4.4.0
 */
class Icon_Library_Rest {
	use Singleton;

	/**
	 * Largest page the list route serves.
	 *
	 * @since 4.4.0
	 * @var int
	 */
	public const MAX_PER_PAGE = 200;

	/**
	 * Registers the REST routes.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Registers the icon list and single-icon routes.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			'metform/v1',
			'/icons',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'can_browse_icons' ),
				'callback'            => array( $this, 'list_icons' ),
				'args'                => array(
					'library'  => array(
						'type'              => 'string',
						'default'           => Icon_Library::DEFAULT_LIBRARY,
						'sanitize_callback' => 'sanitize_key',
					),
					'style'    => array(
						'type'              => 'string',
						'default'           => 'all',
						'sanitize_callback' => 'sanitize_key',
					),
					'search'   => array(
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => array( $this, 'sanitize_search' ),
					),
					'page'     => array(
						'type'              => 'integer',
						'default'           => 1,
						'sanitize_callback' => array( $this, 'clamp_page' ),
						'validate_callback' => 'rest_validate_request_arg',
					),
					'per_page' => array(
						'type'              => 'integer',
						'default'           => self::MAX_PER_PAGE,
						'sanitize_callback' => array( $this, 'clamp_per_page' ),
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
			)
		);

		register_rest_route(
			'metform/v1',
			'/icons/(?P<key>[a-z0-9-]{3,100})',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'permission_callback' => array( $this, 'can_browse_icons' ),
				'callback'            => array( $this, 'get_icon' ),
			)
		);
	}

	/**
	 * Checks whether the current user may browse icons: anyone who can
	 * author content (and so could be building a form).
	 *
	 * @since 4.4.0
	 *
	 * @return bool
	 */
	public function can_browse_icons(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Lists one page of a library's icons, optionally filtered by style and search text.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function list_icons( WP_REST_Request $request ) {
		$library   = (string) $request['library'];
		$libraries = Icon_Library::libraries();
		if ( ! isset( $libraries[ $library ] ) ) {
			return new WP_Error( 'metform_icon_library_not_found', __( 'Unknown icon library.', 'metform' ), array( 'status' => 404 ) );
		}

		$style = (string) $request['style'];
		if ( 'all' !== $style && ! isset( $libraries[ $library ]['styles'][ $style ] ) ) {
			return new WP_Error( 'metform_icon_style_not_found', __( 'Unknown icon style.', 'metform' ), array( 'status' => 400 ) );
		}

		$result = Icon_Library::search( (string) $request['search'], $style, (int) $request['page'], (int) $request['per_page'], $library );

		$items = array();
		foreach ( $result['items'] as $key => $icon ) {
			$items[] = self::prepare_icon( $key, $icon );
		}

		$styles = array();
		foreach ( $libraries[ $library ]['styles'] as $style_key => $style_config ) {
			$styles[] = array(
				'key'   => $style_key,
				'label' => $style_config['label'],
			);
		}

		$response = rest_ensure_response(
			array(
				'items'    => $items,
				'total'    => $result['total'],
				'page'     => $result['page'],
				'per_page' => $result['per_page'],
				'pages'    => $result['pages'],
				'counts'   => Icon_Library::count_by_style( $library ),
				'styles'   => $styles,
			)
		);
		$response->header( 'X-WP-Total', (string) $result['total'] );
		$response->header( 'X-WP-TotalPages', (string) $result['pages'] );

		return $response;
	}

	/**
	 * Gets one icon by key.
	 *
	 * @since 4.4.0
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_icon( WP_REST_Request $request ) {
		$key  = (string) $request['key'];
		$icon = Icon_Library::get( $key );

		if ( null === $icon ) {
			return new WP_Error( 'metform_icon_not_found', __( 'Icon not found.', 'metform' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( self::prepare_icon( $key, $icon ) );
	}

	/**
	 * Trims and length-limits the search text.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $value Raw `search` param.
	 * @return string
	 */
	public function sanitize_search( $value ): string {
		return substr( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ), 0, 100 );
	}

	/**
	 * Clamps `page` to at least 1.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $value Raw `page` param.
	 * @return int
	 */
	public function clamp_page( $value ): int {
		return max( 1, min( 10000, (int) $value ) );
	}

	/**
	 * Clamps `per_page` to 1-{@see MAX_PER_PAGE}.
	 *
	 * @since 4.4.0
	 *
	 * @param mixed $value Raw `per_page` param.
	 * @return int
	 */
	public function clamp_per_page( $value ): int {
		return max( 1, min( self::MAX_PER_PAGE, (int) $value ) );
	}

	/**
	 * Shapes one icon for a response — the fields the picker draws, nothing else.
	 *
	 * @since 4.4.0
	 *
	 * @param string $key  Icon key.
	 * @param array  $icon Icon definition.
	 * @return array{key: string, label: string, style: string, viewBox: string, path: string|string[]}
	 */
	private static function prepare_icon( string $key, array $icon ): array {
		return array(
			'key'     => $key,
			'label'   => (string) ( $icon['label'] ?? $key ),
			'style'   => (string) ( $icon['style'] ?? '' ),
			'viewBox' => (string) ( $icon['viewBox'] ?? '' ),
			'path'    => $icon['path'] ?? '',
		);
	}
}
