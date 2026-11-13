<?php
/**
 * Jeg Kit Class
 *
 * @package jeg-kit
 * @author Jegtheme
 * @since 1.2.0
 */

namespace Jeg\Elementor_Kit\Assets;

use Jeg\Elementor_Kit\Integrations\Freemius;
use Jeg\Elementor_Kit\Options\Settings;
use Jeg\Elementor_Kit\Style\CSS;
use Jeg\Util\Style_Loader;

/**
 * Class Asset
 *
 * @package Jeg\Elementor_Kit
 */
class Asset {
	/**
	 * Class instance
	 *
	 * @var Asset
	 */
	private static $instance;

	/**
	 * Frontend ajax endpoint
	 *
	 * @var string
	 */
	private $endpoint = 'jkit-ajax-request';

	/**
	 * Loaded Scripts
	 */
	private $loaded_scripts = array();

	/**
	 * Block ajax prefix
	 *
	 * @var string
	 */
	public static $element_ajax_prefix = 'jkit_element_ajax_';

	/**
	 * Module constructor.
	 */
	private function __construct() {
		$this->setup_init();
		$this->setup_hook();
	}

	/**
	 * Setup Classes
	 */
	private function setup_init() {
		Background_Image_Optimizer::instance();
		Markup_Compatibility::instance();
	}

	/**
	 * Check if Elementor preview mode is available and active.
	 *
	 * @return bool
	 */
	private function is_elementor_preview_mode() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::instance();

