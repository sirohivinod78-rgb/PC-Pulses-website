<?php
/**
 * WPML Feature List module integration.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Integrations\WPML;

/**
 * Class Feature_List_Module
 *
 * @package Jeg\Elementor_Kit\Integrations\WPML
 */
class Feature_List_Module extends \WPML_Elementor_Module_With_Items {
	/**
	 * Get repeater field key.
	 *
	 * @return string
	 */
	public function get_items_field() {
		return 'sg_setting_list';
	}

	/**
	 * Get translatable repeater fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'sg_setting_list_title',
			'sg_setting_list_content',
			'sg_setting_list_link' => array( 'url' ),
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
			case 'sg_setting_list_title':
				return esc_html__( 'Jeg Kit Feature List: Feature Item: Title', 'jeg-elementor-kit' );
			case 'sg_setting_list_content':
				return esc_html__( 'Jeg Kit Feature List: Feature Item: Content', 'jeg-elementor-kit' );
			case 'url':
				return esc_html__( 'Jeg Kit Feature List: Feature Item: Link', 'jeg-elementor-kit' );
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
			case 'sg_setting_list_title':
				return 'LINE';
			case 'sg_setting_list_content':
				return 'AREA';
			case 'url':
				return 'LINK';
			default:
				return '';
		}
	}
}
