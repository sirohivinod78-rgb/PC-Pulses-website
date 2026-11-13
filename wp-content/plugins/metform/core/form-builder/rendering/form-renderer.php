<?php
/**
 * PHP renderer for Form Builder documents and blocks.
 *
 * @package MetForm\Core\FormBuilder
 */

namespace MetForm\Core\FormBuilder\Rendering;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Block_Registry;
use MetForm\Core\FormBuilder\Document\Repository;
use MetForm\Core\FormBuilder\Dynamic_Css\Style_Engine;
use MetForm\Core\Forms\Action as Form_Action;
use MetForm\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Converts saved Form Builder documents into escaped HTML markup.
 *
 * @since 4.1.8
 */
class Form_Renderer {
	use Singleton;

	/**
	 * Renders a complete form document.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $document Form Builder document.
	 * @param int                  $form_id  Optional. Form post ID. Default 0.
	 * @param bool                 $editor   Optional. Whether this is an editor render. Default false.
	 * @return string Complete rendered form markup.
	 */
	public function render_document( array $document, int $form_id = 0, bool $editor = false ): string {
		$content = $this->render_fragment( $document, $form_id, $editor );

		$instance_id = wp_unique_id( 'metform-wrap-' . $form_id . '-' );
		$action      = rest_url( 'metform/v1/entries/insert/' . $form_id );
		$wp_nonce    = wp_create_nonce( 'wp_rest' );
		$form_nonce  = wp_create_nonce( 'form_nonce' );

		$form_settings = $form_id ? Form_Action::instance()->get_all_data( $form_id ) : array();
		$form_type     = isset( $form_settings['form_type'] ) && ! empty( $form_settings['form_type'] ) ? $form_settings['form_type'] : 'general-form';
		$quiz_summary  = ! empty( $form_settings['quiz_summery'] );

		ob_start();
		?>
		<div
			id="<?php echo esc_attr( $instance_id ); ?>"
			class="mf-form-wrapper mfb-form <?php echo esc_attr( Style_Engine::get_form_class( $form_id ) ); ?>"
			data-metform-form="<?php echo esc_attr( $form_id ); ?>"
			data-form-id="<?php echo esc_attr( $form_id ); ?>"
			data-action="<?php echo esc_url( $action ); ?>"
			data-wp-nonce="<?php echo esc_attr( $wp_nonce ); ?>"
			data-form-nonce="<?php echo esc_attr( $form_nonce ); ?>"
			data-form-type="<?php echo esc_attr( $form_type ); ?>"
			data-quiz-summery="<?php echo $quiz_summary ? 'true' : 'false'; ?>"
			data-mf-renderer="php"
			data-mf-editor="<?php echo $editor ? 'true' : 'false'; ?>"
			<?php echo apply_filters( 'metform/form_builder/extra_wrapper_attributes', '', $form_id, $form_settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
		<form class="metform-form-content" method="post" action="<?php echo esc_url( $action ); ?>" enctype="multipart/form-data" novalidate>
			<input type="hidden" name="form_nonce" value="<?php echo esc_attr( $form_nonce ); ?>">
			<input type="hidden" name="id" value="<?php echo esc_attr( $form_id ); ?>">
			<input type="hidden" name="hidden-fields" value="[]">
			<div class="mf-form-response mfb-form__response" role="status" aria-live="polite"></div>
			<div class="metform-form-main-wrapper mfb-form__content">
				<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders document blocks without the outer form wrapper.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed> $document Form Builder document.
	 * @param int                  $form_id  Optional. Form post ID. Default 0.
	 * @param bool                 $editor   Optional. Whether this is an editor render. Default false.
	 * @return string Rendered block markup.
	 */
	public function render_fragment(
		array $document,
		int $form_id = 0,
		bool $editor = false,
		?string $parent_id = null
	): string {
		$parent  = null !== $parent_id ? $this->resolve_element( $document, $parent_id ) : null;
		$content = '';
		foreach ( $this->children_of( $document, $parent_id ) as $element ) {
			$content .= $this->render_element( $element, $document, $form_id, $editor, $parent );
		}
		return $content;
	}

	/** Finds an element by id and resolves its settings (defaults merged with saved props) — null if not found or its type isn't registered. */
	private function resolve_element( array $document, string $id ): ?array {
		foreach ( ( $document['elements'] ?? array() ) as $element ) {
			if ( ! is_array( $element ) || ( $element['id'] ?? null ) !== $id ) {
				continue;
			}
			$type  = sanitize_key( $element['type'] ?? '' );
			$block = Block_Registry::instance()->get( $type );
			if ( ! $block ) {
				return null;
			}
			return array(
				'type'  => $type,
				'props' => $this->merge_settings( $block->get_default_settings(), (array) ( $element['props'] ?? $element['settings'] ?? array() ) ),
			);
		}
		return null;
	}

	/** Elements whose parentId matches $parent_id (null selects top-level elements). */
	private function children_of( array $document, ?string $parent_id ): array {
		$matches = array();
		foreach ( ( $document['elements'] ?? array() ) as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$element_parent = isset( $element['parentId'] ) && '' !== $element['parentId']
				? (string) $element['parentId']
				: null;
			if ( $element_parent === $parent_id ) {
				$matches[] = $element;
			}
		}
		return $matches;
	}

	/**
	 * Renders one document element.
	 *
	 * @since 4.1.8
	 *
	 * @param array<string, mixed>      $element  Form Builder element.
	 * @param array<string, mixed>      $document Full document data.
	 * @param int                       $form_id  Form post ID.
	 * @param bool                      $editor   Whether this is an editor render.
	 * @param array<string, mixed>|null $parent   Parent block context.
	 *
	 * @return string Rendered element markup, or empty string when unsupported.
	 */
	private function render_element(
		array $element,
		array $document,
		int $form_id,
		bool $editor,
		?array $parent = null
	): string {
		$type  = sanitize_key( $element['type'] ?? '' );
		$block = Block_Registry::instance()->get( $type );

		if ( ! $block ) {
			return '';
		}

		$block_id = sanitize_key(
			$element['id'] ?? wp_unique_id( 'block-' )
		);

		$settings                = $this->merge_settings(
			$block->get_default_settings(),
			(array) ( $element['props'] ?? $element['settings'] ?? array() )
		);
		$settings['__className'] = Style_Engine::get_element_class( $block_id );

		$context = $this->create_render_context(
			$block,
			$type,
			$settings,
			$document,
			$form_id,
			$block_id,
			$editor,
			$parent
		);

		return $block->render( $settings, $context );
	}
	/**
	 * Creates the render context for a block.
	 *
	 * @param Block_Base                $block
	 * @param string                    $type
	 * @param array<string, mixed>      $settings
	 * @param array<string, mixed>      $document
	 * @param int                       $form_id
	 * @param string                    $block_id
	 * @param bool                      $editor
	 * @param array<string, mixed>|null $parent
	 *
	 * @return Render_Context
	 */
	private function create_render_context(
		Block_Base $block,
		string $type,
		array $settings,
		array $document,
		int $form_id,
		string $block_id,
		bool $editor,
		?array $parent
	): Render_Context {
		$accepts_children = ! empty(
			$block->get_capabilities()['acceptsChildren']
		);

		if ( ! $accepts_children ) {
			return new Render_Context(
				$form_id,
				$block_id,
				$editor,
				null,
				$parent,
				array(),
				null,
				fn() => $this->resolve_all( $document )
			);
		}

		$self_as_parent = array(
			'type'  => $type,
			'props' => $settings,
		);

		return new Render_Context(
			$form_id,
			$block_id,
			$editor,
			fn() => $this->render_fragment(
				$document,
				$form_id,
				$editor,
				$block_id
			),
			$parent,
			$this->resolve_children(
				$document,
				$block_id
			),
			fn( string $child_id ) => $this->render_child(
				$child_id,
				$document,
				$form_id,
				$editor,
				$self_as_parent
			),
			fn() => $this->resolve_all( $document )
		);
	}

	/**
	 * Renders a child element by ID.
	 *
	 * @param string               $child_id
	 * @param array<string, mixed> $document
	 * @param int                  $form_id
	 * @param bool                 $editor
	 * @param array<string, mixed> $parent
	 *
	 * @return string
	 */
	private function render_child(
		string $child_id,
		array $document,
		int $form_id,
		bool $editor,
		array $parent
	): string {
		foreach ( $document['elements'] ?? array() as $element ) {
			if (
				is_array( $element ) &&
				( $element['id'] ?? '' ) === $child_id
			) {
				return $this->render_element(
					$element,
					$document,
					$form_id,
					$editor,
					$parent
				);
			}
		}

		return '';
	}

	/** Resolves every child of $parent_id ({id, type, props}) — for blocks that group/filter children before rendering them (see Render_Context::get_children()). */
	private function resolve_children( array $document, string $parent_id ): array {
		$out = array();
		foreach ( $this->children_of( $document, $parent_id ) as $element ) {
			$type  = sanitize_key( $element['type'] ?? '' );
			$block = Block_Registry::instance()->get( $type );
			if ( ! $block ) {
				continue;
			}
			$out[] = array(
				'id'    => (string) ( $element['id'] ?? '' ),
				'type'  => $type,
				'props' => $this->merge_settings( $block->get_default_settings(), (array) ( $element['props'] ?? $element['settings'] ?? array() ) ),
			);
		}
		return $out;
	}

	/**
	 * Resolves every element in the whole document ({id, type, props}), in
	 * document order, regardless of nesting/parentId. Same shape as
	 * resolve_children() but document-global instead of one parent's
	 * immediate children — see Render_Context::get_all_elements().
	 */
	private function resolve_all( array $document ): array {
		$out = array();
		foreach ( ( $document['elements'] ?? array() ) as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			$type  = sanitize_key( $element['type'] ?? '' );
			$block = Block_Registry::instance()->get( $type );
			if ( ! $block ) {
				continue;
			}
			$out[] = array(
				'id'    => (string) ( $element['id'] ?? '' ),
				'type'  => $type,
				'props' => $this->merge_settings( $block->get_default_settings(), (array) ( $element['props'] ?? $element['settings'] ?? array() ) ),
			);
		}
		return $out;
	}

	/**
	 * Merges a saved element's props onto its block's defaults.
	 *
	 * `array_replace_recursive()` (the obvious first choice, used here
	 * until this was found) merges numerically-indexed "list" sub-arrays
	 * **index-by-index**, not wholesale — a saved `options: [A, B]` on top
	 * of a 3-item default `[Option 1, Option 2, Option 3]` silently keeps
	 * "Option 3" bleeding through as a leftover 3rd item nobody configured.
	 * Every Wave 2 block's `options` repeater (and Colorpicker's
	 * `presetColors`) hits this whenever a saved list is shorter than the
	 * block's default. Recurse into associative arrays (so a partial
	 * `styles` override still inherits untouched style keys from the
	 * default) but replace list arrays wholesale instead.
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
	 * Renders the saved Form Builder document for a form.
	 *
	 * @since 4.1.8
	 *
	 * @param int $form_id Form post ID.
	 * @return string Rendered form markup, or an empty string when unavailable.
	 */
	public function render_saved_form( int $form_id ): string {
		$document = Repository::get( $form_id );
		if ( ! is_array( $document ) ) {
			return '';
		}

		Style_Engine::instance()->enqueue_or_create_form_css( $form_id, $document );

		return $this->render_document( $document, $form_id, false );
	}
}
