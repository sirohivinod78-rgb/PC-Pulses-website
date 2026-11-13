<?php
/**
 * Icon SVG helpers.
 *
 * Lazily maps icon classes to local SVG markup.
 *
 * @package Jeg_Elementor_Kit
 */

// phpcs:disable Generic.Files.LineLength.TooLong

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'jkit_normalize_icon_svg_class' ) ) {
	/**
	 * Normalize an icon class.
	 *
	 * @param string $icon_class Icon class or rendered icon markup.
	 *
	 * @return string
	 */
	function jkit_normalize_icon_svg_class( $icon_class ) {
		if ( false !== strpos( $icon_class, '<' ) && false !== strpos( $icon_class, 'class=' ) ) {
			$class_pos = strpos( $icon_class, 'class=' );
			$quote     = substr( $icon_class, $class_pos + 6, 1 );

			if ( '"' === $quote || "'" === $quote ) {
				$class_start = $class_pos + 7;
				$class_end   = strpos( $icon_class, $quote, $class_start );

				if ( false !== $class_end ) {
					$icon_class = substr( $icon_class, $class_start, $class_end - $class_start );
				}
			}
		}

		$icon_class = trim( str_replace( array( "\t", "\n", "\r", "\f" ), ' ', $icon_class ) );
		/*
		 * The icon font contains a small set of distinct glyphs whose names differ
		 * only by case. Resolve those names before lowercasing so each glyph keeps
		 * a unique, filesystem-safe SVG filename.
		 */
		static $case_sensitive_svg_icons = array(
			'jki-battery-light'    => 'jki-battery-light-f9df',
			'jki-Battery-light'    => 'jki-battery-light-fba7',
			'jki-car-light'        => 'jki-car-light-fb06',
			'jki-Car-light'        => 'jki-car-light-fbab',
			'jki-coffee-cup-light' => 'jki-coffee-cup-light-f848',
			'jki-Coffee-cup-light' => 'jki-coffee-cup-light-fbaf',
			'jki-computer-light'   => 'jki-computer-light-fafa',
			'jki-Computer-light'   => 'jki-computer-light-fbb1',
			'jki-download-light'   => 'jki-download-light-f865',
			'jki-Download-light'   => 'jki-download-light-fbbd',
			'jki-leaf-light'       => 'jki-leaf-light-f849',
			'jki-Leaf-light'       => 'jki-leaf-light-fbcd',
			'jki-money-light'      => 'jki-money-light-fac9',
			'jki-Money-light'      => 'jki-money-light-fbd4',
			'jki-target-light'     => 'jki-target-light-fa89',
			'jki-Target-light'     => 'jki-target-light-fbe2',
			'jki-user-light'       => 'jki-user-light-f82a',
			'jki-User-light'       => 'jki-user-light-fbe6',
		);
		$icon_parts = explode( ' ', $icon_class );

		foreach ( $icon_parts as $index => $icon_part ) {
			if ( isset( $case_sensitive_svg_icons[ $icon_part ] ) ) {
				$icon_parts[ $index ] = $case_sensitive_svg_icons[ $icon_part ];
			}
		}

		$icon_parts = array_map( 'strtolower', $icon_parts );
		$icon_class = implode( ' ', $icon_parts );
		static $icon_helpers     = array(
			'fa'                 => true,
			'fas'                => true,
			'far'                => true,
			'fab'                => true,
			'fal'                => true,
			'fa-solid'           => true,
			'fa-regular'         => true,
			'fa-brands'          => true,
			'fa-light'           => true,
			'fa-lg'              => true,
			'fa-xs'              => true,
			'fa-sm'              => true,
			'fa-fw'              => true,
			'fa-spin'            => true,
			'fa-pulse'           => true,
			'fa-border'          => true,
			'fa-pull-left'       => true,
			'fa-pull-right'      => true,
			'fa-flip-horizontal' => true,
			'fa-flip-vertical'   => true,
			'jki'                => true,
		);
		static $valid_icon_chars = 'abcdefghijklmnopqrstuvwxyz0123456789-_';

		for ( $i = count( $icon_parts ) - 1; $i >= 0; $i-- ) {
			$icon_part = trim( $icon_parts[ $i ] );

			if ( '' === $icon_part || isset( $icon_helpers[ $icon_part ] ) || ( 5 === strlen( $icon_part ) && 'fa-' === substr( $icon_part, 0, 3 ) && $icon_part[3] >= '1' && $icon_part[3] <= '9' && 'x' === $icon_part[4] ) || ( 0 === strpos( $icon_part, 'fa-rotate-' ) && ctype_digit( substr( $icon_part, 10 ) ) ) ) {
				continue;
			}

			if ( 4 < strlen( $icon_part ) && 0 === strpos( $icon_part, 'jki-' ) && strspn( $icon_part, $valid_icon_chars, 4 ) === strlen( $icon_part ) - 4 ) {
				return $icon_part;
			}

			if ( 3 < strlen( $icon_part ) && 0 === strpos( $icon_part, 'fa-' ) && strspn( $icon_part, $valid_icon_chars, 3 ) === strlen( $icon_part ) - 3 ) {
				return $icon_part;
			}
		}

		return $icon_class;
	}
}

