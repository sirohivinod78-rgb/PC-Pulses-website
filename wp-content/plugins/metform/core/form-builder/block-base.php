<?php
/**
 * Base contract for Form Builder blocks.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder;

use MetForm\Core\FormBuilder\Advanced\Has_Advanced;
use MetForm\Core\FormBuilder\Dynamic_Css\Css_Value_Formatter;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Field_Renderer;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the metadata, controls, defaults, and rendering contract for blocks.
 *
 * @since 4.1.8
 */
abstract class Block_Base {
	// Advanced tab: get_controls(), build_advanced_css() + the
	// get_advanced_exclude() hook blocks may override.
	use Has_Advanced;

	/**
	 * Gets the unique block type.
	 *
	 * @since 4.1.8
	 *
	 * @return string Block type.
	 */
	abstract public function get_name(): string;

	/**
	 * Gets the human-readable block title.
	 *
	 * @since 4.1.8
	 *
	 * @return string Block title.
	 */
	abstract public function get_title(): string;

	/**
	 * Gets the editor icon name.
	 *
	 * @since 4.1.8
	 *
	 * @return string Icon name.
	 */
	abstract public function get_icon(): string;

	/**
	 * Gets the editor category.
	 *
	 * @since 4.1.8
	 *
	 * @return string Block category.
	 */
	abstract public function get_category(): string;

	/**
	 * Gets the editor sub-group used to bucket this block together with
	 * similar ones inside its category panel (e.g. "input", "choice",
	 * "datetime", "rating-range", "file-verify", "content"). Purely a
	 * client-side display grouping — has no effect on category/tier.
	 * Defaults to 'other' for blocks that don't override it (e.g. Layout
	 * blocks, which aren't sub-grouped in the editor sidebar).
	 *
	 * @since 4.1.10
	 *
	 * @return string Block group.
	 */
	public function get_group(): string {
		return 'other';
	}

	/**
	 * Gets the default block settings.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, mixed> Default settings.
	 */
	abstract public function get_default_settings(): array;

	/**
	 * Renders the inner control markup for field blocks.
	 *
	 * Subclasses implementing field blocks (capabilities['isField'] === true)
	 * should override this method to return just the inner control HTML.
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $settings Block settings.
	 * @param Render_Context       $context  Current rendering context.
	 * @return string Inner control markup.
	 */
	protected function render_markup( array $settings, Render_Context $context ): string {
		return '';
	}

	/**
	 * Gets options passed to Field_Renderer::wrap() for field blocks.
	 *
	 * Subclasses can override this to customize label_for, style_class,
	 * label_extra, after_control, etc.
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $settings Block settings.
	 * @param Render_Context       $context  Current rendering context.
	 * @return array<string, mixed> Wrap options.
	 */
	protected function get_wrap_options( array $settings, Render_Context $context ): array {
		$id = $context->get_element_id();
		return array(
			'style_class' => sanitize_html_class( (string) ( $settings['__className'] ?? '' ) ) ?: $id,
			'label_for'   => $id,
		);
	}

	/**
	 * Renders raw markup for non-field blocks (e.g. Container, Title, Button).
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $settings Block settings.
	 * @param Render_Context       $context  Current rendering context.
	 * @return string Rendered block markup.
	 */
	protected function render_raw( array $settings, Render_Context $context ): string {
		return '';
	}

	/**
	 * Renders the block markup.
	 *
	 * By default, this implements the Template Method Pattern: for field blocks
	 * (capabilities['isField'] === true), it delegates inner markup generation to
	 * render_markup() and automatically wraps it via Field_Renderer::wrap().
	 * Non-field blocks delegate to render_raw(). Any block can still override
	 * render() directly if needed.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $settings Block settings.
	 * @param Render_Context       $context  Current rendering context.
	 * @return string Rendered block markup.
	 */
	public function render( array $settings, Render_Context $context ): string {
		$capabilities = $this->get_capabilities();

		if ( empty( $capabilities['isField'] ) ) {
			return $this->render_raw( $settings, $context );
		}

		$control_html = $this->render_markup( $settings, $context );
		$opts         = $this->get_wrap_options( $settings, $context );

		return Field_Renderer::wrap(
			$settings,
			$context,
			$this->get_name(),
			$control_html,
			$opts
		);
	}

