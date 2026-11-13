<?php
/**
 * WPML Team module integration.
 *
 * @package jeg-kit
 */

namespace Jeg\Elementor_Kit\Integrations\WPML;

/**
 * Class Team_Module
 *
 * @package Jeg\Elementor_Kit\Integrations\WPML
 */
class Team_Module extends \WPML_Elementor_Module_With_Items {
	/**
	 * Get repeater field key.
	 *
	 * @return string
	 */
	public function get_items_field() {
		return 'sg_social_icon';
	}

	/**
	 * Get translatable repeater fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			'sg_social_label',
			'sg_social_link' => array( 'url' ),
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
			case 'sg_social_label':
				return esc_html__( 'Jeg Kit Team: Social Profile: Label', 'jeg-elementor-kit' );
			case 'url':
				return esc_html__( 'Jeg Kit Team: Social Profile: Link', 'jeg-elementor-kit' );
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
			case 'sg_social_label':
				return 'LINE';
			case 'url':
				return 'LINK';
			default:
				return '';
		}
	}
}