if ( ! function_exists( 'jkit_get_icon_svg_family' ) ) {
	/**
	 * Detect the local icon SVG family from an icon class.
	 *
	 * @param string $icon_class Normalized icon class.
	 *
	 * @return string
	 */
	function jkit_get_icon_svg_family( $icon_class ) {
		return 0 === strpos( $icon_class, 'jki-' ) ? 'jkiticon' : 'fa';
	}
}

if ( ! function_exists( 'jkit_get_icon_svg_file_name' ) ) {
	/**
	 * Get icon file name for a normalized icon class.
	 *
	 * @param string $icon_class Normalized icon class.
	 * @param string $family     Icon family.
	 *
	 * @return string
	 */
	function jkit_get_icon_svg_file_name( $icon_class, $family = 'fa' ) {
		static $valid_icon_chars = 'abcdefghijklmnopqrstuvwxyz0123456789-_';

		if ( 'jkiticon' === $family && 0 === strpos( $icon_class, 'jki-' ) ) {
			$icon = substr( $icon_class, 4 );
		} elseif ( 0 === strpos( $icon_class, 'fa fa-' ) ) {
			$icon = substr( $icon_class, 6 );
		} elseif ( 0 === strpos( $icon_class, 'fa-' ) ) {
			$icon = substr( $icon_class, 3 );
		} else {
			$icon = $icon_class;
		}

		$icon = strtolower( $icon );

		return strspn( $icon, $valid_icon_chars ) === strlen( $icon ) ? $icon : '';
	}
}

if ( ! function_exists( 'jkit_get_icon_svg_data' ) ) {
	/**
	 * Load SVG data for one icon.
	 *
	 * @param string $icon   Icon file name.
	 * @param string $family Icon family.
	 *
	 * @return array
	 */
	function jkit_get_icon_svg_data( $icon, $family = 'fa' ) {
		static $icons = array();
		$cache_key    = $family . ':' . $icon;

		if ( isset( $icons[ $cache_key ] ) ) {
			return $icons[ $cache_key ];
		}

		$directory           = 'jkiticon' === $family ? 'jkiticon' : 'fa';
		$file                = JEG_ELEMENTOR_KIT_DIR . 'lib/icon-svg/' . $directory . '/' . $icon . '.php';
		$icons[ $cache_key ] = is_readable( $file ) ? include $file : array(
			'svg'   => '',
			'svg_6' => '',
		);

		return $icons[ $cache_key ];
	}
}

if ( ! function_exists( 'jkit_get_icon_svg' ) ) {
	/**
	 * Get icon SVG markup by icon class.
	 *
	 * @param string $icon_class Icon class, for example fas fa-shopping-cart or jki jki-jeg-kit-logo.
	 * @param string $version    SVG version key.
	 *
	 * @return array
	 */
	function jkit_get_icon_svg( $icon_class, $version = 'svg_6' ) {
		$icon_class = jkit_normalize_icon_svg_class( $icon_class );
		$family     = jkit_get_icon_svg_family( $icon_class );
		$filename   = jkit_get_icon_svg_file_name( $icon_class, $family );
		$svg        = '' !== $filename ? jkit_get_icon_svg_data( $filename, $family ) : array();
		$markup     = isset( $svg[ $version ] ) ? $svg[ $version ] : '';

		if ( '' === $markup && 'svg_6' === $version && isset( $svg['svg'] ) ) {
			$markup = $svg['svg'];
		}

		return array(
			'family' => $family,
			'svg'    => $markup,
			'name'   => $filename,
		);
	}
}

