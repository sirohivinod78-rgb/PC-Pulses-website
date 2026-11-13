<?php
/**
 * WPML Tabs module integration.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Integrations\WPML;

/**
 * Class Tabs_Module
 *
 * @package Jeg\Elementor_Kit\Integrations\WPML
 */
class Tabs_Module extends \WPML_Elementor_Module_With_Items {
	/**
	 * Get repeater field key.
	 *
	 * @return string
	 */
	public function get_items_field() {
		return 'sg_content_list';
	}

	/**
	 * Get translatable repeater fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'sg_content_list_title',
			'sg_content_list_description',
			'sg_content_list_button_link' => array( 'url' ),
			'sg_content_list_button_text',
			'sg_content_text',
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
			case 'sg_content_list_title':
				return esc_html__( 'Jeg Kit Tabs: Tab: Title', 'jeg-elementor-kit' );
			case 'sg_content_list_description':
				return esc_html__( 'Jeg Kit Tabs: Tab: Description', 'jeg-elementor-kit' );
			case 'url':
				return esc_html__( 'Jeg Kit Tabs: Tab: Button Link', 'jeg-elementor-kit' );
			case 'sg_content_list_button_text':
				return esc_html__( 'Jeg Kit Tabs: Tab: Button Text', 'jeg-elementor-kit' );
			case 'sg_content_text':
				return esc_html__( 'Jeg Kit Tabs: Tab: Content', 'jeg-elementor-kit' );
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
			case 'sg_content_list_title':
			case 'sg_content_list_button_text':
				return 'LINE';
			case 'sg_content_list_description':
			case 'sg_content_text':
				return 'VISUAL';
			case 'url':
				return 'LINK';
			default:
				return '';
		}
	}
}
