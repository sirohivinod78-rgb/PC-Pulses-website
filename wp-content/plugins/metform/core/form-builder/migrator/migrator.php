<?php
/**
 * Migration engine: converts Elementor template JSON trees into Standalone Form Builder documents.
 *
 * @package MetForm\Core\FormBuilder\Migrator
 * @since 4.1.9
 */

namespace MetForm\Core\FormBuilder\Migrator;

use MetForm\Core\FormBuilder\Document\Sanitizer as Document_Sanitizer;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Converts Elementor layout/widget JSON trees into native Standalone Form Builder documents.
 * Handles classic sections/columns and modern atomic containers (e-flexbox, e-grid),
 * recursively unwrapping dynamic Elementor $$type object wrappers and normalising all
 * 35+ Free and Pro MetForm widgets along with visual styles.
 *
 * @since 4.1.9
 */
class Migrator {
	use Singleton;

	/**
	 * Main entry point: migrates an Elementor element tree to a Form Builder document.
	 *
	 * @param array $elementor_tree The element tree extracted from remote template or _elementor_data.
	 * @return array Sanitized Form Builder document.
	 */
	public function migrate( array $elementor_tree ): array {
		// Handle potential { "content": [...] } wrapper from remote template
		if ( isset( $elementor_tree['content'] ) && is_array( $elementor_tree['content'] ) ) {
			$elementor_tree = $elementor_tree['content'];
		}

		$elements = Tree_Traverser::instance()->traverse( $elementor_tree );

		$raw_document = array(
			'version'  => '1.0.0',
			'elements' => $elements,
		);

		return Document_Sanitizer::instance()->sanitize( $raw_document );
	}
}
