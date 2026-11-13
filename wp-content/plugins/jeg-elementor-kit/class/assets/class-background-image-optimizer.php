<?php
/**
 * Background Image Optimizer Class
 *
 * @package jeg-kit
 * @author Jegtheme
 * @since x.x.x
 */

namespace Jeg\Elementor_Kit\Assets;

use Jeg\Element\Query;
use Jeg\Elementor_Kit\Templates\Template;

/**
 * Adds early resource hints for high-priority widget background images.
 */
class Background_Image_Optimizer {
	/**
	 * Manifest meta key.
	 */
	const MANIFEST_META_KEY = '_jkit_background_image_preload_manifest';

	/**
	 * Manifest schema version.
	 */
	const MANIFEST_VERSION = 3;

	/**
	 * Class instance.
	 *
	 * @var Background_Image_Optimizer
	 */
	private static $instance;

	/**
	 * Module constructor.
	 */
	private function __construct() {
		add_action( 'elementor/document/after_save', array( $this, 'update_document_manifest' ), 10, 2 );
		add_action( 'added_post_meta', array( $this, 'invalidate_document_manifest' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'invalidate_document_manifest' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'invalidate_document_manifest' ), 10, 3 );

		if ( version_compare( get_bloginfo( 'version' ), '6.6', '>=' ) ) {
			add_filter( 'wp_preload_resources', array( $this, 'add_preload_resources' ) );
		} else {
			add_action( 'wp_head', array( $this, 'print_preload_links' ), 2 );
		}
	}

	/**
	 * Get class instance.
	 *
	 * @return Background_Image_Optimizer
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Update the manifest on the exact document Elementor saved.
	 *
	 * @param \Elementor\Core\Base\Document $document Elementor document.
	 * @param array                         $data     Saved document data.
	 */
	public function update_document_manifest( $document, $data ) {
		if ( ! isset( $data['elements'] ) || ! is_array( $data['elements'] ) ) {
			return;
		}

		$this->save_document_manifest( $document->get_post()->ID, $this->build_manifest( $data['elements'] ) );
	}

	/**
	 * Remove stale manifests when Elementor data is updated outside its normal
	 * document save lifecycle, such as during an import.
	 *
	 * @param int|int[] $_meta_id    Meta ID or deleted meta IDs.
	 * @param int       $document_id Post ID.
	 * @param string    $meta_key    Meta key.
	 */
	public function invalidate_document_manifest( $_meta_id, $document_id, $meta_key ) {
		if ( '_elementor_data' === $meta_key ) {
			delete_metadata( 'post', $document_id, self::MANIFEST_META_KEY );
		}
	}

	/**
	 * Add background images to WordPress resource preloads.
	 *
	 * @param array $preload_resources Existing preload resources.
	 *
	 * @return array
	 */
	public function add_preload_resources( $preload_resources ) {
		foreach ( $this->get_preload_urls() as $url ) {
			$preload_resources[] = array(
				'href'          => $url,
				'as'            => 'image',
				'fetchpriority' => 'high',
			);
		}

		return $preload_resources;
	}

	/**
	 * Print preload links on WordPress versions without fetchpriority support in
	 * wp_preload_resources().
	 */
	public function print_preload_links() {
		foreach ( $this->get_preload_urls() as $url ) {
			printf(
				"<link rel='preload' href='%s' as='image' fetchpriority='high' />\n",
				esc_url( $url )
			);
		}
	}

	/**
	 * Get unique high-priority background image URLs for the current request.
	 *
	 * @return string[]
	 */
	private function get_preload_urls() {
		if ( is_admin() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return array();
		}

		$urls = array();

		foreach ( $this->get_document_ids() as $document_id ) {
			$document = \Elementor\Plugin::instance()->documents->get_doc_for_frontend( $document_id );

			if ( ! $document || ! $document->is_built_with_elementor() ) {
				continue;
			}

			foreach ( $this->get_document_manifest( $document ) as $resource ) {
				$url = $this->get_resource_url( $resource );

				if ( $url ) {
					$urls[ $url ] = true;
				}
			}
		}

		return array_keys( $urls );
	}

