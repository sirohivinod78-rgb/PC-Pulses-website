<?php
namespace MetForm\Core\FormBuilder\Advanced;

use MetForm\Core\FormBuilder\Dynamic_Css\Css_Value_Formatter;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a block's Advanced-tab controls + saved settings into CSS.
 *
 * Unlike the Style tab (fed straight to Css_Value_Formatter), Advanced
 * controls may be hidden by a `condition` (e.g. Custom Width only when
 * Width = Custom) or store a value that isn't CSS (`cssValueMap`, e.g.
 * Width = full → 100%), so both are resolved here first. Responsive
 * visibility is appended last.
 *
 * @since 4.2.0
 */
final class Css_Builder {

	/**
	 * Builds the Advanced CSS for one block instance.
	 *
	 * @param array<int, array<string, mixed>> $controls   The block's Advanced controls.
	 * @param array<string, mixed>             $settings   Merged block settings.
	 * @param string                           $class_name Scoped selector without the leading dot.
	 * @return string Raw CSS.
	 */
	public static function build( array $controls, array $settings, string $class_name ): string {
		$css_settings = $settings;
		$css_controls = array();

		foreach ( $controls as $control ) {
			if ( ! is_array( $control ) || empty( $control['selector'] ) || empty( $control['key'] ) ) {
				continue;
			}
			$condition = $control['condition'] ?? null;
			// Conditions read the saved values, never the CSS-mapped ones.
			if ( Controls::condition_is_responsive( $condition, $settings ) ) {
				// The condition holds on some breakpoints only (e.g. Custom
				// Width while Width = Custom on mobile alone): keep this
				// control's value just for the slots where it holds.
				$matching = array_values(
					array_filter(
						Visibility::DEVICES,
						static function ( $slot ) use ( $condition, $settings ) {
							return Controls::condition_matches( $condition, $settings, $slot );
						}
					)
				);
				if ( ! $matching ) {
					continue;
				}
				if ( count( $matching ) < count( Visibility::DEVICES ) ) {
					self::limit_to_slots( $css_settings, $control['key'], $matching );
					$control['responsive'] = true;
				}
			} elseif ( ! Controls::condition_matches( $condition, $settings ) ) {
				continue;
			}
			if ( ! empty( $control['cssValueMap'] ) && is_array( $control['cssValueMap'] ) ) {
				self::apply_value_map( $css_settings, $control['key'], $control['cssValueMap'] );
			}
			$css_controls[] = $control;
		}

		return Css_Value_Formatter::build_css( $class_name, $css_controls, $css_settings )
			. Visibility::build_css( $settings, $class_name );
	}

	/**
	 * Keeps a saved value only for the given breakpoint slots (each slot
	 * resolved through the mobile → tablet → desktop cascade), so the
	 * control emits CSS just where its condition holds.
	 *
	 * @since 4.4.1
	 *
	 * @param array<string, mixed> $settings Settings (by reference).
	 * @param string               $key      Dot-path key.
	 * @param string[]             $slots    Slots to keep.
	 */
	private static function limit_to_slots( array &$settings, string $key, array $slots ): void {
		$ref = &$settings;
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $ref ) || ! array_key_exists( $segment, $ref ) ) {
				return;
			}
			$ref = &$ref[ $segment ];
		}
		$value   = $ref;
		$limited = array();
		foreach ( $slots as $slot ) {
			$slot_value = Responsive_Value::resolve( $value, $slot, null );
			if ( null !== $slot_value ) {
				$limited[ $slot ] = $slot_value;
			}
		}
		$ref = $limited;
	}

	/**
	 * Replaces a saved value (or each responsive slot of it) with its CSS
	 * value; unmapped values become null so they emit nothing.
	 *
	 * @param array<string, mixed>  $settings Settings (by reference).
	 * @param string                $key      Dot-path key.
	 * @param array<string, string> $map      Saved value => CSS value.
	 */
	private static function apply_value_map( array &$settings, string $key, array $map ): void {
		$mapper = static function ( $value ) use ( $map ) {
			return is_scalar( $value ) && isset( $map[ (string) $value ] ) ? $map[ (string) $value ] : null;
		};

		// Walk to the value by reference; a missing path leaves settings as is.
		$ref = &$settings;
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $ref ) || ! array_key_exists( $segment, $ref ) ) {
				return;
			}
			$ref = &$ref[ $segment ];
		}

		$ref = Responsive_Value::is_responsive( $ref ) ? array_map( $mapper, $ref ) : $mapper( $ref );
	}
}
