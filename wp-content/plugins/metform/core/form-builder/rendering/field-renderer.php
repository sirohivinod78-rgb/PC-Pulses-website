<?php
namespace MetForm\Core\FormBuilder\Rendering;

use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;

defined( 'ABSPATH' ) || exit;

/**
 * Shared field markup — the exact DOM shape every React field block
 * (src/form-builder/blocks/{email,textarea,telephone,...}/index.jsx) already
 * renders, reproduced here 1:1 so the shared base stylesheets that provide
 * default field appearance actually match:
 *
 *   .mfb-field__wrapper{className}[.mfb-field--label-left]
 *     .mfb-field__row
 *       label.mfb-field__label.mfb-label[for]
 *         {label text}
 *         span.mfb-field__required  (only when required)
 *       .mfb-field__control
 *         {control_html}
 *         p.mfb-field__help  (only when helpText set)
 *
 * These class names are NOT cosmetic choices — two separate stylesheets key
 * off them: `src/form-builder/blocks/shared/fieldBlock.css` +
 * `src/form-builder/ui/form-fields/form-fields.css` (loaded in the editor,
 * since every block's index.jsx still imports them even once server-rendered
 * — see PHP_RENDERING_MIGRATION_PLAN.md §0) and their ported equivalent now
 * in `src/frontend/form-builder/style.scss` (loaded on the public site).
 * Using different class names here silently drops ALL of that base styling
 * — border/padding/font/focus-ring on every field — leaving native
 * browser-default-looking inputs even though nothing is "broken" per se.
 * This is the bug reported and fixed in this pass; see the Progress log.
 */
final class Field_Renderer {
	/**
	 * Orchestrates rendering the standard field envelope around control HTML.
	 *
	 * Composes the atomic units (label, control container, row, wrapper shell,
	 * classes, and data attributes) into the full field DOM tree.
	 *
	 * @param array          $settings     The block's resolved settings.
	 * @param Render_Context $context      Rendering context.
	 * @param string         $block_type   Block type name (e.g. 'text', 'email').
	 * @param string         $control_html Inner control markup.
	 * @param array          $opts         Optional customization parameters.
	 * @return string Full field HTML.
	 */
	public static function wrap( array $settings, Render_Context $context, string $block_type, string $control_html, array $opts = array() ): string {
		$opts['label_for'] = $opts['label_for'] ?? $context->get_element_id();

		$label_html        = self::render_label( $settings, $opts );
		$control_container = self::render_control_container( $control_html, $settings, $opts );
		$row_html          = self::render_row( $label_html, $control_container );

		$wrapper_classes = self::build_wrapper_classes( $settings, $block_type, $opts );
		$data_attrs      = self::build_wrapper_attributes( $settings, $context, $block_type );

		return self::render_shell( $row_html, $wrapper_classes, $data_attrs );
	}

	/**
	 * Renders the field label element including required badge and extras.
	 *
	 * @param array $settings Block settings.
	 * @param array $opts     Wrap options (e.g. 'label_for', 'label_tag', 'label_extra', 'label_row_class').
	 * @return string Label HTML, or empty string if label should not be displayed.
	 */
	public static function render_label( array $settings, array $opts = array() ): string {
		$show_label = (bool) ( $settings['showLabel'] ?? true );
		if ( ! $show_label || empty( $settings['label'] ) ) {
			return '';
		}

		$label_tag      = isset( $opts['label_tag'] ) && in_array( $opts['label_tag'], array( 'span', 'div', 'label' ), true ) ? $opts['label_tag'] : 'label';
		$label_for      = $opts['label_for'] ?? '';
		$label_for_attr = ( 'label' === $label_tag && ! empty( $label_for ) ) ? ' for="' . esc_attr( $label_for ) . '"' : '';
		$required       = ! empty( $settings['required'] );
		$label_extra    = ! empty( $opts['label_extra'] ) ? (string) $opts['label_extra'] : '';
		$row_class      = ! empty( $opts['label_row_class'] ) ? (string) $opts['label_row_class'] : '';

		$badge_html = $required ? self::render_required_badge() : '';
		$tag_esc    = esc_attr( $label_tag );

		$label_markup = '<' . $tag_esc . ' class="mfb-field__label mfb-label"' . $label_for_attr . '>'
			. esc_html( $settings['label'] )
			. $badge_html
			. '</' . $tag_esc . '>';

		if ( '' !== $label_extra ) {
			$label_markup .= $label_extra;
		}

		if ( '' !== $row_class ) {
			$label_markup = '<div class="' . esc_attr( $row_class ) . '">' . $label_markup . '</div>';
		}

		return $label_markup;
	}

