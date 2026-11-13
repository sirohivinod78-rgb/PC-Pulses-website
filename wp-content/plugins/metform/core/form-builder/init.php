<?php
namespace MetForm\Core\FormBuilder;

use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\FormBuilder\Editor\Controller as Editor_Controller;
use MetForm\Core\FormBuilder\Editor\Hooks;
use MetForm\Core\FormBuilder\Editor\Preview;
use MetForm\Core\FormBuilder\Editor\Revision_Manager;
use MetForm\Core\FormBuilder\Icons\Boot as Icons_Boot;
use MetForm\Core\FormBuilder\Migrator\Conversion_Controller;
use MetForm\Core\FormBuilder\Rest\Controller as Rest_Controller;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/** Form Builder composition root. */
class Init {
	use Singleton;

	/** Boots every Form Builder feature class. */
	public function init(): void {
		Block_Registry::instance()->init();
		Editor_Controller::instance()->init();
		Rest_Controller::instance()->init();
		Preview::instance()->init();
		Revision_Manager::instance()->init();
		Hooks::instance()->init();
		Conversion_Controller::instance()->init();
		Style_Engine::instance()->init();
		Icons_Boot::instance()->init();
	}
}
