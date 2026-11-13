<?php
namespace MetForm\Core\FormBuilder\Document;

use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Advanced\Visibility;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes a form-builder document before persistence/preview.
 *
 * @since 4.1.8
 */
class Sanitizer {
	use Singleton;

	/**
	 * Sanitizes a full form-builder document.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $document Raw form-builder document.
	 * @return array<string, mixed> Sanitized form-builder document.
	 */
	public function sanitize( array $document ): array {
		$clean = array(
			'version'  => '1.0.0',
			'elements' => array(),
		);

		foreach ( ( $document['elements'] ?? array() ) as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$type  = sanitize_key( $element['type'] ?? '' );
			$block = Block_Registry::instance()->get( $type );
			if ( ! $block ) {
				continue;
			}

			$props = (array) ( $element['props'] ?? $element['settings'] ?? array() );
			if ( 'calculation' === $type && ! isset( $props['expression'] ) && isset( $props['equation'] ) ) {
				$props['expression'] = $props['equation'];
			}
			$sanitized_props  = $this->sanitize_props( $props, $block->get_property_schema() );
			$sanitized_props += $this->sanitize_common( $props );

			$clean['elements'][] = array(
				'id'       => sanitize_key( $element['id'] ?? wp_unique_id( 'block-' ) ),
				'type'     => $type,
				'parentId' => isset( $element['parentId'] ) && is_scalar( $element['parentId'] ) && '' !== $element['parentId']
					? sanitize_key( (string) $element['parentId'] )
					: null,
				'props'    => $sanitized_props,
			);
		}

		return $clean;
	}

	/**
	 * Sanitizes every prop the block's own schema declares.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $props  Raw element props.
	 * @param array<string, mixed> $schema Block's get_property_schema() output.
	 * @return array<string, mixed> Sanitized props limited to the block's schema.
	 */
	private function sanitize_props( array $props, array $schema ): array {
		$clean = array();

		foreach ( $schema as $key => $rule ) {
			if ( ! array_key_exists( $key, $props ) ) {
				continue;
			}
			$value = $this->sanitize_value( $props[ $key ], is_array( $rule ) ? $rule : array() );
			if ( null !== $value ) {
				$clean[ $key ] = $value;
			}
		}

		return $clean;
	}

	/**
	 * Fallback for the cross-cutting keys blocks may accept without
	 * declaring them in get_property_schema() (Input relies on this for
	 * __className; every block gets `styles` support this way too).
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $props Raw element props.
	 * @return array<string, mixed> Sanitized cross-cutting props.
	 */
	private function sanitize_common( array $props ): array {
		$clean = array();

		if ( isset( $props['__className'] ) && is_scalar( $props['__className'] ) ) {
			$clean['__className'] = sanitize_html_class( (string) $props['__className'] );
		}
		if ( isset( $props['styles'] ) && is_array( $props['styles'] ) ) {
			$clean['styles'] = $this->sanitize_styles_tree( $props['styles'] );
		}
		if ( isset( $props['stepIndex'] ) && is_numeric( $props['stepIndex'] ) ) {
			$clean['stepIndex'] = (int) $props['stepIndex'];
		}
		if ( isset( $props['conditional'] ) && is_array( $props['conditional'] ) ) {
			$raw_cond         = $props['conditional'];
			$clean_conditions = array();
			if ( ! empty( $raw_cond['conditions'] ) && is_array( $raw_cond['conditions'] ) ) {
				foreach ( $raw_cond['conditions'] as $c ) {
					if ( ! is_array( $c ) ) {
						continue;
					}
					$field    = sanitize_text_field( (string) ( $c['field'] ?? '' ) );
					$operator = sanitize_key( (string) ( $c['operator'] ?? 'equals' ) );
					$value    = sanitize_text_field( (string) ( $c['value'] ?? '' ) );
					if ( '' !== $field ) {
						$clean_conditions[] = array(
							'field'    => $field,
							'operator' => $operator,
							'value'    => $value,
						);
					}
				}
			}
			$clean['conditional'] = array(
				'enabled'       => ! empty( $raw_cond['enabled'] ) && '0' !== $raw_cond['enabled'] && 'false' !== $raw_cond['enabled'],
				'action'        => ( 'hide' === ( $raw_cond['action'] ?? 'show' ) ) ? 'hide' : 'show',
				'matchCriteria' => ( 'OR' === strtoupper( (string) ( $raw_cond['matchCriteria'] ?? 'AND' ) ) ) ? 'OR' : 'AND',
				'conditions'    => $clean_conditions,
			);
		}

		// Advanced tab (CSS ID/classes, entrance animation, responsive
		// visibility) — shared by every block, so not declared per block.
		$clean += Visibility::sanitize( $props );

		return $clean;
	}

