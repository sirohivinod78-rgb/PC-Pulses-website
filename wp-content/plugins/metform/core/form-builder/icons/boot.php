<?php
/**
 * Boots the Form Builder icon subsystem.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 4.4.0
 */

namespace MetForm\Core\FormBuilder\Icons;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Single composition root for icon-related hooks and REST routes.
 *
 * Form Builder's main controller only knows about this class. Individual
 * icon services remain private implementation details of this subsystem.
 *
 * @since 4.4.0
 */
class Boot {
	use Singleton;

	/**
	 * Registers every icon service.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public function init(): void {
		Svg_Upload_Support::instance()->init();
		Icon_Registry::instance()->init();
		Custom_Icon_Upload::instance()->init();
		Icon_Library_Rest::instance()->init();
		Icon_Rest_Cache::instance()->init();
	}
}