	/**
	 * Get page and active JKit template document IDs.
	 *
	 * @return int[]
	 */
	private function get_document_ids() {
		$queried      = get_queried_object();
		$post_id      = $queried instanceof \WP_Post ? $queried->ID : 0;
		$document_ids = array();

		if ( $post_id && get_post( $post_id ) ) {
			$document_ids[] = (int) $post_id;
		}

		if ( is_404() ) {
			$document_ids[] = (int) get_option( 'jkit_notfound_template' );
		}

		$template = Template::instance();

		foreach ( array( $template->get_header_template(), $template->get_footer_template() ) as $templates ) {
			foreach ( $templates as $template_data ) {
				if ( ! empty( $template_data['id'] ) && $template->check_conditions( $post_id, $template_data['id'] ) ) {
					$document_ids[] = (int) $template_data['id'];
					break;
				}
			}
		}

		return array_unique( array_filter( $document_ids ) );
	}

	/**
	 * Get a saved manifest, or build it once for an existing document.
	 *
	 * @param \Elementor\Core\Base\Document $document Elementor document.
	 *
	 * @return array
	 */
	private function get_document_manifest( $document ) {
		$document_id = $document->get_post()->ID;
		$manifest    = get_metadata( 'post', $document_id, self::MANIFEST_META_KEY, true );

		if ( $this->is_valid_manifest( $manifest ) ) {
			return $manifest['resources'];
		}

		$manifest = $this->build_manifest( $document->get_elements_data() );
		$this->save_document_manifest( $document_id, $manifest );

		return $manifest['resources'];
	}

	/**
	 * Check whether stored manifest data uses the current schema.
	 *
	 * @param mixed $manifest Stored manifest.
	 *
	 * @return bool
	 */
	private function is_valid_manifest( $manifest ) {
		return is_array( $manifest )
			&& isset( $manifest['version'], $manifest['resources'] )
			&& self::MANIFEST_VERSION === (int) $manifest['version']
			&& is_array( $manifest['resources'] );
	}

	/**
	 * Build a versioned manifest from an Elementor element tree.
	 *
	 * @param array $elements Elementor element tree.
	 *
	 * @return array
	 */
	private function build_manifest( $elements ) {
		$resources = array();

		$this->collect_preload_resources( $elements, $resources );

		return array(
			'version'   => self::MANIFEST_VERSION,
			'resources' => array_values( $resources ),
		);
	}

	/**
	 * Save a manifest on a published document, draft, or autosave.
	 *
	 * @param int   $document_id Elementor document ID.
	 * @param array $manifest    Versioned preload manifest.
	 */
	private function save_document_manifest( $document_id, $manifest ) {
		update_metadata( 'post', $document_id, self::MANIFEST_META_KEY, $manifest );
	}

	/**
	 * Collect widget backgrounds configured for high fetch priority.
	 *
	 * @param array $elements  Elementor element tree.
	 * @param array $resources Collected resources keyed to prevent duplicates.
	 */
	private function collect_preload_resources( $elements, &$resources ) {
		if ( ! is_array( $elements ) ) {
			return;
		}

		foreach ( $elements as $element ) {
			$widget_type = isset( $element['widgetType'] ) ? $element['widgetType'] : '';
			$settings    = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

			if ( 'jkit_banner' === $widget_type ) {
				$this->collect_banner_preload_resource( $settings, $resources );
			} elseif ( 'jkit_portfolio_gallery' === $widget_type ) {
				$this->collect_portfolio_gallery_preload_resource( $settings, $resources );
			} elseif ( 'jkit_post_list' === $widget_type ) {
				$this->collect_post_list_preload_resource( $settings, $resources );
			}

			if ( ! empty( $element['elements'] ) ) {
				$this->collect_preload_resources( $element['elements'], $resources );
			}
		}
	}

	/**
	 * Collect a Banner preload resource.
	 *
	 * @param array $settings  Widget settings.
	 * @param array $resources Collected resources.
	 */
	private function collect_banner_preload_resource( $settings, &$resources ) {
		$image_loading  = isset( $settings['sg_banner_image_loading'] ) ? $settings['sg_banner_image_loading'] : 'normal';
		$fetch_priority = isset( $settings['sg_banner_image_fetchpriority'] ) ? $settings['sg_banner_image_fetchpriority'] : 'auto';
		$dynamic        = isset( $settings['__dynamic__'] ) && is_array( $settings['__dynamic__'] ) ? $settings['__dynamic__'] : array();

		if ( 'normal' !== $image_loading || 'high' !== $fetch_priority || ! empty( $dynamic['sg_banner_image'] ) ) {
			return;
		}

		$image = isset( $settings['sg_banner_image'] ) && is_array( $settings['sg_banner_image'] ) ? $settings['sg_banner_image'] : array();
		$size  = isset( $settings['sg_banner_image_size_imagesize_size'] ) && is_string( $settings['sg_banner_image_size_imagesize_size'] ) ? $settings['sg_banner_image_size_imagesize_size'] : 'thumbnail';

		$this->add_image_resource( $image, $size, $resources );
	}

