<?php
/**
 * WPML Client Logo module integration.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Integrations\WPML;

/**
 * Class Client_Logo_Module
 *
 * @package Jeg\Elementor_Kit\Integrations\WPML
 */
class Client_Logo_Module extends \WPML_Elementor_Module_With_Items {
	/**
	 * Get repeater field key.
	 *
	 * @return string
	 */
	public function get_items_field() {
		return 'sg_logo_list';
	}

	/**
	 * Get translatable repeater fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array( 'sg_logo_list_title', 'sg_logo_list_link' => array( 'url' ) );
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
			case 'sg_logo_list_title':
				return esc_html__( 'Jeg Kit Client Logo: Logo: Title', 'jeg-elementor-kit' );
			case 'url':
				return esc_html__( 'Jeg Kit Client Logo: Logo: Link', 'jeg-elementor-kit' );
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
			case 'sg_logo_list_title':
				return 'LINE';
			case 'url':
				return 'LINK';
			default:
				return '';
		}
	}
}
