<?php
/**
 * Post Featured Image View Class
 *
 * @package jeg-kit
 * @author Jegtheme
 * @since 1.5.0
 */

namespace Jeg\Elementor_Kit\Elements\Views;

/**
 * Class Post_Featured_Image_View
 *
 * @package Jeg\Elementor_Kit\Elements\Views
 */
class Post_Featured_Image_View extends View_Abstract {
	/**
	 * Build block content
	 */
	public function build_content() {
		$content = '';
		$post_id = get_the_ID();
		$size    = $this->attribute['sg_image_size_imagesize_size'];
		$title   = get_the_title( $post_id );
		$image   = '';

		if ( has_post_thumbnail( $post_id ) ) {
			$image_attr = $this->get_widget_image_attributes( $this->attribute, 'sg_image' );
			$image_attr = apply_filters( 'jkit_render_image_element', $image_attr, $this->attribute );
			$image      = get_the_post_thumbnail( $post_id, $size, $image_attr );
		}

		if ( empty( $image ) && jeg_is_editor_elementor() ) {
			$image_sizes = wp_get_registered_image_subsizes();
			$dimensions  = isset( $image_sizes[ $size ] ) ? $image_sizes[ $size ] : $image_sizes['large'];
			$image       = $this->render_widget_image_element(
				array(
					'url'    => \Elementor\Utils::get_placeholder_image_src(),
					'width'  => $dimensions['width'],
					'height' => $dimensions['height'],
				),
				$size,
				'sg_image',
				null,
				null,
				null,
				$title
			);
		}

		if ( ! empty( $image ) ) {
			$link_to   = $this->attribute['sg_image_link_to'];
			$animation = ! empty( $this->attribute['st_image_hover_animation'] ) ? 'elementor-animation-' . esc_attr( $this->attribute['st_image_hover_animation'] ) : '';

			switch ( $link_to ) {
				case 'home':
					$content = sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_home_url() ), $image );
					break;
				case 'post':
					$content = sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_the_permalink() ), $image );
					break;
				case 'media':
					$content = sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_the_post_thumbnail_url() ), $image );
					break;
				case 'custom':
					$content = $this->render_url_element( $this->attribute['sg_image_link_to_custom'], null, null, $image );
					break;
				default:
					$content = $image;
					break;
			}

			$content = sprintf( '<div class="post-featured-image %1$s">%2$s</div>', $animation, $content );
		}

		return $this->render_wrapper( 'post-featured-image', $content );
	}
}