	/**
	 * Collect only the initially active Portfolio Gallery image.
	 *
	 * @param array $settings  Widget settings.
	 * @param array $resources Collected resources.
	 */
	private function collect_portfolio_gallery_preload_resource( $settings, &$resources ) {
		$image_loading  = isset( $settings['sg_gallery_image_loading'] ) ? $settings['sg_gallery_image_loading'] : 'normal';
		$fetch_priority = isset( $settings['sg_gallery_image_fetchpriority'] ) ? $settings['sg_gallery_image_fetchpriority'] : 'auto';
		$gallery        = isset( $settings['sg_gallery_list'] ) && is_array( $settings['sg_gallery_list'] ) ? $settings['sg_gallery_list'] : array();
		$image_size     = isset( $settings['sg_gallery_image_size_imagesize_size'] ) && is_string( $settings['sg_gallery_image_size_imagesize_size'] ) ? $settings['sg_gallery_image_size_imagesize_size'] : 'large';
		$first_item     = reset( $gallery );

		if ( 'normal' !== $image_loading || 'high' !== $fetch_priority || ! is_array( $first_item ) ) {
			return;
		}

		$dynamic = isset( $first_item['__dynamic__'] ) && is_array( $first_item['__dynamic__'] ) ? $first_item['__dynamic__'] : array();
		$image   = isset( $first_item['sg_gallery_list_image'] ) && is_array( $first_item['sg_gallery_list_image'] ) ? $first_item['sg_gallery_list_image'] : array();

		if ( empty( $dynamic['sg_gallery_list_image'] ) ) {
			$this->add_image_resource( $image, $image_size, $resources );
		}
	}

	/**
	 * Collect a query-backed Post List preload resource.
	 *
	 * @param array $settings  Widget settings.
	 * @param array $resources Collected resources.
	 */
	private function collect_post_list_preload_resource( $settings, &$resources ) {
		$background_enabled = isset( $settings['sg_content_background_image_enable'] ) ? $settings['sg_content_background_image_enable'] : '';
		$image_loading      = isset( $settings['sg_content_background_image_loading'] ) ? $settings['sg_content_background_image_loading'] : 'normal';
		$fetch_priority     = isset( $settings['sg_content_background_image_fetchpriority'] ) ? $settings['sg_content_background_image_fetchpriority'] : 'auto';
		$unique_content     = isset( $settings['unique_content'] ) ? $settings['unique_content'] : 'disable';
		$sort_by            = isset( $settings['sort_by'] ) ? $settings['sort_by'] : 'latest';

		if (
			'yes' !== $background_enabled
			|| 'normal' !== $image_loading
			|| 'high' !== $fetch_priority
			|| 'disable' !== $unique_content
			|| ! empty( $settings['__dynamic__'] )
			|| in_array( $sort_by, array( 'random', 'random_week', 'random_month' ), true )
			|| 0 === strpos( $sort_by, 'popular_post_jetpack_' )
		) {
			return;
		}

		$query_source = wp_parse_args(
			$settings,
			array(
				'post_type'             => 'post',
				'number_post'           => 1,
				'post_offset'           => 0,
				'include_post'          => '',
				'exclude_post'          => '',
				'include_category'      => '',
				'exclude_category'      => '',
				'include_author'        => '',
				'include_tag'           => '',
				'exclude_tag'           => '',
				'sort_by'               => 'latest',
				'pagination_number_post' => 1,
				'pagination_mode'         => 'disable',
			)
		);
		$query                      = Query::filter_attribute( $query_source );
		$query['content_selection'] = isset( $settings['content_selection'] ) ? $settings['content_selection'] : 'selection';
		$query['related_filter']    = isset( $settings['related_filter'] ) ? $settings['related_filter'] : 'none';
		$image_size                 = isset( $settings['sg_content_image_size_imagesize_size'] ) && is_string( $settings['sg_content_image_size_imagesize_size'] ) ? $settings['sg_content_image_size_imagesize_size'] : 'large';
		$key                        = 'post-list:' . md5( wp_json_encode( array( $query, $image_size ) ) );

		$resources[ $key ] = array(
			'type'  => 'post_list',
			'query' => $query,
			'size'  => $image_size,
		);
	}