if ( ! function_exists( 'jkit_render_icon_svg' ) ) {
	/**
	 * Determine whether Font Awesome icons should be rendered by Elementor.
	 *
	 * @return bool
	 */
	function jkit_should_delegate_font_awesome_icon_rendering() {
		return (bool) apply_filters( 'jkit_delegate_font_awesome_icon_rendering', true );
	}

	/**
	 * Determine whether an icon class belongs to Font Awesome.
	 *
	 * @param string $icon_class Icon class.
	 *
	 * @return bool
	 */
	function jkit_is_font_awesome_icon( $icon_class ) {
		$icon_class = jkit_normalize_icon_svg_class( $icon_class );

		return 0 === strpos( $icon_class, 'fa-' ) || 0 === strpos( $icon_class, 'fa fa-' );
	}

	/**
	 * Render a Font Awesome icon with Elementor's Icon Manager.
	 *
	 * @param string $icon_class Icon class.
	 * @param array  $attr       HTML attributes for the icon.
	 *
	 * @return string
	 */
	function jkit_render_elementor_font_awesome_icon( $icon_class, $attr = array() ) {
		if ( ! class_exists( '\\Elementor\\Icons_Manager' ) ) {
			return '';
		}

		$library = 'fa-solid';

		if ( preg_match( '/(?:^|\s)(?:far|fa-regular)(?:\s|$)/i', $icon_class ) ) {
			$library = 'fa-regular';
		} elseif ( preg_match( '/(?:^|\s)(?:fab|fa-brands)(?:\s|$)/i', $icon_class ) ) {
			$library = 'fa-brands';
		}

		ob_start();
		\Elementor\Icons_Manager::render_icon(
			array(
				'value'   => $icon_class,
				'library' => $library,
			),
			$attr
		);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Determine whether icon-to-SVG conversion is enabled.
	 *
	 * @return bool
	 */
	function jkit_should_convert_icon_svg() {
		if ( ! class_exists( '\Jeg\Elementor_Kit\Options\Options' ) ) {
			return false;
		}

		$options = \Jeg\Elementor_Kit\Options\Options::get_all_options();

		return ! empty( $options['JConvertSvg'] );
	}

	/**
	 * Render icon markup, delegating Font Awesome to Elementor and using JKit SVG
	 * conversion for native JKit icons when enabled.
	 *
	 * @param string $icon_class Icon class.
	 * @param array  $attr       HTML attributes for wrapper.
	 * @param string $tag        Fallback tag when SVG conversion is disabled.
	 *
	 * @return string
	 */
	function jkit_render_icon_markup( $icon_class, $attr = array(), $tag = 'i' ) {
		$attr = array_merge(
			array(
				'aria-hidden' => 'true',
			),
			$attr
		);

		if ( jkit_is_font_awesome_icon( $icon_class ) ) {
			if ( jkit_should_delegate_font_awesome_icon_rendering() ) {
				$elementor_icon = jkit_render_elementor_font_awesome_icon( $icon_class, $attr );

				if ( '' !== $elementor_icon ) {
					return $elementor_icon;
				}
			}
		} elseif ( jkit_should_convert_icon_svg() ) {
			return jkit_render_icon_svg( $icon_class, $attr );
		}

		$classes       = trim( $icon_class . ( empty( $attr['class'] ) ? '' : ' ' . $attr['class'] ) );
		$attr['class'] = $classes;
		$attributes    = '';

		foreach ( $attr as $name => $value ) {
			if ( null === $value || false === $value ) {
				continue;
			}

			$attributes .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return '<' . tag_escape( $tag ) . $attributes . '></' . tag_escape( $tag ) . '>';
	}

	/**
	 * Render an inline SVG icon from a supported icon class.
	 *
	 * @param string $icon_class Icon class.
	 * @param array  $attr       HTML attributes for wrapper.
	 *
	 * @return string
	 */
	function jkit_render_icon_svg( $icon_class, $attr = array() ) {
		$svg = jkit_get_icon_svg( $icon_class );

		if ( empty( $svg['svg'] ) || empty( $svg['name'] ) ) {
			return '';
		}

		$attr = array_merge(
			array(
				'aria-hidden' => 'true',
			),
			$attr
		);

		if ( isset( $attr['class'] ) && is_array( $attr['class'] ) ) {
			$attr['class'] = implode( ' ', array_filter( $attr['class'], 'is_scalar' ) );
		}

		$classes       = 'jkit-icon-svg jkit-svg-' . $svg['family'] . ' jkit-svg-' . $svg['name'];
		$attr['class'] = empty( $attr['class'] ) ? $classes : trim( $attr['class'] . ' ' . $classes );
		$attributes    = '';

		foreach ( $attr as $name => $value ) {
			if ( null === $value || false === $value ) {
				continue;
			}

			$attributes .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return '<span' . $attributes . '>' . $svg['svg'] . '</span>';
	}
}
