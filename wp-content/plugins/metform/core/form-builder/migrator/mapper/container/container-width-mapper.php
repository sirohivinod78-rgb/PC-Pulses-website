<?php
/**
 * Container width mapper: handles classic column widths, custom boxed width, and content width settings.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Mapper\Container
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Mapper\Container;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves column widths, boxed boundaries, and content width mode.
 *
 * @since 4.1.9
 */
class Container_Width_Mapper {
	use Singleton;

	/**
	 * Maps container widths (element width, boxed width, and content width mode).
	 *
	 * @param array  $node          Container node.
	 * @param string $initial_width Width determined from atomic styles or default ('100%').
	 * @return array Width information [ 'width', 'boxed_width', 'content_width' ].
	 */
	public function map( array $node, string $initial_width = '100%' ): array {
		$settings = (array) ( $node['settings'] ?? array() );
		$width    = $this->resolve_column_width( $node, $initial_width );

		$boxed_width   = $this->resolve_boxed_width( $settings );
		$content_width = $this->resolve_content_width( $settings );

		return array(
			'width'         => $width,
			'boxed_width'   => $boxed_width,
			'content_width' => $content_width,
		);
	}

	/**
	 * Resolves column width from classic Elementor section/column settings.
	 *
	 * @param array  $node          Container node.
	 * @param string $default_width Fallback width.
	 * @return string Resolved width.
	 */
	public function resolve_column_width( array $node, string $default_width ): string {
		$el_type  = $node['elType'] ?? '';
		$settings = (array) ( $node['settings'] ?? array() );

		if ( 'column' === $el_type ) {
			if ( isset( $settings['_column_size'] ) ) {
				$col_size = Value_Unwrapper::unwrap( $settings['_column_size'] );
				if ( is_numeric( $col_size ) ) {
					return $col_size . '%';
				}
			} elseif ( isset( $settings['_inline_size'] ) ) {
				$inline_size = Value_Unwrapper::unwrap( $settings['_inline_size'] );
				if ( is_numeric( $inline_size ) ) {
					return $inline_size . '%';
				}
			}
		}

		return $default_width;
	}

	/**
	 * Resolves custom boxed width (e.g. '1600px', '1140px').
	 *
	 * @param array $settings Elementor container settings.
	 * @return string Boxed width with unit.
	 */
	public function resolve_boxed_width( array $settings ): string {
		$boxed_width = '1140px';

		if ( isset( $settings['boxed_width'] ) ) {
			$bw = Value_Unwrapper::unwrap( $settings['boxed_width'] );
			if ( is_array( $bw ) && isset( $bw['size'] ) && '' !== (string) $bw['size'] ) {
				$unit        = $bw['unit'] ?? 'px';
				$boxed_width = $bw['size'] . $unit;
			} elseif ( is_numeric( $bw ) && (float) $bw > 0 ) {
				$boxed_width = $bw . 'px';
			} elseif ( is_string( $bw ) && '' !== $bw ) {
				$boxed_width = preg_match( '/[a-z%]+$/i', $bw ) ? $bw : $bw . 'px';
			}
		}

		return $boxed_width;
	}

	/**
	 * Resolves content width setting ('full' or 'boxed').
	 *
	 * @param array $settings Elementor container settings.
	 * @return string 'full' or 'boxed'.
	 */
	public function resolve_content_width( array $settings ): string {
		$content_width_raw = (string) Value_Unwrapper::unwrap( $settings['content_width'] ?? '' );
		$has_boxed_setting = ! empty( $settings['boxed_width'] );

		return 'full' === $content_width_raw ? 'full' : ( ( 'boxed' === $content_width_raw || $has_boxed_setting ) ? 'boxed' : 'full' );
	}
}