	/**
	 * Add an attachment or URL image to a manifest without duplicates.
	 *
	 * @param array  $image     Elementor image value.
	 * @param string $size      Image size.
	 * @param array  $resources Collected resources.
	 */
	private function add_image_resource( $image, $size, &$resources ) {
		if ( ! empty( $image['id'] ) ) {
			$key = 'attachment:' . (int) $image['id'] . ':' . $size;

			$resources[ $key ] = array(
				'attachment_id' => (int) $image['id'],
				'size'          => $size,
			);
		} elseif ( ! empty( $image['url'] ) ) {
			$url = esc_url_raw( $image['url'] );

			if ( $url ) {
				$resources[ 'url:' . $url ] = array( 'url' => $url );
			}
		}
	}

	/**
	 * Resolve the same image URL used by the widget view.
	 *
	 * @param array $resource Manifest resource.
	 *
	 * @return string
	 */
	private function get_resource_url( $resource ) {
		if ( ! is_array( $resource ) ) {
			return '';
		}

		if ( isset( $resource['type'] ) && 'post_list' === $resource['type'] ) {
			return $this->get_post_list_resource_url( $resource );
		}

		if ( ! empty( $resource['attachment_id'] ) ) {
			$size      = isset( $resource['size'] ) ? $resource['size'] : 'thumbnail';
			$image_src = wp_get_attachment_image_src( $resource['attachment_id'], $size );

			return is_array( $image_src ) ? $image_src[0] : '';
		}

		return isset( $resource['url'] ) ? $resource['url'] : '';
	}

	/**
	 * Resolve the first Post List featured image using a one-post query.
	 *
	 * @param array $resource Post List manifest resource.
	 *
	 * @return string
	 */
	private function get_post_list_resource_url( $resource ) {
		$query = isset( $resource['query'] ) && is_array( $resource['query'] ) ? $resource['query'] : array();
		$query = wp_parse_args(
			$query,
			array(
				'content_selection' => 'selection',
				'related_filter'    => 'none',
				'post_type'         => 'post',
				'post_offset'       => 0,
				'include_post'      => '',
				'exclude_post'      => '',
				'include_category'  => '',
				'exclude_category'  => '',
				'include_author'    => '',
				'include_tag'       => '',
				'exclude_tag'       => '',
				'sort_by'           => 'latest',
			)
		);

		if ( 'related' === $query['content_selection'] && ! $this->prepare_related_post_list_query( $query ) ) {
			return '';
		}

		if ( function_exists( 'jkit_is_multilanguage' ) && jkit_is_multilanguage() ) {
			$query['lang'] = jkit_get_current_language();
		}

		$query['number_post']           = 1;
		$query['pagination_number_post'] = 1;
		$query['pagination_mode']         = 'disable';
		$query['paged']                   = 1;

		$result = Query::get( $query );
		$post   = ! empty( $result['result'][0] ) ? $result['result'][0] : null;

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$size = isset( $resource['size'] ) ? $resource['size'] : 'large';

		return get_the_post_thumbnail_url( $post->ID, $size );
	}

	/**
	 * Apply the same single-post related filters used by Post List rendering.
	 *
	 * @param array $query Query settings.
	 *
	 * @return bool Whether the related query can be resolved safely.
	 */
	private function prepare_related_post_list_query( &$query ) {
		$post_id   = get_queried_object_id();
		$post_type = get_post_type( $post_id );

		if ( ! $post_id || ! $post_type || ( 'none' === $query['related_filter'] && ( is_archive() || is_search() ) ) ) {
			return false;
		}

		if ( in_array( $query['related_filter'], array( 'category', 'both' ), true ) ) {
			$categories = wp_get_post_categories( $post_id );

			if ( empty( $categories ) ) {
				return false;
			}

			$query['include_category'] = implode( ',', $categories );
		}

		if ( in_array( $query['related_filter'], array( 'tag', 'both' ), true ) ) {
			$tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );

			if ( empty( $tags ) || is_wp_error( $tags ) ) {
				return false;
			}

			$query['include_tag'] = implode( ',', $tags );
		}

		$query['post_type']    = $post_type;
		$query['exclude_post'] = implode( ',', array_filter( array( $query['exclude_post'], $post_id ) ) );

		return true;
	}
}