	/**
	 * Renders the required field asterisk indicator badge.
	 *
	 * @return string Required asterisk HTML.
	 */
	public static function render_required_badge(): string {
		return '<span class="mfb-field__required">*</span>';
	}

	/**
	 * Renders the field help / description text.
	 *
	 * @param array $settings Block settings.
	 * @return string Help paragraph HTML, or empty string if not set.
	 */
	public static function render_help_text( array $settings ): string {
		if ( empty( $settings['helpText'] ) ) {
			return '';
		}

		return '<p class="mfb-field__help">' . esc_html( $settings['helpText'] ) . '</p>';
	}

	/**
	 * Renders the placeholder node for client-side field validation error messages.
	 *
	 * @return string Error container HTML.
	 */
	public static function render_error_placeholder(): string {
		return '<div class="mfb-field__error" style="display:none;" role="alert" aria-live="polite"></div>';
	}

	/**
	 * Wraps the control HTML with accessories (after-control content, help text, error node).
	 *
	 * @param string $control_html The core control input markup.
	 * @param array  $settings     Block settings.
	 * @param array  $opts         Wrap options (e.g. 'after_control').
	 * @return string Control container HTML.
	 */
	public static function render_control_container( string $control_html, array $settings, array $opts = array() ): string {
		$after_control = ! empty( $opts['after_control'] ) ? (string) $opts['after_control'] : '';
		$help_html     = self::render_help_text( $settings );
		$error_html    = self::render_error_placeholder();

		return '<div class="mfb-field__control">'
			. $control_html
			. $after_control
			. $help_html
			. $error_html
			. '</div>';
	}

	/**
	 * Wraps label and control elements in the standard row container.
	 *
	 * @param string $label_html             Rendered label markup.
	 * @param string $control_container_html Rendered control container markup.
	 * @return string Row container HTML.
	 */
	public static function render_row( string $label_html, string $control_container_html ): string {
		return '<div class="mfb-field__row">' . $label_html . $control_container_html . '</div>';
	}

	/**
	 * Renders the outermost wrapper element with classes and data attributes.
	 *
	 * @param string $inner_html       Inner row/content markup.
	 * @param array  $wrapper_classes Array of CSS class names.
	 * @param array  $data_attrs      Array of serialized data attribute strings.
	 * @return string Outer shell HTML.
	 */
	public static function render_shell( string $inner_html, array $wrapper_classes, array $data_attrs ): string {
		$filtered_classes = array_values( array_filter( $wrapper_classes ) );
		$class_attr       = ! empty( $filtered_classes ) ? ' class="' . esc_attr( implode( ' ', $filtered_classes ) ) . '"' : '';
		$filtered_attrs   = array_values( array_filter( $data_attrs ) );
		$attrs_str        = ! empty( $filtered_attrs ) ? ' ' . implode( ' ', $filtered_attrs ) : '';

		return '<div' . $class_attr . $attrs_str . '>' . $inner_html . '</div>';
	}

	/**
	 * Resolves all CSS wrapper classes for a block.
	 *
	 * @param array  $settings   Block settings.
	 * @param string $block_type Block type name.
	 * @param array  $opts       Wrap options (e.g. 'style_class', 'extra_classes').
	 * @return array List of CSS class strings.
	 */
	public static function build_wrapper_classes( array $settings, string $block_type, array $opts = array() ): array {
		$required = ! empty( $settings['required'] );
		// Desktop value; per-breakpoint values are applied by
		// Block_Base::build_responsive_markup_css().
		$is_left_label = 'left' === Responsive_Value::resolve( $settings['labelPosition'] ?? 'top', 'desktop', 'top' );
		// Fall back to the element class when a block's get_wrap_options()
		// override forgets `style_class` — without it no generated
		// `{{WRAPPER}}` rule (Style or Advanced) can match this wrapper.
		$style_class   = (string) ( $opts['style_class'] ?? '' ) ?: sanitize_html_class( (string) ( $settings['__className'] ?? '' ) );
		$extra_classes = (array) ( $opts['extra_classes'] ?? array() );

		return array_values(
			array_filter(
				array_merge(
					array(
						'mfb-field__wrapper',
						'mfb-field__' . sanitize_html_class( $block_type ),
						$style_class,
						$required ? 'mfb-required' : '',
						$is_left_label ? 'mfb-field--label-left' : '',
					),
					$extra_classes
				)
			)
		);
	}

