<?php
/**
 * Pure CSS-value formatting for the form builder's style engine.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 1.0.0
 */

namespace MetForm\Core\FormBuilder\Dynamic_Css;

use MetForm\Core\FormBuilder\Advanced\Visibility;

defined( 'ABSPATH' ) || exit;

/**
 * Turns one control declaration + one saved value into a CSS string
 * fragment.
 *
 * No filesystem access, no WP hooks, no singleton state — every method is
 * a static, side-effect-free function of its arguments, so it can be unit
 * tested with plain PHP arrays.
 *
 * This formatter is used by the canonical PHP Style_Engine for both
 * REST-delivered canvas CSS and generated frontend stylesheets.
 *
 * Trust boundary: incoming values have already passed through
 * Document_Sanitizer (`map_deep( 'sanitize_text_field' )` over the whole
 * `styles` map) before this class ever sees them, and only users with the
 * `edit_post` capability on the form can set them. That sanitizer strips
 * tags but does NOT escape characters that are meaningful inside a CSS
 * string or declaration (`"`, `\`, `;`, `{`, `}`), so every value
 * interpolated into the generated stylesheet is escaped again here, for
 * the CSS context specifically, regardless of what ran upstream.
 *
 * @since 1.0.0
 */
final class Css_Value_Formatter {

	/**
	 * Returns the media query strings used for each responsive breakpoint
	 * slot, filterable so themes/child plugins can override them without
	 * patching core.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string> Media query strings keyed by breakpoint
	 *                                slot (e.g. 'tablet', 'mobile'). Keys
	 *                                must match the slots saved in a
	 *                                responsive control's value (see
	 *                                is_responsive_value()).
	 */
	public static function get_responsive_breakpoints(): array {
		return Responsive_Value::get_breakpoints();
	}

	/**
	 * Builds the raw CSS (no wrapping `<style>` tag) for one block
	 * instance from its style-control declarations and saved props.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name     The block instance's scoped CSS class
	 *                                (without the leading dot).
	 * @param array  $style_controls The block's `register_controls()['style']`
	 *                                declarations. Each entry may contain
	 *                                'selector', 'key', 'type', 'default',
	 *                                and 'responsive'.
	 * @param array  $props          The block's merged (defaults + saved)
	 *                                settings, keyed by dot-path.
	 * @return string Generated CSS, including any responsive `@media` blocks.
	 */
	public static function build_css( string $class_name, array $style_controls, array $props ): string {
		$breakpoints = Responsive_Value::get_breakpoints();
		$base        = '';
		$by_slot     = array_fill_keys( array_keys( $breakpoints ), '' );

		foreach ( $style_controls as $control ) {
			if ( ! is_array( $control ) || empty( $control['selector'] ) || empty( $control['key'] ) ) {
				continue;
			}

			$selector = self::resolve_layout_token( (string) $control['selector'], $props );

			$raw_value       = Responsive_Value::get( $props, $control['key'] );
			$type            = $control['type'] ?? null;
			$default         = $control['default'] ?? null;
			$responsive_keys = $control['responsiveKeys'] ?? null;
			$options         = self::control_options( $control );

			if ( 'border' === $type && ! empty( $control['legacy'] ) && is_array( $control['legacy'] ) ) {
				$raw_value = self::apply_border_legacy( $raw_value, $control['legacy'], $props );
			}

			if ( ! empty( $control['responsive'] ) && Responsive_Value::is_responsive( $raw_value ) ) {
				$base .= self::evaluate_selector( $selector, $class_name, $raw_value['desktop'] ?? null, $type, $default, $options );

				foreach ( $breakpoints as $slot => $media ) {
					if ( ! in_array( $slot, Responsive_Value::overridden_slots( $raw_value ), true ) ) {
						continue;
					}
					// A breakpoint override is never compared with the
					// control default: it must still beat a non-default
					// desktop value (e.g. Width 50% desktop, 100% mobile).
					$by_slot[ $slot ] .= self::evaluate_selector( $selector, $class_name, $raw_value[ $slot ], $type, null, $options );
				}
			} elseif ( ! empty( $control['responsive'] ) && is_array( $responsive_keys ) && is_array( $raw_value ) ) {
				// Sub-field-level responsiveness: only SOME keys inside a
				// composite control's value (e.g. a `border` control's
				// `width`/`radius`, or a `typography` control's
				// `fontSize`/`lineHeight`/`letterSpacing`/`wordSpacing`)
				// carry their own independent `{ desktop, tablet, mobile }`
				// wrapper, while the rest of the composite stays a single
				// flat value shared by every breakpoint.
				// `resolve_composite_slot()` builds one fully-resolved
				// composite value per breakpoint slot, which then flows
				// through the same `evaluate_selector()` path.
				$base .= self::evaluate_selector( $selector, $class_name, self::resolve_composite_slot( $raw_value, $responsive_keys, 'desktop' ), $type, $default, $options );

				foreach ( $breakpoints as $slot => $media ) {
					if ( ! self::composite_slot_has_override( $raw_value, $responsive_keys, $slot ) ) {
						continue;
					}
					$slot_value        = self::resolve_composite_slot( $raw_value, $responsive_keys, $slot );
					$by_slot[ $slot ] .= self::evaluate_selector( $selector, $class_name, $slot_value, $type, null, $options );
				}
			} else {
				$base .= self::evaluate_selector( $selector, $class_name, $raw_value, $type, $default, $options );
			}
		}

		// Every desktop rule first, then one block per breakpoint (largest
		// first), so a tablet/mobile rule always comes after — and therefore
		// beats — any desktop rule of the same specificity, whichever control
		// produced it.
		$css = $base;
		foreach ( $breakpoints as $slot => $media ) {
			if ( '' !== $by_slot[ $slot ] ) {
				$css .= $media . ' { ' . $by_slot[ $slot ] . ' }';
			}
		}

		return $css;
	}

