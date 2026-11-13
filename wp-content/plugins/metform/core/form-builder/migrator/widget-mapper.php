<?php
/**
 * Widget mapper: maps Elementor MetForm widgets to Standalone Form Builder blocks.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Advanced_Widget_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Choice_Widget_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Conditional_Logic_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Content_Widget_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Fallback_Widget_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Widget\Input_Property_Mapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Translates Elementor widget nodes into Form Builder block elements.
 * Coordinates delegation to specialized domain mappers.
 *
 * @since 4.1.9
 */
class Widget_Mapper {
	use Singleton;

	/**
	 * Declarative mapping table for standard input fields.
	 * Format: [ 'block_type', 'default_label' ]
	 */
	private const STANDARD_INPUTS = array(
		'text'          => array( 'input', 'Input' ),
		'input'         => array( 'input', 'Input' ),
		'text-input'    => array( 'input', 'Input' ),
		'short-text'    => array( 'input', 'Input' ),
		'email'         => array( 'email', 'Email' ),
		'email-address' => array( 'email', 'Email' ),
		'telephone'     => array( 'telephone', 'Telephone' ),
		'phone'         => array( 'telephone', 'Telephone' ),
		'tel'           => array( 'telephone', 'Telephone' ),
		'password'      => array( 'password', 'Password' ),
		'url'           => array( 'urlinput', 'URL' ),
		'urlinput'      => array( 'urlinput', 'URL' ),
		'website'       => array( 'urlinput', 'URL' ),
		'listing-fname' => array( 'firstnamelisting', 'First Name' ),
		'first-name'    => array( 'firstnamelisting', 'First Name' ),
		'firstname'     => array( 'firstnamelisting', 'First Name' ),
		'listing-lname' => array( 'lastnamelisting', 'Last Name' ),
		'last-name'     => array( 'lastnamelisting', 'Last Name' ),
		'lastname'      => array( 'lastnamelisting', 'Last Name' ),
	);

	/**
	 * Declarative mapping table for Pro fallback widgets.
	 * Format: [ 'pro_block_name', 'pro_type', 'free_fallback_type', 'default_label' ]
	 */
	private const PRO_FALLBACK_INPUTS = array(
		'mobile'       => array( 'mobile', 'mobile', 'telephone', 'Mobile' ),
		'credit-card'  => array( 'creditcard', 'creditcard', 'input', 'Credit Card' ),
		'creditcard'   => array( 'creditcard', 'creditcard', 'input', 'Credit Card' ),
		'signature'    => array( 'signature', 'signature', 'input', 'Signature' ),
		'color-picker' => array( 'colorpicker', 'colorpicker', 'input', 'Color Picker' ),
		'colorpicker'  => array( 'colorpicker', 'colorpicker', 'input', 'Color Picker' ),
		'like-dislike' => array( 'likedislike', 'likedislike', 'rating', 'Like Dislike' ),
		'likedislike'  => array( 'likedislike', 'likedislike', 'rating', 'Like Dislike' ),
	);

