<?php
namespace MetForm\Core\FormBuilder\Advanced;

use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;

defined( 'ABSPATH' ) || exit;

/**
 * Advanced-tab control schema shared by every block (Elementor/Gutenberg
 * style "global" controls).
 *
 * Every CSS control targets the block root: `{{WRAPPER}}`, the element
 * carrying the block's class (`__className`), or `{{LAYOUT_WRAPPER}}` for
 * placement rules (margin, width, alignment, position, offset, z-index):
 * the box that takes part in the parent's layout, which is the root on the
 * page and the editor's DraggableBlock in the canvas (see
 * Css_Value_Formatter::resolve_layout_token()). Values are stored under
 * `styles.adv.*`, so it is sanitized with the rest of the `styles` tree
 * and never collides with Style-tab keys such as `styles.padding` (which,
 * on field blocks, target the inner input rather than the block).
 *
 * The responsiveVisibility behaviour prop stays top-level; see
 * Visibility.
 *
 * Groups can be left out per block via Has_Advanced::get_advanced_exclude()
 * when the block's Style tab already covers them: see self::GROUPS.
 *
 * Controls may declare `cssValueMap` (saved value → CSS value; unmapped
 * values emit nothing) and `condition`; both are honoured on the PHP side
 * when building CSS (Css_Builder::build()), so a hidden
 * control never leaks CSS.
 *
 * @since 4.2.0
 */
final class Controls {

	/** All exclusion groups, in panel order. */
	public const GROUPS = array(
		'spacing',
		'size',
		'align',
		'position',
		'background',
		'border',
		'responsive',
	);

	/**
	 * Builds the Advanced tab controls for a block.
	 *
	 * @param string[] $exclude    Groups to leave out (see self::GROUPS).
	 * @param string   $block_type Block type, passed to the filter.
	 * @return array<int, array<string, mixed>> Control definitions.
	 */
	public static function get( array $exclude = array(), string $block_type = '' ): array {
		$groups = array(
			'spacing'    => self::spacing(),
			'size'       => self::size(),
			'align'      => self::align(),
			'position'   => self::position(),
			'background' => self::background(),
			'border'     => self::border(),
			'responsive' => self::responsive(),
		);

		/**
		 * Filters the Advanced-tab control groups of a block.
		 *
		 * @since 4.2.0
		 *
		 * @param array<string, array> $groups     Controls keyed by group.
		 * @param string               $block_type Block type.
		 * @param string[]             $exclude    Groups the block excludes.
		 */
		$groups = (array) apply_filters( 'metform_form_builder_advanced_control_groups', $groups, $block_type, $exclude );

		foreach ( $exclude as $group ) {
			unset( $groups[ $group ] );
		}

		// Section headers are shared by several groups (Layout = spacing +
		// size), so they're emitted here rather than inside each group.
		$sections = array(
			'adv-layout'     => array( __( 'Layout', 'metform' ), array( 'spacing', 'size', 'align' ) ),
			'adv-position'   => array( __( 'Position', 'metform' ), array( 'position' ) ),
			'adv-background' => array( __( 'Background', 'metform' ), array( 'background' ) ),
			'adv-border'     => array( __( 'Border', 'metform' ), array( 'border' ) ),
			'adv-responsive' => array( __( 'Responsive', 'metform' ), array( 'responsive' ) ),
		);

		$controls = array();
		$used     = array();
		foreach ( $sections as $key => $section_def ) {
			list( $label, $members ) = $section_def;

			$items = array();
			foreach ( $members as $group ) {
				if ( ! empty( $groups[ $group ] ) && is_array( $groups[ $group ] ) ) {
					$items = array_merge( $items, $groups[ $group ] );
				}
				$used[] = $group;
			}
			if ( ! $items ) {
				continue;
			}

			$section    = array(
				'type'  => 'section',
				'key'   => $key,
				'label' => $label,
			);
			$controls[] = $section;
			$controls   = array_merge( $controls, $items );
		}

		// Groups added through the filter that no section above claims get
		// their own trailing section.
		foreach ( array_diff_key( $groups, array_flip( $used ) ) as $group => $items ) {
			if ( ! is_array( $items ) || ! $items ) {
				continue;
			}
			$controls[] = array(
				'type'  => 'section',
				'key'   => 'adv-' . sanitize_key( (string) $group ),
				'label' => ucwords( str_replace( array( '_', '-' ), ' ', (string) $group ) ),
			);
			$controls   = array_merge( $controls, $items );
		}

		return $controls;
	}