	/**
	 * Whether a responsive value carries an override for a breakpoint slot.
	 * `null` and `''` mean "not set" (inherit from the next larger slot).
	 *
	 * @since 4.4.1
	 *
	 * @param mixed  $value Responsive value.
	 * @param string $slot  Breakpoint slot.
	 * @return bool
	 */
	public static function slot_is_set( $value, string $slot ): bool {
		return is_array( $value ) && array_key_exists( $slot, $value ) && null !== $value[ $slot ] && '' !== $value[ $slot ] && array() !== $value[ $slot ];
	}

	/**
	 * Resolves a (possibly responsive) value for one breakpoint slot,
	 * cascading mobile → tablet → desktop the same way the max-width media
	 * queries cascade on the page. A plain value is the desktop value.
	 *
	 * @since 4.4.1
	 *
	 * @param mixed  $value    Plain or `{ desktop, tablet, mobile }` value.
	 * @param string $slot     'desktop' | 'tablet' | 'mobile'.
	 * @param mixed  $fallback Returned when nothing is set.
	 * @return mixed
	 */
	public static function resolve_slot( $value, string $slot = 'desktop', $fallback = null ) {
		return Responsive_Value::resolve( $value, $slot, $fallback );
	}

	/**
	 * Breakpoint slots (other than desktop) at which a responsive value
	 * carries an explicit override.
	 *
	 * @since 4.4.1
	 *
	 * @param mixed $value Possibly responsive value.
	 * @return string[] Slots, in breakpoint order.
	 */
	public static function overridden_slots( $value ): array {
		return Responsive_Value::overridden_slots( $value );
	}

	/**
	 * Resolves the `{{LAYOUT_WRAPPER}}` token of a control selector.
	 *
	 * `{{LAYOUT_WRAPPER}}` marks placement rules (margin, width, alignment,
	 * position, offset, z-index): they must act on the box that takes part
	 * in the parent's layout. On the page that is the block root
	 * ({{WRAPPER}}). In the editor canvas the root sits inside editor-only
	 * wrappers, and the outermost one (the DraggableBlock, class
	 * `mfb-layout-<id>`) is what the canvas grid or a Container's flex row
	 * lays out, so editor renders pass its scoped selector as
	 * `$props['__layoutSelector']` (Style_Engine::build_document_css() with
	 * `$editor = true`).
	 *
	 * Without it (the saved CSS file) the rule targets the root, but not
	 * inside the editor canvas, which also loads that file: there the
	 * editor-mode rule on the layout wrapper applies instead, and the root
	 * must not get the same width/offset a second time. The Preview iframe
	 * carries the canvas body class too, so it is matched back explicitly
	 * (same pattern as Advanced\Visibility).
	 *
	 * @since 4.2.0
	 *
	 * @param string               $template Control selector template.
	 * @param array<string, mixed> $props    Block settings.
	 * @return string Template with `{{LAYOUT_WRAPPER}}` resolved.
	 */
	public static function resolve_layout_token( string $template, array $props ): string {
		if ( false === strpos( $template, '{{LAYOUT_WRAPPER}}' ) ) {
			return $template;
		}

		$layout = $props['__layoutSelector'] ?? '';
		if ( is_string( $layout ) && '' !== $layout ) {
			return str_replace( '{{LAYOUT_WRAPPER}}', '.' . $layout, $template );
		}

		$on_page = sprintf(
			'body:not(.%1$s) {{WRAPPER}}, body.%2$s {{WRAPPER}}',
			Visibility::CANVAS_BODY_CLASS,
			Visibility::PREVIEW_BODY_CLASS
		);

		return str_replace( '{{LAYOUT_WRAPPER}}', $on_page, $template );
	}

	/**
	 * Picks the per-control formatting options evaluate_selector() reads
	 * out of a control declaration.
	 *
	 * @since 4.3.1
	 * @access private
	 *
	 * @param array $control A style-control declaration.
	 * @return array{widthFallback?: string} Formatting options.
	 */
	private static function control_options( array $control ): array {
		$options = array();
		if ( isset( $control['widthFallback'] ) && is_scalar( $control['widthFallback'] ) ) {
			$options['widthFallback'] = (string) $control['widthFallback'];
		}
		return $options;
	}

