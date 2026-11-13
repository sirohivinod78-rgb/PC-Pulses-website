<?php
/**
 * Feature-detection guard for WordPress 7.1's Icon API.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Prevents calls to Icon API functions on older WordPress versions.
 */
class Icon_Api_Compat {

	/**
	 * Checks whether the complete native WordPress Icon API is available.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'wp_register_icon_collection' )
			&& function_exists( 'wp_register_icon' )
			&& function_exists( 'wp_unregister_icon' )
			&& function_exists( 'wp_unregister_icon_collection' )
			&& function_exists( 'wp_get_icon' );
	}

	/** This is a static utility class. */
	private function __construct() {}
}
