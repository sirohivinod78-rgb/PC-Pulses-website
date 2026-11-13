<?php
/**
 * Shared icon rendering helper for Form Builder blocks.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves an icon value and optionally wraps it in an HTML element.
 * Blocks call this class directly and provide their wrapper details.
 *
 * @since 4.4.0
 */
class Block_Icon {

	/**
	 * Resolves and renders one icon.
	 *
	 * When `$tag` is empty, only the resolved `<svg>` or `<img>` is returned.
	 * Otherwise the icon is wrapped in the requested element.
	 *
	 * @param mixed                $value      Stored icon value.
	 * @param string               $class      Space-separated wrapper classes.
	 * @param array<string, mixed> $attributes Wrapper attributes.
	 * @param string               $tag        Wrapper tag, or empty for none.
	 * @return string
	 */
	public static function icon( $value, string $class = '', array $attributes = array(), string $tag = '' ): string {
		$icon_html = Icon_Resolver::resolve( $value );
		if ( '' === $icon_html || '' === $tag ) {
			return $icon_html;
		}

		$tag        = self::normalize_tag( $tag );
		$attributes = self::add_class_attribute( $attributes, $class );

		return sprintf(
			'<%1$s%2$s>%3$s</%1$s>',
			$tag,
			self::render_attributes( $attributes ),
			$icon_html
		);
	}

	/**
	 * Limits wrappers to the elements used by Form Builder icons.
	 */
	private static function normalize_tag( string $tag ): string {
		$tag = strtolower( trim( $tag ) );

		return in_array( $tag, array( 'span', 'div' ), true ) ? $tag : 'span';
	}

	/**
	 * Sanitizes wrapper classes and adds them to the attribute collection.
	 * The explicit `$class` argument takes precedence over `class` supplied
	 * in `$attributes`.
	 *
	 * @param array<string, mixed> $attributes Wrapper attributes.
	 * @param string               $class      Space-separated class names.
	 * @return array<string, mixed>
	 */
	private static function add_class_attribute( array $attributes, string $class ): array {
		$classes = self::sanitize_classes( $class );
		if ( '' === $classes ) {
			return $attributes;
		}

		return array( 'class' => $classes ) + $attributes;
	}

	/**
	 * Sanitizes a space-separated class list and removes duplicates.
	 */
	private static function sanitize_classes( string $class ): string {
		$classes = preg_split( '/\s+/', trim( $class ), -1, PREG_SPLIT_NO_EMPTY );
		$classes = array_map( 'sanitize_html_class', $classes );
		$classes = array_filter( array_unique( $classes ) );

		return implode( ' ', $classes );
	}

	/**
	 * Renders the wrapper attribute collection.
	 *
	 * @param array<string, mixed> $attributes Wrapper attributes.
	 */
	private static function render_attributes( array $attributes ): string {
		$html = '';

		foreach ( $attributes as $name => $value ) {
			$html .= self::render_attribute( (string) $name, $value );
		}

		return $html;
	}

	/**
	 * Renders one allowed wrapper attribute.
	 *
	 * @param string $name  Attribute name.
	 * @param mixed  $value Attribute value.
	 */
	private static function render_attribute( string $name, $value ): string {
		$name = strtolower( $name );

		if ( ! self::is_allowed_attribute( $name ) || false === $value || null === $value ) {
			return '';
		}

		if ( true === $value ) {
			return ' ' . $name;
		}

		return is_scalar( $value )
			? sprintf( ' %s="%s"', $name, esc_attr( (string) $value ) )
			: '';
	}

	/**
	 * Restricts wrapper attributes to inert metadata and accessibility values.
	 */
	private static function is_allowed_attribute( string $name ): bool {
		return in_array( $name, array( 'class', 'id', 'role', 'title', 'hidden' ), true )
			|| 1 === preg_match( '/^(?:aria|data)-[a-z0-9_.:-]+$/', $name );
	}

	/** This is a static utility class. */
	private function __construct() {}
}
