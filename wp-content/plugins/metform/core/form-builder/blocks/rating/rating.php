<?php
namespace MetForm\Core\FormBuilder\Blocks\Rating;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;

defined( 'ABSPATH' ) || exit;

/**
 * Ported from src/form-builder/blocks/rating/index.jsx (React) — see
 * PHP_RENDERING_MIGRATION_PLAN.md's Wave 4.
 *
 * Zero-JS baseline: `maxStars` real `<input type="radio">`s (one per whole
 * star, sharing one `name` group — this is what gives native keyboard
 * arrow-key navigation and `required` validation for free), each paired
 * with a `<label>` star icon. DOM order is reversed (star N first) and the
 * row is `flex-direction: row-reverse` (style.scss) so the classic
 * `:checked ~ label` / `:hover ~ label` general-sibling-combinator trick
 * fills every star up to and including the interacted one — see the CSS
 * comment block in style.scss for the exact rules. This alone reproduces
 * click-to-select + hover-preview identically to the old React block, with
 * zero JS, whenever `allowHalf` is off (the default).
 *
 * `allowHalf` needs a JS module (src/frontend/form-builder/blocks/rating/
 * index.js — this is one of the Wave-4 blocks the plan flags as needing
 * its own module) because no whole-star radio can represent a `4.5` value.
 * The module detects which half of a star was clicked and, only then,
 * rewrites that ONE radio's own `value` attribute before checking it —
 * deliberately not a second hidden input or a renamed `name`, so the
 * `required`/keyboard-group semantics above stay identical in both modes
 * and the zero-JS fallback for `allowHalf` forms is "whole-star only",
 * never "field doesn't submit at all". See index.js's own doc comment.
 *
 * Icon shape: no server-side icon library exists yet (same gap as
 * Checkbox/Radio's unrendered `icon` option field) — but rating only ever
 * needs exactly 4 fixed shapes (not arbitrary user-picked icons), so
 * they're hand-drawn inline SVGs here instead of left unrendered.
 */
final class Rating extends Block_Base {
	private const ICON_DEFAULT_FG = array(
		'star'   => '#f59e0b',
		'heart'  => '#ef4444',
		'thumb'  => '#3b82f6',
		'smiley' => '#eab308',
	);

	/** star/heart/thumb are filled shapes; smiley is drawn as an outline (matches index.jsx's ICON_FILL_MODE). */
	private const ICON_FILL_MODE = array(
		'star'   => 'fill',
		'heart'  => 'fill',
		'thumb'  => 'fill',
		'smiley' => 'stroke',
	);