		return isset( $elementor->preview ) && $elementor->preview->is_preview_mode();
	}

	/**
	 * Setup Hooks
	 */
	private function setup_hook() {
		add_filter( 'wp_handle_upload', array( $this, 'svg_upload_handler' ) );
		add_filter( 'jeg_inline_css_enabled', array( $this, 'inline_css_enabled' ), 10, 3 );
		add_filter( 'jeg_inline_css_size_limit', array( $this, 'inline_css_size_limit' ), 10, 3 );

		add_action( 'admin_init', array( $this, 'remove_form_control' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ), 98 );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_frontend_scripts' ), 98 );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'load_frontend_assets' ), 98 );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'load_editor_assets' ) );
		add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'load_editor_scripts' ) );
		add_action( 'elementor/element/parse_css', array( $this, 'add_post_css' ), 10, 2 );

		add_action( 'elementor/frontend/before_get_builder_content', array( $this, 'enqueue_conditional_scripts' ), 99, 2 );

		/** Add portfolio gallery css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_portfolio_gallery_css' ), 10, 2 );

		/** Add social share css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_social_share_css' ), 10, 2 );

		/** Add progress bar css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_progress_bar_css' ), 10, 2 );

		/** Add dual button css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_dual_button_css' ), 10, 2 );

		/** Add feature list css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_feature_list_css' ), 10, 2 );

		/** Add icon box css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_icon_box_css' ), 10, 2 );

		/** Add testimonials css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_testimonials_css' ), 10, 2 );

		/** Add client logo css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_client_logo_css' ), 10, 2 );

		/** Add mailchimp css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_mailchimp_css' ), 10, 2 );

		/** Add tabs css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_tabs_css' ), 10, 2 );

		/** Add post block css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_post_block_css' ), 10, 2 );

		/** Add gallery css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_gallery_css' ), 10, 2 );

		/** Add nav menu css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_nav_menu_css' ), 10, 2 );

		/** Add testimonials css custom handler */
		add_action( 'elementor/element/parse_css', array( $this, 'add_product_carousel_css' ), 10, 2 );

		/** Register Additional Icons */
		add_filter( 'elementor/icons_manager/additional_tabs', array( $this, 'register_icons' ) );
	}

	/**
	 * Get class instance
	 *
	 * @return Asset
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Load editor assets
	 */
	public function load_editor_assets() {
		wp_enqueue_style( 'jkit-icons', JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/jkiticon.css', array(), JEG_ELEMENTOR_KIT_VERSION );
		wp_enqueue_style( 'tippy', JEG_ELEMENTOR_KIT_URL . '/assets/js/tippy/tippy.css', array(), '6.3.7' );
		wp_enqueue_style( 'jkit-elements-editor', JEG_ELEMENTOR_KIT_URL . '/assets/css/admin/editor.css', array(), JEG_ELEMENTOR_KIT_VERSION );
	}

	/**
	 * Load editor scripts
	 */
	public function load_editor_scripts() {
		wp_register_script( 'popper', JEG_ELEMENTOR_KIT_URL . '/assets/js/popper/popper.min.js', array(), '2.11.8', true );
		wp_register_script( 'tippy', JEG_ELEMENTOR_KIT_URL . '/assets/js/tippy/tippy-bundle.umd.min.js', array( 'popper' ), '6.3.7', true );
		wp_enqueue_script( 'jkit-elements-editor', JEG_ELEMENTOR_KIT_URL . '/assets/js/elementor/editor-support.js', array( 'jquery', 'tippy' ), JEG_ELEMENTOR_KIT_VERSION, true );

		wp_localize_script(
			'jkit-elements-editor',
			'jkit_editor',
			array(
				// 'pro_banner' => file_get_contents( JEG_ELEMENTOR_KIT_DIR . '/assets/img/admin/jkit-pro-banner.svg' ),
				// 'pro_banner' => JEG_ELEMENTOR_KIT_URL . '/assets/img/admin/jkit-pro-banner.svg',
				'pro_banner' => pro_banner_popup_template(),
			)
		);

		$editor_asset = JEG_ELEMENTOR_KIT_DIR . '/lib/dependencies/editor.asset.php';
		$include      = include $editor_asset;
		wp_enqueue_script(
			'jkit-editor-bundle',
			JEG_ELEMENTOR_KIT_URL . '/assets/js/editor/editor.js',
			isset( $include['dependencies'] ) ? $include['dependencies'] : array(),
			JEG_ELEMENTOR_KIT_VERSION,
			true
		);

		$editor_option = $this->get_pricing_modal_option();
		$mirror_js     = $this->build_pricing_modal_assignment_js( $editor_option );
		wp_add_inline_script( 'jkit-editor-bundle', $mirror_js );

	}

	/**
	 * Load frontend assets
	 */
	public function load_frontend_assets() {
		Style_Loader::register_style( 'sweetalert2', JEG_ELEMENTOR_KIT_DIR . 'assets/js/sweetalert2/sweetalert2.min.css', JEG_ELEMENTOR_KIT_URL . '/assets/js/sweetalert2/sweetalert2.min.css', array(), '11.6.16', 'all', 'asset', JEG_ELEMENTOR_KIT_URL . '/assets/js/sweetalert2/' );
		Style_Loader::register_style( 'tiny-slider', JEG_ELEMENTOR_KIT_DIR . 'assets/js/tiny-slider/tiny-slider.css', JEG_ELEMENTOR_KIT_URL . '/assets/js/tiny-slider/tiny-slider.css', array(), '2.9.3', 'all', 'asset', JEG_ELEMENTOR_KIT_URL . '/assets/js/tiny-slider/' );
		if ( $this->should_enqueue_font_icon_styles() ) {
			Style_Loader::register_style( 'jkit-icons', JEG_ELEMENTOR_KIT_DIR . 'assets/fonts/jkiticon/jkiticon.css', JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/jkiticon.css', array(), JEG_ELEMENTOR_KIT_VERSION, 'all', 'asset', JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/' );
		}
		Style_Loader::enqueue_style( 'jkit-elements-main', JEG_ELEMENTOR_KIT_DIR . 'assets/css/elements/main.css', JEG_ELEMENTOR_KIT_URL . '/assets/css/elements/main.css', array(), JEG_ELEMENTOR_KIT_VERSION, 'all', 'asset', JEG_ELEMENTOR_KIT_URL . '/assets/css/elements/' );
		$this->load_generated_global_style();
		if ( $this->should_enqueue_font_icon_styles() ) {
			$this->enqueue_font_icon_styles();
		}
		if ( $this->should_enqueue_font_awesome_icon_styles() ) {
			$this->enqueue_font_awesome_icon_styles();
		}

		/** Banner Asset */
		wp_register_style( 'jkit-notice-banner', JEG_ELEMENTOR_KIT_URL . '/assets/css/admin/notice-banner.css', array(), JEG_ELEMENTOR_KIT_VERSION );

		/** WP Admin Bar Style in Frontend */
		if ( is_admin_bar_showing() ) {
			wp_enqueue_style( 'jkit-admin', JEG_ELEMENTOR_KIT_URL . '/assets/css/admin/admin.css', array(), JEG_ELEMENTOR_KIT_VERSION );
			wp_enqueue_script( 'jkit-admin', JEG_ELEMENTOR_KIT_URL . '/assets/js/admin/admin.js', array( 'lodash', 'react', 'react-dom', 'regenerator-runtime', 'wp-api-fetch', 'wp-data', 'wp-hooks', 'wp-i18n', 'wp-notices' ), JEG_ELEMENTOR_KIT_VERSION, true );

			$frontend_option = $this->get_pricing_modal_option();
			$mirror_js       = $this->build_pricing_modal_assignment_js( $frontend_option );
			wp_add_inline_script( 'jkit-admin', $mirror_js );
		}
	}

	/**
	 * Provide JKit inline CSS enabled setting to jeg-framework.
	 *
	 * @param bool   $enabled Current enabled value.
	 * @param string $context CSS context.
	 * @param string $handle  Style handle.
	 *
	 * @return bool
	 */
	public function inline_css_enabled( $enabled, $context, $handle ) {
		if ( ! $this->is_inline_css_handle_allowed( $handle ) ) {
			return $enabled;
		}

		return Inline_CSS_Policy::is_enabled( $context );
	}

	/**
	 * Provide JKit inline CSS size limit to jeg-framework.
	 *
	 * @param int    $limit   Current size limit in KB.
	 * @param string $context CSS context.
	 * @param string $handle  Style handle.
	 *
	 * @return int
	 */
	public function inline_css_size_limit( $limit, $context, $handle ) {
		if ( ! $this->is_inline_css_handle_allowed( $handle ) ) {
			return $limit;
		}

		return Inline_CSS_Policy::get_size_limit_kb();
	}

	/**
	 * Check whether JKit should provide inline CSS policy for a framework style handle.
	 *
	 * @param string $handle Style handle.
	 *
	 * @return bool
	 */
	private function is_inline_css_handle_allowed( $handle ) {
		$handles = array(
			'jkit-elements-main',
			'jkit-icons',
			'sweetalert2',
			'tiny-slider',
			'jeg-dynamic-style',
			'jeg-kit-pro-elementor-widgets',
			'jeg-kit-pro-templates',
			'jkit-pro-templates',
			'jkit-pro-background-motion-effect',
		);

		return in_array( $handle, apply_filters( 'jkit_inline_css_handles', $handles ), true );
	}

	/**
	 * Load generated global CSS.
	 *
	 * @return void
	 */
	private function load_generated_global_style() {
		$settings = Settings::current_settings();
		$name     = CSS::get_css_file_name( 'global' );
		$file     = CSS::get_file_info( $name, 'path' );

		if ( Inline_CSS_Policy::is_enabled( 'global', $settings ) ) {
			$css     = new CSS( $settings, false );
			$content = $css->get_css( 'global' );

			if ( '' === trim( $content ) ) {
				return;
			}

			if ( Inline_CSS_Policy::can_inline( $content, 'global', $settings ) ) {
				wp_register_style( 'jkit-elements-global', false, array( 'jkit-elements-main' ), JEG_ELEMENTOR_KIT_VERSION );
				wp_enqueue_style( 'jkit-elements-global' );
				wp_add_inline_style( 'jkit-elements-global', $content );

				return;
			}

			$css->combine_css();
		} elseif ( ! file_exists( $file ) ) {
			new CSS( $settings );
		}

		if ( file_exists( $file ) ) {
			wp_enqueue_style(
				'jkit-elements-global',
				CSS::get_file_info( $name, 'url' ),
				array( 'jkit-elements-main' ),
				JEG_ELEMENTOR_KIT_VERSION
			);
		}
	}

	/**
	* Check whether the JKit icon font stylesheet should be enqueued.
	*
	* @return bool
	*/
	private function should_enqueue_font_icon_styles() {
		if ( is_admin() || $this->is_elementor_preview_mode() ) {
			return true;
		}

		if ( ! function_exists( 'jkit_should_convert_icon_svg' ) ) {
			return true;
		}

		return ! jkit_should_convert_icon_svg();
	}

	/**
	 * Check whether Font Awesome styles should be enqueued.
	 *
	 * @return bool
	 */
	private function should_enqueue_font_awesome_icon_styles() {
		if ( is_admin() || $this->is_elementor_preview_mode() ) {
			return true;
		}

		if ( ! function_exists( 'jkit_should_convert_icon_svg' ) ) {
			return true;
		}

		return ! jkit_should_convert_icon_svg() || (
			function_exists( 'jkit_should_delegate_font_awesome_icon_rendering' ) && jkit_should_delegate_font_awesome_icon_rendering()
		);
	}

	/**
	 * Enqueue the JKit icon font stylesheet used as the non-SVG fallback.
	 *
	 * @return void
	 */
	private function enqueue_font_icon_styles() {
		wp_enqueue_style( 'jkit-icons' );
	}

	/**
	 * Enqueue registered Font Awesome styles when Elementor renders them.
	 *
	 * @return void
	 */
	private function enqueue_font_awesome_icon_styles() {

		$font_awesome_handles = array(
			'elementor-icons-fa-solid',
			'elementor-icons-fa-regular',
			'elementor-icons-fa-brands',
		);

		foreach ( $font_awesome_handles as $handle ) {
			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}

	/**
	 * Load Admin Styles
	 * 
	 * @return void
	 */
	public function load_admin_assets() {
		wp_enqueue_style( 'jkit-icons', JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/jkiticon.css', array(), JEG_ELEMENTOR_KIT_VERSION );
		wp_enqueue_style( 'jkit-admin', JEG_ELEMENTOR_KIT_URL . '/assets/css/admin/admin.css', array(), JEG_ELEMENTOR_KIT_VERSION );
		wp_enqueue_script( 'jkit-admin', JEG_ELEMENTOR_KIT_URL . '/assets/js/admin/admin.js', array( 'lodash', 'react', 'react-dom', 'regenerator-runtime', 'wp-api-fetch', 'wp-data', 'wp-hooks', 'wp-i18n', 'wp-notices' ), JEG_ELEMENTOR_KIT_VERSION, true );

		$admin_option = $this->get_pricing_modal_option();
		$mirror_js    = $this->build_pricing_modal_assignment_js( $admin_option );
		wp_add_inline_script( 'jkit-admin', $mirror_js );
	}

	/**
	 * Load Scripts
	 */
	public function load_frontend_scripts() {
		wp_register_script( 'sweetalert2', JEG_ELEMENTOR_KIT_URL . '/assets/js/sweetalert2/sweetalert2.min.js', null, '11.6.16', true );
		wp_register_script( 'tiny-slider', JEG_ELEMENTOR_KIT_URL . '/assets/js/tiny-slider/tiny-slider.js', null, '2.9.3', true );
		wp_register_script( 'isotope', JEG_ELEMENTOR_KIT_URL . '/assets/js/isotope/isotope.min.js', null, '3.0.6', true );
		wp_register_script( 'chartjs', JEG_ELEMENTOR_KIT_URL . '/assets/js/chartjs/chart.min.js', null, '3.9.1', true );
		wp_register_script( 'goodshare', JEG_ELEMENTOR_KIT_URL . '/assets/js/goodshare/goodshare.min.js', null, '6.1.5', true );

		// A virtual dependency makes this helper available before each widget script,
		// including when an optimizer delays external scripts in a separate stage.
		wp_register_script( 'jkit-elementor-ready', false, array( 'jquery' ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_add_inline_script( 'jkit-elementor-ready', <<<'JS'
(function (window) {
    if (window.jkitElementorReady) {
        return;
    }

    var registrations = {};

    window.jkitElementorReady = {
        register: function (handle, Handler, actions) {
            if (registrations[handle]) {
                return;
            }

            // jQuery may itself be delayed when this inline helper first runs.
            var $ = window.jQuery;
            registrations[handle] = true;
            var initialized = new WeakSet();

            function ready($element) {
                var element = $element && $element[0];
                if (!element || initialized.has(element)) {
                    return;
                }

                // A new DOM node on an Elementor rerender gets a new handler.
                // Mark only after construction succeeds so a later ready can retry.
                window.elementorFrontend.elementsHandler.addHandler(Handler, { $element: $element });
                initialized.add(element);
            }

            function attach() {
                if (!window.elementorFrontend || !window.elementorFrontend.hooks || !window.elementorFrontend.elementsHandler) {
                    return false;
                }

                actions.forEach(function (action) {
                    window.elementorFrontend.hooks.addAction(action, ready);
                });

                return true;
            }

            // Elementor creates hooks/elementsHandler immediately before it emits
            // elementor/frontend/init, then performs its first ready scan.
            if (!attach()) {
                $(window).one('elementor/frontend/init', attach);
                return;
            }

            // The init event has already passed. Replay only this handler for
            // existing matching nodes, without dispatching other ready actions.
            actions.forEach(function (action) {
                var type = action.slice('frontend/element_ready/'.length);
                var selector = type.indexOf('.') === -1
                    ? '[data-element_type="' + type + '"]'
                    : '[data-element_type="widget"][data-widget_type="' + type + '"]';

                $(selector).each(function () {
                    ready($(this));
                });
            });
        }
    };
})(window);
JS
		);

		$elementor_dependencies = array( 'elementor-frontend', 'jkit-elementor-ready' );

		wp_register_script( 'jkit-element-navmenu', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/nav-menu.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-funfact', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/fun-fact.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-progressbar', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/progress-bar.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-clientlogo', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/client-logo.js', array_merge( $elementor_dependencies, array( 'tiny-slider' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-testimonials', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/testimonials.js', array_merge( $elementor_dependencies, array( 'tiny-slider' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-accordion', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/accordion.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-gallery', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/gallery.js', array_merge( $elementor_dependencies, array( 'imagesloaded', 'isotope' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-team', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/team.js', array_merge( $elementor_dependencies, array( 'sweetalert2' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-piechart', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/pie-chart.js', array_merge( $elementor_dependencies, array( 'chartjs' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-portfoliogallery', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/portfolio-gallery.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-background-lazy', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/background-lazy.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-animatedtext', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/animated-text.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-countdown', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/countdown.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-videobutton', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/video-button.js', array_merge( $elementor_dependencies, array( 'sweetalert2' ) ), JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-mailchimp', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/mailchimp.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-offcanvas', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/off-canvas.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-tabs', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/tabs.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_register_script( 'jkit-element-search', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/search.js', array_merge( $elementor_dependencies, array( 'sweetalert2' ) ), JEG_ELEMENTOR_KIT_VERSION, true );

		wp_register_script( 'jkit-element-product-carousel', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/product-carousel.js', array_merge( $elementor_dependencies, array( 'tiny-slider' ) ), JEG_ELEMENTOR_KIT_VERSION, true );

		wp_register_script( 'jkit-sticky-element', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/sticky-element.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		if ( $this->is_elementor_preview_mode() ) {
			wp_enqueue_script( 'jkit-sticky-element' );
		}

		wp_register_script( 'jkit-element-pagination', JEG_ELEMENTOR_KIT_URL . '/assets/js/elements/post-pagination.js', $elementor_dependencies, JEG_ELEMENTOR_KIT_VERSION, true );
		wp_localize_script( 'jkit-element-pagination', 'jkit_element_pagination_option', $this->localize_script() );

		wp_add_inline_script( 'elementor-frontend', $this->ajax_url() );

		/** Banner Asset */
		wp_register_script( 'jkit-notice-banner', JEG_ELEMENTOR_KIT_URL . '/assets/js/admin/notice-banner.js', array( 'jquery' ), JEG_ELEMENTOR_KIT_VERSION, true );
	}

	/**
	 * Add ajax URL
	 */
	public function ajax_url() {
		if ( ! is_admin() ) {
			$ajax_url = add_query_arg( array( $this->endpoint => 'jkit_elements' ), esc_url( home_url( '/' ) ) );

			return 'var jkit_ajax_url = "' . esc_url( $ajax_url ) . '", jkit_nonce = "' . jkit_create_global_nonce() . '";';
		}

		return null;
	}

	/**
	 * Localize script
	 *
	 * @return mixed
	 */
	public function localize_script() {
		$option['element_prefix'] = self::$element_ajax_prefix;

		return $option;
	}

	/**
	 * Build pricing modal payload for client-side usage.
	 *
	 * @return array
	 */
	protected function get_pricing_modal_option() {
		$pricing_config = Freemius::instance()->get_pricing_config();
		return array(
			'proActive'       => defined( 'JEG_KIT_PRO' ),
			'freemius'       => array(
				'pricing' => $pricing_config,
			),
			'pricingPlan'    => jkit_get_pricing_plan(),
			'bannerDataPricing' => jkit_get_banner_data( true ),
			'imgDir'         => JEG_ELEMENTOR_KIT_URL . '/assets/img/',
			'pricingData'    => $this->get_localized_pricing_data( $pricing_config ),
			'wpRestNonce'    => wp_create_nonce( 'wp_rest' ),
			'proServerUrl'   => defined( 'JEG_ELEMENT_PRO_SERVER_URL' ) ? esc_url( JEG_ELEMENT_PRO_SERVER_URL ) : '',
		);
	}

	/**
	 * Fetch and cache Freemius pricing plans for localize payload.
	 *
	 * @param array|null $pricing_config Freemius pricing config.
	 *
	 * @return array|null
	 */
	protected function get_localized_pricing_data( $pricing_config ) {
		if ( empty( $pricing_config ) ) {
			return null;
		}

		$transient_key = 'jkit_pricing_data_' . md5( wp_json_encode( array(
			isset( $pricing_config['plugin_id'] ) ? $pricing_config['plugin_id'] : '',
			isset( $pricing_config['bundle_id'] ) ? $pricing_config['bundle_id'] : '',
			isset( $pricing_config['sandbox'] ) ? $pricing_config['sandbox'] : '',
			isset( $pricing_config['s_ctx_type'] ) ? $pricing_config['s_ctx_type'] : '',
			isset( $pricing_config['s_ctx_id'] ) ? $pricing_config['s_ctx_id'] : '',
			isset( $pricing_config['s_ctx_secure'] ) ? $pricing_config['s_ctx_secure'] : '',
		) ) );

		$cached = get_transient( $transient_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$data = Freemius::instance()->get_pricing_data();
		if ( empty( $data['plans'] ) || ! is_array( $data['plans'] ) ) {
			return null;
		}

		set_transient( $transient_key, $data, 10 * MINUTE_IN_SECONDS );

		return $data;
	}

	/**
	 * Build JS assignments for pricing modal variables on both legacy and dashboard globals.
	 *
	 * @param array $option Pricing payload.
	 *
	 * @return string
	 */
	protected function build_pricing_modal_assignment_js( $option ) {
		$mirror_js = $this->build_window_assignment_js( 'jkit.proActive', $option['proActive'], false )
			. $this->build_window_assignment_js( 'jkit.options.freemius', $option['freemius'], false )
			. $this->build_window_assignment_js( 'jkit.pricingPlan', $option['pricingPlan'], false )
			. $this->build_window_assignment_js( 'jkit.bannerDataPricing', $option['bannerDataPricing'], false )
			. $this->build_window_assignment_js( 'jkit.imgDir', $option['imgDir'], false )
			. $this->build_window_assignment_js( 'jkit.pricingData', $option['pricingData'], false )
			. $this->build_window_assignment_js( 'jkit.wpRestNonce', $option['wpRestNonce'], false )
			. $this->build_window_assignment_js( 'jkit.proServerUrl', $option['proServerUrl'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.freemius', $option['freemius'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.pricingPlan', $option['pricingPlan'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.bannerDataPricing', $option['bannerDataPricing'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.imgDir', $option['imgDir'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.pricingData', $option['pricingData'], false )
			. $this->build_window_assignment_js( 'JkitPricingCache.data', $option['pricingData'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.wpRestNonce', $option['wpRestNonce'], false )
			. $this->build_window_assignment_js( 'JkitDashboardOption.proServerUrl', $option['proServerUrl'], false );

		return $mirror_js;
	}

	/**
	 * Build JavaScript snippet that ensures a nested `window` path exists and assigns a value.
	 *
	 * @param string $path Dot-separated path, e.g. 'jkit.options.freemius'.
	 * @param mixed  $value Value to assign. If $raw is false the value is JSON-encoded.
	 * @param bool   $raw  Whether $value is a raw JS expression (true) or PHP value to json_encode (false).
	 *
	 * @return string JS code string (immediately-invoked function expression).
	 */
	protected function build_window_assignment_js( $path, $value, $raw = false ) {
		$parts = explode( '.', $path );
		$acc   = 'window';
		$lines = array();
		foreach ( $parts as $part ) {
			$acc     .= "['" . esc_js( $part ) . "']";
			$lines[]  = "if ( typeof {$acc} === 'undefined' ) { {$acc} = {}; }";
		}

		if ( $raw ) {
			$val = $value;
		} else {
			$val = wp_json_encode( $value );
		}

		$assign = "{$acc} = {$val};";

		$js = '(function(){' . implode( '', $lines ) . $assign . '})();';

		return $js;
	}

	/**
	 * Add Jeg Element Kit Custom CSS
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_post_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		$element_settings = $element->get_settings();

		if ( empty( $element_settings['st_css_custom'] ) ) {
			return;
		}

		$css = trim( $element_settings['st_css_custom'] );

		if ( empty( $css ) ) {
			return;
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Portfolio Gallery Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_portfolio_gallery_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_portfolio_gallery' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-portfolio-gallery .row-item';
		$column            = $settings['sg_setting_column_responsive'];

		if ( $count_breakpoints > 0 ) {
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ':nth-child(' . strval( $column['size'] ) . 'n) { border-right-width:0; } }';
		} else {
			$css .= $selector . ':nth-child(' . strval( $column['size'] ) . 'n) { border-right-width:0; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			if ( isset( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) ) {
				$column  = $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ];
				$css    .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ':nth-child(' . strval( $column['size'] ) . 'n) { border-right-width:0; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			if ( isset( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) ) {
				$column  = $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
				$css    .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ':nth-child(' . strval( $column['size'] ) . 'n) { border-right-width:0; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Social Share Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_social_share_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_social_share' !== $element->get_unique_name() ) {
			return;
		}

		$css         = '';
		$settings    = $element->get_settings();
		$breakpoints = jkit_get_responsive_breakpoints();
		$selector    = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-social-share .social-share-list > li a';
		$align       = $settings['sg_social_alignment_responsive'];

		if ( 'left' === $align ) {
			$css .= $selector . ' { margin-right: auto; }';
		} elseif ( 'center' === $align ) {
			$css .= $selector . ' { margin-left:auto; margin-right: auto; }';
		} else {
			$css .= $selector . ' { margin-left: auto; }';
		}

		foreach ( $breakpoints as $breakpoint ) {
			$align = $settings[ 'sg_social_alignment_responsive_' . $breakpoint['key'] ];

			if ( 'left' === $align ) {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { margin-left: unset; margin-right: auto; } }';
			} elseif ( 'center' === $align ) {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { margin-left:auto; margin-right: auto; } }';
			} else {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { margin-left: auto; margin-right: unset; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Progress Bar Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_progress_bar_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_progress_bar' !== $element->get_unique_name() ) {
			return;
		}

		$settings = $element->get_settings();

		if ( 'stripe' !== $settings['sg_progress_style'] ) {
			return;
		}

		$css = '';

		$breakpoints = jkit_get_responsive_breakpoints();
		$selector    = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-progress-bar .progress-group.stripe .progress-skill-bar .skill-bar .skill-track';
		$globals     = $settings['__globals__'];
		$track_color = $settings['st_track_stripe_color_responsive'];
		$track_bg    = $settings['st_track_stripe_background_color_responsive'];

		$color_default    = 'var(--jkit-txt-color)';
		$bg_color_default = 'var(--jkit-bg-color)';
		$color            = 'var(--jkit-txt-color)';
		$bg_color         = 'var(--jkit-bg-color)';

		if ( isset( $globals ) && isset( $globals['st_track_stripe_color_responsive'] ) && '' !== $globals['st_track_stripe_color_responsive'] ) {
			$track_color = 'var(--e-global-color-' . str_replace( 'globals/colors?id=', '', $globals['st_track_stripe_color_responsive'] ) . ')';
		}

		if ( isset( $globals ) && isset( $globals['st_track_stripe_background_color_responsive'] ) && '' !== $globals['st_track_stripe_background_color_responsive'] ) {
			$track_bg = 'var(--e-global-color-' . str_replace( 'globals/colors?id=', '', $globals['st_track_stripe_background_color_responsive'] ) . ')';
		}

		if ( $track_color || $track_bg ) {
			$color     = $track_color ? $track_color : $color_default;
			$bg_color  = $track_bg ? $track_bg : $bg_color_default;
			$css      .= $selector . ' { background: -o-repeating-linear-gradient(left, ' . $color . ', ' . $color . ' 4px, ' . $bg_color . ' 4px, ' . $bg_color . ' 8px); background: repeating-linear-gradient(to right, ' . $color . ', ' . $color . ' 4px, ' . $bg_color . ' 4px, ' . $bg_color . ' 8px); }';
		}

		foreach ( $breakpoints as $breakpoint ) {
			$track_color = $settings[ 'st_track_stripe_color_responsive_' . $breakpoint['key'] ];
			$track_bg    = $settings[ 'st_track_stripe_background_color_responsive_' . $breakpoint['key'] ];

			if ( isset( $globals ) && isset( $globals[ 'st_track_stripe_color_responsive_' . $breakpoint['key'] ] ) && '' !== $globals[ 'st_track_stripe_color_responsive_' . $breakpoint['key'] ] ) {
				$track_color = 'var(--e-global-color-' . str_replace( 'globals/colors?id=', '', $globals[ 'st_track_stripe_color_responsive_' . $breakpoint['key'] ] ) . ')';
			}

			if ( isset( $globals ) && isset( $globals[ 'st_track_stripe_background_color_responsive_' . $breakpoint['key'] ] ) && '' !== $globals[ 'st_track_stripe_background_color_responsive_' . $breakpoint['key'] ] ) {
				$track_bg = 'var(--e-global-color-' . str_replace( 'globals/colors?id=', '', $globals[ 'st_track_stripe_background_color_responsive_' . $breakpoint['key'] ] ) . ')';
			}

			if ( $track_color || $track_bg ) {
				$color     = $track_color ? $track_color : $color_default;
				$bg_color  = $track_bg ? $track_bg : $bg_color_default;
				$css      .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { background: -o-repeating-linear-gradient(left, ' . $color . ', ' . $color . ' 4px, ' . $bg_color . ' 4px, ' . $bg_color . ' 8px); background: repeating-linear-gradient(to right, ' . $color . ', ' . $color . ' 4px, ' . $bg_color . ' 4px, ' . $bg_color . ' 8px); } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Dual Button Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_dual_button_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_dual_button' !== $element->get_unique_name() ) {
			return;
		}

		$css         = '';
		$settings    = $element->get_settings();
		$breakpoints = jkit_get_responsive_breakpoints();
		$selector    = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-dual-button';
		$align       = $settings['sg_dual_alignment_responsive'];

		if ( 'left' === $align ) {
			$css .= $selector . ' { -webkit-box-pack: start; -ms-flex-pack: start; justify-content: flex-start; }';
		} elseif ( 'center' === $align ) {
			$css .= $selector . ' { -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; }';
		} else {
			$css .= $selector . ' { -webkit-box-pack: end; -ms-flex-pack: end; justify-content: flex-end; }';
		}

		foreach ( $breakpoints as $breakpoint ) {
			$align = $settings[ 'sg_dual_alignment_responsive_' . $breakpoint['key'] ];

			if ( 'left' === $align ) {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { -webkit-box-pack: start; -ms-flex-pack: start; justify-content: flex-start; } }';
			} elseif ( 'center' === $align ) {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; } }';
			} else {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' { -webkit-box-pack: end; -ms-flex-pack: end; justify-content: flex-end; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Feature List Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_feature_list_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_feature_list' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-feature-list';
		$position          = $settings['sg_setting_icon_position_responsive'];
		$connector_enable  = $settings['sg_setting_connector_enable'];

		if ( $count_breakpoints > 0 ) {
			if ( 'left' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: left; -webkit-box-orient: horizontal; -webkit-box-direction: normal; -ms-flex-direction: row; flex-direction: row; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-right: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: right; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} else {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-right: 0 !important;  margin-bottom: 0 !important; } }';
			}
		} elseif ( 'left' === $position ) {
			$css .= $selector . ' .feature-list-items .feature-list-item { text-align: left; -webkit-box-orient: horizontal; -webkit-box-direction: normal; -ms-flex-direction: row; flex-direction: row; display: -webkit-box; display: -ms-flexbox; display: flex; }';
			$css .= $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-right: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; }';
		} elseif ( 'right' === $position ) {
			$css .= $selector . ' .feature-list-items .feature-list-item { text-align: right; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; display: -webkit-box; display: -ms-flexbox; display: flex; }';
			$css .= $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; }';
		} else {
			$css .= $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-right: 0 !important;  margin-bottom: 0 !important; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			$position = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $i ]['key'] ];

			if ( 'left' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: left; -webkit-box-orient: horizontal; -webkit-box-direction: normal; -ms-flex-direction: row; flex-direction: row; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-right: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: right; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} else {
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-right: 0 !important;  margin-bottom: 0 !important; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			$position = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];

			if ( 'left' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: left; -webkit-box-orient: horizontal; -webkit-box-direction: normal; -ms-flex-direction: row; flex-direction: row; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-right: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { text-align: right; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; display: -webkit-box; display: -ms-flexbox; display: flex; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-top: 0 !important;  margin-bottom: 0 !important; } }';
			} else {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .feature-list-content-box { margin-left: 0 !important; margin-right: 0 !important;  margin-bottom: 0 !important; } }';
			}
		}

		if ( $connector_enable ) {
			$position          = $settings['sg_setting_icon_position_responsive'];
			$icon_size         = $settings['st_icon_circle_size_responsive'];
			$connector_type    = $settings['st_list_connector_type'];
			$icon_shape        = $settings['sg_setting_icon_shape'];
			$shape_view        = $settings['sg_setting_shape_view'];
			$border_width      = $settings['st_icon_border_width_responsive'];
			$border_width_size = '' !== $border_width['size'] ? intval( $border_width['size'] ) : 0;
			$offset            = '' !== $icon_size['size'] ? intval( $icon_size['size'] ) : 70;
			$prev_offset       = $offset;
			$prev_border_width = $border_width_size;

			if ( 'rhombus' === $icon_shape ) {
				$offset += 30;
			}

			if ( 'framed' === $shape_view ) {
				$offset += 2 * $border_width_size;
			}

			if ( $count_breakpoints > 0 ) {
				if ( 'left' === $position ) {
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: 0; right: calc(100% - ' . $offset . $icon_size['unit'] . '); } }';
				} elseif ( 'right' === $position ) {
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: calc(100% - ' . $offset . $icon_size['unit'] . '); right: 0; } }';
				} else {
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { display: none; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { padding-left: 50px; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:before { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 0px; top: 0; z-index: 1; border-right: none !important; height: 100%; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:after { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 5px; top: 50%; width: 23px; z-index: 2; border-top: none !important; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:not(:last-child):before { height: calc(100% + 8px); } }';
				}
			} elseif ( 'left' === $position ) {
				$css .= $selector . ' .feature-list-items .feature-list-item .connector { left: 0; right: calc(100% - ' . $offset . $icon_size['unit'] . '); }';
			} elseif ( 'right' === $position ) {
				$css .= $selector . ' .feature-list-items .feature-list-item .connector { left: calc(100% - ' . $offset . $icon_size['unit'] . '); right: 0; }';
			} else {
				$css .= $selector . ' .feature-list-items .feature-list-item .connector { display: none; }';
				$css .= $selector . ' .feature-list-items .feature-list-item { padding-left: 50px; }';
				$css .= $selector . ' .feature-list-items .feature-list-item:before { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 0px; top: 0; z-index: 1; border-right: none !important; height: 100%; }';
				$css .= $selector . ' .feature-list-items .feature-list-item:after { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 5px; top: 50%; width: 23px; z-index: 2; border-top: none !important; }';
				$css .= $selector . ' .feature-list-items .feature-list-item:not(:last-child):before { height: calc(100% + 8px); }';
			}

			for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
				$position    = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $i ]['key'] ];
				$icon_size   = $settings[ 'st_icon_circle_size_responsive_' . $breakpoints[ $i ]['key'] ];
				$offset      = '' !== $icon_size['size'] ? intval( $icon_size['size'] ) : $prev_offset;
				$prev_offset = $offset;

				if ( 'rhombus' === $icon_shape ) {
					$offset += 30;
				}

				if ( 'framed' === $shape_view ) {
					$border_width      = $settings[ 'st_icon_border_width_responsive_' . $breakpoints[ $i ]['key'] ];
					$border_width_size = '' !== $border_width['size'] ? intval( $border_width['size'] ) : $prev_border_width;
					$prev_border_width = $border_width_size;

					$offset += 2 * $border_width_size;
				}

				if ( 'left' === $position ) {
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: 0; right: calc(100% - ' . $offset . $icon_size['unit'] . ');} }';
				} elseif ( 'right' === $position ) {
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: calc(100% - ' . $offset . $icon_size['unit'] . '); right: 0; } }';
				} else {
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { display: none; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { padding-left: 50px; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:before { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 0px; top: 0; z-index: 1; border-right: none !important; height: 100%; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:after { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 5px; top: 50%; width: 23px; z-index: 2; border-top: none !important; } }';
					$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:not(:last-child):before { height: calc(100% + 8px); } }';
				}
			}

			if ( $count_breakpoints > 0 ) {
				$position    = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
				$icon_size   = $settings[ 'st_icon_circle_size_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
				$offset      = '' !== $icon_size['size'] ? intval( $icon_size['size'] ) : $prev_offset;
				$prev_offset = $offset;

				if ( 'rhombus' === $icon_shape ) {
					$offset += 30;
				}

				if ( 'framed' === $shape_view ) {
					$border_width      = $settings[ 'st_icon_border_width_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
					$border_width_size = '' !== $border_width['size'] ? intval( $border_width['size'] ) : $prev_border_width;
					$prev_border_width = $border_width_size;

					$offset += 2 * $border_width_size;
				}

				if ( 'left' === $position ) {
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: 0; right: calc(100% - ' . $offset . $icon_size['unit'] . '); } }';
				} elseif ( 'right' === $position ) {
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { left: calc(100% - ' . $offset . $icon_size['unit'] . '); right: 0; } }';
				} else {
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item .connector { display: none; } }';
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item { padding-left: 30px; } }';
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:before { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 0px; top: 0; z-index: 1; border-right: none !important; height: 100%; } }';
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:after { content: ""; position: absolute; display: block; border-style: solid; border-color: var(--jkit-element-bg-color); border-width: 1px; left: 5px; top: 50%; width: 23px; z-index: 2; border-top: none !important; } }';
					$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items .feature-list-item:not(:last-child):before { height: calc(100% + 8px); } }';
				}
			}

			if ( 'modern' === $connector_type ) {
				$position = $settings['sg_setting_icon_position_responsive'];

				if ( $count_breakpoints > 0 ) {
					if ( 'right' === $position ) {
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-right: 50px; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { right: 0 } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { right: 5px } }';
					} else {
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-left: 50px; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { left: 0; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { left: 5px; } }';
					}
				} elseif ( 'right' === $position ) {
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-right: 50px; }';
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { right: 0 }';
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { right: 5px }';
				} else {
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-left: 50px; }';
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { left: 0; }';
					$css .= $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { left: 5px; }';
				}

				for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
					$position = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $i ]['key'] ];

					if ( 'right' === $position ) {
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-right: 50px; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { right: 0; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { right: 5px; } }';
					} else {
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-left: 50px; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { left: 0; } }';
						$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { left: 5px; } }';
					}
				}

				if ( $count_breakpoints > 0 ) {
					$position = $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];

					if ( 'right' === $position ) {
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-right: 30px; } }';
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { right: 0; } }';
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:after { right: 5px; } }';

					} else {
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item { padding-left: 30px; } }';
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { left: 0; } }';
						$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .feature-list-items.connector-type-modern .feature-list-item:before { after: 5px; } }';

					}
				}
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Icon Box Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_icon_box_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_icon_box' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-icon-box';
		$position          = ( isset( $settings['sg_setting_icon_position'] ) && 'top' !== $settings['sg_setting_icon_position'] && empty( $settings['sg_setting_icon_position_responsive'] ) ) ? $settings['sg_setting_icon_position'] : $settings['sg_setting_icon_position_responsive'];

		if ( $count_breakpoints > 0 ) {
			if ( 'left' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; flex-direction: row; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: 15px; margin-left: unset; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-left: 15px; margin-right: unset; } }';
			} elseif ( 'top' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: block } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			} elseif ( 'bottom' === $position ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; flex-direction: column-reverse; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			}
		} elseif ( 'left' === $position ) {
			$css .= $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; flex-direction: row; }';
			$css .= $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: 15px; margin-left: unset; }';
		} elseif ( 'right' === $position ) {
			$css .= $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; }';
			$css .= $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-left: 15px; margin-right: unset; }';
		} elseif ( 'top' === $position ) {
			$css .= $selector . ' .jkit-icon-box-wrapper { display: block; }';
			$css .= $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; }';
		} elseif ( 'bottom' === $position ) {
			$css .= $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; flex-direction: column-reverse; }';
			$css .= $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			$max_min  = 'max';
			$position = isset( $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $i ]['key'] ] ) ? $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $i ]['key'] ] : '';

			if ( 'widescreen' === $breakpoints[ $i ]['key'] ) {
				$max_min = 'min';
			}

			if ( 'left' === $position ) {
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; flex-direction: row; } }';
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: 15px; margin-left: unset; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; } }';
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-left: 15px; margin-right: unset; } }';
			} elseif ( 'top' === $position ) {
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: block } }';
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			} elseif ( 'bottom' === $position ) {
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; flex-direction: column-reverse; } }';
				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			$position = isset( $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ] ) ? $settings[ 'sg_setting_icon_position_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ] : '';

			if ( 'left' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; flex-direction: row; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: 15px; margin-left: unset; } }';
			} elseif ( 'right' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: horizontal; -webkit-box-direction: reverse; -ms-flex-direction: row-reverse; flex-direction: row-reverse; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-left: 15px; margin-right: unset; } }';
			} elseif ( 'top' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: block } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			} elseif ( 'bottom' === $position ) {
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper { display: -webkit-box; display: -ms-flexbox; display: flex; flex-direction: column-reverse; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-icon-box-wrapper .icon-box.icon-box-header { margin-right: unset; margin-left: unset; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Testimonials Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_testimonials_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_testimonials' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-testimonials';
		$items             = $settings['sg_setting_slide_show_responsive'];

		if ( $count_breakpoints > 0 ) {
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item { width: calc(' . strval( $items['size'] ) . ') } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
		} else {
			$css .= $selector . ' .testimonials-track:not(.tns-slider) { display: flex; flex-direction: row; }';
			$css .= $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item { width: calc(' . strval( $items['size'] ) . ') }';
			$css .= $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; }';
			$css .= $selector . ' .testimonials-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			if ( isset( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) ) {
				$items = $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ];

				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item { width: calc(' . strval( $items['size'] ) . ') } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			if ( isset( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) ) {
				$items = $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];

				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item { width: calc(' . strval( $items['size'] ) . ') } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider) .testimonial-item:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .testimonials-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Client Logo Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_client_logo_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_client_logo' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-client-logo';
		$items             = $settings['sg_setting_slide_show_responsive'];

		if ( $count_breakpoints > 0 ) {
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider { width: calc(' . strval( $items['size'] ) . ') } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .client-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
		} else {
			$css .= $selector . ' .client-track:not(.tns-slider) { display: flex; flex-direction: row; }';
			$css .= $selector . ' .client-track:not(.tns-slider) .client-slider { width: calc(' . strval( $items['size'] ) . ') }';
			$css .= $selector . ' .client-track:not(.tns-slider) .client-slider:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; }';
			$css .= $selector . ' .client-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			if ( isset( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) ) {
				$items = $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $i ]['key'] ];

				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider { width: calc(' . strval( $items['size'] ) . ') } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			if ( isset( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) ) {
				$items = $settings[ 'sg_setting_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];

				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) { display: flex; flex-direction: row; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider { width: calc(' . strval( $items['size'] ) . ') } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider) .client-slider:nth-child(n+' . strval( (int) $items['size'] + 1 ) . ') { display: none; } }';
				$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .client-track:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Mailchimp Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_mailchimp_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_mailchimp' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$style             = $settings['sg_form_style'];
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-mailchimp';
		$mobile_breakpoint = array_filter(
			$breakpoints,
			function ( $v, $k ) {
				return 'mobile' === $v['key'];
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( count( $mobile_breakpoint ) > 0 && 'inline' === $style ) {
			sort( $mobile_breakpoint );
			$breakpoint_value = $mobile_breakpoint[0]['value'];

			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.style-inline .jkit-form-wrapper.extra-fields .jkit-submit-input-holder{ -webkit-box-flex:0; -ms-flex:0 0 100%; flex:0 0 100%; max-width:100%; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.jeg-elementor-kit.jkit-mailchimp.style-inline .jkit-form-wrapper.extra-fields .jkit-input-wrapper:nth-last-child(2) { margin-right: 0!important; } }';
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Post Block Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_post_block_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_post_block' !== $element->get_unique_name() ) {
			return;
		}

		$css                = '';
		$settings           = $element->get_settings();
		$breakpoints        = jkit_get_responsive_breakpoints();
		$type               = $settings['sg_content_postblock_type'];
		$content_breakpoint = $settings['sg_content_breakpoint'];
		$selector           = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-postblock';
		$custom_breakpoint  = array_filter(
			$breakpoints,
			function ( $v, $k ) use ( $content_breakpoint ) {
				return $content_breakpoint === $v['key'];
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( count( $custom_breakpoint ) > 0 ) {
			sort( $custom_breakpoint );
			$breakpoint_value = $custom_breakpoint[0]['value'];

			if ( 'type-1' === $type ) {
				$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.postblock-type-1 .jkit-post { display: block; -webkit-box-align: stretch; -ms-flex-align: stretch; align-items: stretch; } }';
				$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.postblock-type-1 .jkit-thumb { -webkit-box-flex: 1; -ms-flex: 1 0 auto; flex: 1 0 auto; max-width: 100%; } }';
			} elseif ( 'type-4' === $type ) {
				$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.postblock-type-4 .jkit-post { display: block; } }';
				$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.postblock-type-4 .jkit-thumb { -webkit-box-ordinal-group: 1; -ms-flex-order: 0; order: 0; -webkit-box-flex: 1; -ms-flex: 1 0 auto; flex: 1 0 auto; max-width: 100%; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Tabs Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_tabs_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_tabs' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$breakpoints       = jkit_get_responsive_breakpoints();
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-tabs';
		$mobile_breakpoint = array_filter(
			$breakpoints,
			function ( $v, $k ) {
				return 'mobile' === $v['key'];
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( count( $mobile_breakpoint ) > 0 ) {
			sort( $mobile_breakpoint );
			$breakpoint_value = $mobile_breakpoint[0]['value'];

			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .tab-nav-list { -ms-flex-wrap: wrap; flex-wrap: wrap; -webkit-box-orient: horizontal; -webkit-box-direction: normal; -ms-flex-flow: row wrap; flex-flow: row wrap; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .tab-nav-list .tab-nav { -webkit-box-flex: 1; -ms-flex: 1 1 auto; flex: 1 1 auto; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .tab-nav-list.caret-on .tab-nav.active::after { display: none; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.layout-vertical { -ms-flex-wrap: wrap; flex-wrap: wrap; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.layout-vertical .tab-navigation { -webkit-box-flex: 1; -ms-flex: 1 100%; flex: 1 100%; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.layout-vertical .tab-nav-list { -webkit-box-flex: 1; -ms-flex: 1 100%; flex: 1 100%; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.layout-vertical .tab-nav-list .tab-nav { width: 100%; height: auto !important; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . '.layout-vertical .tab-nav-list.caret-on .tab-nav.active::after { display: none; } }';
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Gallery Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_gallery_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_gallery' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-gallery';
		$columns           = $settings['sg_setting_column_responsive'];

		$css .= $selector . ' .gallery-items .gallery-item-wrap { width: calc(100% / ' . strval( intval( $columns['size'] ) ) . ' ); float: left; }';

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			if ( isset( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ]['size'] ) ) {
				$max_min = 'max';
				$columns = $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $i ]['key'] ];

				if ( 'widescreen' === $breakpoints[ $i ]['key'] ) {
					$max_min = 'min';
				}

				$css .= '@media (' . $max_min . '-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .gallery-items .gallery-item-wrap { width: calc(100% / ' . strval( intval( $columns['size'] ) ) . ' ); float: left; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			if ( isset( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) && ! empty( $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ]['size'] ) ) {
				$columns  = $settings[ 'sg_setting_column_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
				$css     .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .gallery-items .gallery-item-wrap { width: calc(100% / ' . strval( intval( $columns['size'] ) ) . ' ); float: left; } }';
			}
		}

		// Legacy galleries without a mobile column value use the base one-column layout.
		// Explicit mobile values (including multi-column footer galleries) remain intact.
		foreach ( $breakpoints as $breakpoint ) {
			if ( 'mobile' === $breakpoint['key'] && empty( $settings['sg_setting_column_responsive_mobile']['size'] ) ) {
				$css .= '@media (max-width: ' . strval( $breakpoint['value'] ) . 'px) {' . $selector . ' .gallery-items .gallery-item-wrap { width: 100%; float: left; } }';
			}
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add Nav Menu Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_nav_menu_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_nav_menu' !== $element->get_unique_name() ) {
			return;
		}

		$css                = '';
		$settings           = $element->get_settings();
		$breakpoints        = jkit_get_responsive_breakpoints();
		$content_breakpoint = $settings['sg_menu_breakpoint'];
		$selector           = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-nav-menu';
		$custom_breakpoint  = array_filter(
			$breakpoints,
			function ( $v, $k ) use ( $content_breakpoint ) {
				return $content_breakpoint === $v['key'];
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( count( $custom_breakpoint ) > 0 ) {
			sort( $custom_breakpoint );
			$breakpoint_value = $custom_breakpoint[0]['value'];

			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-hamburger-menu { display: block; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper { width: 100%; max-width: 360px; border-radius: 0; background-color: #f7f7f7; width: 100%; position: fixed; top: 0; left: -110%; height: 100%!important; box-shadow: 0 10px 30px 0 rgba(255,165,0,0); overflow-y: auto; overflow-x: hidden; padding-top: 0; padding-left: 0; padding-right: 0; display: flex; flex-direction: column-reverse; justify-content: flex-end; -moz-transition: left .6s cubic-bezier(.6,.1,.68,.53), width .6s; -webkit-transition: left .6s cubic-bezier(.6,.1,.68,.53), width .6s; -o-transition: left .6s cubic-bezier(.6,.1,.68,.53), width .6s; -ms-transition: left .6s cubic-bezier(.6,.1,.68,.53), width .6s; transition: left .6s cubic-bezier(.6,.1,.68,.53), width .6s; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper.active { left: 0; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu-container { overflow-y: hidden; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-nav-identity-panel { padding: 10px 0px 10px 0px; display: block; position: relative; z-index: 5; width: 100%; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-nav-identity-panel .jkit-nav-site-title { display: inline-block; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-nav-identity-panel .jkit-close-menu { display: block; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu { display: block; height: 100%; overflow-y: auto; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children > a i, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children > a .jkit-icon-svg, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children > a > svg, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.has-mega-menu > a i, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.has-mega-menu > a .jkit-icon-svg, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.has-mega-menu > a > svg { margin-left: auto; border: 1px solid var(--jkit-border-color); border-radius: 3px; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children > a i, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children > a .jkit-icon-svg, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.has-mega-menu > a i, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li.has-mega-menu > a .jkit-icon-svg { padding: 4px 15px; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li.menu-item-has-children .sub-menu { position: inherit; box-shadow: none; background: none; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li { display: block; width: 100%; position: inherit; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li .sub-menu { display: none; max-height: 2500px; opacity: 0; visibility: hidden; transition: max-height 5s ease-out; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li .sub-menu.dropdown-open { display: block; opacity: 1; visibility: visible; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li a { display: block; } }';
			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu li a i, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li a .jkit-icon-svg, ' . $selector . ' .jkit-menu-wrapper .jkit-menu li a > svg { float: right; } }';
			$css .= '@media (min-width: ' . strval( $breakpoint_value + 1 ) . 'px) {' . $selector . ' .jkit-menu-wrapper .jkit-menu-container { height: 100%; } }';

			$css .= '@media (max-width: ' . $breakpoint_value . 'px) {.admin-bar ' . $selector . ' .jkit-menu-wrapper { top: 32px; } }';
		}

		$css .= '@media (max-width: 782px) {.admin-bar ' . $selector . ' .jkit-menu-wrapper { top: 46px; } }';

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Add product carousel Custom CSS Handler
	 *
	 * @param object $post_css \Elementor\Core\DynamicTags\Dynamic_CSS.
	 * @param object $element  Element_Base.
	 */
	public function add_product_carousel_css( $post_css, $element ) {
		if ( $post_css instanceof \Elementor\Core\DynamicTags\Dynamic_CSS ) {
			return;
		}

		if ( 'jkit_product_carousel' !== $element->get_unique_name() ) {
			return;
		}

		$css               = '';
		$settings          = $element->get_settings();
		$breakpoints       = jkit_get_responsive_breakpoints();
		$count_breakpoints = count( $breakpoints );
		$selector          = '.elementor-element.elementor-element-' . $element->get_id() . ' .jeg-elementor-kit.jkit-product-carousel';
		$items             = $settings['sg_carousel_slide_show_responsive'];
		$spacing           = $settings['sg_carousel_margin_responsive'];

		if ( $count_breakpoints > 0 ) {
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) { display: flex; flex-direction: row; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block { width: calc(' . strval( $items['size'] ) . '); padding-right: ' . $spacing['size'] . $spacing['unit'] . '; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block:nth-child(n+' . strval( $items['size'] + 1 ) . ') { display: none; } }';
			$css .= '@media (min-width: ' . strval( $breakpoints[0]['value'] + 1 ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
		} else {
			$css .= $selector . ' .jkit-products:not(.tns-slider) { display: flex; flex-direction: row; }';
			$css .= $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block { width: calc(' . strval( $items['size'] ) . '); padding-right: ' . $spacing . '; }';
			$css .= $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block:nth-child(n+' . strval( $items['size'] + 1 ) . ') { display: none; }';
			$css .= $selector . ' .jkit-products:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; }';
		}

		for ( $i = 0; $i < $count_breakpoints - 1; $i++ ) {
			$items   = $settings[ 'sg_carousel_slide_show_responsive_' . $breakpoints[ $i ]['key'] ];
			$spacing = $settings['sg_carousel_margin_responsive'];

			if ( ! empty( $items['size'] ) ) {
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) { display: flex; flex-direction: row; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block { width: calc(' . strval( $items['size'] ) . '); padding-right: ' . $spacing['size'] . $spacing['unit'] . '; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block:nth-child(n+' . strval( $items['size'] + 1 ) . ') { display: none; } }';
				$css .= '@media (min-width: ' . strval( $breakpoints[ $i + 1 ]['value'] + 1 ) . 'px) and (max-width: ' . strval( $breakpoints[ $i ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
			}
		}

		if ( $count_breakpoints > 0 ) {
			$items   = $settings[ 'sg_carousel_slide_show_responsive_' . $breakpoints[ $count_breakpoints - 1 ]['key'] ];
			$spacing = $settings['sg_carousel_margin_responsive'];

			$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) { display: flex; flex-direction: row; } }';
			$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block { width: calc(' . strval( $items['size'] ) . '); padding-right: ' . $spacing['size'] . $spacing['unit'] . '; } }';
			$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider) .jkit-product-block:nth-child(n+' . strval( $items['size'] + 1 ) . ') { display: none; } }';
			$css .= '@media (max-width: ' . strval( $breakpoints[ $count_breakpoints - 1 ]['value'] ) . 'px) {' . $selector . ' .jkit-products:not(.tns-slider):not(:nth-child(' . strval( $items['size'] ) . ')) { margin-right: 10px; } }';
		}

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Register Additional Icons
	 *
	 * @param array $icons Registered icons.
	 */
	public function register_icons( $icons ) {
		$icon_url = $this->should_enqueue_font_icon_styles() ? JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/jkiticon.css' : '';

		$icons['jkiticon'] = array(
			'name'            => 'jkiticon',
			'label'           => esc_html__( 'Jeg Kit - Icons', 'jeg-elementor-kit' ),
			'url'             => $icon_url,
			'enqueue'         => array(),
			'prefix'          => 'jki-',
			'displayPrefix'   => '',
			'labelIcon'       => 'jki jki-jeg-kit-logo',
			'ver'             => JEG_ELEMENTOR_KIT_VERSION,
			'fetchJson'       => JEG_ELEMENTOR_KIT_URL . '/assets/fonts/jkiticon/jkiticon.js',
			'native'          => true,
			'render_callback' => array( $this, 'render_jkit_icon' ),
		);

		return $icons;
	}

	/**
	 * Render JKit icons as inline SVG for Elementor icon controls.
	 *
	 * @param array  $icon Icon data from Elementor.
	 * @param array  $attributes HTML attributes.
	 * @param string $tag Unused tag argument required by Elementor.
	 *
	 * @return string
	 */
	public function render_jkit_icon( $icon, $attributes = array(), $tag = 'i' ) {
		unset( $tag );

		if ( ! function_exists( 'jkit_render_icon_markup' ) || empty( $icon['value'] ) || ! is_string( $icon['value'] ) ) {
			return '';
		}

		if ( false === strpos( $icon['value'], 'jki-' ) ) {
			$icon['value'] = 'jki-' . $icon['value'];
		}

		return jkit_render_icon_markup( $icon['value'], $attributes, 'i' );
	}

	/**
	 * Remove form control script on edit.php page
	 */
	public function remove_form_control() {
		global $pagenow;

		$conditions = jkit_remove_form_control();

		if ( 'edit.php' === $pagenow || $conditions ) {
			remove_action( 'admin_enqueue_scripts', array( \Jeg\Form\Form_Builder::get_instance(), 'form_control_script' ), 10 );
			wp_enqueue_script(
				'jeg-form-builder-script',
				JEG_URL . '/assets/js/form/form-builder.js',
				array(
					'jquery',
					'underscore',
					'wp-util',
					'customize-controls',
					'customize-base',
					'wp-color-picker',
					'jquery-ui-spinner',
				),
				jeg_get_version(),
				true
			);
		}
	}

	/**
	 * SVG Upload Handler
	 * 
	 * Check if the file is an SVG, if so handle appropriately
	 *
	 * @param array $data An array of data for a single file.
	 *
	 * @return mixed
	 */
	public function svg_upload_handler( $data ) {
		if ( isset( $data['type'] ) && 'image/svg+xml' === $data['type'] ) {
			$sanitized = $this->svg_sanitizer( $data['file'] );

			if ( ! $sanitized ) {
				$data['error'] = esc_html__( 'This file cannot be sanitized; therefore, it cannot be checked for security and cannot be uploaded for security reasons. For further inquiries, please contact Jeg Kit support.', 'jeg-elementor-kit' );
			}
		}

		return $data;
	}

	/**
	 * Sanitize the SVG
	 *
	 * @param string $path File path.
	 *
	 * @return strng|false Sanitized SVG content or false on failure
	 */
	protected function svg_sanitizer( $path ) {
		$svg_sanitizer = new \enshrined\svgSanitize\Sanitizer();

		$file = file_get_contents( $path );
		$file = $svg_sanitizer->sanitize( $file );

		if ( $file ) {
			file_put_contents( $path, $file );
		}

		return $file;
	}

	/**
	 * Check loaded scripts
	 * 
	 * @param string $handle Script handle.
	 * 
	 * @return bool
	 */
	private function script_loaded( $handle ) {
		if ( isset( $this->loaded_scripts[ $handle ] ) && $this->loaded_scripts[ $handle ] ) {
			return true;
		}

		return false;
	}

	/**
	 * Find an option value inside an elements tree (DFS recursive).
	 *
	 * @param array $elements_data Elements array to search.
	 * @param string $option_id Option key to find.
	 * @param string|array $type (unused) kept for compatibility.
	 *
	 * @return mixed|null
	 */
	private function get_element_settings( $elements_data, $option_id, $type = null ) {
		if ( empty( $elements_data ) || ! is_array( $elements_data ) ) {
			return null;
		}

		foreach ( $elements_data as $element_data ) {
			if ( isset( $element_data['settings'] ) && array_key_exists( $option_id, $element_data['settings'] ) ) {
				return $element_data['settings'][ $option_id ];
			}

			if ( isset( $element_data['elements'] ) && ! empty( $element_data['elements'] ) && is_array( $element_data['elements'] ) ) {
				$value = $this->get_element_settings( $element_data['elements'], $option_id, $type );

				if ( null !== $value ) {
					return $value;
				}
			}
		}

		return null;
	}

	/**
	 * Load Conditional Scripts
	 * 
	 * @param \Elementor\Core\Base\Document $document Document instance.
	 * @param mixed $_is_excerpt Whether the excerpt is being called.
	 */
	public function enqueue_conditional_scripts( $document, $_is_excerpt ) {
		$elements_data = $document->get_elements_data();

		if ( ! $this->script_loaded( 'jkit-sticky-element' ) && $this->get_element_settings( $elements_data, 'jkit_sticky_section' ) === 'enabled' ) {
			$this->loaded_scripts['jkit-sticky-element'] = true;
			wp_enqueue_script( 'jkit-sticky-element' );
		}
	}
}