	/**
	 * Gets the structural capabilities supported by the block.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, bool> Block capabilities.
	 */
	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => false,
		);
	}

	/**
	 * Gets the block property schema.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, mixed> Property schema.
	 */
	public function get_property_schema(): array {
		return array();
	}

	/**
	 * Gets the editor control definitions.
	 *
	 * A `style` control with a `selector` is treated by the editor as
	 * CSS-only: changing it refreshes the block's CSS without re-rendering
	 * its markup. If render_markup() also reads that control's value, set
	 * `'rendersMarkup' => true` on it so the editor re-renders the markup too.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, mixed> Editor control definitions.
	 */
	public function register_controls(): array {
		return array(
			'content' => array(),
			'style'   => array(),
			'advance' => array(),
		);
	}

	/**
	 * Registered WP style/script handles this block needs beyond the shared
	 * server-rendered-base.scss stylesheet (already loaded everywhere — see
	 * PHP_MIGRATION_MASTER_PLAN.md §3). Almost never needed: a block's
	 * *default* look belongs in server-rendered-base.scss, not here. This
	 * exists for a block that genuinely needs its own extra, separately
	 * registered asset (e.g. a third-party library) enqueued specifically
	 * when it's used in the canvas. The runtime reads the saved document and
	 * enqueues these handles only for block types present in that form.
	 *
	 * @return string[] Registered style/script handles (via Assets_Enqueue
	 *                   or wp_register_style/wp_register_script elsewhere).
	 */
	public function get_frontend_assets(): array {
		return array();
	}

	/**
	 * Builds the dynamic CSS for this block instance.
	 *
	 * This method is the block-level extension point used by the dynamic CSS
	 * engine. By default, it converts the block's registered style controls
	 * and saved settings into CSS scoped by the supplied selector.
	 *
	 * A block may override this method when it needs to generate additional
	 * rules, such as layout CSS or styles derived from non-style controls. An
	 * override may append to the parent result or replace it when appropriate.
	 *
	 * Return CSS rules only. The dynamic CSS engine combines the block output
	 * and manages the generated form stylesheet file and its enqueueing.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $settings   Saved block-instance settings.
	 * @param string               $class_name Scoped selector for the block instance.
	 * @return string Generated CSS without a style element.
	 */
	public function build_dynamic_css( array $settings, string $class_name ): string {
		return Css_Value_Formatter::build_css( $class_name, $this->register_controls()['style'] ?? array(), $settings );
	}

	/**
	 * Per-breakpoint CSS for settings whose desktop value is applied through
	 * markup — a class picked in render() — rather than a control
	 * `selector` (e.g. Label Position's `mfb-field--label-left`).
	 *
	 * Shape: setting key => [ value => CSS rules using `{{WRAPPER}}` ]. Each
	 * value's rules must fully describe that state (they override whatever
	 * the desktop class set). Only per-breakpoint overrides are emitted —
	 * the desktop value keeps coming from the markup class.
	 *
	 * Blocks add their own entries by merging with the parent's.
	 *
	 * @since 4.4.1
	 *
	 * @return array<string, array<string, string>> Rules.
	 */
	protected function get_responsive_markup_rules(): array {
		return array(
			'labelPosition' => array(
				'top'  => '{{WRAPPER}} .mfb-field__row { display: block; } {{WRAPPER}} .mfb-field__label { flex: none; margin-bottom: 0.375rem; } {{WRAPPER}} .mfb-field__control { flex: none; }',
				'left' => '{{WRAPPER}} .mfb-field__row { display: flex; align-items: center; gap: 0.75rem; } {{WRAPPER}} .mfb-field__label { flex: 0 0 120px; margin-bottom: 0; } {{WRAPPER}} .mfb-field__control { flex: 1; min-width: 0; }',
			),
		);
	}

	/**
	 * Builds the media-query CSS for get_responsive_markup_rules(): for each
	 * such setting saved per breakpoint, the rules of the value in effect at
	 * every breakpoint that overrides it.
	 *
	 * Called by Style_Engine next to build_dynamic_css(), so blocks that
	 * override that method still get it.
	 *
	 * @since 4.4.1
	 *
	 * @param array<string, mixed> $settings   Block settings.
	 * @param string               $class_name Scoped selector for the block instance.
	 * @return string CSS.
	 */
	public function build_responsive_markup_css( array $settings, string $class_name ): string {
		$breakpoints = Responsive_Value::get_breakpoints();
		$by_slot     = array_fill_keys( array_keys( $breakpoints ), '' );

		foreach ( $this->get_responsive_markup_rules() as $key => $values ) {
			$setting = $settings[ $key ] ?? null;
			if ( ! is_array( $values ) || ! Responsive_Value::is_responsive( $setting ) ) {
				continue;
			}
			foreach ( Responsive_Value::overridden_slots( $setting ) as $slot ) {
				$value = Responsive_Value::resolve( $setting, $slot, null );
				if ( is_scalar( $value ) && isset( $values[ (string) $value ] ) ) {
					$by_slot[ $slot ] .= str_replace( '{{WRAPPER}}', '.' . $class_name, $values[ (string) $value ] );
				}
			}
		}

		$css = '';
		foreach ( $breakpoints as $slot => $media ) {
			if ( '' !== $by_slot[ $slot ] ) {
				$css .= $media . ' { ' . $by_slot[ $slot ] . ' }';
			}
		}
		return $css;
	}

	/**
	 * Gets the block definition exposed to the editor.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, mixed> Public block definition.
	 */
	public function get_public_definition(): array {
		return array(
			'type'           => $this->get_name(),
			'title'          => $this->get_title(),
			'icon'           => $this->get_icon(),
			'category'       => $this->get_category(),
			'group'          => $this->get_group(),
			'defaults'       => $this->get_default_settings(),
			'propertySchema' => $this->get_property_schema(),
			'controls'       => $this->get_controls(),
			'capabilities'   => $this->get_capabilities(),
		);
	}
}
