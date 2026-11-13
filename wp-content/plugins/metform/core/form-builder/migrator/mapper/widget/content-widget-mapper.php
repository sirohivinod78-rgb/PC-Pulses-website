<?php
/**
 * Content widget mapper for static content blocks (heading, paragraph, divider, etc.).
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Widget
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Widget;

use MetForm\Core\FormBuilder\Migrator\Style_Extractor;
use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Maps heading, paragraph, and divider blocks.
 *
 * @since 4.1.9
 */
class Content_Widget_Mapper {
	use Singleton;

	/**
	 * Maps heading / title blocks.
	 *
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array
	 */
	public function map_heading( array $settings, string $node_id, string $block_id, ?string $parent_id ): array {
		$raw_tag    = (string) Value_Unwrapper::unwrap( $settings['header_size'] ?? ( $settings['tag'] ?? 'h2' ) );
		$is_p       = 'p' === $raw_tag;
		$block_type = $is_p ? 'subtitle' : 'title';
		$tag        = $is_p ? 'p' : ( in_array( $raw_tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $raw_tag : 'h2' );

		$title_styles = array();
		$extractor    = Style_Extractor::instance();

		if ( ! empty( $settings['title_color'] ) ) {
			$title_styles['color'] = (string) Value_Unwrapper::unwrap( $settings['title_color'] );
		}
		$align_val = $settings['align'] ?? ( $settings['text_align'] ?? null );
		if ( ! empty( $align_val ) ) {
			$title_styles['textAlign'] = (string) Value_Unwrapper::unwrap( $align_val );
		}

		$typography = $extractor->extract_typography( $settings, 'typography_' );
		if ( $typography ) {
			$title_styles['typography'] = $typography;
		}
		$advanced_styles = $extractor->extract_advanced_styles( $settings );
		if ( ! empty( $advanced_styles ) ) {
			$title_styles['adv'] = $advanced_styles;
		}

		$props = array(
			'text' => (string) Value_Unwrapper::unwrap( $settings['title'] ?? ( $settings['text'] ?? '' ) ),
			'tag'  => $tag,
		);

		if ( ! empty( $title_styles ) ) {
			$props['styles'] = $title_styles;
		}

		$conditional_props = Conditional_Logic_Mapper::instance()->extract( $settings );
		if ( ! empty( $conditional_props ) ) {
			$props['conditional'] = $conditional_props['conditional'];
		}

		return array(
			'id'       => $block_id,
			'type'     => $block_type,
			'parentId' => $parent_id,
			'props'    => $props,
		);
	}

	/**
	 * Maps paragraph / text content blocks.
	 *
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array
	 */
	public function map_paragraph( array $settings, string $node_id, string $block_id, ?string $parent_id ): array {
		$text = (string) Value_Unwrapper::unwrap( $settings['editor'] ?? ( $settings['html'] ?? ( $settings['text'] ?? '' ) ) );

		$props = array(
			'text' => $text,
		);

		$styles    = array();
		$extractor = Style_Extractor::instance();

		$color = $settings['text_color'] ?? ( $settings['color'] ?? null );
		if ( ! empty( $color ) ) {
			$styles['color'] = (string) Value_Unwrapper::unwrap( $color );
		}

		$align = $settings['align'] ?? ( $settings['text_align'] ?? null );
		if ( ! empty( $align ) ) {
			$styles['textAlign'] = (string) Value_Unwrapper::unwrap( $align );
		}

		$typography = $extractor->extract_typography( $settings, 'typography_' );
		if ( $typography ) {
			$styles['typography'] = $typography;
		}

		$advanced_styles = $extractor->extract_advanced_styles( $settings );
		if ( ! empty( $advanced_styles ) ) {
			$styles['adv'] = $advanced_styles;
		}

		if ( ! empty( $styles ) ) {
			$props['styles'] = $styles;
		}

		$conditional_props = Conditional_Logic_Mapper::instance()->extract( $settings );
		if ( ! empty( $conditional_props ) ) {
			$props['conditional'] = $conditional_props['conditional'];
		}

		return array(
			'id'       => $block_id,
			'type'     => 'paragraph',
			'parentId' => $parent_id,
			'props'    => $props,
		);
	}

	/**
	 * Maps divider blocks.
	 *
	 * @param array       $settings  Widget settings.
	 * @param string      $node_id   Node ID.
	 * @param string      $block_id  Block ID.
	 * @param string|null $parent_id Parent container ID.
	 * @return array
	 */
	public function map_divider( array $settings, string $node_id, string $block_id, ?string $parent_id ): array {
		$color    = (string) Value_Unwrapper::unwrap( $settings['color'] ?? '#EAECF1' );
		$gap_size = isset( $settings['gap']['size'] ) && '' !== (string) $settings['gap']['size'] ? (int) $settings['gap']['size'] : 15;
		$gap      = max( 4, $gap_size ) . 'px';

		return array(
			'id'       => $block_id,
			'type'     => 'paragraph',
			'parentId' => $parent_id,
			'props'    => array(
				'text'   => ' ',
				'styles' => array(
					'border'  => array(
						'borderType' => 'solid',
						'width'      => array(
							'top'    => '1px',
							'right'  => '0px',
							'bottom' => '0px',
							'left'   => '0px',
						),
						'color'      => $color,
						'radius'     => array(
							'top'    => '0px',
							'right'  => '0px',
							'bottom' => '0px',
							'left'   => '0px',
						),
					),
					'margin'  => array(
						'top'    => $gap,
						'right'  => '0px',
						'bottom' => $gap,
						'left'   => '0px',
					),
					'padding' => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
				),
			),
		);
	}
}
