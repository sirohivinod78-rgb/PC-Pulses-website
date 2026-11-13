<?php
namespace MetForm\Core\FormBuilder\Blocks\Subtitle;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Style_Controls;

defined( 'ABSPATH' ) || exit;

final class Subtitle extends Block_Base {
	private const TAGS = array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' );

	public function get_name(): string {
		return 'subtitle'; }
	public function get_title(): string {
		return __( 'Subtitle', 'metform' ); }
	public function get_icon(): string {
		return 'Heading2'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'content'; }

	public function get_default_settings(): array {
		return array(
			'text'   => __( 'Subtitle', 'metform' ),
			'tag'    => 'h2',
			'styles' => array( 'color' => '#6b7280' ),
		);
	}

	public function get_property_schema(): array {
		return array(
			'text' => array( 'type' => 'string' ),
			'tag'  => array(
				'type' => 'string',
				'enum' => self::TAGS,
			),
		);
	}


	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'text',
					'key'     => 'text',
					'label'   => __( 'Subtitle Text', 'metform' ),
					'default' => __( 'Subtitle', 'metform' ),
				),
				array(
					'type'    => 'select',
					'key'     => 'tag',
					'label'   => __( 'HTML Tag', 'metform' ),
					'default' => 'h2',
					'options' => array_map(
						static fn( $tag ) => array(
							'label' => 'p' === $tag ? 'p' : strtoupper( $tag ),
							'value' => $tag,
						),
						self::TAGS
					),
				),
			),
			'style'   => Style_Controls::typography( '#6b7280', '.mfb-subtitle__heading' ),
		);
	}

	public function render( array $settings, Render_Context $context ): string {
		$tag   = in_array( $settings['tag'], self::TAGS, true ) ? $settings['tag'] : 'h2';
		$class = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		// mfb-subtitle__wrapper / mfb-subtitle__heading match subtitle/index.jsx
		// and Subtitle.css exactly.
		return sprintf(
			'<div class="mfb-subtitle__wrapper %3$s" data-mf-block-id="%1$s"><%2$s class="mfb-subtitle__heading">%4$s</%2$s></div>',
			esc_attr( $context->get_block_id() ),
			esc_attr( $tag ),
			esc_attr( $class ),
			esc_html( $settings['text'] )
		);
	}
}
