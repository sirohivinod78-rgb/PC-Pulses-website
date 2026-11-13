<?php
namespace MetForm\Core\FormBuilder\Blocks\Paragraph;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Style_Controls;

defined( 'ABSPATH' ) || exit;

final class Paragraph extends Block_Base {
	public function get_name(): string {
		return 'paragraph'; }
	public function get_title(): string {
		return __( 'Paragraph', 'metform' ); }
	public function get_icon(): string {
		return 'AlignLeft'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'content'; }

	public function get_default_settings(): array {
		return array(
			'text'   => '',
			'styles' => array( 'color' => '#374151' ),
		);
	}

	public function get_property_schema(): array {
		return array(
			'text' => array( 'type' => 'string' ),
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
					'label'   => __( 'Default Text', 'metform' ),
					'default' => '',
				),
			),
			// The wrapper's only child: the <p> on the page, the editable
			// <input> in the editor canvas (ParagraphCanvas.jsx).
			'style'   => Style_Controls::typography( '#374151', '> *' ),
		);
	}

	public function render( array $settings, Render_Context $context ): string {
		$class = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		// mfb-paragraph__wrapper / mfb-paragraph__text match paragraph/index.jsx
		// and its Paragraph.css exactly.
		return sprintf(
			'<div class="mfb-paragraph__wrapper %2$s" data-mf-block-id="%1$s"><p class="mfb-paragraph__text">%3$s</p></div>',
			esc_attr( $context->get_block_id() ),
			esc_attr( $class ),
			esc_html( $settings['text'] )
		);
	}
}
