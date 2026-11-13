<?php
/**
 * Keep Elementor's generated styles in sync with its effective markup mode.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Assets;

class Markup_Compatibility {
	/** @var Markup_Compatibility */
	private static $instance;

	/** @return Markup_Compatibility */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	private function __construct() {
		add_action( 'elementor/init', array( $this, 'synchronize_styles' ), 99 );
	}

	/**
	 * A Default feature selection can change effective state across upgrades.
	 * Invalidate once per mode, before CSS or cached widget HTML is rendered.
	 * Elementor generates the appropriate native and JegKit control selectors;
	 * do not rewrite those selectors or change the site's feature preference.
	 */
	public function synchronize_styles() {
		$elementor = \Elementor\Plugin::$instance;
		if ( ! isset( $elementor->experiments, $elementor->files_manager ) ) {
			return;
		}

		$mode = $elementor->experiments->is_feature_active( 'e_optimized_markup' ) ? 'optimized' : 'legacy';
		if ( get_option( 'jkit_elementor_markup_mode' ) === $mode ) {
			return;
		}

		// Includes Elementor's element cache, which can otherwise retain old wrappers.
		$elementor->files_manager->clear_cache();
		update_option( 'jkit_elementor_markup_mode', $mode, false );
	}
}
