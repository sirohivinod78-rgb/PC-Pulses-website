<?php
/**
 * Background extractor for converting Elementor background group controls to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts classic (color, image) and gradient background settings.
 *
 * @since 4.1.9
 */
class Background_Extractor {
	use Singleton;

	/**
	 * Extracts background properties (Classic: color, image with position/size/repeat/attachment;
	 * Gradient: type, angle, position, colorStart, locationStart, colorEnd, locationEnd;
	 * and optional overlay opacity) from Elementor Group_Control_Background settings.
	 *
	 * @param array  $settings Elementor settings array.
	 * @param string $prefix   Key prefix (e.g. 'background_', 'background_hover_', '_background_', '_background_hover_', 'background_overlay_').
	 * @return array|null Form Builder background control value array, or null if not active.
	 */
	public function extract( array $settings, string $prefix = 'background_' ): ?array {
		$bg_type = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'background' ] ?? '' );

		// Check if an image is explicitly configured even if bg_type is not set
		$img_raw       = Value_Unwrapper::unwrap( $settings[ $prefix . 'image' ] ?? null );
		$has_image_url = false;
		if ( is_array( $img_raw ) && ! empty( $img_raw['url'] ) ) {
			$has_image_url = true;
		} elseif ( is_string( $img_raw ) && '' !== trim( $img_raw ) ) {
			$has_image_url = true;
		}

		// If neither classic/gradient nor an image is present, avoid unselected ghost colors (e.g. #FF0000 in templates 12/5)
		if ( 'classic' !== $bg_type && 'gradient' !== $bg_type && ! $has_image_url ) {
			return null;
		}

		$dimension_extractor = Dimension_Extractor::instance();

		// Handle Gradient
		if ( 'gradient' === $bg_type ) {
			$color_start = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'color' ] ?? '' );
			$color_end   = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'color_b' ] ?? '' );

			if ( '' === $color_start && '' === $color_end ) {
				return null;
			}

			$g_type = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'gradient_type' ] ?? 'linear' );
			if ( 'radial' !== $g_type ) {
				$g_type = 'linear';
			}

			$loc_start = 0;
			if ( isset( $settings[ $prefix . 'color_stop' ] ) ) {
				$cs = Value_Unwrapper::unwrap( $settings[ $prefix . 'color_stop' ] );
				if ( is_array( $cs ) && isset( $cs['size'] ) && '' !== (string) $cs['size'] ) {
					$loc_start = (float) $cs['size'];
				} elseif ( is_numeric( $cs ) ) {
					$loc_start = (float) $cs;
				}
			}

			$loc_end = 100;
			if ( isset( $settings[ $prefix . 'color_b_stop' ] ) ) {
				$cbs = Value_Unwrapper::unwrap( $settings[ $prefix . 'color_b_stop' ] );
				if ( is_array( $cbs ) && isset( $cbs['size'] ) && '' !== (string) $cbs['size'] ) {
					$loc_end = (float) $cbs['size'];
				} elseif ( is_numeric( $cbs ) ) {
					$loc_end = (float) $cbs;
				}
			}

			$angle = 180;
			if ( isset( $settings[ $prefix . 'gradient_angle' ] ) ) {
				$ga = Value_Unwrapper::unwrap( $settings[ $prefix . 'gradient_angle' ] );
				if ( is_array( $ga ) && isset( $ga['size'] ) && '' !== (string) $ga['size'] ) {
					$angle = (float) $ga['size'];
				} elseif ( is_numeric( $ga ) ) {
					$angle = (float) $ga;
				}
			}

			$pos = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'gradient_position' ] ?? 'center center' );
			if ( '' === $pos ) {
				$pos = 'center center';
			}

			$result = array(
				'type'     => 'gradient',
				'gradient' => array(
					'type'          => $g_type,
					'angle'         => $angle,
					'position'      => $pos,
					'colorStart'    => $color_start,
					'locationStart' => $loc_start,
					'colorEnd'      => $color_end,
					'locationEnd'   => $loc_end,
				),
			);

			// Overlay opacity if present
			$opacity = $dimension_extractor->extract_opacity_value( $settings[ $prefix . 'opacity' ] ?? null );
			if ( null !== $opacity ) {
				$result['opacity'] = $opacity;
			}

			return $result;
		}

		// Handle Classic
		$color = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'color' ] ?? '' );
		$url   = '';
		$id    = 0;

		if ( is_array( $img_raw ) ) {
			$url = (string) ( $img_raw['url'] ?? '' );
			$id  = (int) ( $img_raw['id'] ?? 0 );
		} elseif ( is_string( $img_raw ) && '' !== $img_raw ) {
			$url = $img_raw;
		}

		if ( '' === $color && '' === $url ) {
			return null;
		}

		$result = array(
			'type'  => 'classic',
			'color' => $color,
		);

		if ( '' !== $url ) {
			// Position (handles presets and custom xpos/ypos)
			$pos_raw = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'position' ] ?? '' );
			if ( 'custom' === $pos_raw ) {
				$x_pos    = $dimension_extractor->extract_size_value( $settings[ $prefix . 'xpos' ] ?? null ) ?? '0%';
				$y_pos    = $dimension_extractor->extract_size_value( $settings[ $prefix . 'ypos' ] ?? null ) ?? '0%';
				$position = "{$x_pos} {$y_pos}";
			} elseif ( '' !== $pos_raw ) {
				$position = $pos_raw;
			} else {
				$position = 'center center';
			}

			// Repeat
			$repeat_raw = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'repeat' ] ?? '' );
			$repeat     = '' !== $repeat_raw ? $repeat_raw : 'no-repeat';

			// Size (handles cover, contain, auto, and custom bg_width)
			$size_raw = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'size' ] ?? '' );
			if ( 'custom' === $size_raw ) {
				$bg_width = $dimension_extractor->extract_size_value( $settings[ $prefix . 'bg_width' ] ?? null );
				$size     = $bg_width ?: 'cover';
			} elseif ( '' !== $size_raw ) {
				$size = $size_raw;
			} else {
				$size = 'cover';
			}

			// Attachment
			$att_raw    = (string) Value_Unwrapper::unwrap( $settings[ $prefix . 'attachment' ] ?? '' );
			$attachment = '' !== $att_raw ? $att_raw : 'scroll';

			$image = array(
				'url'        => $url,
				'position'   => $position,
				'size'       => $size,
				'repeat'     => $repeat,
				'attachment' => $attachment,
			);
			if ( $id > 0 ) {
				$image['id'] = $id;
			}

			$result['image'] = $image;
		}

		// Overlay opacity if present
		$opacity = $dimension_extractor->extract_opacity_value( $settings[ $prefix . 'opacity' ] ?? null );
		if ( null !== $opacity ) {
			$result['opacity'] = $opacity;
		}

		return $result;
	}
}
