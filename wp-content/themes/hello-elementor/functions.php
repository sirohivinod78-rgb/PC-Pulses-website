<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

use Elementor\WPNotificationsPackage\V110\Notifications as ThemeNotifications;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.3.0' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);
			add_theme_support( 'align-wide' );
			add_theme_support( 'responsive-embeds' );

			/*
			 * Editor Styles
			 */
			add_theme_support( 'editor-styles' );
			add_editor_style( 'editor-styles.css' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_custom_button_styles' ) ) {
	/**
	 * Enqueue custom CSS to override Elementor button styles.
	 *
	 * @return void
	 */
	function hello_elementor_custom_button_styles() {
		$custom_override_path = get_template_directory() . '/custom-override.css';

		wp_enqueue_style(
			'hello-elementor-custom-override',
			get_template_directory_uri() . '/custom-override.css',
			[],
			filemtime( $custom_override_path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_custom_button_styles', 20 );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Admin notice
if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

function hello_elementor_get_theme_notifications(): ThemeNotifications {
	static $notifications = null;

	if ( null === $notifications ) {
		require get_template_directory() . '/vendor/autoload.php';

		$notifications = new ThemeNotifications(
			'hello-elementor',
			HELLO_ELEMENTOR_VERSION,
			'theme'
		);
	}

	return $notifications;
}

hello_elementor_get_theme_notifications();

/**
 * PC Builder Enqueue Scripts and Styles
 */
if ( ! function_exists( 'hello_elementor_pc_builder_scripts' ) ) {
	function hello_elementor_pc_builder_scripts() {
		wp_enqueue_script(
			'hello-elementor-pc-builder-link',
			get_template_directory_uri() . '/pc-builder-link.js',
			[],
			filemtime( get_template_directory() . '/pc-builder-link.js' ),
			true
		);
		wp_localize_script(
			'hello-elementor-pc-builder-link',
			'pcBuilderLinkConfig',
			[
				'target' => home_url( '/pc-builder-india/' ),
			]
		);

		// Only load on the PC Builder page or custom route.
		if (
			! is_page_template( 'template-pc-builder.php' )
			&& intval( get_query_var( 'pc_builder_page', 0 ) ) !== 1
		) {
			return;
		}

		// Enqueue PC Builder CSS
		wp_enqueue_style(
			'hello-elementor-pc-builder',
			get_template_directory_uri() . '/pc-builder.css',
			[],
			filemtime( get_template_directory() . '/pc-builder.css' )
		);

		// Load component data
		$data = require get_template_directory() . '/pc-builder-data.php';

		// Enqueue PC Builder JS
		wp_enqueue_script(
			'hello-elementor-pc-builder',
			get_template_directory_uri() . '/pc-builder.js',
			[],
			filemtime( get_template_directory() . '/pc-builder.js' ),
			true
		);

		// Pass component data to JavaScript
		wp_localize_script(
			'hello-elementor-pc-builder',
			'pcBuilderComponents',
			$data
		);
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_pc_builder_scripts', 20 );

/**
 * Add PC Builder route for a /pc-builder/ landing URL.
 */
if ( ! function_exists( 'hello_elementor_pc_builder_rewrite_rule' ) ) {
	function hello_elementor_pc_builder_rewrite_rule() {
		add_rewrite_rule( '^pc-builder/?$', 'index.php?pc_builder_page=1', 'top' );
	}
}

if ( ! function_exists( 'hello_elementor_pc_builder_query_vars' ) ) {
	function hello_elementor_pc_builder_query_vars( $vars ) {
		$vars[] = 'pc_builder_page';
		return $vars;
	}
}

if ( ! function_exists( 'hello_elementor_pc_builder_template' ) ) {
	function hello_elementor_pc_builder_template( $template ) {
		if ( intval( get_query_var( 'pc_builder_page', 0 ) ) === 1 ) {
			return get_template_directory() . '/template-pc-builder.php';
		}
		return $template;
	}
}

add_action( 'init', 'hello_elementor_pc_builder_rewrite_rule' );
add_filter( 'query_vars', 'hello_elementor_pc_builder_query_vars' );
add_filter( 'template_include', 'hello_elementor_pc_builder_template', 99 );
add_action( 'after_switch_theme', 'flush_rewrite_rules' );

/**
 * Add global JavaScript functions for PC Builder
 */
if ( ! function_exists( 'hello_elementor_pc_builder_inline_script' ) ) {
	function hello_elementor_pc_builder_inline_script() {
		if ( ! is_page_template( 'template-pc-builder.php' ) && intval( get_query_var( 'pc_builder_page', 0 ) ) !== 1 ) {
			return;
		}
		?>
		<script>
		function exportBuild() {
			if (window.pcBuilder) {
				const buildData = window.pcBuilder.exportBuild();
				const element = document.createElement("a");
				element.setAttribute("href", "data:text/plain;charset=utf-8," + encodeURIComponent(buildData));
				element.setAttribute("download", "pc-build-" + new Date().toISOString().split('T')[0] + ".json");
				element.style.display = "none";
				document.body.appendChild(element);
				element.click();
				document.body.removeChild(element);
				alert("Build exported successfully!");
			}
		}

		function resetBuild() {
			if (window.pcBuilder && confirm("Are you sure you want to reset your build? This cannot be undone.")) {
				window.pcBuilder.resetBuild();
			}
		}
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'hello_elementor_pc_builder_inline_script' );

if ( ! function_exists( 'hello_elementor_pc_builder_body_class' ) ) {
	function hello_elementor_pc_builder_body_class( $classes ) {
		if (
			is_page_template( 'template-pc-builder.php' )
			|| is_page_template( 'template-pc-builder-india.php' )
			|| intval( get_query_var( 'pc_builder_page', 0 ) ) === 1
		) {
			$classes[] = 'pc-builder-page';
		}
		return $classes;
	}
}
add_filter( 'body_class', 'hello_elementor_pc_builder_body_class' );

/**
 * Register PC Builder as a page template option
 */
if ( ! function_exists( 'hello_elementor_register_custom_templates' ) ) {
	function hello_elementor_register_custom_templates( $templates ) {
		$templates['template-pc-builder.php'] = 'PC Builder';
		$templates['template-pc-builder-india.php'] = 'PC Builder India';
		return $templates;
	}
}
add_filter( 'theme_page_templates', 'hello_elementor_register_custom_templates' );

if ( ! function_exists( 'hello_elementor_pc_builder_india_shortcode' ) ) {
	function hello_elementor_pc_builder_india_shortcode() {
		return sprintf(
			'<iframe class="pc-builder-india-frame" src="%1$s" title="%2$s" width="100%%" height="1000" loading="eager"></iframe>',
			esc_url( home_url( '/pc-builder-india/index.html?embed=1' ) ),
			esc_attr__( 'PC Builder India', 'hello-elementor' )
		);
	}
}
add_shortcode( 'pc_pulses_builder_app', 'hello_elementor_pc_builder_india_shortcode' );

if ( ! function_exists( 'hello_elementor_ensure_pc_builder_india_page' ) ) {
	function hello_elementor_ensure_pc_builder_india_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = get_page_by_path( 'pc-builder-india', OBJECT, 'page' );
		$page_id = absint( get_option( 'hello_elementor_pc_builder_india_page_id' ) );

		if ( ! $page && $page_id ) {
			$stored_page = get_post( $page_id );
			if ( $stored_page && 'pc-builder-india' === $stored_page->post_name ) {
				$page = $stored_page;
			}
		}

		if ( $page && ( 'page' !== $page->post_type || 'trash' === $page->post_status ) ) {
			$page = null;
		}

		if ( ! $page ) {
			$page_id = wp_insert_post(
				[
					'post_title'   => 'PC Builder India',
					'post_name'    => 'pc-builder-india',
					'post_type'    => 'page',
					'post_status'  => 'publish',
					'post_content' => '[pc_pulses_builder_app]',
				],
				true
			);

			if ( is_wp_error( $page_id ) ) {
				error_log( 'PC Builder India page creation failed: ' . $page_id->get_error_message() );
				return;
			}

			$page = get_post( $page_id );
		} elseif ( false === strpos( $page->post_content, '[pc_pulses_builder_app]' ) ) {
			$updated_page_id = wp_update_post(
				[
					'ID'           => $page->ID,
					'post_content' => trim( $page->post_content . "\n\n[pc_pulses_builder_app]" ),
				],
				true
			);

			if ( is_wp_error( $updated_page_id ) ) {
				error_log( 'PC Builder India page update failed: ' . $updated_page_id->get_error_message() );
				return;
			}
		}

		if ( ! $page ) {
			error_log( 'PC Builder India page could not be loaded after creation.' );
			return;
		}

		update_post_meta( $page->ID, '_wp_page_template', 'template-pc-builder-india.php' );
		update_option( 'hello_elementor_pc_builder_india_page_id', $page->ID );
	}
}
add_action( 'admin_init', 'hello_elementor_ensure_pc_builder_india_page', 20 );