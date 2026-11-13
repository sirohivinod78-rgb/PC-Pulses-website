<?php
/**
 * Style Loader
 *
 * @author Jegstudio
 * @since 1.3.4
 * @package jeg-framework
 */

namespace Jeg\Util;

/**
 * Class Style_Loader
 *
 * @package Jeg\Util
 */
class Style_Loader {
	/**
	 * Register a style and inline it when the configured policy allows.
	 *
	 * @param string $handle       Style handle.
	 * @param string $file         Local CSS file path.
	 * @param string $url          CSS file URL.
	 * @param array  $dependencies Style dependencies.
	 * @param string $version      Style version.
	 * @param string $media        Style media.
	 * @param string $context      Inline policy context.
	 * @param string $base_url     Base URL for relative CSS urls.
	 * @param string $inline_css   Additional CSS to append inline.
	 *
	 * @return bool True when registered inline.
	 */
	public static function register_style( $handle, $file, $url, $dependencies = array(), $version = false, $media = 'all', $context = 'asset', $base_url = null, $inline_css = '' ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			return false;
		}

		$content = self::get_style_content( $file, $base_url, $inline_css );

		if ( null !== $content && self::can_inline( $content, $context, $handle ) ) {
			wp_register_style( $handle, false, $dependencies, $version, $media );

			if ( '' !== trim( $content ) ) {
				wp_add_inline_style( $handle, $content );
			}

			return true;
		}

		wp_register_style( $handle, $url, $dependencies, $version, $media );

		if ( '' !== trim( (string) $inline_css ) ) {
			wp_add_inline_style( $handle, $inline_css );
		}

		return false;
	}

	/**
	 * Enqueue a style and inline it when the configured policy allows.
	 *
	 * @param string $handle       Style handle.
	 * @param string $file         Local CSS file path.
	 * @param string $url          CSS file URL.
	 * @param array  $dependencies Style dependencies.
	 * @param string $version      Style version.
	 * @param string $media        Style media.
	 * @param string $context      Inline policy context.
	 * @param string $base_url     Base URL for relative CSS urls.
	 * @param string $inline_css   Additional CSS to append inline.
	 *
	 * @return bool True when enqueued inline.
	 */
	public static function enqueue_style( $handle, $file, $url, $dependencies = array(), $version = false, $media = 'all', $context = 'asset', $base_url = null, $inline_css = '' ) {
		$inline = self::register_style( $handle, $file, $url, $dependencies, $version, $media, $context, $base_url, $inline_css );

		wp_enqueue_style( $handle );

		return $inline;
	}

	/**
	 * Check whether CSS content can be rendered inline.
	 *
	 * @param string $css     CSS content.
	 * @param string $context Inline policy context.
	 * @param string $handle  Style handle.
	 *
	 * @return bool
	 */
	private static function can_inline( $css, $context, $handle ) {
		$enabled = (bool) apply_filters( 'jeg_inline_css_enabled', false, $context, $handle );

		if ( ! $enabled ) {
			return false;
		}

		$limit = absint( apply_filters( 'jeg_inline_css_size_limit', 0, $context, $handle ) );

		return 0 === $limit || strlen( (string) $css ) <= $limit * KB_IN_BYTES;
	}

	/**
	 * Get CSS file content and rewrite relative urls when inlined.
	 *
	 * @param string $file       Local CSS file path.
	 * @param string $base_url   Base URL for relative CSS urls.
	 * @param string $inline_css Additional CSS to append inline.
	 *
	 * @return string|null
	 */
	private static function get_style_content( $file, $base_url = null, $inline_css = '' ) {
		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return '' === trim( (string) $inline_css ) ? null : (string) $inline_css;
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $content ) {
			return '' === trim( (string) $inline_css ) ? null : (string) $inline_css;
		}

		if ( null !== $base_url ) {
			$content = self::replace_relative_css_urls( $content, $base_url );
		}

		return trim( $content . "\n" . $inline_css );
	}

	/**
	 * Rewrite relative CSS urls to absolute URLs before inlining.
	 *
	 * @param string $css      CSS content.
	 * @param string $base_url Base URL for relative CSS urls.
	 *
	 * @return string
	 */
	private static function replace_relative_css_urls( $css, $base_url ) {
		return preg_replace_callback(
			'/url\(\s*([\'"]?)(?![a-z][a-z0-9+.-]*:|\/\/|\/|#)([^\'")]+)\1\s*\)/i',
			function ( $matches ) use ( $base_url ) {
				$url = preg_replace( '/^\.\//', '', $matches[2] );

				return 'url("' . esc_url_raw( trailingslashit( $base_url ) . $url ) . '")';
			},
			$css
		);
	}
}