	/**
	 * Translates an Elementor widget to a Form Builder block element.
	 *
	 * @param string      $widget_type Elementor widget identifier.
	 * @param array       $settings    Elementor widget settings.
	 * @param string      $node_id     Original node ID.
	 * @param string|null $parent_id   Parent container ID.
	 * @return array|null Mapped Form Builder block or null if unsupported.
	 */
	public function map( string $widget_type, array $settings, string $node_id, ?string $parent_id ): ?array {
		$block_id = sanitize_key( 'block-' . $node_id );

		// Tier 1: Allow custom widget mapping via filter
		$custom_block = apply_filters( 'metform/form_builder/migrate_widget', null, $widget_type, $settings, $node_id, $parent_id );
		if ( is_array( $custom_block ) ) {
			return $custom_block;
		}

		// Tier 2: Normalize identifier: lowercase, remove brackets and prefixes
		$type = strtolower( trim( str_replace( '[]', '', $widget_type ) ) );
		$type = preg_replace( '/^(mf[-_]|metform[-_])/', '', $type );

		// A. Declarative Standard Inputs
		if ( isset( self::STANDARD_INPUTS[ $type ] ) ) {
			[ $block_type, $label ] = self::STANDARD_INPUTS[ $type ];
			return array(
				'id'       => $block_id,
				'type'     => $block_type,
				'parentId' => $parent_id,
				'props'    => Input_Property_Mapper::instance()->map( $settings, $node_id, esc_html__( $label, 'metform' ) ),
			);
		}

		// B. Declarative Pro Fallback Inputs
		if ( isset( self::PRO_FALLBACK_INPUTS[ $type ] ) ) {
			[ $pro_key, $pro_type, $fallback_type, $label ] = self::PRO_FALLBACK_INPUTS[ $type ];
			$is_pro = null !== Block_Registry::instance()->get( $pro_key );
			return array(
				'id'       => $block_id,
				'type'     => $is_pro ? $pro_type : $fallback_type,
				'parentId' => $parent_id,
				'props'    => Input_Property_Mapper::instance()->map( $settings, $node_id, esc_html__( $label, 'metform' ) ),
			);
		}

		// C. Content widgets (heading, title, paragraph, html, divider)
		if ( in_array( $type, array( 'heading', 'title' ), true ) ) {
			return Content_Widget_Mapper::instance()->map_heading( $settings, $node_id, $block_id, $parent_id );
		}
		if ( in_array( $type, array( 'text-editor-core', 'simple-message', 'html' ), true ) ) {
			return Content_Widget_Mapper::instance()->map_paragraph( $settings, $node_id, $block_id, $parent_id );
		}
		if ( 'divider' === $type ) {
			return Content_Widget_Mapper::instance()->map_divider( $settings, $node_id, $block_id, $parent_id );
		}

		// D. Choice / Selection widgets
		if ( in_array(
			$type,
			array(
				'select',
				'dropdown',
				'multi-select',
				'multiselect',
				'radio',
				'radio-button',
				'radio-group',
				'toggle-select',
				'image-select',
				'checkbox',
				'checkbox-group',
			),
			true
		) ) {
			return Choice_Widget_Mapper::instance()->map( $type, $settings, $node_id, $block_id, $parent_id );
		}

		// E. Advanced & Specialized widgets
		$advanced_block = Advanced_Widget_Mapper::instance()->map( $type, $settings, $node_id, $block_id, $parent_id );
		if ( null !== $advanced_block ) {
			return $advanced_block;
		}

		// Tier 3: Heuristic Fallback
		return Fallback_Widget_Mapper::instance()->map( $settings, $node_id, $block_id, $parent_id );
	}

	/**
	 * Maps paragraph / text content blocks.
	 *
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent ID.
	 * @return array
	 */
	public function map_paragraph( array $settings, string $node_id, string $block_id, ?string $parent_id ): array {
		return Content_Widget_Mapper::instance()->map_paragraph( $settings, $node_id, $block_id, $parent_id );
	}

	/**
	 * Wraps a non-empty styles array in ['styles' => $styles] for merging into props.
	 *
	 * @param array $styles Styles dictionary.
	 * @return array
	 */
	public function extract_styles_wrapper( array $styles ): array {
		return Style_Extractor::instance()->wrap( $styles );
	}

	/**
	 * Extracts conditional logic configuration from Elementor widget settings.
	 *
	 * @param array $settings Widget settings.
	 * @return array ['conditional' => [...]] or empty array if no active condition.
	 */
	public function extract_conditional_props( array $settings ): array {
		return Conditional_Logic_Mapper::instance()->extract( $settings );
	}

	/**
	 * Maps common attributes for standard input fields.
	 *
	 * @param array  $settings      Widget settings.
	 * @param string $node_id       Node ID.
	 * @param string $default_label Default label text.
	 * @return array
	 */
	public function map_standard_input_props( array $settings, string $node_id, string $default_label = 'Input' ): array {
		return Input_Property_Mapper::instance()->map( $settings, $node_id, $default_label );
	}

	/**
	 * Maps option fields (select, radio, checkbox, image-select) and normalizes repeaters.
	 *
	 * @param array  $settings      Widget settings.
	 * @param string $node_id       Node ID.
	 * @param string $default_label Default label text.
	 * @return array
	 */
	public function map_option_props( array $settings, string $node_id, string $default_label = 'Options' ): array {
		return Choice_Widget_Mapper::instance()->map_option_props( $settings, $node_id, $default_label );
	}

	/**
	 * Resolves a safe, non-empty field name.
	 *
	 * @param array  $settings Widget settings.
	 * @param string $fallback Fallback name.
	 * @return string
	 */
	public function resolve_name( array $settings, string $fallback ): string {
		return Input_Property_Mapper::instance()->resolve_name( $settings, $fallback );
	}
}
