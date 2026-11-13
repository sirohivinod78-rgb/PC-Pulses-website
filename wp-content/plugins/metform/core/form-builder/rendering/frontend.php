<?php
namespace MetForm\Core\FormBuilder\Rendering;

use MetForm\Base\Assets_Enqueue;
use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Document\Repository;

defined( 'ABSPATH' ) || exit;

/** Provides frontend rendering and asset discovery for saved builder forms. */
final class Frontend {
	public static function get_markup( int $post_id ) {
		if ( ! Repository::is_builder_form( $post_id ) ) {
			return null;
		}

		if ( 'draft' === get_post_status( $post_id ) && ! current_user_can( 'edit_posts' ) ) {
			return '';
		}

		Assets_Enqueue::get( 'metform-builder-frontend' );
		$document = Repository::get( $post_id );
		foreach ( Block_Registry::instance()->used_in_document( is_array( $document ) ? $document : array() ) as $block ) {
			foreach ( $block->get_frontend_assets() as $handle ) {
				wp_enqueue_style( $handle );
				wp_enqueue_script( $handle );
			}
		}

		do_action( 'metform/form_builder/frontend/enqueue_assets', $post_id );
		do_action( 'metform/form_builder/frontend/enqueue_scripts', $post_id );

		return Form_Renderer::instance()->render_saved_form( $post_id );
	}
}
