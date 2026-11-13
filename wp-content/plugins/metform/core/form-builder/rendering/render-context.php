<?php
/**
 * Rendering context for Form Builder blocks.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder\Rendering;

defined( 'ABSPATH' ) || exit;

/**
 * Provides form, block, and editor state to PHP block renderers.
 *
 * @since 4.1.8
 */
class Render_Context {
	/**
	 * Current form ID.
	 *
	 * @since 4.1.8
	 *
	 * @var int
	 */
	private $form_id;

	/**
	 * Current block ID.
	 *
	 * @since 4.1.8
	 *
	 * @var string
	 */
	private $block_id;

	/**
	 * Whether the block is being rendered for the editor.
	 *
	 * @since 4.1.8
	 *
	 * @var bool
	 */
	private $editor;
	/** @var callable|null Renders this block's children; null for leaf blocks. */
	private $render_children_fn;
	/** @var array{type: string, props: array}|null This block's parent element, resolved (defaults merged with saved props) — null at the document's top level. */
	private $parent;
	/** @var array<int, array{id: string, type: string, props: array}> Immediate children, resolved — empty for leaf blocks. */
	private $children;
	/** @var callable|null Renders one specific child by id; null for leaf blocks. */
	private $render_child_fn;
	/** @var callable|null Resolves every element in the whole document, flat, regardless of nesting — see get_all_elements(). */
	private $resolve_all_fn;

	/**
	 * Creates a rendering context.
	 *
	 * @since 4.1.8
	 *
	 * @param int    $form_id  Current form ID.
	 * @param string $block_id Current block ID.
	 * @param bool   $editor   Optional. Whether this is an editor render. Default false.
	 */
	public function __construct( int $form_id, string $block_id, bool $editor = false, ?callable $render_children_fn = null, ?array $parent = null, array $children = array(), ?callable $render_child_fn = null, ?callable $resolve_all_fn = null ) {
		$this->form_id            = $form_id;
		$this->block_id           = $block_id;
		$this->editor             = $editor;
		$this->render_children_fn = $render_children_fn;
		$this->parent             = $parent;
		$this->children           = $children;
		$this->render_child_fn    = $render_child_fn;
		$this->resolve_all_fn     = $resolve_all_fn;
	}

	/**
	 * Gets the current form ID.
	 *
	 * @since 4.1.8
	 *
	 * @return int Form ID.
	 */
	public function get_form_id(): int {
		return $this->form_id; }

	/**
	 * Gets the current block ID.
	 *
	 * @since 4.1.8
	 *
	 * @return string Block ID.
	 */
	public function get_block_id(): string {
		return $this->block_id; }

	/**
	 * Determines whether this is an editor render.
	 *
	 * @since 4.1.8
	 *
	 * @return bool True for editor rendering, otherwise false.
	 */
	public function is_editor(): bool {
		return $this->editor; }

	/**
	 * Gets a form-scoped HTML element ID for the current block.
	 *
	 * @since 4.1.8
	 *
	 * @return string HTML element ID.
	 */
	public function get_element_id(): string {
		return 'mfb-' . $this->form_id . '-' . sanitize_html_class( $this->block_id );
	}

	/**
	 * Render this block's children (blocks whose parentId points at it).
	 * Only meaningful for blocks whose get_capabilities()['acceptsChildren']
	 * is true (Container, Multistep, ...); returns '' for leaf blocks.
	 */
	public function render_children(): string {
		return $this->render_children_fn ? (string) call_user_func( $this->render_children_fn ) : '';
	}

	/**
	 * This block's parent element (`{type, props}`, props already merged
	 * with that block's own defaults) — null at the document's top level.
	 * Mirrors the React editor's `elementById[self.parentId]` parent-walk
	 * pattern, for blocks that need to read a parent MultiStep's
	 * `steps`/`currentStep` this way — see Form_Renderer::render_fragment().
	 */
	public function get_parent(): ?array {
		return $this->parent;
	}

	/**
	 * Immediate children (`{id, type, props}`, unrendered), resolved but
	 * NOT rendered — for blocks that need to group/filter their children
	 * before rendering them (e.g. Multistep groups them by `stepIndex`), unlike
	 * `render_children()`'s flat "render everything
	 * in document order" for e.g. a block with no such grouping.
	 */
	public function get_children(): array {
		return $this->children;
	}

	/** Renders exactly one child by id (from get_children()) — the per-child counterpart to render_children(). */
	public function render_child( string $child_id ): string {
		return $this->render_child_fn ? (string) call_user_func( $this->render_child_fn, $child_id ) : '';
	}

	/**
	 * Every element in the whole document, resolved (`{id, type, props}`,
	 * props already merged with that block's defaults), in document order,
	 * regardless of nesting/parentId — unlike get_parent()/get_children()
	 * (tree-relative to this block), this is document-global. For blocks
	 * that need to read sibling field metadata anywhere in the tree, not
	 * just their immediate family — Summary (§5) is the first and, as of
	 * this writing, only consumer, since it lists every input-type field in
	 * the form regardless of where it's nested. Mirrors the React editor's
	 * `useAppStore(s => s.form.elements)` (a flat store, no tree walk
	 * needed there). Computed lazily via the closure Form_Renderer supplies
	 * — free for every block that never calls this.
	 */
	public function get_all_elements(): array {
		return $this->resolve_all_fn ? (array) call_user_func( $this->resolve_all_fn ) : array();
	}
}
