<?php
/**
 * WPML Gallery module integration.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Integrations\WPML;

/**
 * Class Gallery_Module
 *
 * @package Jeg\Elementor_Kit\Integrations\WPML
 */
class Gallery_Module extends \WPML_Elementor_Module_With_Items {
	/**
	 * Get repeater field key.
	 *
	 * @return string
	 */
	public function get_items_field() {
		return 'sg_gallery_list';
	}

	/**
	 * Get translatable repeater fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'sg_gallery_list_video_link' => array( 'url' ),
			'sg_gallery_list_control_name',
			'sg_gallery_list_item_name',
			'sg_gallery_list_price',
			'sg_gallery_list_category',
			'sg_gallery_list_content',
			'sg_gallery_list_link' => array( 'url' ),
		);
	}

	/**
	 * Get translation label.
	 *
	 * @param string $field Field key.
	 *
	 * @return string
	 */
	protected function get_title( $field ) {
		switch ( $field ) {
			case 'url':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Link', 'jeg-elementor-kit' );
			case 'sg_gallery_list_control_name':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Control Name', 'jeg-elementor-kit' );
			case 'sg_gallery_list_item_name':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Item Name', 'jeg-elementor-kit' );
			case 'sg_gallery_list_price':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Price', 'jeg-elementor-kit' );
			case 'sg_gallery_list_category':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Category', 'jeg-elementor-kit' );
			case 'sg_gallery_list_content':
				return esc_html__( 'Jeg Kit Gallery: Gallery Item: Content', 'jeg-elementor-kit' );
			default:
				return '';
		}
	}

	/**
	 * Get WPML editor type.
	 *
	 * @param string $field Field key.
	 *
	 * @return string
	 */
	protected function get_editor_type( $field ) {
		switch ( $field ) {
			case 'url':
				return 'LINK';
			case 'sg_gallery_list_content':
				return 'VISUAL';
			case 'sg_gallery_list_control_name':
			case 'sg_gallery_list_item_name':
			case 'sg_gallery_list_price':
			case 'sg_gallery_list_category':
				return 'LINE';
			default:
				return '';
		}
	}
}
