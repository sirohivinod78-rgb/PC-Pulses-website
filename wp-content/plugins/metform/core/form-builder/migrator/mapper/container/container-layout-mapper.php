<?php
/**
 * Container layout mapper: handles flex/grid layout types, directions, alignment, and stacking heuristics.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Container
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Container;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves layout, direction, alignment, wrap, and smart row-to-column stacking heuristics.
 *
 * @since 4.1.9
 */
class Container_Layout_Mapper {
	use Singleton;

	/**
	 * Maps container layout properties from modern styles, settings, and children hierarchy.
	 *
	 * @param array $node Elementor container node.
	 * @return array Layout definitions (layout, direction, justify, align, wrap, width).
	 */
	public function map( array $node ): array {
		$el_type  = $node['elType'] ?? '';
		$settings = (array) ( $node['settings'] ?? array() );
		$styles   = (array) ( $node['styles'] ?? array() );

		$direction = 'column';
		$width     = '100%';
		$layout    = ( 'grid' === ( $settings['container_type'] ?? '' ) || 'e-grid' === $el_type ) ? 'grid' : 'flex';
		$justify   = 'flex-start';
		$align     = 'stretch';
		$wrap      = 'nowrap';

		// 1. Parse modern Elementor atomic classes and variants ($node['styles'])
		$atomic_props = $this->extract_atomic_props( $styles );
		if ( isset( $atomic_props['direction'] ) ) {
			$direction = $atomic_props['direction'];
		}
		if ( isset( $atomic_props['width'] ) ) {
			$width = $atomic_props['width'];
		}
		if ( isset( $atomic_props['justify'] ) ) {
			$justify = $atomic_props['justify'];
		}
		if ( isset( $atomic_props['align'] ) ) {
			$align = $atomic_props['align'];
		}
		if ( isset( $atomic_props['wrap'] ) ) {
			$wrap = $atomic_props['wrap'];
		}

		// 2. Parse flex settings from $node['settings']
		if ( isset( $settings['flex_direction'] ) ) {
			$dir_val = (string) Value_Unwrapper::unwrap( $settings['flex_direction'] );
			if ( in_array( $dir_val, array( 'row', 'row-reverse' ), true ) ) {
				$direction = 'row';
			} elseif ( in_array( $dir_val, array( 'column', 'column-reverse' ), true ) ) {
				$direction = 'column';
			}
		}

		if ( isset( $settings['flex_justify_content'] ) ) {
			$justify_val = (string) Value_Unwrapper::unwrap( $settings['flex_justify_content'] );
			if ( in_array( $justify_val, array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ), true ) ) {
				$justify = $justify_val;
			}
		}

		if ( isset( $settings['flex_align_items'] ) ) {
			$align_val = (string) Value_Unwrapper::unwrap( $settings['flex_align_items'] );
			if ( in_array( $align_val, array( 'stretch', 'flex-start', 'center', 'flex-end' ), true ) ) {
				$align = $align_val;
			}
		}

		if ( isset( $settings['flex_wrap'] ) ) {
			$wrap = (string) Value_Unwrapper::unwrap( $settings['flex_wrap'] );
		}

		// 3. Apply smart row-to-column stacking heuristics
		[ $direction, $wrap ] = $this->apply_stacking_heuristics( $direction, $wrap, $node );

