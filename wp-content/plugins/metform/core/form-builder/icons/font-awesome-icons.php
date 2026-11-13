<?php
/**
 * Registers Font Awesome with WordPress's native Icon API.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.0
 */

namespace MetForm\Core\FormBuilder\Icons;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the bundled Font Awesome library as a native icon collection.
 */
class Font_Awesome_Icons {

	/**
	 * Native WordPress icon collection slug.
	 *
	 * @var string
	 */
	public const COLLECTION = 'metform-font-awesome';

	/**
	 * Registers the collection and every bundled Font Awesome icon.
	 *
	 * The existing `fas-*`, `far-*`, and `fab-*` keys are used as icon names,
	 * so saved Form Builder values remain stable across the API migration.
	 *
	 * @return void
	 */
	public static function register_all(): void {
		if ( ! Icon_Api_Compat::is_available() ) {
			return;
		}

		wp_register_icon_collection(
			self::COLLECTION,
			array(
				'label'       => __( 'Font Awesome', 'metform' ),
				'description' => __( 'Font Awesome icons bundled with MetForm.', 'metform' ),
			)
		);

		foreach ( Icon_Library::get_library_icons() as $key => $icon ) {
			$content = Icon_Library::render( $key );
			if ( '' === $content ) {
				continue;
			}

			wp_register_icon(
				self::qualified_name( $key ),
				array(
					'label'   => (string) $icon['label'],
					'content' => $content,
				)
			);
		}
	}

	/**
	 * Converts a stored Font Awesome key to its native qualified icon name.
	 *
	 * @param string $key Stored icon key, such as `fas-star`.
	 * @return string
	 */
	public static function qualified_name( string $key ): string {
		return self::COLLECTION . '/' . sanitize_key( $key );
	}
}
