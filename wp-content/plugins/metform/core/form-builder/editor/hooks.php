<?php
/**
 * Standalone Form Builder hooks.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder\Editor;

use Elementor\Plugin as Elementor_Plugin;
use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Handles WordPress hooks for the standalone Form Builder.
 *
 * @since 4.1.8
 */
class Hooks {
	use Singleton;

	/**
	 * Registers actions and filters.
	 *
	 * @since 4.1.8
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'display_post_states', array( $this, 'add_post_states' ), 10, 2 );
	}

	/**
	 * Add post display state for MetForm forms in the admin list table.
	 *
	 * Displays "Metform Builder" alongside the form name for standalone builder forms,
	 * mirroring the "— Elementor" post state added by Elementor.
	 *
	 * @since 4.1.8
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 * @return array Filtered post display states.
	 */
	public function add_post_states( $post_states, $post ) {
		if ( ! $post || 'metform-form' !== $post->post_type ) {
			return $post_states;
		}

		if ( Repository::is_builder_form( $post->ID ) ) {
			unset( $post_states['elementor'] );
			$post_states['metform_builder'] = esc_html__( 'Metform Builder', 'metform' );
		} elseif ( ! class_exists( Elementor_Plugin::class ) ) {
			$post_states['elementor'] = esc_html__( 'Elementor', 'metform' );
		}

		return $post_states;
	}
}