	/**
	 * Resolves all client-side data attributes for a field block.
	 *
	 * @param array          $settings   Block settings.
	 * @param Render_Context $context    Rendering context.
	 * @param string         $block_type Block type name.
	 * @return array List of formatted data attribute strings.
	 */
	public static function build_wrapper_attributes( array $settings, Render_Context $context, string $block_type ): array {
		$required     = ! empty( $settings['required'] );
		$required_msg = (string) ( $settings['requiredMessage'] ?? '' );
		$conditional  = is_array( $settings['conditional'] ?? null ) ? $settings['conditional'] : array();

		return array_values(
			array_filter(
				array_merge(
					array(
						'data-mf-block-id="' . esc_attr( $context->get_block_id() ) . '"',
						'data-mf-field-type="' . esc_attr( $block_type ) . '"',
					),
					self::build_required_attrs( $required, $required_msg ),
					self::build_validation_attrs( $settings, $block_type ),
					self::build_conditional_attrs( $conditional, $context->is_editor() ),
					self::build_quiz_attrs( $settings )
				)
			)
		);
	}

	/**
	 * Build HTML attributes for required field rules.
	 *
	 * @param bool   $required
	 * @param string $required_msg
	 * @return array
	 */
	public static function build_required_attrs( bool $required, string $required_msg ): array {
		if ( ! $required ) {
			return array();
		}

		$attrs = array( 'data-mf-required="true"' );
		if ( $required_msg !== '' ) {
			$attrs[] = 'data-mf-required-msg="' . esc_attr( $required_msg ) . '"';
		}

		return $attrs;
	}

	/**
	 * Build HTML attributes for validation settings.
	 *
	 * @param array  $settings
	 * @param string $block_type
	 * @return array
	 */
	public static function build_validation_attrs( array $settings, string $block_type ): array {
		$validation = is_array( $settings['validation'] ?? null ) ? $settings['validation'] : array();

		if ( 'number' === $block_type && ( ! isset( $validation['type'] ) || 'none' === $validation['type'] ) ) {
			if ( isset( $settings['min'] ) && '' !== (string) $settings['min'] ) {
				$validation['min'] = $settings['min'];
			}
			if ( isset( $settings['max'] ) && '' !== (string) $settings['max'] ) {
				$validation['max'] = $settings['max'];
			}
			if ( isset( $validation['min'] ) || isset( $validation['max'] ) ) {
				$validation['type'] = 'by_range';
			}
		}

		if ( ! empty( $validation['type'] ) && 'none' !== $validation['type'] ) {
			return array( 'data-mf-validation="' . esc_attr( wp_json_encode( $validation ) ) . '"' );
		}

		return array();
	}

	/**
	 * Build HTML attributes for conditional logic rules.
	 *
	 * @param array $conditional
	 * @param bool  $is_editor
	 * @return array
	 */
	public static function build_conditional_attrs( array $conditional, bool $is_editor ): array {
		$has_conditional = ! empty( $conditional['enabled'] ) && ! empty( $conditional['conditions'] );
		if ( ! $has_conditional ) {
			return array();
		}

		$attrs = array( 'data-mf-conditional="' . esc_attr( wp_json_encode( $conditional ) ) . '"' );

		$initially_hidden = ! $is_editor && ( 'show' === ( $conditional['action'] ?? 'show' ) );
		if ( $initially_hidden ) {
			$attrs[] = 'data-mf-condition-hidden="true"';
			$attrs[] = 'style="display:none;"';
			$attrs[] = 'hidden';
		}

		return $attrs;
	}

	/**
	 * Build HTML attributes for quiz settings.
	 *
	 * @param array $settings
	 * @return array
	 */
	public static function build_quiz_attrs( array $settings ): array {
		$quiz_point      = isset( $settings['quizPoint'] ) && is_numeric( $settings['quizPoint'] ) ? (float) $settings['quizPoint'] : 0.0;
		$quiz_neg_point  = isset( $settings['quizNegativePoint'] ) && is_numeric( $settings['quizNegativePoint'] ) ? (float) $settings['quizNegativePoint'] : 0.0;
		$quiz_has_answer = ! empty( $settings['quizAddAnswer'] );
		$quiz_answer     = $quiz_has_answer && isset( $settings['quizAnswer'] ) ? (string) $settings['quizAnswer'] : '';

		if ( $quiz_point <= 0 && $quiz_neg_point <= 0 && ! $quiz_has_answer ) {
			return array();
		}

		$attrs = array( 'data-quiz-point="' . esc_attr( (string) $quiz_point ) . '"' );
		if ( $quiz_neg_point > 0 ) {
			$attrs[] = 'data-quiz-negative-point="' . esc_attr( (string) $quiz_neg_point ) . '"';
		}
		if ( $quiz_has_answer && $quiz_answer !== '' ) {
			$attrs[] = 'data-quiz-answer="' . esc_attr( $quiz_answer ) . '"';
		}

		return $attrs;
	}
}
