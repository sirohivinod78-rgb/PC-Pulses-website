<?php
namespace MetForm\Core\FormBuilder\Blocks\Title;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Style_Controls;

defined( 'ABSPATH' ) || exit;

final class Title extends Block_Base {
	private const TAGS = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );

	public function get_name(): string {
		return 'title'; }
	public function get_title(): string {
		return __( 'Title', 'metform' ); }
	public function get_icon(): string {
		return 'Heading1'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'content'; }

	public function get_default_settings(): array {
		return array(
			'text'   => __( 'Title', 'metform' ),
			'tag'    => 'h1',
			'styles' => array( 'color' => '#111827' ),
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
					'label'   => __( 'Title Text', 'metform' ),
					'default' => __( 'Title', 'metform' ),
				),
				array(
					'type'    => 'select',
					'key'     => 'tag',
					'label'   => __( 'HTML Tag', 'metform' ),
					'default' => 'h1',
					'options' => array_map(
						static fn( $tag ) => array(
							'label' => strtoupper( $tag ),
							'value' => $tag,
						),
						self::TAGS
					),
				),
			),
			'style'   => Style_Controls::typography( '#111827', '.mfb-title__heading' ),
		);
	}

	public function render( array $settings, Render_Context $context ): string {
		$tag   = in_array( $settings['tag'], self::TAGS, true ) ? $settings['tag'] : 'h1';
		$class = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		// mfb-title__wrapper / mfb-title__heading match title/index.jsx and
		// Title.css exactly (base margin/weight/color/size) — see the
		// Progress log's field-class-name bug for why this matters.
		return sprintf(
			'<div class="mfb-title__wrapper %3$s" data-mf-block-id="%1$s"><%2$s class="mfb-title__heading">%4$s</%2$s></div>',
			esc_attr( $context->get_block_id() ),
			esc_attr( $tag ),
			esc_attr( $class ),
			esc_html( $settings['text'] )
		);
	}
}
