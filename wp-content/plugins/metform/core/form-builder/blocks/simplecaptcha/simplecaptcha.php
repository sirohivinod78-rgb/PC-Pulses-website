<?php
namespace MetForm\Core\FormBuilder\Blocks\Simplecaptcha;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Dynamic_Css\Responsive_Value;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

/**
 * PHP port of src/form-builder/blocks/simplecaptcha/index.jsx + manifest.js —
 * a text-challenge CAPTCHA. Wave 4, block 9/11 (§5.5 / §4 checklist).
 * Security-sensitive per §5's classification and the PRD's "keep CAPTCHA and
 * payment secrets server-side" requirement (§23).
 *
 * **Not a 1:1 port — and deliberately NOT one, for a security reason, not
 * just a "no native HTML equivalent" one:** React's version generates the
 * challenge code with `Math.random()` IN THE BROWSER (index.jsx's
 * `generateCode()`), draws it onto a `<canvas>`, and validates the typed
 * answer against that same client-held value — the answer never leaves the
 * browser, but it also never involves the server at all. A static PHP shell
 * of that exact approach would either (a) have no code to compare against at
 * all (nothing to draw server-side), or (b) require embedding the answer
 * into the page for JS to later compare against, which is precisely the
 * "leak the answer client-side" failure mode §5 warns against. Neither is
 * acceptable for a security control.
 *
 * Instead, this reuses the plugin's EXISTING server-side CAPTCHA
 * infrastructure verbatim — `widgets/simple-captcha/generate-captcha.php`,
 * the same GD-drawn PNG + `$_SESSION['mf_captcha_text']` mechanism the
 * legacy Elementor `mf-simple-captcha` widget (`widgets/simple-captcha/
 * simple-captcha.php`) already depends on in production. This block's
 * render() only points an `<img>` at that endpoint — see
 * `captcha_image_url()`. The actual code generation and session write
 * happen in that file's own separate HTTP request (triggered when the
 * browser loads the `<img>`), safely before THAT request's own output
 * starts. render() deliberately does NOT call `session_start()` itself: by
 * the time a block renders, the page's own HTML output has already begun,
 * so starting a session here would hit "headers already sent" and silently
 * fail — reusing the existing endpoint sidesteps that entirely instead of
 * reimplementing GD/session logic a second time.
 *
 * **The submitted field is always named `mf-captcha-challenge`, never this
 * block's configurable `name` setting.** This is intentional and required,
 * not an oversight: `core/entries/action.php` special-cases the exact
 * literal string `mf-captcha-challenge` (submitted value) and
 * `mf-simple-captcha` (widgetType-keyed field existence) — see
 * `Action::submit()` lines checking `isset($this->fields['mf-simple-captcha'])`
 * and `$form_data['mf-captcha-challenge'] === $_SESSION['mf_captcha_text']`.
 * These match the legacy widget's own hardcoded `name="mf-captcha-challenge"`
 * input (it has no configurable name at all). The manifest's `name` control
 * is kept for settings-panel UI parity with manifest.js (and is still
 * sanitized as a `field_name`), but render() ignores it for the actual
 * submitted input — using the user's name here would silently break both
 * checks above, since neither would ever find the field.
 *
 * `core/entries/map-block-document.php` (`Map_Block_Document::fields()`)
 * needed a matching fix as part of this same change: it previously had no
 * `simplecaptcha` entry in `WIDGET_TYPE_MAP` at all (falling through to the
 * generic `mf-simplecaptcha`, no hyphen — never matching action.php's
 * `mf-simple-captcha` checks), and it only ever registered a field keyed by
 * the user's `name` prop, which is empty by default and thus skipped
 * entirely (`'' === $name` short-circuits registration) — meaning a
 * form-builder captcha block's presence was invisible to action.php's
 * security check regardless. `Map_Block_Document::fields()` now special-
 * cases `simplecaptcha` the same way the legacy `Map_El::search_el()` already
 * special-cases widgets with no `mf_input_name` setting (recaptcha, and this
 * one): register the field keyed by its widgetType string instead of by
 * name, unconditionally. See that class's own doc comment for the full
 * before/after.
 *
 * `required` here only controls the native HTML5 `required` attribute
 * (blocks an empty submission client-side via `form.reportValidity()`,
 * same as every other field block) — the actual answer-match check in
 * `action.php` runs unconditionally whenever this block exists on the form
 * at all, regardless of this setting, matching the legacy widget's own
 * hardcoded (non-configurable) `'required' => true`.
 *
 * `mismatchMessage`/`requiredMessage` have no native-HTML equivalent (the
 * browser's own default validation-bubble text is shown instead) — same
 * "accepted, saved, client-side-only enhancement deferred to a future §4.1
 * JS module" treatment already used for every other block's custom
 * validation-message settings.
 *
 * The refresh button renders (`.mfb-captcha__refresh`) but is inert without
 * JS — same "native control alone, JS adds the convenience" tradeoff as
 * Simplerepeater's Add/Remove buttons and Mobile's country picker. A future
 * §4.1 JS module can re-fetch the `<img src>` (with a cache-busting query
 * param) on click instead of requiring a full page reload to get a new
 * challenge.
 *
 * `styles.captchaBackground`/`captchaTextColor`/`captchaNoiseColor` are
 * accepted, saved, and shown in the settings panel (manifest.js keeps them,
 * unchanged), but are now genuinely inert — not just "no JS module yet" —
 * because there is no CSS selector that could apply them even in principle.
 * React's version read these three directly in JS to choose fill colors for
 * its own `<canvas>` drawing calls (`ctx.fillStyle = props.styles?.
 * captchaBackground || ...`); manifest.js's style controls for them have
 * always had no `selector` key for exactly that reason — they were
 * JS-canvas-only settings, never CSS-backed. This render's `<img>` is a
 * real GD-drawn PNG from `generate-captcha.php`, whose background/text/
 * noise colors are chosen by that file's own `rand()` calls — CSS cannot
 * recolor a raster image's already-drawn pixels, and there is no longer a
 * canvas draw call on this page for a future JS module to redirect either.
 * `styles.captchaWidth`/`captchaHeight`/`captchaBorder` remain fully
 * functional (sizing/border apply to the `<img>` element's box, not its
 * pixel content) and unchanged from manifest.js.
 *
 * Known pre-existing limitation, NOT introduced or fixed by this migration:
 * `$_SESSION['mf_captcha_text']` is a single global slot, not scoped per
 * block instance or per form. A page with more than one `simplecaptcha`
 * block (or more than one MetForm form using one) will have each `<img>`
 * request overwrite the same session value — the legacy Elementor widget has
 * always had this same constraint. Out of scope here; would need its own
 * redesign (e.g. a per-instance token) to fix properly.
 */
