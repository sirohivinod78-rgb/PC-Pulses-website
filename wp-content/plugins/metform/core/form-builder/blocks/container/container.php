<?php
namespace MetForm\Core\FormBuilder\Blocks\Container;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Css_Value_Formatter;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

final class Container extends Block_Base {
	/**
	 * Allowed values of the keyword layout settings. Every one of them can
	 * be set per breakpoint; an unknown value falls back to the default.
	 */
	private const ENUMS = array(
		'direction'    => array( 'row', 'column', 'row-reverse', 'column-reverse' ),
		'wrap'         => array( 'nowrap', 'wrap' ),
		'justify'      => array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ),
		'alignItems'   => array( 'stretch', 'flex-start', 'center', 'flex-end' ),
		'alignContent' => array( 'stretch', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' ),
	);

	public function get_name(): string {
		return 'container'; }
	public function get_title(): string {
		return __( 'Container', 'metform' ); }
	public function get_icon(): string {
		return 'LayoutGrid'; }
	public function get_category(): string {
		return 'Layout'; }

	public function get_group(): string {
		return 'other'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren'  => true,
			'isField'          => false,
			'cannotNestInside' => array( 'multistep' ),
		);
	}

	public function get_default_settings(): array {
		return array(
			'layoutType'   => 'flex',
			'direction'    => 'column',
			'justify'      => 'flex-start',
			'alignItems'   => 'stretch',
			'alignContent' => 'stretch',
			'wrap'         => 'nowrap',
			'gridColumns'  => 2,
			'gridRows'     => 1,
			'contentWidth' => 'full',
			'boxedWidth'   => '1140px',
			'styles'       => array(
				'columnGap' => '20px',
				'rowGap'    => '20px',
				'padding'   => array(
					'top'    => '0px',
					'right'  => '0px',
					'bottom' => '0px',
					'left'   => '0px',
				),
			),
			'__className'  => '',
		);
	}

	public function get_property_schema(): array {
		return array(
			'layoutType'      => array(
				'type' => 'string',
				'enum' => array( 'flex', 'grid' ),
			),
			// No panel control: set by dragging the resize handle between two
			// side-by-side containers (ContainerCanvas / useContainerResize),
			// per breakpoint.
			'width'           => array(
				'type'       => 'number',
				'responsive' => true,
			),
			'direction'       => array(
				'type'       => 'string',
				'enum'       => self::ENUMS['direction'],
				'responsive' => true,
			),
			'justify'         => array(
				'type'       => 'string',
				'enum'       => self::ENUMS['justify'],
				'responsive' => true,
			),
			'alignItems'      => array(
				'type'       => 'string',
				'enum'       => self::ENUMS['alignItems'],
				'responsive' => true,
			),
			'alignContent'    => array(
				'type'       => 'string',
				'enum'       => self::ENUMS['alignContent'],
				'responsive' => true,
			),
			'wrap'            => array(
				'type'       => 'string',
				'enum'       => self::ENUMS['wrap'],
				'responsive' => true,
			),
			'gridColumns'     => array(
				'type'       => 'number',
				'responsive' => true,
			),
			'gridRows'        => array(
				'type'       => 'number',
				'responsive' => true,
			),
			'gridColumnSizes' => array( 'type' => 'responsive_number_array' ),
			'gridRowSizes'    => array( 'type' => 'responsive_number_array' ),
			'contentWidth'    => array(
				'type' => 'string',
				'enum' => array( 'boxed', 'full' ),
			),
			'boxedWidth'      => array( 'type' => 'responsive_string' ),
			'columnSettings'  => array( 'type' => 'object' ),
			'styles'          => array( 'type' => 'object' ),
			'__className'     => array( 'type' => 'css_class' ),
		);
	}

	/**
	 * Width comes from the Container's own Content Width control, the
	 * resize-handle width share and the parent's flex layout CSS, which an Advanced width
	 * would fight. Every other Advanced group applies, as on other blocks.
	 *
	 * @since 4.2.0
	 *
	 * @return string[] Advanced\Controls groups to leave out.
	 */
	protected function get_advanced_exclude(): array {
		return array( 'size' );
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
					'type'    => 'iconButtonGroup',
					'key'     => 'layoutType',
					'label'   => __( 'Layout', 'metform' ),
					'default' => 'flex',
					'options' => array(
						array(
							'label' => __( 'Flexbox', 'metform' ),
							'value' => 'flex',
							'icon'  => 'Rows3',
						),
						array(
							'label' => __( 'Grid', 'metform' ),
							'value' => 'grid',
							'icon'  => 'Grid3x3',
						),
					),
				),
				array(
					'type'    => 'iconButtonGroup',
					'key'     => 'contentWidth',
					'label'   => __( 'Content Width', 'metform' ),
					'default' => 'full',
					'options' => array(
						array(
							'label' => __( 'Boxed', 'metform' ),
							'value' => 'boxed',
							'icon'  => 'RectangleHorizontal',
						),
						array(
							'label' => __( 'Full Width', 'metform' ),
							'value' => 'full',
							'icon'  => 'StretchHorizontal',
						),
					),
				),
				array(
					'type'       => 'range',
					'key'        => 'boxedWidth',
					'label'      => __( 'Width', 'metform' ),
					'default'    => '1140px',
					'units'      => array( 'px', '%' ),
					'min'        => 0,
					'max'        => 1600,
					'step'       => 10,
					'condition'  => array( 'contentWidth' => 'boxed' ),
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.minHeight',
					'label'      => __( 'Min Height', 'metform' ),
					'default'    => '100px',
					'units'      => array( 'px', 'vh' ),
					'min'        => 0,
					'max'        => 1200,
					'step'       => 10,
					'selector'   => '{{WRAPPER}} { min-height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'iconButtonGroup',
					'key'        => 'direction',
					'label'      => __( 'Direction', 'metform' ),
					'help'       => __( 'A Row stacks into a column on mobile unless you set a Mobile direction.', 'metform' ),
					'default'    => 'column',
					'responsive' => true,
					'options'    => array(
						array(
							'label' => __( 'Row', 'metform' ),
							'value' => 'row',
							'icon'  => 'ArrowRight',
						),
						array(
							'label' => __( 'Column', 'metform' ),
							'value' => 'column',
							'icon'  => 'ArrowDown',
						),
						array(
							'label' => __( 'Row Reversed', 'metform' ),
							'value' => 'row-reverse',
							'icon'  => 'ArrowLeft',
						),
						array(
							'label' => __( 'Column Reversed', 'metform' ),
							'value' => 'column-reverse',
							'icon'  => 'ArrowUp',
						),
					),
					'condition'  => array( 'layoutType!' => 'grid' ),
				),
				array(
					'type'       => 'select',
					'key'        => 'justify',
					'label'      => __( 'Justify Content', 'metform' ),
					'default'    => 'flex-start',
					'options'    => array(
						array(
							'label' => __( 'Start', 'metform' ),
							'value' => 'flex-start',
						),
						array(
							'label' => __( 'Center', 'metform' ),
							'value' => 'center',
						),
						array(
							'label' => __( 'End', 'metform' ),
							'value' => 'flex-end',
						),
						array(
							'label' => __( 'Space Between', 'metform' ),
							'value' => 'space-between',
						),
						array(
							'label' => __( 'Space Around', 'metform' ),
							'value' => 'space-around',
						),
						array(
							'label' => __( 'Space Evenly', 'metform' ),
							'value' => 'space-evenly',
						),
					),
					'condition'  => array( 'layoutType!' => 'grid' ),
					'responsive' => true,
				),
				array(
					'type'       => 'iconButtonGroup',
					'key'        => 'wrap',
					'label'      => __( 'Wrap', 'metform' ),
					'default'    => 'nowrap',
					'options'    => array(
						array(
							'label' => __( 'No Wrap', 'metform' ),
							'value' => 'nowrap',
							'icon'  => 'Minus',
						),
						array(
							'label' => __( 'Wrap', 'metform' ),
							'value' => 'wrap',
							'icon'  => 'WrapText',
						),
					),
					'condition'  => array( 'layoutType!' => 'grid' ),
					'responsive' => true,
				),
				array(
					'type'       => 'select',
					'key'        => 'alignContent',
					'label'      => __( 'Align Content', 'metform' ),
					'help'       => __( 'Only affects multi-line content (Wrap enabled).', 'metform' ),
					'default'    => 'stretch',
					'options'    => array(
						array(
							'label' => __( 'Stretch', 'metform' ),
							'value' => 'stretch',
						),
						array(
							'label' => __( 'Start', 'metform' ),
							'value' => 'flex-start',
						),
						array(
							'label' => __( 'Center', 'metform' ),
							'value' => 'center',
						),
						array(
							'label' => __( 'End', 'metform' ),
							'value' => 'flex-end',
						),
						array(
							'label' => __( 'Space Between', 'metform' ),
							'value' => 'space-between',
						),
						array(
							'label' => __( 'Space Around', 'metform' ),
							'value' => 'space-around',
						),
					),
					'condition'  => array(
						'layoutType!' => 'grid',
						'wrap'        => 'wrap',
					),
					'responsive' => true,
				),
				array(
					'type'       => 'number',
					'key'        => 'gridColumns',
					'label'      => __( 'Grid Columns', 'metform' ),
					'default'    => 2,
					'min'        => 1,
					'max'        => 6,
					'condition'  => array( 'layoutType' => 'grid' ),
					'responsive' => true,
				),
				array(
					'type'       => 'number',
					'key'        => 'gridRows',
					'label'      => __( 'Grid Rows', 'metform' ),
					'help'       => __( 'Explicit row count. Rows beyond the content still reserve their own height, same as Elementor.', 'metform' ),
					'default'    => 1,
					'min'        => 1,
					'max'        => 12,
					'condition'  => array( 'layoutType' => 'grid' ),
					'responsive' => true,
				),
				array(
					'type'       => 'select',
					'key'        => 'alignItems',
					'label'      => __( 'Align Items', 'metform' ),
					'default'    => 'stretch',
					'options'    => array(
						array(
							'label' => __( 'Stretch', 'metform' ),
							'value' => 'stretch',
						),
						array(
							'label' => __( 'Start', 'metform' ),
							'value' => 'flex-start',
						),
						array(
							'label' => __( 'Center', 'metform' ),
							'value' => 'center',
						),
						array(
							'label' => __( 'End', 'metform' ),
							'value' => 'flex-end',
						),
					),
					'responsive' => true,
				),
			),
			// Margin, padding, background (normal/hover), border, shadow and
			// position live in the shared Advanced tab, like every other block;
			// the Style tab keeps only what is specific to a Container.
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Gap', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.columnGap',
					'label'      => __( 'Column Gap', 'metform' ),
					'default'    => '20px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 100,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} > .mfb-container__flex { column-gap: {{VALUE}}; --mfb-col-gap: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.rowGap',
					'label'      => __( 'Row Gap', 'metform' ),
					'default'    => '20px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 100,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} > .mfb-container__flex { row-gap: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.padding',
					'label'      => __( 'Padding', 'metform' ),
					// Legacy: container padding/margin live in Advanced >
					// Spacing now (new containers start with 10px padding
					// there, see createBlockActions.js). These stay registered
					// so a form saved before that still renders them, but are
					// never shown — the editor moves their value into Advanced
					// on load (migrateLegacyContainerSpacing()).
					'condition'  => array( '__legacySpacing' => true ),
					'default'    => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} { padding: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'dimensions',
					'key'        => 'styles.margin',
					'label'      => __( 'Margin', 'metform' ),
					'condition'  => array( '__legacySpacing' => true ),
					'default'    => array(
						'top'    => '0px',
						'right'  => '0px',
						'bottom' => '0px',
						'left'   => '0px',
					),
					'selector'   => '{{WRAPPER}} { margin: {{TOP}} {{RIGHT}} {{BOTTOM}} {{LEFT}}; }',
					'responsive' => true,
				),
				array(
					'type'  => 'section',
					'label' => __( 'Background Overlay', 'metform' ),
				),
				array(
					'type'     => 'background',
					'key'      => 'styles.backgroundOverlay',
					'label'    => __( 'Background Overlay', 'metform' ),
					'help'     => __( 'A second background layer, painted above Background and below the content.', 'metform' ),
					'default'  => array(
						'type'  => 'classic',
						'color' => '',
					),
					'selector' => '{{WRAPPER}} > .mfb-container__overlay { {{VALUE}} }',
				),
			),
		);
	}


	public function build_dynamic_css( array $settings, string $class_name ): string {
		$controls       = $this->register_controls();
		$content_styles = array_values(
			array_filter(
				$controls['content'] ?? array(),
				static function ( $control ) {
					return is_array( $control ) && isset( $control['selector'] );
				}
			)
		);
		$style_controls = array_merge( $content_styles, $controls['style'] ?? array() );
		return Css_Value_Formatter::build_css( $class_name, $style_controls, $settings )
			. $this->build_layout_css( $class_name, $settings );
	}

	/** Byte-wise `!important` injection is overkill here (Style_Engine's version exists specifically to dodge quoted `;` in url()/font-family values) — this layout CSS is only ever keywords/numbers/calc(), a plain replace is safe. */
	private function force_important( string $css ): string {
		return str_replace( ';', ' !important;', $css );
	}

	private function build_layout_css( string $class, array $settings ): string {
		$breakpoints = Responsive_Value::get_breakpoints();
		$slots       = array_merge( array( 'desktop' => '' ), $breakpoints );
		$layout_type = 'grid' === ( $settings['layoutType'] ?? 'flex' ) ? 'grid' : 'flex';
		$root_sel    = ".{$class} > .mfb-container__flex";
		$items_sel   = "{$root_sel} > .mfb-container, {$root_sel} > .mfb-container__child";
		$handle_sel  = "{$root_sel} > .mfb-container__child > .mfb-container__resize-handle";
		$fields_sel  = ":where({$root_sel}) > :where(.mfb-field__wrapper, .mfb-paragraph__wrapper, .mfb-title__wrapper, .mfb-subtitle__wrapper, .mfb-container__fill)";

		$content_width_css = '';
		if ( 'full' !== ( $settings['contentWidth'] ?? 'boxed' ) ) {
			$boxed_width_setting = $settings['boxedWidth'] ?? '1140px';
			$boxed_width         = (string) Responsive_Value::resolve( $boxed_width_setting, 'desktop', '1140px' );
			// Boxed constrains the content box, not the root (as Elementor's
			// boxed container does): the root keeps the Advanced margin,
			// background and border at full width, and nothing here fights
			// the Advanced margin's left/right.
			$content_width_css = "{$root_sel} { width: 100%; max-width: {$boxed_width}; margin-left: auto; margin-right: auto; }";
			foreach ( Responsive_Value::overridden_slots( $boxed_width_setting ) as $slot ) {
				$bp_width           = (string) $boxed_width_setting[ $slot ];
				$content_width_css .= "{$breakpoints[ $slot ]} { {$root_sel} { max-width: {$bp_width}; } }";
			}
		}

		// This container's OWN share inside a row parent (the parent's item
		// rule reads `--mfb-width`), per breakpoint. It applies whatever this
		// container's own layout is — without it a flex column inside a row
		// fell back to its content size.
		$width_setting = $settings['width'] ?? '';
		$width_css     = self::width_rule( $class, Responsive_Value::resolve( $width_setting, 'desktop', '' ) );
		foreach ( Responsive_Value::overridden_slots( $width_setting ) as $slot ) {
			$rule = self::width_rule( $class, $width_setting[ $slot ] );
			if ( '' !== $rule ) {
				$width_css .= "{$breakpoints[ $slot ]} { {$rule} }";
			}
		}

		// The stacking rules (container → overlay → flex box) and the
		// default column layout are the same for every container, so they
		// live once in the container stylesheet (blocks/container/style.scss)
		// instead of being printed per container.
		if ( 'flex' === $layout_type && self::is_default_flex_layout( $settings, array_keys( $slots ) ) ) {
			return $this->force_important( $content_width_css . $width_css );
		}

		// Desktop rules, then a media query for each breakpoint whose
		// resolved layout differs from the one above it.
		$layout_css = '';
		$field_css  = '';
		$previous   = null;
		foreach ( $slots as $slot => $media ) {
			$rules = $this->layout_rules( $layout_type, $settings, $slot, $root_sel, $items_sel, $handle_sel, $fields_sel );
			if ( $rules === $previous ) {
				continue;
			}
			$previous = $rules;

			list( $slot_css, $slot_field_css ) = $rules;
			$layout_css                       .= '' === $media ? $slot_css : "{$media} { {$slot_css} }";
			if ( '' !== $slot_field_css ) {
				$field_css .= '' === $media ? $slot_field_css : "{$media} { {$slot_field_css} }";
			}
		}

		if ( 'grid' === $layout_type ) {
			$counts = array();
			foreach ( array_keys( $slots ) as $slot ) {
				$counts[ $slot ] = array(
					self::grid_count( $settings, 'gridColumns', $slot ),
					self::grid_count( $settings, 'gridRows', $slot ),
				);
			}
			// Track sizes go through custom properties (kept out of
			// force_important() on purpose) so the editor's inline values,
			// including the live resize preview, override them.
			return Grid_Tracks::build_css( $root_sel, $settings, $counts, $breakpoints )
				. $this->force_important( $content_width_css . $width_css . $layout_css );
		}

		return $this->force_important( $content_width_css . $width_css . $layout_css ) . $field_css;
	}

	/**
	 * The flex or grid rules for one breakpoint slot, from the settings
	 * resolved at that slot (mobile → tablet → desktop):
	 * `[ important, plain ]`. The plain part (field items stretching to
	 * share a row) stays overridable so an Advanced > Width on a field
	 * still wins.
	 *
	 * @since 4.4.1
	 *
	 * @return string[]
	 */
	private function layout_rules( string $layout_type, array $settings, string $slot, string $root_sel, string $items_sel, string $handle_sel, string $fields_sel ): array {
		$align_items = self::keyword( $settings, 'alignItems', $slot, 'stretch' );

		if ( 'grid' === $layout_type ) {
			$grid_columns = self::grid_count( $settings, 'gridColumns', $slot );
			$grid_rows    = self::grid_count( $settings, 'gridRows', $slot );
			return array(
				"{$root_sel} { display: grid; grid-template-columns: var(--mfb-grid-cols, repeat({$grid_columns}, 1fr)); grid-template-rows: var(--mfb-grid-rows, repeat({$grid_rows}, minmax(80px, auto))); grid-auto-rows: minmax(80px, auto); align-items: {$align_items}; }"
				. "{$items_sel} { width: auto; min-width: 0; }"
				. "{$handle_sel} { display: none; }",
				'',
			);
		}

		$align_content = self::keyword( $settings, 'alignContent', $slot, 'stretch' );
		$justify       = self::keyword( $settings, 'justify', $slot, 'flex-start' );
		$wrap          = self::keyword( $settings, 'wrap', $slot, 'nowrap' );
		$direction     = self::direction( $settings, $slot );
		$is_wrap       = 'wrap' === $wrap;

		// Every item resets the inherited `--mfb-width` / `--mfb-grow` its
		// ancestors set: a nested container only reads its OWN share.
		$css = "{$root_sel} { display: flex; flex-direction: {$direction}; flex-wrap: {$wrap}; justify-content: {$justify}; align-items: {$align_items}; align-content: {$align_content}; --mfb-width: initial; --mfb-grow: initial; }";

		if ( false !== strpos( $direction, 'column' ) ) {
			// Stacked: nested containers take the full cross size.
			return array(
				$css
				. "{$items_sel} { flex: 0 0 auto; width: 100%; }"
				. "{$handle_sel} { display: none; }",
				"{$fields_sel} { flex: 0 1 auto; min-width: 0; }",
			);
		}
		// Side by side: a sized container takes its `width` share; an unsized
		// one shares what is left equally with the other unsized items. Uses
		// this slot's wrap, not the desktop one.
		$flex = $is_wrap
			? '1 1 calc(var(--mfb-width, 100%) - var(--mfb-col-gap, 16px))'
			: 'var(--mfb-grow, 1) 1 var(--mfb-width, 0%)';
		return array(
			$css
			. "{$items_sel} { flex: {$flex}; width: auto; min-width: 0; }"
			. "{$handle_sel} { display: " . ( $is_wrap ? 'none' : 'block' ) . '; }',
			"{$fields_sel} { flex: " . ( $is_wrap ? '1 1 auto' : '1 1 0%' ) . '; min-width: 0; }',
		);
	}

	/**
	 * Whether a flex container's layout equals the stylesheet default:
	 * column + no wrap on every device, start/stretch/stretch alignment.
	 *
	 * @param array    $settings Merged settings.
	 * @param string[] $slots    Breakpoint slots, desktop first.
	 */
	private static function is_default_flex_layout( array $settings, array $slots ): bool {
		foreach ( $slots as $slot ) {
			if (
				'column' !== self::direction( $settings, $slot )
				|| 'nowrap' !== self::keyword( $settings, 'wrap', $slot, 'nowrap' )
				|| 'flex-start' !== self::keyword( $settings, 'justify', $slot, 'flex-start' )
				|| 'stretch' !== self::keyword( $settings, 'alignItems', $slot, 'stretch' )
				|| 'stretch' !== self::keyword( $settings, 'alignContent', $slot, 'stretch' )
			) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Flex direction resolved at a slot. Elementor parity: a row stacks on
	 * mobile unless the user set a Mobile direction explicitly.
	 */
	private static function direction( array $settings, string $slot ): string {
		$direction = self::keyword( $settings, 'direction', $slot, 'column' );
		if ( 'mobile' === $slot && 0 === strpos( $direction, 'row' ) && ! in_array( 'mobile', Responsive_Value::overridden_slots( $settings['direction'] ?? null ), true ) ) {
			return 'column';
		}
		return $direction;
	}

	/** A keyword setting resolved at a slot, limited to its allowed values. */
	private static function keyword( array $settings, string $key, string $slot, string $fallback ): string {
		$value = (string) Responsive_Value::resolve( $settings[ $key ] ?? null, $slot, $fallback );
		return in_array( $value, self::ENUMS[ $key ], true ) ? $value : $fallback;
	}

	/** Grid column / row count resolved at a slot (at least 1). */
	private static function grid_count( array $settings, string $key, string $slot ): int {
		return max( 1, (int) Responsive_Value::resolve( $settings[ $key ] ?? null, $slot, 1 ) );
	}

	/**
	 * `--mfb-width` rule for a width share in %, or '' when unset. A sized
	 * container does not grow (`--mfb-grow: 0`), so unsized siblings share
	 * what is left.
	 */
	private static function width_rule( string $class, $width ): string {
		return is_numeric( $width ) && (float) $width > 0
			? ".{$class} { --mfb-width: " . ( (float) $width + 0 ) . '%; --mfb-grow: 0; }'
			: '';
	}

	/**
	 * Renders the container and, directly inside `.mfb-container__flex`, each
	 * child in document order — fields and nested containers alike (which
	 * render themselves the same way, recursively).
	 */
	public function render( array $settings, Render_Context $context ): string {
		$layout_type = 'grid' === ( $settings['layoutType'] ?? 'flex' ) ? 'grid' : 'flex';

		$children_html = '';
		foreach ( $context->get_children() as $child ) {
			$children_html .= $context->render_child( $child['id'] );
		}

		$style_class       = sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $context->get_element_id();
		$editor_hint_class = $context->is_editor() ? ' mfb-container--editor-hint' : '';
		$conditional       = is_array( $settings['conditional'] ?? null ) ? $settings['conditional'] : array();
		$has_conditional   = ! empty( $conditional['enabled'] ) && ! empty( $conditional['conditions'] );
		$initially_hidden  = ! $context->is_editor() && $has_conditional && ( 'show' === ( $conditional['action'] ?? 'show' ) );

		$cond_attr = '';
		if ( $has_conditional ) {
			$cond_attr .= ' data-mf-conditional="' . esc_attr( wp_json_encode( $conditional ) ) . '"';
			if ( $initially_hidden ) {
				$cond_attr .= ' data-mf-condition-hidden="true" style="display:none;" hidden';
			}
		}

		return sprintf(
			'<div class="mfb-container %1$s%2$s"%6$s data-mf-block-id="%3$s" data-layout="%4$s"><div class="mfb-container__overlay" aria-hidden="true"></div><div class="mfb-container__flex">%5$s</div></div>',
			esc_attr( $style_class ),
			esc_attr( $editor_hint_class ),
			esc_attr( $context->get_block_id() ),
			esc_attr( $layout_type ),
			$children_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$cond_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
