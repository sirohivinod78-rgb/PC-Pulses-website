<?php
/**
 * Inline CSS policy.
 *
 * @package jeg-kit
 * @author Jegtheme
 * @since 3.2.9
 */

namespace Jeg\Elementor_Kit\Assets;

use Jeg\Elementor_Kit\Options\Settings;

/**
 * Class Inline_CSS_Policy
 *
 * Centralizes the inline CSS setting, size limit, and filters.
 *
 * @package Jeg\Elementor_Kit
 */
class Inline_CSS_Policy {
	/**
	 * Inline CSS option key.
	 */
	const OPTION_ENABLED = 'JAdditionalInlineCSS';

	/**
	 * Inline CSS size limit option key.
	 */
	const OPTION_SIZE_LIMIT = 'JAdditionalInlineCSSSizeLimit';

	/**
	 * Default inline CSS size limit in KB.
	 */
	const DEFAULT_SIZE_LIMIT = 500;

	/**
	 * Check whether inline CSS is enabled.
	 *
	 * @param string     $context  CSS context.
	 * @param array|null $settings Current settings.
	 *
	 * @return bool
	 */
	public static function is_enabled( $context = 'asset', $settings = null ) {
		$settings = self::settings( $settings );
		$enabled  = ! empty( $settings[ self::OPTION_ENABLED ] );

		return (bool) apply_filters( 'jkit_inline_css_enabled', $enabled, $context, $settings );
	}

	/**
	 * Get inline CSS size limit in KB.
	 *
	 * A value of 0 means no limit.
	 *
	 * @param array|null $settings Current settings.
	 *
	 * @return int
	 */
	public static function get_size_limit_kb( $settings = null ) {
		$settings = self::settings( $settings );
		$limit    = isset( $settings[ self::OPTION_SIZE_LIMIT ] ) ? absint( $settings[ self::OPTION_SIZE_LIMIT ] ) : self::DEFAULT_SIZE_LIMIT;

		return absint( apply_filters( 'jkit_inline_css_size_limit', $limit, $settings ) );
	}

	/**
	 * Get inline CSS size limit in bytes.
	 *
	 * A value of 0 means no limit.
	 *
	 * @param array|null $settings Current settings.
	 *
	 * @return int
	 */
	public static function get_size_limit_bytes( $settings = null ) {
		$limit = self::get_size_limit_kb( $settings );

		return 0 === $limit ? 0 : $limit * KB_IN_BYTES;
	}

	/**
	 * Check whether CSS content can be rendered inline.
	 *
	 * @param string     $css      CSS content.
	 * @param string     $context  CSS context.
	 * @param array|null $settings Current settings.
	 *
	 * @return bool
	 */
	public static function can_inline( $css, $context = 'asset', $settings = null ) {
		$settings = self::settings( $settings );

		if ( ! self::is_enabled( $context, $settings ) ) {
			return false;
		}

		$css   = trim( (string) $css );
		$limit = self::get_size_limit_bytes( $settings );

		if ( '' === $css ) {
			return false;
		}

		return 0 === $limit || strlen( $css ) <= $limit;
	}

	/**
	 * Get settings.
	 *
	 * @param array|null $settings Current settings.
	 *
	 * @return array
	 */
	private static function settings( $settings = null ) {
		return is_array( $settings ) ? $settings : Settings::current_settings();
	}
}