final class Simplecaptcha extends Block_Base {
	public function get_name(): string {
		return 'simplecaptcha'; }
	public function get_title(): string {
		return __( 'Simple Captcha', 'metform' ); }
	public function get_icon(): string {
		return 'ShieldCheck'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'file-verify'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array(
			'showLabel'       => true,
			'labelPosition'   => 'top',
			'label'           => __( 'Captcha', 'metform' ),
			'captchaDisplay'  => 'block',
			'name'            => '',
			'placeholder'     => __( 'Enter the code above', 'metform' ),
			'helpText'        => '',
			'required'        => true,
			'requiredMessage' => '',
			'mismatchMessage' => __( 'Incorrect code, please try again.', 'metform' ),
			'styles'          => array(
				'captchaBackground' => '#f3f4f6',
				'captchaTextColor'  => '#111827',
				'captchaNoiseColor' => '#9ca3af',
			),
		);
	}

	public function get_property_schema(): array {
		return array(
			'showLabel'       => array( 'type' => 'boolean' ),
			'label'           => array( 'type' => 'string' ),
			'labelPosition'   => array(
				'type'       => 'string',
				'enum'       => array( 'top', 'left' ),
				'responsive' => true,
			),
			'captchaDisplay'  => array(
				'type'       => 'string',
				'enum'       => array( 'block', 'inline' ),
				'responsive' => true,
			),
			// Kept for settings-panel UI parity with manifest.js (and still
			// sanitized as a field_name) — NOT used by render() for the
			// actual submitted input name. See class doc comment.
			'name'            => array( 'type' => 'field_name' ),
			'placeholder'     => array( 'type' => 'string' ),
			'helpText'        => array( 'type' => 'string' ),
			'required'        => array( 'type' => 'boolean' ),
			'requiredMessage' => array( 'type' => 'string' ),
			'mismatchMessage' => array( 'type' => 'string' ),
		);
	}

