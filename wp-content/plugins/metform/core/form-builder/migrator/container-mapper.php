<?php
/**
 * Container mapper: maps Elementor sections, columns, and atomic containers to Form Builder props.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Core\FormBuilder\Migrator\Mapper\Container\Container_Gap_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Container\Container_Layout_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Container\Container_Style_Mapper;
use MetForm\Core\FormBuilder\Migrator\Mapper\Container\Container_Width_Mapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Handles layout containers (section, column, container, e-flexbox, e-grid) conversion.
 * Coordinates delegation to specialized domain mappers.
 *
 * @since 4.1.9
 */
class Container_Mapper {
	use Singleton;

	/**
	 * Maps container properties inspecting modern styles, classic settings, and children hierarchy.
	 *
	 * @param array $node Elementor container node.
	 * @return array Form Builder container props.
	 */
	public function map_props( array $node ): array {
		$settings = (array) ( $node['settings'] ?? array() );
		$elements = (array) ( $node['elements'] ?? array() );

		// 1. Resolve layout, direction, alignment, wrap, and smart stacking heuristics.
		$layout_mapper = Container_Layout_Mapper::instance();
		$layout_data   = $layout_mapper->map( $node );

		// 2. Resolve grid rows/cols and flex/grid gaps.
		$gap_mapper = Container_Gap_Mapper::instance();
		$gap_data   = $gap_mapper->map( $layout_data['layout'], $settings, $elements );

		// 3. Resolve container column width, boxed width, and content width mode.
		$width_mapper = Container_Width_Mapper::instance();
		$width_data   = $width_mapper->map( $node, $layout_data['width'] );

		// 4. Resolve box styles, responsive padding/margins, backgrounds, borders, shadows, and overlays.
		$style_mapper = Container_Style_Mapper::instance();
		$box_styles   = $style_mapper->map( $settings, $width_data['width'], $gap_data['col_gap'], $gap_data['row_gap'] );

		$props = array(
			'layoutType'   => $layout_data['layout'],
			'direction'    => $layout_data['direction'],
			'justify'      => $layout_data['justify'],
			'alignItems'   => $layout_data['align'],
			'wrap'         => $layout_data['wrap'],
			'contentWidth' => $width_data['content_width'],
			'boxedWidth'   => $width_data['boxed_width'],
			'styles'       => $box_styles,
		);

		if ( 'grid' === $layout_data['layout'] ) {
			$props['gridColumns'] = $gap_data['grid_cols'];
			$props['gridRows']    = $gap_data['grid_rows'];
		}

		$conditional_props = Widget_Mapper::instance()->extract_conditional_props( $settings );
		if ( ! empty( $conditional_props ) ) {
			$props['conditional'] = $conditional_props['conditional'];
		}

		return $props;
	}
}