	public function get_name(): string {
		return 'rating'; }
	public function get_title(): string {
		return __( 'Rating', 'metform' ); }
	public function get_icon(): string {
		return 'Star'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'rating-range'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'label'           => __( 'Rating', 'metform' ),
			'labelPosition'   => 'top',
			'name'            => '',
			'helpText'        => '',
			'maxStars'        => 5,
			'iconShape'       => 'star',
			'allowHalf'       => false,
			'required'        => false,
			'requiredMessage' => '',
			'showLabel'       => true,
			'styles'          => array(),
		);
	}

	public function get_property_schema(): array {
		return array(
			'showLabel'       => array( 'type' => 'boolean' ),
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'name'            => array( 'type' => 'field_name' ),
			'helpText'        => array( 'type' => 'string' ),
			'maxStars'        => array( 'type' => 'number' ),
			'iconShape'       => array(
				'type' => 'string',
				'enum' => array( 'star', 'heart', 'thumb', 'smiley' ),
			),
			'allowHalf'       => array( 'type' => 'boolean' ),
			'required'        => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
		);
	}

	public function register_controls(): array {
		$typography_selector = static function ( string $el ): string {
			return "{{WRAPPER}} {$el} { font-family: {{FONT_FAMILY}}; font-size: {{FONT_SIZE}}; font-weight: {{FONT_WEIGHT}}; font-style: {{FONT_STYLE}}; text-transform: {{TEXT_TRANSFORM}}; text-decoration: {{TEXT_DECORATION}}; line-height: {{LINE_HEIGHT}}; letter-spacing: {{LETTER_SPACING}}; word-spacing: {{WORD_SPACING}}; }";
		};

		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showLabel',
					'label'   => __( 'Show Label', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'Rating', 'metform' ),
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'labelPosition',
					'label'      => __( 'Label Position', 'metform' ),
					'default'    => 'top',
					'options'    => array(
						array(
							'label' => __( 'Top', 'metform' ),
							'value' => 'top',
						),
						array(
							'label' => __( 'Left', 'metform' ),
							'value' => 'left',
						),
					),
					'condition'  => array( 'showLabel!' => false ),
					'responsive' => true,
				),
				array(
					'type'  => 'text',
					'key'   => 'name',
					'label' => __( 'Name', 'metform' ),
					'help'  => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'number',
					'key'     => 'maxStars',
					'label'   => __( 'Number of Stars', 'metform' ),
					'default' => 5,
				),
				array(
					'type'    => 'select',
					'key'     => 'iconShape',
					'label'   => __( 'Icon Shape', 'metform' ),
					'default' => 'star',
					'options' => array(
						array(
							'label' => __( 'Star', 'metform' ),
							'value' => 'star',
						),
						array(
							'label' => __( 'Heart', 'metform' ),
							'value' => 'heart',
						),
						array(
							'label' => __( 'Thumb', 'metform' ),
							'value' => 'thumb',
						),
						array(
							'label' => __( 'Smiley', 'metform' ),
							'value' => 'smiley',
						),
					),
				),
				array(
					'type'    => 'toggle',
					'key'     => 'allowHalf',
					'label'   => __( 'Allow Half Ratings', 'metform' ),
					'default' => false,
				),

				array(
					'type'        => 'section',
					'key'         => 'settings',
					'label'       => __( 'Settings', 'metform' ),
					'defaultOpen' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'required',
					'label'   => __( 'Required', 'metform' ),
					'default' => false,
				),
				array(
					'type'      => 'text',
					'key'       => 'requiredMessage',
					'label'     => __( 'Required Message', 'metform' ),
					'help'      => __( 'Shown when no star is selected.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
			),
			'style'   => array(
				array(
					'type'        => 'section',
					'label'       => __( 'Label', 'metform' ),
					'defaultOpen' => true,
					'condition'   => array( 'showLabel!' => false ),
				),
				array(
					'type'      => 'color',
					'key'       => 'styles.labelColor',
					'label'     => __( 'Label Color', 'metform' ),
					'default'   => '#374151',
					'selector'  => '{{WRAPPER}} .mfb-label { color: {{VALUE}}; }',
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.labelTypography',
					'label'          => __( 'Label Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-label' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
					'condition'      => array( 'showLabel!' => false ),
				),
				array(
					'type'  => 'section',
					'label' => __( 'Help Text & Error', 'metform' ),
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.helpTextColor',
					'label'    => __( 'Help Text Color', 'metform' ),
					'default'  => '#6b7280',
					'selector' => '{{WRAPPER}} .mfb-field__help { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.errorColor',
					'label'    => __( 'Error Color', 'metform' ),
					'default'  => '#ef4444',
					'selector' => '{{WRAPPER}} .mfb-field__error { color: {{VALUE}}; }',
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.helpTextTypography',
					'label'          => __( 'Help Text Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-field__help' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),
				array(
					'type'           => 'typography',
					'key'            => 'styles.errorTypography',
					'label'          => __( 'Error Typography', 'metform' ),
					'selector'       => $typography_selector( '.mfb-field__error' ),
					'responsive'     => true,
					'responsiveKeys' => array( 'fontSize', 'lineHeight', 'letterSpacing', 'wordSpacing' ),
				),

				// JS-side control label/default are dynamic per iconShape
				// ("Filled Stars"/"Filled Hearts"/...) — a PHP array is
				// serialized once to JSON for the settings panel, so it
				// can't recompute per-render like index.jsx's manifest.js
				// does. Static "Icons" label here; render() below still
				// computes the *correct* per-shape auto-default at print
				// time directly (not through this control's static
				// 'default'), so visual output has full parity even though
				// the settings-panel copy is slightly less specific.
				array(
					'type'  => 'section',
					'label' => __( 'Icons', 'metform' ),
				),
				array(
					'type'          => 'color',
					'key'           => 'styles.starColor',
					'rendersMarkup' => true,
					'label'         => __( 'Filled Icon Color', 'metform' ),
					'default'       => self::ICON_DEFAULT_FG['star'],
					'selector'      => '{{WRAPPER}} .mfb-rating__star-icon-fg { color: {{VALUE}}; }',
				),
				array(
					'type'     => 'color',
					'key'      => 'styles.starInactiveColor',
					'label'    => __( 'Empty Icon Color', 'metform' ),
					'default'  => '#d1d5db',
					'selector' => '{{WRAPPER}} .mfb-rating__star-icon-bg { color: {{VALUE}}; }',
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.starSize',
					'label'      => __( 'Icon Size', 'metform' ),
					'default'    => '24px',
					'units'      => array( 'px' ),
					'min'        => 12,
					'max'        => 64,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-rating__star-icon { width: {{VALUE}}; height: {{VALUE}}; }',
					'responsive' => true,
				),
				array(
					'type'       => 'range',
					'key'        => 'styles.starGap',
					'label'      => __( 'Gap Between Icons', 'metform' ),
					'default'    => '4px',
					'units'      => array( 'px' ),
					'min'        => 0,
					'max'        => 20,
					'step'       => 1,
					'selector'   => '{{WRAPPER}} .mfb-rating__row { gap: {{VALUE}}; }',
					'responsive' => true,
				),
			),
		);
	}

	/** One shape's outline path(s), viewBox 0 0 24 24 — see this class's doc comment on why these are hand-drawn instead of a library lookup. */
	private function icon_path( string $shape ): string {
		switch ( $shape ) {
			case 'heart':
				return '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 1 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>';
			case 'thumb':
				return '<path d="M2 20h3V10H2v10zm19-9c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L11.83 2 6.41 7.42C6.15 7.68 6 8.03 6 8.41V18c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-1.91l-.01-.01L21 11z"/>';
			case 'smiley':
				return '<circle cx="12" cy="12" r="9.5"/><path d="M8 14s1.4 2 4 2 4-2 4-2" stroke-linecap="round"/><circle cx="8.7" cy="9.2" r="0.9" style="fill:currentColor;stroke:none;"/><circle cx="15.3" cy="9.2" r="0.9" style="fill:currentColor;stroke:none;"/>';
			case 'star':
			default:
				return '<path d="M12 2.5l3.1 6.28 6.93.99-5.02 4.89 1.19 6.9L12 17.77l-6.2 3.79 1.19-6.9-5.02-4.89 6.93-.99z"/>';
		}
	}

	private function render_icon_svg( string $shape, string $extra_class ): string {
		return sprintf(
			'<svg class="%1$s" viewBox="0 0 24 24" aria-hidden="true">%2$s</svg>',
			esc_attr( 'mfb-rating__star-icon ' . $extra_class ),
			$this->icon_path( $shape ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id         = $context->get_element_id();
		$name       = (string) ( $settings['name'] ?? '' );
		$max_stars  = max( (int) ( $settings['maxStars'] ?? 5 ), 1 );
		$allow_half = ! empty( $settings['allowHalf'] );
		$icon_shape = in_array( $settings['iconShape'] ?? 'star', array( 'star', 'heart', 'thumb', 'smiley' ), true ) ? $settings['iconShape'] : 'star';
		$fill_mode  = self::ICON_FILL_MODE[ $icon_shape ] ?? 'fill';
		$required   = ! empty( $settings['required'] );

		// Same "correct auto-default per icon shape, real user override still
		// wins" reasoning as this method's own doc comment / register_controls()'s
		// note above: Style_Engine only ever emits CSS for a value that differs
		// from styles.starColor's static schema default, so an unset override
		// still needs *this* icon-shape-aware color applied directly.
		$styles   = is_array( $settings['styles'] ?? null ) ? $settings['styles'] : array();
		$fg_color = '' !== (string) ( $styles['starColor'] ?? '' )
			? (string) $styles['starColor']
			: ( self::ICON_DEFAULT_FG[ $icon_shape ] ?? self::ICON_DEFAULT_FG['star'] );

		$rows = '';
		for ( $star = $max_stars; $star >= 1; $star-- ) {
			$input_id = $id . '-star-' . $star;
			$rows    .= sprintf(
				'<input type="radio" id="%1$s" name="%2$s" value="%3$d" class="mfb-rating__input" data-star="%3$d" %4$s>',
				esc_attr( $input_id ),
				esc_attr( $name ),
				$star,
				$required ? 'required' : ''
			);
			$rows    .= sprintf(
				'<label for="%1$s" class="mfb-rating__star" data-star="%2$d" aria-label="%3$s">'
					. '<span class="mfb-rating__star-icon-wrap mfb-rating__star-icon-wrap--%4$s">%5$s'
					. '<span class="mfb-rating__star-icon-fill">%6$s</span>'
					. '</span></label>',
				esc_attr( $input_id ),
				$star,
				esc_attr( sprintf( /* translators: %d: star count */ _n( '%d star', '%d stars', $star, 'metform' ), $star ) ),
				esc_attr( $fill_mode ),
				$this->render_icon_svg( $icon_shape, 'mfb-rating__star-icon-bg' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$this->render_icon_svg( $icon_shape, 'mfb-rating__star-icon-fg' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}

		return sprintf(
			'<div class="mfb-rating__row" role="radiogroup" aria-label="%1$s" data-allow-half="%2$s" style="--mfb-rating-fg: %3$s;">%4$s</div>',
			esc_attr( $settings['label'] ?? __( 'Rating', 'metform' ) ),
			$allow_half ? 'true' : 'false',
			esc_attr( $fg_color ),
			$rows // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	public function get_wrap_options( array $settings, Render_Context $context ): array {
		// Keep the parent's `style_class` so the wrapper carries the element
		// class every generated `{{WRAPPER}}` rule (Style + Advanced) targets.
		$opts              = parent::get_wrap_options( $settings, $context );
		$opts['label_for'] = $context->get_element_id() . '-star-1';

		return $opts;
	}
}