	public function register_controls(): array {
		return array(
			'content' => array(
				array(
					'type'        => 'section',
					'key'         => 'content',
					'label'       => __( 'Content', 'metform' ),
					'defaultOpen' => true,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'showLabel',
					'label'   => __( 'Show Label', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'label',
					'label'     => __( 'Label', 'metform' ),
					'default'   => __( 'Captcha', 'metform' ),
					'inline'    => true,
					'condition' => array( 'showLabel!' => false ),
				),
				array(
					'type'       => 'select',
					'key'        => 'labelPosition',
					'label'      => __( 'Label Position', 'metform' ),
					'default'    => 'top',
					'inline'     => true,
					'options'    => array(
						array(
							'label' => __( 'Top', 'metform' ),
							'value' => 'top',
						),
						array(
							'label' => __( 'Left', 'metform' ),
							'value' => 'left',
						),
					),
					'condition'  => array( 'showLabel!' => false ),
					'responsive' => true,
				),
				array(
					'type'       => 'select',
					'key'        => 'captchaDisplay',
					'label'      => __( 'Captcha Display', 'metform' ),
					'default'    => 'block',
					'inline'     => true,
					'options'    => array(
						array(
							'label' => __( 'Block', 'metform' ),
							'value' => 'block',
						),
						array(
							'label' => __( 'Inline', 'metform' ),
							'value' => 'inline',
						),
					),
					'responsive' => true,
				),
				array(
					'type'   => 'text',
					'key'    => 'name',
					'label'  => __( 'Name', 'metform' ),
					'inline' => true,
					'help'   => __( 'Name is must required. Enter name without space or any special character. use only underscore/ hyphen (_/-) for multiple word. Name must be different.', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'placeholder',
					'label'   => __( 'Placeholder', 'metform' ),
					'default' => __( 'Enter the code above', 'metform' ),
					'inline'  => true,
				),
				array(
					'type'  => 'text',
					'key'   => 'helpText',
					'label' => __( 'Help Text', 'metform' ),
					'help'  => __( 'Shown below the field to guide users', 'metform' ),
				),
				array(
					'type'    => 'text',
					'key'     => 'mismatchMessage',
					'label'   => __( 'Mismatch Message', 'metform' ),
					'default' => __( 'Incorrect code, please try again.', 'metform' ),
					'help'    => __( 'Shown when the typed code does not match the challenge.', 'metform' ),
				),

				array(
					'type'        => 'section',
					'key'         => 'settings',
					'label'       => __( 'Settings', 'metform' ),
					'defaultOpen' => false,
				),
				array(
					'type'    => 'toggle',
					'key'     => 'required',
					'label'   => __( 'Required', 'metform' ),
					'default' => true,
				),
				array(
					'type'      => 'text',
					'key'       => 'requiredMessage',
					'label'     => __( 'Required Message', 'metform' ),
					'help'      => __( 'Shown when the field is left empty.', 'metform' ),
					'condition' => array( 'required' => true ),
				),
			),
			'style'   => Input_Controls::style(
				'.mfb-captcha__input',
				array(
					array(
						'type'  => 'section',
						'label' => __( 'Captcha Challenge', 'metform' ),
					),
					array(
						'type'       => 'range',
						'key'        => 'styles.captchaWidth',
						'label'      => __( 'Width', 'metform' ),
						'default'    => '200px',
						'units'      => array( 'px' ),
						'min'        => 100,
						'max'        => 320,
						'step'       => 5,
						'selector'   => '{{WRAPPER}} .mfb-captcha__canvas { width: {{VALUE}}; }',
						'responsive' => true,
					),
					array(
						'type'       => 'range',
						'key'        => 'styles.captchaHeight',
						'label'      => __( 'Height', 'metform' ),
						'default'    => '50px',
						'units'      => array( 'px' ),
						'min'        => 40,
						'max'        => 120,
						'step'       => 2,
						'selector'   => '{{WRAPPER}} .mfb-captcha__canvas { height: {{VALUE}}; }',
						'responsive' => true,
					),
					array(
						'type'           => 'border',
						'key'            => 'styles.captchaBorder',
						'label'          => __( 'Border', 'metform' ),
						'default'        => array(
							'borderType' => 'solid',
							'width'      => array(
								'top'    => '1px',
								'right'  => '1px',
								'bottom' => '1px',
								'left'   => '1px',
							),
							'color'      => '#d1d5db',
							'radius'     => array(
								'top'    => '4px',
								'right'  => '4px',
								'bottom' => '4px',
								'left'   => '4px',
							),
						),
						'selector'       => '{{WRAPPER}} .mfb-captcha__canvas { {{VALUE}} }',
						'responsive'     => true,
						'responsiveKeys' => array( 'width', 'radius' ),
					),
					array(
						'type'    => 'color',
						'key'     => 'styles.captchaBackground',
						'label'   => __( 'Background', 'metform' ),
						'default' => '#f3f4f6',
					),
					array(
						'type'    => 'color',
						'key'     => 'styles.captchaTextColor',
						'label'   => __( 'Code Color', 'metform' ),
						'default' => '#111827',
					),
					array(
						'type'    => 'color',
						'key'     => 'styles.captchaNoiseColor',
						'label'   => __( 'Noise Color', 'metform' ),
						'default' => '#9ca3af',
					),
					array(
						'type'     => 'color',
						'key'      => 'styles.refreshColor',
						'label'    => __( 'Refresh Icon Color', 'metform' ),
						'default'  => '#6b7280',
						'selector' => '{{WRAPPER}} .mfb-captcha__refresh { color: {{VALUE}}; }',
					),
				)
			),
		);
	}

	/**
	 * Reuses widgets/simple-captcha/generate-captcha.php verbatim — see the
	 * class doc comment for why this render() doesn't generate the
	 * challenge/image itself.
	 */
	private function captcha_image_url(): string {
		return defined( 'METFORM_FREE_URL' )
			? METFORM_FREE_URL . 'widgets/simple-captcha/generate-captcha.php'
			: plugins_url( 'widgets/simple-captcha/generate-captcha.php', dirname( __DIR__, 3 ) . '/metform.php' );
	}

	/**
	 * Per-breakpoint values of captchaDisplay (applied through a markup class
	 * on desktop). See Block_Base::get_responsive_markup_rules().
	 *
	 * @since 4.4.1
	 */
	protected function get_responsive_markup_rules(): array {
		return array_merge(
			parent::get_responsive_markup_rules(),
			array(
				'captchaDisplay' => array(
					'block'  => '{{WRAPPER}} .mfb-captcha__container { flex-direction: column; align-items: stretch; } {{WRAPPER}} .mfb-captcha__container .mfb-captcha__input { flex: none; }',
					'inline' => '{{WRAPPER}} .mfb-captcha__container { flex-direction: row; align-items: flex-start; } {{WRAPPER}} .mfb-captcha__container .mfb-captcha__input { flex: 1; }',
				),
			)
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id        = $context->get_element_id();
		$required  = ! empty( $settings['required'] );
		$is_inline = 'inline' === Responsive_Value::resolve( $settings['captchaDisplay'] ?? 'block', 'desktop', 'block' );

		$image_html = sprintf(
			'<img src="%1$s" alt="%2$s" width="200" height="50" class="mfb-captcha__canvas">',
			esc_url( $this->captcha_image_url() ),
			esc_attr( __( 'CAPTCHA challenge', 'metform' ) )
		);

		// Inert until a future §4.1 JS module attaches a click handler that
		// re-fetches $image_html's src (with a cache-busting param) — see
		// class doc comment.
		//
		// The icon itself used to be `<span class="mfb-icon" data-icon=
		// "RotateCw">` — a placeholder meant for some future client-side
		// icon-rendering pass that was never built anywhere in this
		// system (nothing in MetForm reads `data-icon`, and no block
		// prints it any more; "RotateCw" is a lucide-react component name,
		// meaningful only inside the JS *editor* bundle's icon picker —
		// not something a public page's HTML can render on its own). The
		// button rendered with a real empty box and no icon glyph inside
		// it at all, on every page load, not just until a click handler
		// gets wired up. Fixed the same way Rating's star icons already
		// solve this (render_icon_svg()) — an inline `<svg>` with
		// `stroke="currentColor"`, drawn server-side so it's visible with
		// zero JS, and colorable via `styles.refreshColor`'s existing
		// `{{WRAPPER}} .mfb-captcha__refresh { color: {{VALUE}}; }`
		// selector without needing a `fill`/`stroke` override.
		$refresh_html = sprintf(
			'<button type="button" class="mfb-captcha__refresh" aria-label="%1$s"><svg class="mfb-captcha__refresh-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"></path><path d="M21 3v5h-5"></path></svg></button>',
			esc_attr( __( 'Refresh captcha', 'metform' ) )
		);

		$input_html = sprintf(
			'<input type="text" id="%1$s" name="mf-captcha-challenge" class="mfb-field__input mfb-captcha__input" placeholder="%2$s" autocomplete="off" %3$s>',
			esc_attr( $id ),
			esc_attr( (string) ( $settings['placeholder'] ?: __( 'Enter the code above', 'metform' ) ) ),
			$required ? 'required' : ''
		);

		return sprintf(
			'<div class="mfb-captcha__container%1$s"><div class="mfb-captcha__challenge">%2$s%3$s</div>%4$s</div>',
			$is_inline ? ' mfb-captcha__inline' : '',
			$image_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$refresh_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$input_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}
}
