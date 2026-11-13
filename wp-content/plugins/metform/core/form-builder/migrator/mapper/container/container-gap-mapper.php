<?php
/**
 * Container gap mapper: handles grid rows/columns, gap calculations, and collision handling.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Container
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Container;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves grid dimensions and flex/grid gaps for layout containers.
 *
 * @since 4.1.9
 */
class Container_Gap_Mapper {
	use Singleton;

	/**
	 * Maps grid dimensions and layout gaps.
	 *
	 * @param string $layout   Container layout type ('grid' or 'flex').
	 * @param array  $settings Elementor container settings.
	 * @param array  $elements Child elements.
	 * @return array Gaps and grid dimensions [ 'col_gap', 'row_gap', 'grid_rows', 'grid_cols' ].
	 */
	public function map( string $layout, array $settings, array $elements = array() ): array {
		[ $grid_rows, $grid_cols ] = $this->resolve_grid_dimensions( $settings, $elements );
		[ $col_gap, $row_gap ]     = $this->resolve_gaps( $layout, $settings, $grid_cols );

		return array(
			'col_gap'   => $col_gap,
			'row_gap'   => $row_gap,
			'grid_rows' => $grid_rows,
			'grid_cols' => $grid_cols,
		);
	}

	/**
	 * Resolves grid rows and columns configuration.
	 *
	 * @param array $settings Elementor container settings.
	 * @param array $elements Child elements array.
	 * @return array [ $grid_rows, $grid_cols ]
	 */
	private function resolve_grid_dimensions( array $settings, array $elements ): array {
		$grid_rows = 1;
		$grid_cols = 2;

		if ( isset( $settings['grid_rows_grid'] ) ) {
			$gr = Value_Unwrapper::unwrap( $settings['grid_rows_grid'] );
			if ( is_array( $gr ) && isset( $gr['size'] ) && '' !== (string) $gr['size'] ) {
				$grid_rows = max( 1, (int) $gr['size'] );
			} elseif ( is_numeric( $gr ) && (int) $gr > 0 ) {
				$grid_rows = (int) $gr;
			}
		}

		if ( isset( $settings['grid_columns_grid'] ) ) {
			$gc = Value_Unwrapper::unwrap( $settings['grid_columns_grid'] );
			if ( is_array( $gc ) && isset( $gc['size'] ) && '' !== (string) $gc['size'] ) {
				$grid_cols = (int) $gc['size'];
			} elseif ( is_numeric( $gc ) ) {
				$grid_cols = (int) $gc;
			}
		} elseif ( ! empty( $elements ) && is_array( $elements ) ) {
			$child_count = count( $elements );
			if ( $child_count > 0 ) {
				$grid_cols = max( 1, (int) ceil( $child_count / $grid_rows ) );
			}
		}

		return array( $grid_rows, $grid_cols );
	}

	/**
	 * Resolves column and row gaps for flex and grid layouts.
	 *
	 * @param string $layout    'grid' or 'flex'.
	 * @param array  $settings  Elementor container settings.
	 * @param int    $grid_cols Current grid column count.
	 * @return array [ $col_gap, $row_gap ]
	 */
	private function resolve_gaps( string $layout, array $settings, int $grid_cols ): array {
		$col_gap = '16px';
		$row_gap = '16px';

		if ( 'grid' === $layout ) {
			if ( isset( $settings['grid_gaps'] ) && is_array( $settings['grid_gaps'] ) ) {
				$gg   = $settings['grid_gaps'];
				$unit = $gg['unit'] ?? 'px';
				if ( isset( $gg['column'] ) && '' !== (string) $gg['column'] ) {
					$col_gap = $gg['column'] . $unit;
				}
				if ( isset( $gg['row'] ) && '' !== (string) $gg['row'] ) {
					$row_gap = $gg['row'] . $unit;
				}
			} elseif ( isset( $settings['flex_gap'] ) ) {
				$fg = $settings['flex_gap'];
				if ( is_array( $fg ) ) {
					$unit = $fg['unit'] ?? 'px';
					if ( isset( $fg['column'] ) && '' !== (string) $fg['column'] ) {
						$col_gap = $fg['column'] . $unit;
					}
					if ( isset( $fg['row'] ) && '' !== (string) $fg['row'] ) {
						$row_gap = $fg['row'] . $unit;
					}
				} elseif ( is_numeric( $fg ) ) {
					$col_gap = $fg . 'px';
					$row_gap = $fg . 'px';
				}
			}

			// In case flex_gap has larger row gap
			if ( isset( $settings['flex_gap'] ) && is_array( $settings['flex_gap'] ) ) {
				$fg   = $settings['flex_gap'];
				$unit = $fg['unit'] ?? 'px';
				if ( isset( $fg['row'] ) && '' !== (string) $fg['row'] && (int) $fg['row'] > (int) $row_gap ) {
					$row_gap = $fg['row'] . $unit;
				}
			}

			// Prevent 0px collision in multi-column grids
			if ( $grid_cols > 1 && ( '0px' === $col_gap || '0' === $col_gap || 0 === (int) $col_gap ) ) {
				if ( ! empty( $settings['grid_gaps_tablet']['column'] ) ) {
					$col_gap = ( (string) $settings['grid_gaps_tablet']['column'] ) . ( $settings['grid_gaps_tablet']['unit'] ?? 'px' );
				} else {
					$col_gap = '24px';
				}
			}
		} elseif ( isset( $settings['flex_gap'] ) ) {
				$fg = $settings['flex_gap'];
			if ( is_array( $fg ) ) {
				$unit = $fg['unit'] ?? 'px';
				if ( isset( $fg['column'] ) && '' !== (string) $fg['column'] ) {
					$col_gap = $fg['column'] . $unit;
				}
				if ( isset( $fg['row'] ) && '' !== (string) $fg['row'] ) {
					$row_gap = $fg['row'] . $unit;
				}
			} elseif ( is_numeric( $fg ) ) {
				$col_gap = $fg . 'px';
				$row_gap = $fg . 'px';
			}
		}

		return array( $col_gap, $row_gap );
	}
}
