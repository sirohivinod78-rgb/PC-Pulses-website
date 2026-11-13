<?php
/**
 * Form-builder style engine orchestrator.
 *
 * @package MetForm
 * @subpackage FormBuilder
 * @since 1.0.0
 */

namespace MetForm\Core\FormBuilder\Dynamic_Css;

use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a saved form document into plain CSS by walking its elements and
 * asking each block to build its own dynamic CSS (via
 * Css_Value_Formatter::build_css(), typically called from inside the
 * block's own `build_dynamic_css()`).
 *
 * This is the canonical CSS generator for both delivery paths. The editor
 * canvas receives this output through REST and mounts it temporarily; saving
 * writes the same output to the form-specific frontend stylesheet.
 *
 * File caching (write/enqueue/invalidate) is delegated to
 * Css_File_Cache; per-value CSS formatting is delegated to
 * Css_Value_Formatter. This class only orchestrates: walk the document,
 * merge settings, hand off.
 *
 * Trust boundary: values reaching here have already passed through
 * Document_Sanitizer (`map_deep( 'sanitize_text_field' )` for the whole
 * `styles` map) before this class ever sees it — same trust model as
 * every other block setting: only users with the `edit_post` capability
 * on the form can set them. Css_Value_Formatter re-escapes for the CSS
 * context specifically; see its class docblock.
 *
 * @since 1.0.0
 */
final class Style_Engine {
	use Singleton;

	/** Registers generated stylesheet lifecycle hooks. */
	public function init(): void {
		add_action( 'before_delete_post', array( $this, 'delete_form_css' ) );
	}

	/**
	 * Form scope used instead of `.metform-{id}` for editor-rendered CSS
	 * (inserted after the leading `.` that `{{WRAPPER}}` adds).
	 *
	 * The editor bundle re-uses the frontend block styles, but
	 * postcss-prefix-selector prefixes every rule with
	 * `#metform-builder-root`. That lifts base rules such as
	 * `input.mfb-field__input { background-color: #fff !important; }` to ID
	 * specificity, so they beat the generated `!important` control CSS
	 * (class specificity) and e.g. an input's Background / Border Color
	 * never shows on the canvas even though the frontend is fine. Both the
	 * canvas and the preview body carry `metform-builder-canvas` and
	 * contain `#metform-builder-root`, so scoping through both outranks any
	 * prefixed base rule while leaving frontend CSS untouched.
	 *
	 * @since 4.1.8
	 * @var string
	 */
	const EDITOR_SCOPE = 'metform-builder-canvas #metform-builder-root';

	/**
	 * Builds the complete dynamic stylesheet for a saved form document.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $form_id  The form's post ID.
	 * @param array $document The form's saved document array, expected to
	 *                         contain an 'elements' list of block entries
	 *                         with 'type', 'id', and 'props' (or legacy
	 *                         'settings') keys.
	 * @param bool  $editor   Whether the CSS is for the builder canvas or
	 *                         preview rather than the frontend. See
	 *                         EDITOR_SCOPE.
	 * @param bool  $canvas   Builder-canvas render (implies $editor):
	 *                         placement rules (`{{LAYOUT_WRAPPER}}`) target
	 *                         the canvas's DraggableBlock (`mfb-layout-<id>`)
	 *                         instead of the block root.
	 * @return string The concatenated CSS for every recognized element in
	 *                 the document.
	 */
	public function build_document_css( int $form_id, array $document, bool $editor = false, bool $canvas = false ): string {
		$css        = '';
		$editor     = $editor || $canvas;
		$form_class = $editor ? self::EDITOR_SCOPE : self::get_form_class( $form_id );

		foreach ( (array) ( $document['elements'] ?? array() ) as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$block = Block_Registry::instance()->get( sanitize_key( $element['type'] ?? '' ) );
			if ( ! $block ) {
				continue;
			}

			$element_class = self::get_element_class( (string) ( $element['id'] ?? '' ) );
			if ( '' === $element_class ) {
				continue;
			}

			$props                = $this->merge_settings( $block->get_default_settings(), (array) ( $element['props'] ?? $element['settings'] ?? array() ) );
			$props['__className'] = $element_class;
			// Lets a block skip page-only rules on the builder canvas, which
			// renders some blocks' markup itself (e.g. Multistep).
			$props['__isCanvas'] = $canvas;
			// {{LAYOUT_WRAPPER}}: the canvas lays out the DraggableBlock; the
			// preview (no editor wrappers) the root itself. Unset for the
			// saved file, which resolves it with a canvas guard instead.
			if ( $canvas ) {
				$props['__layoutSelector'] = $form_class . ' .' . self::get_layout_class( (string) ( $element['id'] ?? '' ) );
			} elseif ( $editor ) {
				$props['__layoutSelector'] = $form_class . ' .' . $element_class;
			}
			$scoped_element_selector = $form_class . ' .' . $element_class;
			$css                    .= $block->build_dynamic_css( $props, $scoped_element_selector );
			// Advanced tab — separate from build_dynamic_css() so blocks that
			// override that method (Container, add-on blocks) still get it.
			$css .= $block->build_advanced_css( $props, $scoped_element_selector );
			// Per-breakpoint values of markup-class settings (Label Position,
			// option layout, Button alignment, …).
			$css .= $block->build_responsive_markup_css( $props, $scoped_element_selector );
		}

		return $css;
	}

	/**
	 * Returns the stable form-level CSS class used to scope every
	 * element's generated selectors to a single form instance.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return string The form's CSS class, e.g. 'metform-123'.
	 */
	public static function get_form_class( int $form_id ): string {
		return 'metform-' . absint( $form_id );
	}

	/**
	 * Returns the stable CSS class for a document element ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $element_id The element's ID as stored in the document.
	 * @return string The element's CSS class (e.g. 'metform-element-abc123'),
	 *                or '' when $element_id sanitizes to an empty string.
	 */
	public static function get_element_class( string $element_id ): string {
		$element_id = sanitize_key( $element_id );
		return '' !== $element_id ? 'metform-element-' . $element_id : '';
	}

	/**
	 * Returns the class of a block's layout wrapper in the editor canvas
	 * (DraggableBlock.jsx), the box the canvas lays out. Keep in sync with
	 * the JS side.
	 *
	 * @since 4.2.0
	 *
	 * @param string $element_id The element's ID as stored in the document.
	 * @return string The layout class (e.g. 'mfb-layout-abc123'), or ''.
	 */
	public static function get_layout_class( string $element_id ): string {
		$element_id = sanitize_key( $element_id );
		return '' !== $element_id ? 'mfb-layout-' . $element_id : '';
	}

	/**
	 * Recursively merges a block's saved props over its default settings.
	 *
	 * List values (sequential arrays) are replaced wholesale rather than
	 * merged index-by-index; associative arrays are merged key-by-key.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @param array $defaults The block's default settings, as returned by
	 *                         `get_default_settings()`.
	 * @param array $props    The element's saved props/settings to merge
	 *                         over the defaults.
	 * @return array The merged settings array.
	 */
	private function merge_settings( array $defaults, array $props ): array {
		foreach ( $props as $key => $value ) {
			if ( is_array( $value ) && isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) && ! array_is_list( $value ) ) {
				$defaults[ $key ] = $this->merge_settings( $defaults[ $key ], $value );
			} else {
				$defaults[ $key ] = $value;
			}
		}
		return $defaults;
	}

	/**
	 * Removes a generated form stylesheet after its form is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return void
	 */
	public function delete_form_css( int $form_id ): void {
		Css_File_Cache::instance()->delete( $form_id );
	}

	/**
	 * Generates a form's dynamic CSS and writes it to its cache file.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $form_id  The form's post ID.
	 * @param array $document The form's saved document array.
	 * @return bool True on success, false if the CSS could not be written
	 *              to disk.
	 */
	public function save_form_css( int $form_id, array $document ): bool {
		return Css_File_Cache::instance()->write( $form_id, $this->build_document_css( $form_id, $document ) );
	}

	/**
	 * Enqueues a previously generated form CSS file, if one exists.
	 *
	 * @since 1.0.0
	 *
	 * @param int $form_id The form's post ID.
	 * @return bool True if the stylesheet was enqueued, false if no cache
	 *              file exists for this form.
	 */
	public function enqueue_form_css( int $form_id ): bool {
		return Css_File_Cache::instance()->enqueue( $form_id );
	}

	/**
	 * Enqueues the form stylesheet, generating and caching it first when
	 * it is missing or stale.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $form_id  The form's post ID.
	 * @param array $document The form's saved document array, used to
	 *                         regenerate the CSS when the cache is
	 *                         missing or out of date.
	 * @return bool True if a stylesheet (existing or freshly generated)
	 *              was enqueued, false otherwise.
	 */
	public function enqueue_or_create_form_css( int $form_id, array $document ): bool {
		$cache = Css_File_Cache::instance();

		if ( $cache->is_current( $form_id ) && $cache->enqueue( $form_id ) ) {
			return true;
		}

		return $this->save_form_css( $form_id, $document )
			&& $cache->enqueue( $form_id );
	}
}
