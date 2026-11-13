<?php
namespace MetForm\Core\FormBuilder\Advanced;

defined( 'ABSPATH' ) || exit;

/**
 * Advanced-tab support for Block_Base.
 *
 * Blocks never declare the Advanced tab themselves: get_controls() injects
 * the shared schema (Controls) into whatever register_controls() returns.
 * A block only customises it through get_advanced_exclude() (groups its
 * Style tab already covers).
 *
 * Advanced CSS always targets the block root: the element carrying the
 * block class (`metform-element-<id>`), which every block renders on its
 * outermost element. Style controls target elements inside that root.
 *
 * @since 4.2.0
 */
trait Has_Advanced {

	/**
	 * Advanced controls per block class.
	 *
	 * @var array<string, array>
	 */
	private static $advanced_controls_cache = array();

	/**
	 * Advanced groups this block leaves out (see Controls::GROUPS).
	 *
	 * @return string[]
	 */
	protected function get_advanced_exclude(): array {
		return array();
	}

	/**
	 * The shared Advanced controls for this block (built once per class).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_advanced_controls(): array {
		$class = static::class;
		if ( ! isset( self::$advanced_controls_cache[ $class ] ) ) {
			self::$advanced_controls_cache[ $class ] = Controls::get( $this->get_advanced_exclude(), $this->get_name() );
		}

		return self::$advanced_controls_cache[ $class ];
	}

	/**
	 * register_controls() with the Advanced tab filled in. A block-provided
	 * `advance` array is kept as is; nothing, an empty array or the legacy
	 * `'rich'` marker becomes the shared schema.
	 *
	 * @return array<string, mixed> Controls keyed by tab.
	 */
	public function get_controls(): array {
		$controls = $this->register_controls();
		$advance  = $controls['advance'] ?? null;

		if ( ! is_array( $advance ) || ! $advance ) {
			$controls['advance'] = $this->get_advanced_controls();
		}

		return $controls;
	}

	/**
	 * Advanced CSS for one instance. Separate from build_dynamic_css() so
	 * blocks overriding that method (Container) still get it.
	 *
	 * @param array<string, mixed> $settings   Merged block settings.
	 * @param string               $class_name Scoped selector (ends with the element class).
	 * @return string Raw CSS.
	 */
	public function build_advanced_css( array $settings, string $class_name ): string {
		return Css_Builder::build( $this->get_controls()['advance'], $settings, $class_name );
	}
}
