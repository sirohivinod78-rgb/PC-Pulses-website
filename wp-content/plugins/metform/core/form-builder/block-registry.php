<?php
/**
 * Registry for PHP Form Builder blocks.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder;

use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Stores registered block instances and exposes their public definitions.
 *
 * @since 4.1.8
 */
class Block_Registry {
	use Singleton;

	/** Registers all core and filtered Form Builder blocks. */
	public function init(): void {
		foreach ( Block_Manifest::get() as $block ) {
			$this->register( $block );
		}
	}

	/**
	 * Registered blocks keyed by block type.
	 *
	 * @since 4.1.8
	 *
	 * @var array<string, Block_Base>
	 */
	private $blocks = array();

	/**
	 * Registers a block instance.
	 *
	 * @since 4.1.8
	 *
	 * @param Block_Base $block Block instance to register.
	 * @return void
	 */
	public function register( Block_Base $block ): void {
		$this->blocks[ $block->get_name() ] = $block;
	}

	/**
	 * Gets a registered block by type.
	 *
	 * @since 4.1.8
	 *
	 * @param string $name Block type.
	 * @return Block_Base|null Registered block, or null when unavailable.
	 */
	public function get( string $name ) {
		return $this->blocks[ $name ] ?? null;
	}

	/**
	 * Gets all registered blocks.
	 *
	 * @since 4.1.9
	 *
	 * @return array<string, Block_Base> All registered blocks, keyed by type.
	 */
	public function all(): array {
		return $this->blocks;
	}

	/** Returns unique registered block instances used by a document. */
	public function used_in_document( array $document ): array {
		$used = array();
		foreach ( (array) ( $document['elements'] ?? array() ) as $element ) {
			$type = is_array( $element ) ? sanitize_key( $element['type'] ?? '' ) : '';
			if ( $type && isset( $this->blocks[ $type ] ) ) {
				$used[ $type ] = $this->blocks[ $type ];
			}
		}
		return $used;
	}

	/**
	 * Gets definitions safe to expose to the editor.
	 *
	 * @since 4.1.8
	 *
	 * @return array<string, array<string, mixed>> Public block definitions.
	 */
	public function get_public_definitions(): array {
		$definitions = array();
		foreach ( $this->blocks as $name => $block ) {
			$definitions[ $name ] = $block->get_public_definition();
		}
		return $definitions;
	}
}
