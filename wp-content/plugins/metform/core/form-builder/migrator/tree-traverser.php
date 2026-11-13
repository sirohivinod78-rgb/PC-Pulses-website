<?php
/**
 * Tree traverser: recursively converts Elementor layout/widget JSON trees into Form Builder elements.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Traverses Elementor JSON AST nodes, maintaining container hierarchies and ensuring submit buttons.
 *
 * @since 4.1.9
 */
class Tree_Traverser {
	use Singleton;

	/**
	 * Traverses an Elementor element tree, extracts all elements, and guarantees a submit button.
	 *
	 * @param array $tree Elementor node list.
	 * @return array Flat list of Form Builder elements.
	 */
	public function traverse( array $tree ): array {
		$elements = array();
		$this->traverse_elements( $tree, null, $elements );
		$this->ensure_submit_button( $elements );

		return $elements;
	}

	/**
	 * Recursively traverses Elementor tree and maps nodes to Form Builder elements.
	 *
	 * @param array       $tree        Elementor node list.
	 * @param string|null $parent_id   Active parent container ID.
	 * @param array       &$output     Output list of flat elements.
	 * @param array|null  $parent_node Active parent Elementor node.
	 */
	public function traverse_elements( array $tree, ?string $parent_id, array &$output, ?array $parent_node = null ): void {
		foreach ( $tree as $node ) {
			if ( ! is_array( $node ) ) {
				continue;
			}

			$el_type  = $node['elType'] ?? '';
			$node_id  = sanitize_key( $node['id'] ?? wp_unique_id( 'migrated-' ) );
			$settings = (array) ( $node['settings'] ?? array() );
			$children = (array) ( $node['elements'] ?? array() );

			// 1. Layout containers: section, column, container, e-flexbox, e-grid
			if ( in_array( $el_type, array( 'section', 'column', 'container', 'e-flexbox', 'e-grid' ), true ) ) {
				$container_id = 'cont-' . $node_id;

				$output[] = array(
					'id'       => $container_id,
					'type'     => 'container',
					'parentId' => $parent_id,
					'props'    => Container_Mapper::instance()->map_props( $node ),
				);

				if ( ! empty( $children ) ) {
					$this->traverse_elements( $children, $container_id, $output, $node );
				}
				continue;
			}

			// 2. Form widgets & content
			if ( 'widget' === $el_type ) {
				$widget_type = $node['widgetType'] ?? '';
				$block       = Widget_Mapper::instance()->map( $widget_type, $settings, $node_id, $parent_id );

				if ( $block ) {
					// In an Elementor row-wrap container, any stacked blocks (headings, paragraphs, inherit-width items)
					// need widthType: 'full' so they wrap onto their own lines instead of squeezing into a row.
					if ( $parent_node ) {
						$parent_settings = (array) ( $parent_node['settings'] ?? array() );
						$p_dir           = $parent_settings['flex_direction'] ?? '';
						$p_wrap          = $parent_settings['flex_wrap'] ?? '';
						if ( 'row' === $p_dir && 'wrap' === $p_wrap ) {
							$ew     = $settings['_element_width'] ?? '';
							$has_cw = ! empty( $settings['_element_custom_width'] ) || ! empty( $settings['_custom_width'] );
							if ( ! $has_cw && ( 'inherit' === $ew || in_array( $widget_type, array( 'heading', 'text-editor' ), true ) ) ) {
								if ( ! isset( $block['props']['styles']['adv']['widthType'] ) ) {
									$block['props']['styles']['adv']['widthType'] = 'full';
								}
							}
						}
					}
					$output[] = $block;
				}
			}
		}
	}

	/**
	 * Ensures the migrated form elements contain at least one submit button.
	 *
	 * @param array &$elements Elements list.
	 */
	public function ensure_submit_button( array &$elements ): void {
		foreach ( $elements as $el ) {
			if ( ( $el['type'] ?? '' ) === 'button' ) {
				return;
			}
		}

		$elements[] = array(
			'id'       => wp_unique_id( 'btn-' ),
			'type'     => 'button',
			'parentId' => null,
			'props'    => array(
				'text'       => esc_html__( 'Submit Button', 'metform' ),
				'buttonType' => 'submit',
				'alignment'  => 'left',
			),
		);
	}
}