	/**
	 * Sanitizes the free-form `styles` tree, treating any `url` leaf as a
	 * URL rather than plain text.
	 *
	 * Every block's Background/Background Overlay/Background Hover control
	 * stores its picked image as `styles.background.image.url` (and the
	 * same shape for the overlay/hover variants). Running the whole tree
	 * through `sanitize_text_field()` (the previous behavior, via
	 * `map_deep()`) is fine for color/position/size/repeat values, but
	 * `sanitize_text_field()` is not URL-aware — it can mangle characters
	 * that are valid and meaningful in a URL (e.g. query strings some CDN or
	 * image-optimizer setups append to an attachment URL). `esc_url_raw()`
	 * is the correct sanitizer for a value that will be persisted and later
	 * re-emitted as a URL.
	 *
	 * @since 4.1.9
	 *
	 * @param array<string, mixed> $tree Raw `styles` value.
	 * @return array<string, mixed> Sanitized `styles` value.
	 */
	private function sanitize_styles_tree( array $tree ): array {
		$clean = array();

		foreach ( $tree as $key => $value ) {
			if ( is_array( $value ) ) {
				$clean[ $key ] = $this->sanitize_styles_tree( $value );
			} elseif ( 'url' === $key && is_scalar( $value ) ) {
				$clean[ $key ] = esc_url_raw( (string) $value );
			} elseif ( is_scalar( $value ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}

		return $clean;
	}

	/**
	 * Sanitizes one prop value according to its schema rule's declared type.
	 *
	 * @since 4.1.8
	 *
	 * @param mixed                $value Raw prop value.
	 * @param array<string, mixed> $rule  Schema rule for this prop (expects a `type` key, optional `enum`).
	 * @return mixed Sanitized value, or null to drop the prop.
	 */
	private function sanitize_value( $value, array $rule ) {
		// `'responsive' => true` on any rule: the saved value is either the
		// plain (desktop) value or a `{desktop,tablet,mobile}` map whose
		// slots are each sanitized with the same rule (enum included).
		// Empty slots are dropped, which is how a breakpoint override is
		// cleared (it then inherits from the next larger breakpoint).
		if ( ! empty( $rule['responsive'] ) && Responsive_Value::is_responsive( $value ) ) {
			$inner = $rule;
			unset( $inner['responsive'] );
			$out = array();
			foreach ( Visibility::DEVICES as $slot ) {
				if ( ! array_key_exists( $slot, $value ) || null === $value[ $slot ] || '' === $value[ $slot ] ) {
					continue;
				}
				$clean = $this->sanitize_value( $value[ $slot ], $inner );
				if ( null !== $clean ) {
					$out[ $slot ] = $clean;
				}
			}
			if ( ! $out ) {
				return null;
			}
			// Only desktop left: store the plain value again.
			return array( 'desktop' ) === array_keys( $out ) ? $out['desktop'] : $out;
		}

		$type = $rule['type'] ?? 'string';

		switch ( $type ) {
			case 'boolean':
				return (bool) $value;

			case 'number':
			case 'integer':
				return is_numeric( $value ) ? $value + 0 : null;

			case 'field_name':
				return is_scalar( $value ) ? sanitize_key( (string) $value ) : null;

			case 'css_class':
				return is_scalar( $value ) ? sanitize_html_class( (string) $value ) : null;

			case 'url':
				return is_scalar( $value ) ? esc_url_raw( (string) $value ) : null;

			case 'object':
				return is_array( $value ) ? map_deep( $value, 'sanitize_text_field' ) : null;

			case 'array':
				return is_array( $value ) ? array_map( 'sanitize_text_field', array_filter( $value, 'is_scalar' ) ) : null;

			// Unlike 'array' (a list of free-text strings), this is for a
			// list that must stay numeric end to end: sanitize_text_field()
			// would cast every entry to a string, and editor code doing
			// raw `+`/`-` arithmetic on such values would silently
			// concatenate instead of add after a save/reload.
			case 'number_array':
				return is_array( $value )
					? array_values(
						array_map(
							static function ( $entry ) {
								return is_numeric( $entry ) ? $entry + 0 : 0;
							},
							$value
						)
					)
					: null;

			// Per-breakpoint numeric lists (Container's grid track sizes):
			// `{desktop?, tablet?, mobile?}` each a list of positive numbers.
			// A bare list is kept as the desktop slot. Lists are capped at
			// 12 entries and every entry clamped to (0, 10000].
			case 'responsive_number_array':
				if ( ! is_array( $value ) || empty( $value ) ) {
					return null;
				}
				if ( array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
					$value = array( 'desktop' => $value );
				}
				$clean = array();
				foreach ( array( 'desktop', 'tablet', 'mobile' ) as $slot ) {
					if ( empty( $value[ $slot ] ) || ! is_array( $value[ $slot ] ) ) {
						continue;
					}
					$clean[ $slot ] = array_map(
						static function ( $entry ) {
							$number = is_numeric( $entry ) ? (float) $entry : 0.0;
							return $number > 0 ? min( $number, 10000.0 ) : 0;
						},
						array_slice( array_values( $value[ $slot ] ), 0, 12 )
					);
				}
				return empty( $clean ) ? null : $clean;

			// A handful of top-level (not `styles.*`) content controls can be
			// marked `responsive: true` too (Container's `direction`) — the
			// saved value is then either a plain string or a
			// `{desktop,tablet,mobile}` object, unlike every other `string`
			// key which is always a plain scalar. Handles both shapes
			// instead of forcing a single fixed type.
			case 'responsive_string':
				if ( is_array( $value ) ) {
					return map_deep( $value, 'sanitize_text_field' );
				}
				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : null;

			// Icon field value. Scalars: an icon key ("fas-star", what the
			// picker saves for a bundled library icon since 4.4.0 — plain
			// [a-z0-9-], so sanitize_text_field() leaves it unchanged), or
			// a media URL, or a base64 data URI. Arrays: the WordPress
			// Icon API's `{type:'icon', collection, name, attachmentId?}`
			// (`core`/`metform-custom` picks).
			case 'icon_value':
				if ( is_array( $value ) ) {
					if ( 'icon' !== ( $value['type'] ?? '' ) || empty( $value['collection'] ) || empty( $value['name'] ) ) {
						return null;
					}

					$clean = array(
						'type'       => 'icon',
						'collection' => sanitize_key( (string) $value['collection'] ),
						'name'       => sanitize_key( (string) $value['name'] ),
					);

					if ( isset( $value['attachmentId'] ) && is_numeric( $value['attachmentId'] ) ) {
						$clean['attachmentId'] = (int) $value['attachmentId'];
					}

					return $clean;
				}

				return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : null;

			case 'string':
			default:
				if ( ! is_scalar( $value ) ) {
					return null;
				}
				$clean = sanitize_text_field( (string) $value );
				if ( ! empty( $rule['enum'] ) && ! in_array( $clean, $rule['enum'], true ) ) {
					// Invalid enum value — drop it and let Form_Renderer's
					// merge_settings() fill in from get_default_settings().
					return null;
				}
				return $clean;
		}
	}
}