	/**
	 * Whether a stored value holds anything CSS can use: a non-blank
	 * scalar, or an array with at least one such scalar anywhere inside it.
	 *
	 * @since 4.3.1
	 * @access private
	 *
	 * @param mixed $value Stored value.
	 * @return bool True when the value is not empty.
	 */
	private static function has_css_value( $value ): bool {
		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				if ( self::has_css_value( $item ) ) {
					return true;
				}
			}
			return false;
		}
		return null !== $value && '' !== trim( (string) $value );
	}

	/**
	 * Read-time fallback for border settings saved under older, separate
	 * keys before they moved into a `border` control (e.g. an input's
	 * standalone `styles.borderColor`).
	 *
	 * `$legacy` maps a sub-key of the border value to its old location:
	 * `[ 'color' => [ 'key' => 'styles.borderColor', 'default' => '#d1d5db' ] ]`.
	 * The old value fills that sub-key only when the border value's own
	 * sub-key is empty, and is skipped when it equals the old control's
	 * default (which never produced CSS), so old forms render exactly as
	 * before without being re-saved.
	 *
	 * @since 4.3.1
	 * @access private
	 *
	 * @param mixed $raw_value The border control's saved value.
	 * @param array $legacy    Sub-key => [ 'key' => dot-path, 'default' => mixed ].
	 * @param array $props     The block's merged settings.
	 * @return mixed The border value with legacy sub-values filled in, or
	 *               $raw_value unchanged when nothing applied.
	 */
	private static function apply_border_legacy( $raw_value, array $legacy, array $props ) {
		$value   = is_array( $raw_value ) ? $raw_value : array();
		$changed = false;

		foreach ( $legacy as $sub_key => $source ) {
			if ( ! is_array( $source ) || empty( $source['key'] ) || self::has_css_value( $value[ $sub_key ] ?? null ) ) {
				continue;
			}
			$legacy_value = Responsive_Value::get( $props, (string) $source['key'] );
			if ( ! self::has_css_value( $legacy_value ) ) {
				continue;
			}
			if ( array_key_exists( 'default', $source ) && self::values_equal( $legacy_value, $source['default'] ) ) {
				continue;
			}
			$value[ $sub_key ] = $legacy_value;
			$changed           = true;
		}

		return $changed ? $value : $raw_value;
	}

	/**
	 * Builds one fully-resolved composite value for a single breakpoint
	 * slot, used by the sub-field-level responsive path in `build_css()`.
	 * Every key in `$raw_value` passes through unchanged EXCEPT the keys
	 * named in `$responsive_keys`: for those, if the sub-value is itself
	 * `{ desktop, tablet, mobile }`-shaped, it's replaced with that slot's
	 * value (falling back to its own 'desktop' slot, then to the raw
	 * sub-value itself, so a not-yet-responsive sub-field degrades
	 * gracefully instead of going blank).
	 *
	 * @since 1.0.0
	 *
	 * @param array    $raw_value       The composite control's full saved value.
	 * @param string[] $responsive_keys Sub-keys of $raw_value that may carry
	 *                                   their own responsive wrapper.
	 * @param string   $slot            'desktop' | 'tablet' | 'mobile'.
	 * @return array The resolved composite value for that slot.
	 */
	private static function resolve_composite_slot( array $raw_value, array $responsive_keys, string $slot ): array {
		$out = $raw_value;
		foreach ( $responsive_keys as $key ) {
			if ( ! array_key_exists( $key, $raw_value ) ) {
				continue;
			}
			$sub = $raw_value[ $key ];
			if ( Responsive_Value::is_responsive( $sub ) ) {
				$out[ $key ] = Responsive_Value::resolve( $sub, $slot, null );
			}
		}
		return $out;
	}

	/**
	 * Whether a composite control's value has an actual override set for a
	 * given non-desktop breakpoint slot on any of its responsive sub-keys —
	 * used to decide whether that slot's `@media` block is worth emitting
	 * at all (mirrors the whole-value path's own `isset(...) && null !==`
	 * check).
	 *
	 * @since 1.0.0
	 *
	 * @param array    $raw_value       The composite control's full saved value.
	 * @param string[] $responsive_keys Sub-keys of $raw_value that may carry
	 *                                   their own responsive wrapper.
	 * @param string   $slot            'tablet' | 'mobile' (never 'desktop' —
	 *                                   the desktop rule is always emitted).
	 * @return bool
	 */
	private static function composite_slot_has_override( array $raw_value, array $responsive_keys, string $slot ): bool {
		foreach ( $responsive_keys as $key ) {
			$sub = $raw_value[ $key ] ?? null;
			if ( Responsive_Value::is_responsive( $sub ) && in_array( $slot, Responsive_Value::overridden_slots( $sub ), true ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Determines whether a control's raw value is a per-breakpoint map
	 * (e.g. `[ 'desktop' => ..., 'tablet' => ... ]`) rather than a plain
	 * scalar or list.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value read from saved props.
	 * @return bool True when $value is an associative array containing at
	 *              least one of the 'desktop', 'tablet', 'mobile' keys.
	 */
	public static function is_responsive_value( $value ): bool {
		return Responsive_Value::is_responsive( $value );
	}

	/**
	 * Backward-compatible alias for Responsive_Value::get().
	 *
	 * @since 1.0.0
	 * @deprecated 4.4.1 Use Responsive_Value::get().
	 *
	 * @param array  $props   The props array to read from.
	 * @param string $dot_key Dot-delimited path, e.g. 'border.width.top'.
	 * @return mixed The resolved value, or null if any segment of the path
	 *               is missing.
	 */
	public static function get_value_by_key( array $props, string $dot_key ) {
		return Responsive_Value::get( $props, $dot_key );
	}

	/**
	 * Fills a single selector template with one resolved value and
	 * appends `!important` to every declaration produced.
	 *
	 * Returns an empty string when the template is empty, the value is
	 * null, or the value equals the control's declared default — matching
	 * the JS engine's "nothing changed from default, emit nothing" rule.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $template        Selector/declaration template
	 *                                       containing `{{WRAPPER}}` and one
	 *                                       or more `{{...}}` value
	 *                                       placeholders.
	 * @param string      $class_name      The block instance's scoped CSS
	 *                                       class (without the leading dot).
	 * @param mixed       $value           The resolved value for this
	 *                                       control (already the
	 *                                       breakpoint-specific value when
	 *                                       responsive).
	 * @param string|null $control_type    One of 'dimensions', 'typography',
	 *                                       'border', 'boxShadow',
	 *                                       'background', or null for a
	 *                                       plain scalar control.
	 * @param mixed       $control_default The control's declared default
	 *                                       value, used to skip emitting CSS
	 *                                       that matches it.
	 * @param array       $options         Optional. Per-control formatting
	 *                                       options (see control_options()).
	 *                                       Default empty array.
	 * @return string The filled, `!important`-suffixed CSS, or '' when
	 *                 there is nothing to output.
	 */
	public static function evaluate_selector( string $template, string $class_name, $value, ?string $control_type, $control_default, array $options = array() ): string {
		if ( '' === $template ) {
			return '';
		}
		if ( null === $value || self::values_equal( $value, $control_default ) ) {
			return '';
		}

		$out = str_replace( '{{WRAPPER}}', '.' . $class_name, $template );

		switch ( $control_type ) {
			case 'dimensions':
				$out = self::fill_dimensions( $out, $value );
				break;
			case 'typography':
				$out = self::fill_typography( $out, $value );
				break;
			case 'border':
				// `{{VALUE}}` templates get longhand declarations from
				// border_to_css(); the per-part placeholders are still
				// filled for templates written against the older API.
				$out = false !== strpos( $out, '{{VALUE}}' )
					? str_replace( '{{VALUE}}', self::border_to_css( $value, $options['widthFallback'] ?? '' ), $out )
					: self::fill_border( $out, $value );
				break;
			case 'boxShadow':
				$out = str_replace( '{{VALUE}}', self::box_shadow_to_css( $value ) ?? 'none', $out );
				break;
			case 'background':
				$out = str_replace( '{{VALUE}}', self::background_to_css( $value ) ?? '', $out );
				break;
			default:
				$out = str_replace( '{{VALUE}}', is_array( $value ) ? '' : self::sanitize_css_value( $value ), $out );
				break;
		}

		// Strip empty or whitespace-only declarations like "font-style: ;" or "border-width:   ;"
		$out = preg_replace( '/[a-zA-Z0-9_-]+:\s*;/i', '', $out );
		// If rule block has no declarations left, return empty string
		if ( preg_match( '/^[^\{]*\{\s*\}\s*$/', trim( $out ) ) ) {
			return '';
		}

		return self::force_important( $out );
	}

	/**
	 * Compares a value against a control's default the same way the JS
	 * engine does: strict equality first, then a JSON-string fallback for
	 * arrays/objects.
	 *
	 * Mirrors `value === default || JSON.stringify(value) === JSON.stringify(default)`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $a First value to compare.
	 * @param mixed $b Second value to compare.
	 * @return bool True if the two values are considered equal.
	 */
	public static function values_equal( $a, $b ): bool {
		if ( $a === $b ) {
			return true;
		}
		return wp_json_encode( $a ) === wp_json_encode( $b );
	}

	/**
	 * Strips everything that could let a value escape the single CSS
	 * declaration or string it's being dropped into.
	 *
	 * Removes quote characters, backslashes, `;`, `{`, `}`, angle
	 * brackets, and CSS comment delimiters. Applied to every scalar value
	 * interpolated into generated CSS, in addition to (not instead of)
	 * whatever sanitization already ran upstream in Document_Sanitizer —
	 * CSS has its own escaping rules that `sanitize_text_field()` does not
	 * cover.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value The raw value to sanitize for CSS output.
	 * @return string The sanitized, trimmed string.
	 */
	public static function sanitize_css_value( $value ): string {
		$value = (string) $value;
		$value = preg_replace( '/\/\*.*?\*\//s', '', $value ); // Strip CSS comments.
		$value = str_replace( array( '"', "'", '\\', ';', '{', '}', '<', '>' ), '', $value );
		return trim( $value );
	}

	/**
	 * Same as sanitize_css_value(), but also strips parentheses so the
	 * result is safe to use unquoted inside a wrapper like `url(...)` or a
	 * gradient function.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param mixed $value The raw value to sanitize.
	 * @return string The sanitized, parenthesis-free string.
	 */
	private static function sanitize_css_bare_token( $value ): string {
		$value = self::sanitize_css_value( $value );
		return str_replace( array( '(', ')' ), '', $value );
	}

	/**
	 * Validates and escapes a URL for safe use inside `url("...")`.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param mixed $value The raw URL value to sanitize.
	 * @return string The escaped URL, or '' if it is empty or unusable.
	 */
	private static function sanitize_css_url( $value ): string {
		$value = (string) $value;
		$safe  = function_exists( 'esc_url_raw' ) ? esc_url_raw( $value ) : filter_var( $value, FILTER_SANITIZE_URL );
		if ( '' === $safe ) {
			return '';
		}
		// esc_url_raw() allows scheme-relative/relative/absolute URLs; still escape quote/backslash defensively.
		return str_replace( array( '"', '\\' ), array( '%22', '%5C' ), $safe );
	}

	/**
	 * Appends `!important` to every declaration in a CSS string, skipping
	 * any `;` found inside quoted values (e.g.
	 * `url("data:image/png;base64,...")`).
	 *
	 * Uses a byte-wise scan, which is safe on UTF-8 input: the quote and
	 * semicolon characters are single-byte ASCII, and every UTF-8
	 * continuation/lead byte is >= 0x80, so it never collides with them.
	 *
	 * @since 1.0.0
	 *
	 * @param string $css The CSS string to process.
	 * @return string The same CSS with `!important` appended to every
	 *                 top-level declaration.
	 */
	public static function force_important( string $css ): string {
		$out   = '';
		$quote = null;
		$len   = strlen( $css );
		for ( $i = 0; $i < $len; $i++ ) {
			$ch = $css[ $i ];
			if ( $quote ) {
				$out .= $ch;
				if ( $ch === $quote ) {
					$quote = null;
				}
			} elseif ( '"' === $ch || "'" === $ch ) {
				$quote = $ch;
				$out  .= $ch;
			} elseif ( ';' === $ch ) {
				$out .= ' !important;';
			} else {
				$out .= $ch;
			}
		}
		return $out;
	}

	/**
	 * Finds the first non-empty side of a dimensions value, used to infer
	 * which CSS unit (px, %, em, ...) the whole set was entered in.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param mixed $value A dimensions value shaped like
	 *                      `[ 'top' => ..., 'right' => ..., 'bottom' => ..., 'left' => ... ]`.
	 * @return string The first non-empty side's raw string value, or '' if
	 *                 none is set.
	 */
	private static function first_non_empty_side( $value ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}
		foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
			if ( array_key_exists( $side, $value ) && '' !== (string) $value[ $side ] ) {
				return (string) $value[ $side ];
			}
		}
		return '';
	}

	/**
	 * Extracts the trailing CSS unit (letters/percent sign) from a
	 * dimension string such as `'12px'` or `'50%'`.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param string $side A single dimension value, e.g. '12px'.
	 * @return string The extracted unit (e.g. 'px', '%'), or '' if none is found.
	 */
	private static function extract_unit( string $side ): string {
		return preg_match( '/[a-z%]+$/i', $side, $m ) ? $m[0] : '';
	}

	/**
	 * Fills a 'dimensions' control template (top/right/bottom/left + unit).
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The selector/declaration template containing
	 *                          `{{TOP}}`, `{{RIGHT}}`, `{{BOTTOM}}`,
	 *                          `{{LEFT}}`, and/or `{{UNIT}}` placeholders.
	 * @param mixed  $value    A dimensions value, e.g.
	 *                          `[ 'top' => '10px', 'right' => '10px', ... ]`.
	 * @return string The template with all dimension placeholders filled.
	 */
	public static function fill_dimensions( string $template, $value ): string {
		$v   = is_array( $value ) ? $value : array();
		$out = str_replace( '{{TOP}}', self::sanitize_css_value( $v['top'] ?? '' ), $template );
		$out = str_replace( '{{RIGHT}}', self::sanitize_css_value( $v['right'] ?? '' ), $out );
		$out = str_replace( '{{BOTTOM}}', self::sanitize_css_value( $v['bottom'] ?? '' ), $out );
		$out = str_replace( '{{LEFT}}', self::sanitize_css_value( $v['left'] ?? '' ), $out );
		$out = str_replace( '{{UNIT}}', self::extract_unit( self::first_non_empty_side( $v ) ), $out );
		return $out;
	}

	/**
	 * Appends a fallback CSS unit to a bare numeric value, or sanitizes
	 * the value as-is when it already carries its own unit/keyword.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param mixed  $raw            The raw value (int, float, numeric
	 *                                 string, unit-suffixed string, or
	 *                                 keyword).
	 * @param string $fallback_unit The unit to append when $raw is a bare
	 *                                number, e.g. 'px'.
	 * @return string The value with a unit applied, or '' when $raw is
	 *                 null/empty.
	 */
	private static function with_unit( $raw, string $fallback_unit ): string {
		if ( null === $raw || '' === $raw ) {
			return '';
		}
		if ( is_int( $raw ) || is_float( $raw ) ) {
			return $raw . $fallback_unit;
		}
		if ( is_string( $raw ) && preg_match( '/^-?\d*\.?\d+$/', $raw ) ) {
			return $raw . $fallback_unit;
		}
		return self::sanitize_css_value( $raw );
	}

	/**
	 * Fills a 'typography' control template (font family, size, weight,
	 * style, transform, decoration, line height, letter/word spacing).
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The selector/declaration template containing
	 *                          `{{FONT_FAMILY}}`, `{{FONT_SIZE}}`,
	 *                          `{{FONT_WEIGHT}}`, `{{FONT_STYLE}}`,
	 *                          `{{TEXT_TRANSFORM}}`, `{{TEXT_DECORATION}}`,
	 *                          `{{LINE_HEIGHT}}`, `{{LETTER_SPACING}}`,
	 *                          and/or `{{WORD_SPACING}}` placeholders.
	 * @param mixed  $value    A typography value, e.g.
	 *                          `[ 'fontFamily' => 'Inter', 'fontSize' => 16, ... ]`.
	 * @return string The template with all typography placeholders filled.
	 */
	public static function fill_typography( string $template, $value ): string {
		$v   = is_array( $value ) ? $value : array();
		$out = str_replace( '{{FONT_FAMILY}}', self::sanitize_css_value( $v['fontFamily'] ?? '' ), $template );
		$out = str_replace( '{{FONT_SIZE}}', self::with_unit( $v['fontSize'] ?? null, 'px' ), $out );
		$out = str_replace( '{{FONT_WEIGHT}}', self::sanitize_css_value( $v['fontWeight'] ?? '' ), $out );
		$out = str_replace( '{{FONT_STYLE}}', self::sanitize_css_value( $v['fontStyle'] ?? '' ), $out );
		$out = str_replace( '{{TEXT_TRANSFORM}}', self::sanitize_css_value( $v['textTransform'] ?? '' ), $out );
		$out = str_replace( '{{TEXT_DECORATION}}', self::sanitize_css_value( $v['textDecoration'] ?? '' ), $out );
		$out = str_replace( '{{LINE_HEIGHT}}', self::sanitize_css_value( $v['lineHeight'] ?? '' ), $out );
		$out = str_replace( '{{LETTER_SPACING}}', self::with_unit( $v['letterSpacing'] ?? null, 'px' ), $out );
		$out = str_replace( '{{WORD_SPACING}}', self::with_unit( $v['wordSpacing'] ?? null, 'px' ), $out );
		return $out;
	}

	/**
	 * Fills a 'border' control template (type, color, per-side width via
	 * fill_dimensions(), and per-corner radius).
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The selector/declaration template containing
	 *                          `{{BORDER_TYPE}}`, `{{COLOR}}`,
	 *                          dimension placeholders (see
	 *                          fill_dimensions()), and/or `{{RADIUS_TOP}}`,
	 *                          `{{RADIUS_RIGHT}}`, `{{RADIUS_BOTTOM}}`,
	 *                          `{{RADIUS_LEFT}}` placeholders.
	 * @param mixed  $value    A border value, e.g.
	 *                          `[ 'borderType' => 'solid', 'color' => '#000',
	 *                          'width' => [...], 'radius' => [...] ]`.
	 * @return string The template with all border placeholders filled.
	 */
	public static function fill_border( string $template, $value ): string {
		$v      = is_array( $value ) ? $value : array();
		$out    = str_replace( '{{BORDER_TYPE}}', self::sanitize_css_value( $v['borderType'] ?? '' ), $template );
		$out    = str_replace( '{{COLOR}}', self::sanitize_css_value( $v['color'] ?? '' ), $out );
		$out    = self::fill_dimensions( $out, $v['width'] ?? array() );
		$radius = is_array( $v['radius'] ?? null ) ? $v['radius'] : array();
		$out    = str_replace( '{{RADIUS_TOP}}', self::sanitize_css_value( $radius['top'] ?? '' ), $out );
		$out    = str_replace( '{{RADIUS_RIGHT}}', self::sanitize_css_value( $radius['right'] ?? '' ), $out );
		$out    = str_replace( '{{RADIUS_BOTTOM}}', self::sanitize_css_value( $radius['bottom'] ?? '' ), $out );
		$out    = str_replace( '{{RADIUS_LEFT}}', self::sanitize_css_value( $radius['left'] ?? '' ), $out );
		return $out;
	}

	/**
	 * Converts a 'border' control value into longhand CSS declarations —
	 * the `{{VALUE}}` of a `type: 'border'` control's selector template.
	 *
	 * Only what is set is output, one longhand per side/corner, so an
	 * unset part keeps the element's base CSS and a single side or corner
	 * never spreads to all four (as a `border-width: 2px` shorthand
	 * would):
	 * - borderType: '' (Default) outputs no border-style; 'none' outputs
	 *   `border-style: none` and skips width/color, which it hides anyway.
	 * - width: `border-{side}-width` per non-empty side. When a visible
	 *   style is chosen but no side has a width, `$width_fallback` (if
	 *   given) is output as `border-width` — field borders pass '1px', the
	 *   width their base CSS already has.
	 * - color: `border-color` when set to a plain color string.
	 * - radius: `border-{corner}-radius`, mapping the dimensions control's
	 *   top/right/bottom/left to top-left/top-right/bottom-right/bottom-left
	 *   (the same order as the `border-radius` shorthand).
	 *
	 * Expects a single-breakpoint value: build_css() resolves the
	 * responsive width/radius slots before calling this.
	 *
	 * @since 4.3.1
	 *
	 * @param mixed  $value          A border value, e.g.
	 *                                `[ 'borderType' => 'solid', 'color' => '#000',
	 *                                'width' => [...], 'radius' => [...] ]`.
	 * @param string $width_fallback Optional. Width used when a visible
	 *                                style has no width. Default ''.
	 * @return string Space-separated declarations, or '' when nothing is set.
	 */
	public static function border_to_css( $value, string $width_fallback = '' ): string {
		$v     = is_array( $value ) ? $value : array();
		$type  = is_scalar( $v['borderType'] ?? null ) ? self::sanitize_css_value( $v['borderType'] ) : '';
		$decls = array();

		if ( '' !== $type ) {
			$decls[] = 'border-style: ' . $type . ';';
		}

		if ( 'none' !== $type ) {
			$width     = is_array( $v['width'] ?? null ) ? $v['width'] : array();
			$has_width = false;
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$side_value = is_scalar( $width[ $side ] ?? null ) ? self::sanitize_css_value( $width[ $side ] ) : '';
				if ( '' !== $side_value ) {
					$decls[]   = 'border-' . $side . '-width: ' . $side_value . ';';
					$has_width = true;
				}
			}
			$width_fallback = self::sanitize_css_value( $width_fallback );
			if ( ! $has_width && '' !== $type && '' !== $width_fallback ) {
				$decls[] = 'border-width: ' . $width_fallback . ';';
			}

			$color = is_scalar( $v['color'] ?? null ) ? self::sanitize_css_value( $v['color'] ) : '';
			if ( '' !== $color ) {
				$decls[] = 'border-color: ' . $color . ';';
			}
		}

		$radius  = is_array( $v['radius'] ?? null ) ? $v['radius'] : array();
		$corners = array(
			'top'    => 'top-left',
			'right'  => 'top-right',
			'bottom' => 'bottom-right',
			'left'   => 'bottom-left',
		);
		foreach ( $corners as $side => $corner ) {
			$corner_value = is_scalar( $radius[ $side ] ?? null ) ? self::sanitize_css_value( $radius[ $side ] ) : '';
			if ( '' !== $corner_value ) {
				$decls[] = 'border-' . $corner . '-radius: ' . $corner_value . ';';
			}
		}

		return implode( ' ', $decls );
	}

	/**
	 * Converts a 'background' control value into CSS declarations. Port
	 * of the JS engine's `background.js` `backgroundToCss()`.
	 *
	 * Only the active type is output (see normalize_background() for the
	 * stored shapes it accepts):
	 * - no background — explicit transparent.
	 * - 'classic'  — background-color, plus the image and its position /
	 *   size / repeat / attachment when an image is set (both combine; the
	 *   image paints over the color).
	 * - 'gradient' — linear/radial gradient.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $v The background control's value.
	 * @return string|null The generated CSS declaration(s), or null when
	 *                      there is nothing to output (no type, no color,
	 *                      no image, no gradient colors set).
	 */
	public static function background_to_css( $v ): ?string {
		if ( ! is_array( $v ) ) {
			return null;
		}

		$v = self::normalize_background( $v );

		// No background is an explicit choice, so it must also clear the
		// background color the block's base stylesheet paints (e.g. inputs'
		// white). background-image is left alone: base styles use it for
		// functional glyphs such as the select chevron. Stored
		// color/image/gradient are kept but ignored, so picking a type again
		// restores them.
		if ( 'none' === $v['type'] ) {
			return 'background-color: transparent;';
		}

		if ( 'gradient' === $v['type'] ) {
			$g              = $v['gradient'];
			$gradient_type  = self::sanitize_css_bare_token( $g['type'] ?? 'linear' );
			$angle          = is_numeric( $g['angle'] ?? null ) ? (float) $g['angle'] : 180;
			$position       = self::sanitize_css_bare_token( $g['position'] ?? 'center center' );
			$color_start    = self::sanitize_css_value( $g['colorStart'] ?? '' );
			$location_start = is_numeric( $g['locationStart'] ?? null ) ? (float) $g['locationStart'] : 0;
			$color_end      = self::sanitize_css_value( $g['colorEnd'] ?? '' );
			$location_end   = is_numeric( $g['locationEnd'] ?? null ) ? (float) $g['locationEnd'] : 100;

			if ( ! $color_start && ! $color_end ) {
				return null;
			}

			$stops        = ( $color_start ?: 'transparent' ) . ' ' . $location_start . '%, ' . ( $color_end ?: 'transparent' ) . ' ' . $location_end . '%';
			$image        = 'radial' === $gradient_type
				? "radial-gradient(circle at {$position}, {$stops})"
				: "linear-gradient({$angle}deg, {$stops})";
			$declarations = array( "background-image: {$image};" );
			if ( isset( $v['opacity'] ) && '' !== (string) $v['opacity'] && is_numeric( $v['opacity'] ) ) {
				$declarations[] = 'opacity: ' . (float) $v['opacity'] . ';';
			}
			return implode( ' ', $declarations );
		}

		if ( 'classic' !== $v['type'] ) {
			return null;
		}

		$declarations = array();
		$color        = self::sanitize_css_value( $v['color'] );
		if ( '' !== $color ) {
			$declarations[] = "background-color: {$color};";
		}

		$img = $v['image'];
		$url = self::sanitize_css_url( $img['url'] ?? '' );
		if ( '' !== $url ) {
			$declarations[] = 'background-image: url("' . $url . '");';
			foreach ( array( 'position', 'size', 'repeat', 'attachment' ) as $prop ) {
				$val = self::sanitize_css_bare_token( $img[ $prop ] ?? '' );
				if ( '' !== $val ) {
					$declarations[] = "background-{$prop}: {$val};";
				}
			}
		}

		if ( isset( $v['opacity'] ) && '' !== (string) $v['opacity'] && is_numeric( $v['opacity'] ) ) {
			$declarations[] = 'opacity: ' . (float) $v['opacity'] . ';';
		}

		return $declarations ? implode( ' ', $declarations ) : null;
	}

	/**
	 * Flattens every stored background shape into one. Port of the JS
	 * `normalizeBackground()`; keep the two in sync.
	 *
	 * Accepted shapes:
	 * - v3 (current): `{ type, version: 3, classic: { color, image }, gradient }`.
	 * - v2: top-level `color` / `image` with a separate 'image' type whose
	 *   `image.fallbackColor` becomes the Classic color. v2 Classic was
	 *   color-only, so its leftover image is dropped.
	 * - pre-v2 (no version): top-level `color` / `image`, Classic already
	 *   combined both; unset image properties took these fallbacks.
	 * A present-but-null type (the document sanitizer saves null as ''),
	 * and the pre-v2 'none', mean an explicit "no background". A missing
	 * type on a pre-v2 value with a color/image set means Classic.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $v Raw stored value.
	 * @return array{type: string|null, color: mixed, image: array, gradient: array}
	 *               `type` is 'none', 'classic', 'gradient', or null when unset.
	 */
	private static function normalize_background( array $v ): array {
		$version  = is_numeric( $v['version'] ?? null ) ? (int) $v['version'] : 0;
		$raw_type = array_key_exists( 'type', $v ) ? (string) $v['type'] : null;
		$top_img  = is_array( $v['image'] ?? null ) ? $v['image'] : array();

		if ( '' === $raw_type || 'none' === $raw_type ) {
			$type = 'none';
		} elseif ( 'image' === $raw_type ) {
			$type = 'classic';
		} elseif ( null === $raw_type ) {
			$type = ( ! $version && ( ! empty( $v['color'] ) || ! empty( $top_img['url'] ) ) ) ? 'classic' : null;
		} else {
			$type = $raw_type;
		}

		$defaults = array();
		if ( is_array( $v['classic'] ?? null ) ) {
			$color = $v['classic']['color'] ?? '';
			$img   = is_array( $v['classic']['image'] ?? null ) ? $v['classic']['image'] : array();
		} elseif ( 2 === $version ) {
			$is_image = 'image' === $raw_type;
			$color    = $is_image ? ( $top_img['fallbackColor'] ?? '' ) : ( $v['color'] ?? '' );
			$img      = $is_image ? $top_img : array();
		} else {
			$color    = $v['color'] ?? '';
			$img      = $top_img;
			$defaults = array(
				'position'   => 'center center',
				'size'       => 'cover',
				'repeat'     => 'no-repeat',
				'attachment' => 'scroll',
			);
		}

		foreach ( $defaults as $prop => $default ) {
			if ( empty( $img[ $prop ] ) ) {
				$img[ $prop ] = $default;
			}
		}

		return array(
			'type'     => $type,
			'color'    => $color,
			'image'    => $img,
			'gradient' => is_array( $v['gradient'] ?? null ) ? $v['gradient'] : array(),
		);
	}

	/**
	 * Converts a 'boxShadow' control value into a CSS `box-shadow` value.
	 * Port of the JS engine's `boxShadow.js` `boxShadowToCss()`.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $v The box-shadow control's value, e.g.
	 *                  `[ 'horizontal' => 0, 'vertical' => 4, 'blur' => 8,
	 *                  'spread' => 0, 'color' => '#000', 'position' => 'inset' ]`.
	 * @return string|null The generated `box-shadow` value (without the
	 *                      property name), or null when no shadow is set.
	 */
	public static function box_shadow_to_css( $v ): ?string {
		if ( ! is_array( $v ) ) {
			return null;
		}
		$h        = is_numeric( $v['horizontal'] ?? null ) ? (float) $v['horizontal'] : 0;
		$vert     = is_numeric( $v['vertical'] ?? null ) ? (float) $v['vertical'] : 0;
		$blur     = is_numeric( $v['blur'] ?? null ) ? (float) $v['blur'] : 0;
		$spread   = is_numeric( $v['spread'] ?? null ) ? (float) $v['spread'] : 0;
		$color    = self::sanitize_css_value( $v['color'] ?? '' );
		$position = self::sanitize_css_bare_token( $v['position'] ?? '' );

		$active = 0 != $h || 0 != $vert || 0 != $blur || 0 != $spread || '' !== $color;
		if ( ! $active ) {
			return null;
		}

		$prefix      = 'inset' === $position ? 'inset ' : '';
		$final_color = $color ?: '#000000';
		return "{$prefix}{$h}px {$vert}px {$blur}px {$spread}px {$final_color}";
	}
}
