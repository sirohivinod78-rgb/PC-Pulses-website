<?php
/**
 * Jeg Kit Banner Class
 *
 * @package jeg-kit
 * @author Jegtheme
 * @since 2.5.5
 */

namespace Jeg\Elementor_Kit\Banner;

use Jeg\Elementor_Kit\Init;

/**
 * Class Banner
 *
 * @package jeg-kit
 */
class Banner {
	/**
	 * Option Name.
	 *
	 * @var string
	 */
	private $option_name = 'jkit_banner_active_time';

	/**
	 * Option Name.
	 *
	 * @var string
	 */
	private $key_upgrade_to_pro = 'jkit_banner_upgrade_to_pro';

	/**
	 * Global event banner data.
	 *
	 * @var mixed
	 */
	private $global_event_banner = false;

	/**
	 * Template slug
	 *
	 * @var string
	 */
	private $template_slug = 'templates/banner/';

	/**
	 * Class instance
	 *
	 * @var Element
	 */
	private static $instance;

	/**
	 * Init constructor.
	 */
	public function __construct() {
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'metform-menu-settings', 'metform_wpmet_plugins' ) ) ) {
			add_action( 'in_admin_header', array( $this, 'notice' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'notice' ) );
		}
		add_action( 'admin_notices', array( $this, 'load_global_event_banner' ) );
		add_action( 'wp_ajax_jkit_notice_banner_close', array( $this, 'close' ) );
		add_action( 'wp_ajax_jkit_notice_banner_review', array( $this, 'review' ) );
		add_action( 'wp_ajax_jkit_notice_banner_upgrade_close', array( $this, 'close_banner_upgrade' ) );
		add_action( 'wp_ajax_jkit_dismiss_global_event_banner', array( $this, 'dismiss_global_event_banner' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Get class instance
	 *
	 * @return Banner
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Enqueue Script.
	 */
	public function enqueue_scripts() {
		if ( $this->can_render_notice() || $this->can_render_upgrade_to_pro_banner() || $this->can_render_global_event_banner() ) {
			wp_enqueue_script( 'jkit-notice-banner', JEG_ELEMENTOR_KIT_URL . '/assets/js/admin/notice-banner.js', array( 'jquery' ), JEG_ELEMENTOR_KIT_VERSION, true );
			wp_enqueue_style( 'jkit-notice-banner', JEG_ELEMENTOR_KIT_URL . '/assets/css/admin/notice-banner.css', array(), JEG_ELEMENTOR_KIT_VERSION );
		}
	}

	/**
	 * Register Active Time.
	 */
	public function register_active_banner() {
		$option = get_option( $this->option_name, true );

		if ( 'review' !== $option && (bool) $option ) {
			update_option( $this->option_name, true );
		}

		update_option( $this->key_upgrade_to_pro, true );
	}

	/**
	 * Get Second by days.
	 *
	 * @param int $days Days Number.
	 *
	 * @return int
	 */
	public function get_second( $days ) {
		return $days * 24 * 60 * 60;
	}

	/**
	 * Check if we can render notice.
	 */
	public function can_render_notice() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return false;
		}

		$option = get_option( $this->option_name );

		if ( 'review' === $option ) {
			return false;
		}

		return (bool) $option;
	}

	/**
	 * Check if we can render banner upgrade to pro.
	 */
	public function can_render_upgrade_to_pro_banner() {
		if ( ! current_user_can( 'edit_theme_options' ) || defined( 'JEG_KIT_PRO' ) ) {
			return false;
		}

		$option = get_option( $this->key_upgrade_to_pro, 'none' );

		if ( 'none' === $option ) {
			update_option( $this->key_upgrade_to_pro, true );

			return true;
		}

		if ( is_numeric( $option ) ) {
			return time() >= (int) $option;
		}

		if ( false === $option ) {
			update_option( $this->key_upgrade_to_pro, time() + $this->get_second( 14 ) );
		}

		return (bool) $option;
	}

	/**
	 * Check if current page is the Jeg Kit dashboard.
	 *
	 * @return bool
	 */
	private function is_jegkit_dashboard() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		return 'jkit' === $page;
	}

	/**
	 * Get global event banner data.
	 *
	 * @return mixed
	 */
	private function get_global_event_banner() {
		if ( false === $this->global_event_banner ) {
			$this->global_event_banner = jkit_get_banner_data( true, 'bannerGlobal' );
		}

		return $this->global_event_banner;
	}

	/**
	 * Normalize event banner data.
	 *
	 * @param mixed $event_banner Event banner data.
	 *
	 * @return array
	 */
	private function get_event_banner_data( $event_banner ) {
		return is_array( $event_banner ) ? $event_banner : get_object_vars( $event_banner );
	}

	/**
	 * Sanitize banner size value.
	 *
	 * @param mixed $value Size value.
	 *
	 * @return string
	 */
	private function sanitize_banner_size( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( preg_match( '/^\d+(?:\.\d+)?(?:px|em|rem|%|vw|vh)$/', $value ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Get global event banner wrapper style.
	 *
	 * @param array $data Event banner data.
	 *
	 * @return string
	 */
	private function get_global_event_banner_wrapper_style( $data ) {
		$background_color = ! empty( $data['bannerGlobalBackgroundColor'] ) ? sanitize_hex_color( $data['bannerGlobalBackgroundColor'] ) : '';
		$styles           = array();

		if ( ! empty( $background_color ) ) {
			$styles[] = 'background-color:' . $background_color;
		}

		return implode( ';', $styles );
	}

	/**
	 * Get global event banner image style.
	 *
	 * @param array $data Event banner data.
	 *
	 * @return string
	 */
	private function get_global_event_banner_image_style( $data ) {
		$max_width  = ! empty( $data['bannerGlobalMaxWidth'] ) ? $this->sanitize_banner_size( $data['bannerGlobalMaxWidth'] ) : '';
		$max_height = ! empty( $data['bannerGlobalMaxHeight'] ) ? $this->sanitize_banner_size( $data['bannerGlobalMaxHeight'] ) : '';
		$styles     = array();

		if ( ! empty( $max_width ) ) {
			$styles[] = 'max-width:' . $max_width;
		}

		if ( ! empty( $max_height ) ) {
			$styles[] = 'max-height:' . $max_height;
		}

		if ( ! empty( $styles ) ) {
			array_unshift( $styles, 'width:auto' );
		}

		return implode( ';', $styles );
	}

	/**
	 * Get global event banner ID.
	 *
	 * @param mixed $event_banner Event banner data.
	 *
	 * @return string
	 */
	private function get_global_event_banner_id( $event_banner ) {
		$data = $this->get_event_banner_data( $event_banner );

		if ( ! empty( $data['bannerId'] ) ) {
			return sanitize_key( $data['bannerId'] );
		}

		$payload = wp_json_encode(
			array(
				'bannerGlobal'  => isset( $data['bannerGlobal'] ) ? $data['bannerGlobal'] : '',
				'url'           => isset( $data['url'] ) ? $data['url'] : '',
				'expired'       => isset( $data['expired'] ) ? $data['expired'] : '',
				'bannerUpdated' => isset( $data['bannerUpdated'] ) ? $data['bannerUpdated'] : '',
			)
		);

		return wp_hash( false !== $payload ? $payload : '' );
	}

	/**
	 * Check if global event banner can be rendered.
	 *
	 * @return bool
	 */
	public function can_render_global_event_banner() {
		if ( ! current_user_can( 'edit_theme_options' ) || defined( 'JEG_KIT_PRO' ) || $this->is_jegkit_dashboard() ) {
			return false;
		}

		$event_banner = $this->get_global_event_banner();

		if ( ! jkit_is_event_banner_valid( $event_banner, 'bannerGlobal' ) ) {
			return false;
		}

		$data    = $this->get_event_banner_data( $event_banner );
		$expired = strtotime( $data['expired'] );

		if ( ! $expired || current_time( 'timestamp' ) > $expired ) {
			return false;
		}

		$banner_id = $this->get_global_event_banner_id( $event_banner );
		$user_id   = get_current_user_id();

		if ( empty( $banner_id ) ) {
			return false;
		}

		if ( $user_id && hash_equals( (string) get_user_meta( $user_id, 'jkit_global_event_banner_dismissed', true ), $banner_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Close Button Clicked.
	 */
	public function close() {
		update_option( $this->option_name, false );
		wp_send_json_success();
	}

	/**
	 * Close Button Clicked.
	 */
	public function close_banner_upgrade() {
		update_option( $this->key_upgrade_to_pro, time() + $this->get_second( 14 ) );

		wp_send_json_success();
	}

	/**
	 * Review Button Clicked.
	 */
	public function review() {
		update_option( $this->option_name, 'review' );
		wp_send_json_success();
	}

	/**
	 * Load global event banner in WordPress admin pages.
	 */
	public function load_global_event_banner() {
		if ( ! $this->can_render_global_event_banner() ) {
			return;
		}

		$event_banner = $this->get_global_event_banner();
		$data         = $this->get_event_banner_data( $event_banner );
		$banner_id    = $this->get_global_event_banner_id( $event_banner );
		$wrap_style   = $this->get_global_event_banner_wrapper_style( $data );
		$image_style  = $this->get_global_event_banner_image_style( $data );
		$banner_url   = add_query_arg(
			array(
				'utm_medium' => 'global-event-banner',
			),
			$data['url']
		);

		?>
		<div id="jkit-global-event-banner-<?php echo esc_attr( $banner_id ); ?>" class="notice jkit-global-event-banner"<?php echo ! empty( $wrap_style ) ? ' style="' . esc_attr( $wrap_style ) . '"' : ''; ?>>
			<a href="<?php echo esc_url( $banner_url ); ?>" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( $data['bannerGlobal'] ); ?>" alt="<?php esc_attr_e( 'Jeg Kit event banner', 'jeg-elementor-kit' ); ?>"<?php echo ! empty( $image_style ) ? ' style="' . esc_attr( $image_style ) . '"' : ''; ?> />
			</a>
			<button type="button" class="notice-dismiss jkit-global-event-banner-dismiss" data-banner-id="<?php echo esc_attr( $banner_id ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'jkit_global_event_banner' ) ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'jeg-elementor-kit' ); ?></span>
			</button>
		</div>
		<script>
			( function() {
				var banner = document.getElementById( 'jkit-global-event-banner-<?php echo esc_js( $banner_id ); ?>' );

				if ( ! banner ) {
					return;
				}

				var dismissButton = banner.querySelector( '.jkit-global-event-banner-dismiss' );

				if ( ! dismissButton ) {
					return;
				}

				dismissButton.addEventListener( 'click', function() {
					var request = new FormData();

					banner.remove();
					request.append( 'action', 'jkit_dismiss_global_event_banner' );
					request.append( 'nonce', dismissButton.dataset.nonce );
					request.append( 'banner_id', dismissButton.dataset.bannerId );

					window.fetch( window.ajaxurl, {
						method: 'POST',
						credentials: 'same-origin',
						body: request
					} );
				} );
			}() );
		</script>
		<?php
	}

	/**
	 * Dismiss global event banner.
	 */
	public function dismiss_global_event_banner() {
		check_ajax_referer( 'jkit_global_event_banner', 'nonce' );

		$user_id = get_current_user_id();

		if ( ! $user_id || ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$banner_id = isset( $_POST['banner_id'] ) ? sanitize_key( wp_unslash( $_POST['banner_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( empty( $banner_id ) ) {
			wp_send_json_error( null, 400 );
		}

		$event_banner = $this->get_global_event_banner();

		if ( ! jkit_is_event_banner_valid( $event_banner, 'bannerGlobal' ) ) {
			wp_send_json_error( null, 404 );
		}

		$expected_banner_id = $this->get_global_event_banner_id( $event_banner );

		if ( empty( $expected_banner_id ) || ! hash_equals( $expected_banner_id, $banner_id ) ) {
			wp_send_json_error( null, 400 );
		}

		update_user_meta( $user_id, 'jkit_global_event_banner_dismissed', $banner_id );
		wp_send_json_success();
	}

	/**
	 * Show Notice.
	 */
	public function notice() {
		if ( $this->can_render_notice() ) {
			jkit_get_template_part( $this->template_slug . 'notice-banner' );
		}

		if ( ! $this->can_render_global_event_banner() && $this->can_render_upgrade_to_pro_banner() ) {
			jkit_get_template_part( $this->template_slug . 'upgrade-to-pro' );
		}
	}
}
