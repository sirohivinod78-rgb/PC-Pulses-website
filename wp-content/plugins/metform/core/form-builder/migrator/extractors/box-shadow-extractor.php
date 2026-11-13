<?php
/**
 * Box shadow extractor for converting Elementor box shadow controls to Form Builder format.
 *
 * @package MetForm\Core\FormBuilder\Migrator\Extractors
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator\Extractors;

use MetForm\Core\FormBuilder\Migrator\Value_Unwrapper;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Extracts box shadow settings compatible with both Css_Value_Formatter and BoxShadowControl.
 *
 * @since 4.1.9
 */
class Box_Shadow_Extractor {
	use Singleton;

	/**
	 * Extracts box shadow from Elementor box_shadow_box_shadow setting as an array
	 * compatible with both Css_Value_Formatter and Form Builder BoxShadowControl.
	 *
	 * @param mixed $shadow Shadow settings map.
	 * @return array|null
	 */
	public function extract( $shadow ): ?array {
		if ( ! is_array( $shadow ) || empty( $shadow['color'] ) ) {
			return null;
		}

		return array(
			'horizontal' => (float) ( $shadow['horizontal'] ?? 0 ),
			'vertical'   => (float) ( $shadow['vertical'] ?? 0 ),
			'blur'       => (float) ( $shadow['blur'] ?? 0 ),
			'spread'     => (float) ( $shadow['spread'] ?? 0 ),
			'color'      => (string) Value_Unwrapper::unwrap( $shadow['color'] ),
			'position'   => ! empty( $shadow['position'] ) && 'inset' === $shadow['position'] ? 'inset' : 'outside',
		);
	}
}
