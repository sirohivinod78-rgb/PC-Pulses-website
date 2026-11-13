<?php
/**
 * Registers MetForm's icon collections with WordPress's Icon API.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.3.0
 */

namespace MetForm\Core\FormBuilder\Icons;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Font Awesome and `metform-custom` icon collections, then
 * every icon belonging to them. Core's own `core` collection (WordPress's
 * built-in icon set) and its icons are already registered by
 * `_wp_register_default_icon_collections()` / `_wp_register_default_icons()`
 * on `init` at priority `0`; this class runs at the default priority so it
 * registers after core, without needing to depend on it.
 * On older WordPress versions, registration is skipped and bundled icons
 * continue rendering directly from MetForm's JSON library.
 *
 * @since 4.3.0
 * @since 4.4.0 Registers bundled Font Awesome icons with the native API.
 */
class Icon_Registry {
	use Singleton;

	/**
	 * Registers the icon collection hook.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'init', array( $this, 'register_collections_and_icons' ) );
	}

	/**
	 * Registers MetForm's icon collections and their icons.
	 *
	 * @since 4.3.0
	 *
	 * @return void
	 */
	public function register_collections_and_icons(): void {
		if ( ! Icon_Api_Compat::is_available() ) {
			return;
		}

		Font_Awesome_Icons::register_all();

		wp_register_icon_collection(
			'metform-custom',
			array(
				'label'       => __( 'Custom Icons', 'metform' ),
				'description' => __( 'Custom SVG icons uploaded by the user.', 'metform' ),
			)
		);

		Custom_Icons::register_all();
	}
}
