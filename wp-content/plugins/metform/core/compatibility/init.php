<?php
/**
 * Compatibility bootstrap.
 *
 * @package MetForm\Core\Compatibility
 */

namespace MetForm\Core\Compatibility;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Initialize MetForm compatibility modules.
 */
class Init {

	use Singleton;

	/**
	 * Initialize compatibility modules.
	 *
	 * @return void
	 */
	public function init() {
		Seo_Plugin::instance()->init();
	}
}