	/**
	 * Mirrors the editor's evaluateCondition() (utils/style/evaluateCondition.js)
	 * so CSS is only generated for controls that are visible. `$form.*` keys
	 * are editor context and always pass here.
	 *
	 * @param mixed                $condition Control condition.
	 * @param array<string, mixed> $props     Block settings.
	 * @return bool
	 */
	public static function condition_matches( $condition, array $props, string $slot = 'desktop' ): bool {
		if ( empty( $condition ) || ! is_array( $condition ) ) {
			return true;
		}

		foreach ( $condition as $raw_key => $expected ) {
			$raw_key = (string) $raw_key;
			// `$or`: a list of conditions, at least one of which must match.
			if ( '$or' === $raw_key ) {
				$any = false;
				foreach ( (array) $expected as $sub ) {
					if ( self::condition_matches( $sub, $props, $slot ) ) {
						$any = true;
						break;
					}
				}
				if ( ! $any ) {
					return false;
				}
				continue;
			}
			$negate = '!' === substr( $raw_key, -1 );
			$key    = $negate ? substr( $raw_key, 0, -1 ) : $raw_key;

			if ( 0 === strpos( $key, '$form.' ) ) {
				continue;
			}

			// A responsive value is compared at the given breakpoint slot,
			// with the same mobile → tablet → desktop cascade as the CSS.
			$actual = Responsive_Value::resolve( Responsive_Value::get( $props, $key ), $slot, null );

			$matches = is_array( $expected )
				? in_array( $actual, $expected, true )
				: $actual === $expected;

			if ( $negate ? $matches : ! $matches ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Whether a control condition reads any responsive (per-breakpoint)
	 * value, i.e. whether it can match on one breakpoint and not another.
	 *
	 * @since 4.4.1
	 *
	 * @param mixed $condition Control condition.
	 * @param array $props     Saved settings.
	 * @return bool
	 */
	public static function condition_is_responsive( $condition, array $props ): bool {
		if ( empty( $condition ) || ! is_array( $condition ) ) {
			return false;
		}
		foreach ( $condition as $raw_key => $expected ) {
			if ( '$or' === (string) $raw_key ) {
				foreach ( (array) $expected as $sub ) {
					if ( self::condition_is_responsive( $sub, $props ) ) {
						return true;
					}
				}
				continue;
			}
			$key = rtrim( (string) $raw_key, '!' );
			if ( 0 === strpos( $key, '$form.' ) ) {
				continue;
			}
			if ( Responsive_Value::is_responsive( Responsive_Value::get( $props, $key ) ) ) {
				return true;
			}
		}
		return false;
	}

	/*
	------------------------------------------------------------------ */
	/*
	Groups                                                              */
	/* ------------------------------------------------------------------ */

	/**
	 * Builds a select option list from value => label pairs.
	 *
	 * @param array<string, string> $pairs Options.
	 * @return array<int, array{label:string,value:string}>
	 */
	private static function options( array $pairs ): array {
		$options = array();
		foreach ( $pairs as $value => $label ) {
			$options[] = array(
				'label' => $label,
				'value' => (string) $value,
			);
		}
		return $options;
	}

	private static function spacing(): array {
		return array(
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.adv.margin',
				'label'      => __( 'Margin', 'metform' ),
				'selector'   => '{{LAYOUT_WRAPPER}} { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.adv.padding',
				'label'      => __( 'Padding', 'metform' ),
				'selector'   => '{{WRAPPER}} { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
				'responsive' => true,
			),
		);
	}

	private static function size(): array {
		return array(
			array(
				'type'        => 'select',
				'key'         => 'styles.adv.widthType',
				'label'       => __( 'Width', 'metform' ),
				'default'     => '',
				'options'     => self::options(
					array(
						''       => __( 'Default', 'metform' ),
						'full'   => __( 'Full Width (100%)', 'metform' ),
						'inline' => __( 'Inline (auto)', 'metform' ),
						'custom' => __( 'Custom', 'metform' ),
					)
				),
				'cssValueMap' => array(
					'full'   => '100%',
					'inline' => 'auto',
				),
				'selector'    => '{{LAYOUT_WRAPPER}} { width: {{VALUE}}; max-width: 100%; box-sizing: border-box; flex: 0 0 auto; }',
				'responsive'  => true,
			),
			array(
				'type'       => 'range',
				'key'        => 'styles.adv.customWidth',
				'label'      => __( 'Custom Width', 'metform' ),
				'units'      => array( '%', 'px', 'vw' ),
				// Per-unit limits; a flat min/max would override every unit's range.
				'unitRanges' => array(
					'%'  => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
					'px' => array(
						'min'  => 0,
						'max'  => 2000,
						'step' => 1,
					),
					'vw' => array(
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					),
				),
				'selector'   => '{{LAYOUT_WRAPPER}} { width: {{VALUE}}; max-width: 100%; box-sizing: border-box; flex: 0 0 auto; }',
				'responsive' => true,
				'condition'  => array( 'styles.adv.widthType' => 'custom' ),
			),
		);
	}

	/**
	 * Alignment of the block inside its parent, like Elementor's Align Self:
	 * `align-self` on the parent's cross axis (a Container's flex row or
	 * column) and `justify-self` inline in a grid (the form itself, a
	 * Multistep step, a grid Container). Saved values keep the flex names;
	 * grid treats `flex-start`/`flex-end` as start/end.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private static function align(): array {
		return array(
			array(
				'type'       => 'select',
				'key'        => 'styles.adv.alignSelf',
				'label'      => __( 'Alignment', 'metform' ),
				'help'       => __( 'Aligns this block inside its parent: horizontally in the form, a step or a column Container, vertically in a row Container.', 'metform' ),
				'default'    => '',
				'options'    => self::options(
					array(
						''           => __( 'Default', 'metform' ),
						'flex-start' => __( 'Start', 'metform' ),
						'center'     => __( 'Center', 'metform' ),
						'flex-end'   => __( 'End', 'metform' ),
						'stretch'    => __( 'Stretch', 'metform' ),
					)
				),
				'selector'   => '{{LAYOUT_WRAPPER}} { align-self: {{VALUE}}; justify-self: {{VALUE}}; }',
				'responsive' => true,
			),
		);
	}

	private static function position(): array {
		return array(
			array(
				'type'     => 'select',
				'key'      => 'styles.adv.position',
				'label'    => __( 'Position', 'metform' ),
				'default'  => '',
				'options'  => self::options(
					array(
						''         => __( 'Default', 'metform' ),
						'relative' => __( 'Relative', 'metform' ),
						'absolute' => __( 'Absolute', 'metform' ),
						'fixed'    => __( 'Fixed', 'metform' ),
					)
				),
				'selector' => '{{LAYOUT_WRAPPER}} { position: {{VALUE}}; }',
			),
			array(
				'type'       => 'dimensions',
				'key'        => 'styles.adv.offset',
				'label'      => __( 'Offset', 'metform' ),
				'selector'   => '{{LAYOUT_WRAPPER}} { top: {{TOP}}; right: {{RIGHT}}; bottom: {{BOTTOM}}; left: {{LEFT}}; }',
				'responsive' => true,
				'condition'  => array( 'styles.adv.position' => array( 'relative', 'absolute', 'fixed' ) ),
			),
			array(
				'type'       => 'number',
				'key'        => 'styles.adv.zIndex',
				'label'      => __( 'Z-Index', 'metform' ),
				'default'    => '',
				'selector'   => '{{LAYOUT_WRAPPER}} { z-index: {{VALUE}}; }',
				'responsive' => true,
			),
		);
	}

	private static function background(): array {
		return array(
			array(
				'type' => 'tabs',
				'key'  => 'adv-background-tabs',
				'tab'  => array( 'normal', 'hover' ),
			),
			array(
				'type'     => 'background',
				'key'      => 'styles.adv.background',
				'label'    => __( 'Background', 'metform' ),
				'selector' => '{{WRAPPER}} { {{VALUE}} }',
				'tab'      => 'normal',
			),
			array(
				'type'     => 'background',
				'key'      => 'styles.adv.backgroundHover',
				'label'    => __( 'Background', 'metform' ),
				'selector' => '{{WRAPPER}}:hover { {{VALUE}} }',
				'tab'      => 'hover',
			),
			array(
				'type'     => 'range',
				'key'      => 'styles.adv.transitionDuration',
				'label'    => __( 'Transition Duration', 'metform' ),
				'help'     => __( 'How smoothly hover changes (background, border, shadow) animate.', 'metform' ),
				'default'  => '',
				'units'    => array( 'ms' ),
				'min'      => 0,
				'max'      => 3000,
				'step'     => 50,
				'selector' => '{{WRAPPER}} { transition-duration: {{VALUE}}; transition-property: background, border-color, border-width, border-radius, box-shadow; transition-timing-function: ease-in-out; }',
				'tab'      => 'hover',
			),
		);
	}

	private static function border(): array {
		return array(
			array(
				'type' => 'tabs',
				'key'  => 'adv-border-tabs',
				'tab'  => array( 'normal', 'hover' ),
			),
			array(
				'type'           => 'border',
				'key'            => 'styles.adv.border',
				'label'          => __( 'Border', 'metform' ),
				'selector'       => '{{WRAPPER}} { {{VALUE}} }',
				'responsive'     => true,
				'responsiveKeys' => array( 'width', 'radius' ),
				'tab'            => 'normal',
			),
			array(
				'type'     => 'boxShadow',
				'key'      => 'styles.adv.boxShadow',
				'label'    => __( 'Box Shadow', 'metform' ),
				'selector' => '{{WRAPPER}} { box-shadow: {{VALUE}}; }',
				'tab'      => 'normal',
			),
			array(
				'type'           => 'border',
				'key'            => 'styles.adv.borderHover',
				'label'          => __( 'Border', 'metform' ),
				'selector'       => '{{WRAPPER}}:hover { {{VALUE}} }',
				'responsive'     => true,
				'responsiveKeys' => array( 'width', 'radius' ),
				'tab'            => 'hover',
			),
			array(
				'type'     => 'boxShadow',
				'key'      => 'styles.adv.boxShadowHover',
				'label'    => __( 'Box Shadow', 'metform' ),
				'selector' => '{{WRAPPER}}:hover { box-shadow: {{VALUE}}; }',
				'tab'      => 'hover',
			),
		);
	}

	private static function responsive(): array {
		$labels = array(
			'desktop' => __( 'Show on Desktop', 'metform' ),
			'tablet'  => __( 'Show on Tablet', 'metform' ),
			'mobile'  => __( 'Show on Mobile', 'metform' ),
		);

		$controls = array();
		foreach ( Visibility::DEVICES as $device ) {
			$controls[] = array(
				'type'    => 'toggle',
				'key'     => 'responsiveVisibility.' . $device,
				'label'   => $labels[ $device ],
				'default' => true,
			);
		}
		$controls[ count( $controls ) - 1 ]['help'] = __( 'Hidden blocks stay visible (dimmed) in the editor so you can still select them.', 'metform' );

		return $controls;
	}
}