		return array(
			'layout'    => $layout,
			'direction' => $direction,
			'justify'   => $justify,
			'align'     => $align,
			'wrap'      => $wrap,
			'width'     => $width,
		);
	}

	/**
	 * Extracts layout properties from modern atomic CSS variants.
	 *
	 * @param array $styles Node styles array.
	 * @return array Extracted props.
	 */
	private function extract_atomic_props( array $styles ): array {
		$extracted = array();

		foreach ( $styles as $class_def ) {
			$variants = (array) ( $class_def['variants'] ?? array() );
			foreach ( $variants as $variant ) {
				$breakpoint = $variant['meta']['breakpoint'] ?? '';
				$props      = (array) ( $variant['props'] ?? array() );

				if ( '' === $breakpoint || 'desktop' === $breakpoint ) {
					if ( isset( $props['flex-direction'] ) ) {
						$dir_val = (string) Value_Unwrapper::unwrap( $props['flex-direction'] );
						if ( in_array( $dir_val, array( 'row', 'row-reverse' ), true ) ) {
							$extracted['direction'] = 'row';
						} elseif ( in_array( $dir_val, array( 'column', 'column-reverse' ), true ) ) {
							$extracted['direction'] = 'column';
						}
					}
					if ( isset( $props['width'] ) ) {
						$w_val = Value_Unwrapper::unwrap( $props['width'] );
						if ( is_numeric( $w_val ) ) {
							$extracted['width'] = $w_val . '%';
						} elseif ( is_string( $w_val ) && '' !== $w_val ) {
							$extracted['width'] = $w_val;
						}
					}
					if ( isset( $props['justify-content'] ) ) {
						$extracted['justify'] = (string) Value_Unwrapper::unwrap( $props['justify-content'] );
					}
					if ( isset( $props['align-items'] ) ) {
						$extracted['align'] = (string) Value_Unwrapper::unwrap( $props['align-items'] );
					}
					if ( isset( $props['flex-wrap'] ) ) {
						$extracted['wrap'] = (string) Value_Unwrapper::unwrap( $props['flex-wrap'] );
					}
				}
			}
		}

		return $extracted;
	}

	/**
	 * Applies smart row-to-column stacking heuristics.
	 *
	 * In Elementor, a container with flex_direction = row and flex_wrap = wrap behaves as a vertical column
	 * because its children default to width 100% (_element_width = inherit).
	 * In Form Builder, widgets do not take 100% width inside row containers, so direction = row would force
	 * all fields into a single horizontal line across the screen.
	 *
	 * @param string $direction Current flex direction.
	 * @param string $wrap      Current flex wrap.
	 * @param array  $node      Container node.
	 * @return array [ $direction, $wrap ]
	 */
	private function apply_stacking_heuristics( string $direction, string $wrap, array $node ): array {
		if ( 'row' !== $direction ) {
			return array( $direction, $wrap );
		}

		$is_inner    = ! empty( $node['isInner'] );
		$children    = (array) ( $node['elements'] ?? array() );
		$child_count = count( $children );

		if ( ! $is_inner ) {
			// Root form containers in Form Builder always stack their rows/sections vertically.
			return array( 'column', 'nowrap' );
		}

		if ( 'wrap' === $wrap && $child_count > 1 ) {
			// If the inner container explicitly has children with custom width,
			// preserve row wrap so inline fields can sit side-by-side.
			$has_inline_fields = false;
			foreach ( $children as $child_node ) {
				$cs = (array) ( $child_node['settings'] ?? array() );
				if ( ! empty( $cs['_element_custom_width'] ) || ! empty( $cs['_custom_width'] ) ) {
					$has_inline_fields = true;
					break;
				}
			}

			if ( ! $has_inline_fields ) {
				// Check if the inner container contains headings, text-editors, buttons, child containers,
				// or full-width elements that form a vertical sequence.
				$has_stacked_blocks = false;
				foreach ( $children as $child_node ) {
					$wt = $child_node['widgetType'] ?? '';
					$ct = $child_node['elType'] ?? '';
					if ( 'container' === $ct || in_array( $wt, array( 'heading', 'text-editor', 'divider', 'button', 'mf-button' ), true ) ) {
						$has_stacked_blocks = true;
						break;
					}
					$ew = $child_node['settings']['_element_width'] ?? '';
					if ( 'inherit' === $ew ) {
						$has_stacked_blocks = true;
						break;
					}
				}
				if ( $has_stacked_blocks ) {
					return array( 'column', 'nowrap' );
				}
			}
		}

		return array( $direction, $wrap );
	}
}
