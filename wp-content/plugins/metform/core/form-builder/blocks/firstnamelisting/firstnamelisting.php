<?php
namespace MetForm\Core\FormBuilder\Blocks\Firstnamelisting;

use MetForm\Core\FormBuilder\Block_Base;
use MetForm\Core\FormBuilder\Rendering\Render_Context;
use MetForm\Core\FormBuilder\Shared\Input_Controls;

defined( 'ABSPATH' ) || exit;

/**
 * PHP port of src/form-builder/blocks/firstnamelisting/index.jsx +
 * manifest.js — a thin Shared_Input_Controls text field, structurally
 * identical to Input's (same get_default_settings()/register_controls()
 * shape). `name` is left at Input_Controls::default_props()'s empty
 * default — like every other field block, its actual default (`mf-firstname`)
 * is filled in client-side on drop by utils/fieldName.js's
 * DEFAULT_FIELD_NAMES map, not hardcoded here. The Name field itself is a
 * normal, editable Input_Controls::content() control, same as
 * Input/Color Picker/etc. — it used to be hard-disabled and hardcoded to
 * `fname` here in PHP, which was both inconsistent with every other block
 * and made the field look broken/missing in the editor.
 *
 * NB: the class must be named exactly `Firstnamelisting` (single leading
 * capital, no internal case transitions) — the autoloader only inserts a
 * hyphen at lowercase→uppercase transitions, so `FirstNameListing` would
 * resolve to `first-name-listing.php`, not this file's actual path.
 */
final class Firstnamelisting extends Block_Base {
	public function get_name(): string {
		return 'firstnamelisting'; }
	public function get_title(): string {
		return __( 'First Name (Listing)', 'metform' ); }
	public function get_icon(): string {
		return 'User'; }
	public function get_category(): string {
		return 'Basic'; }

	public function get_group(): string {
		return 'input'; }

	public function get_capabilities(): array {
		return array(
			'acceptsChildren' => false,
			'isField'         => true,
		);
	}

	public function get_default_settings(): array {
		return array_merge(
			Input_Controls::default_props( __( 'First Name', 'metform' ) ),
			array(
				'placeholder' => 'First Name',
			)
		);
	}

	public function get_property_schema(): array {
		return Input_Controls::schema();
	}

	public function register_controls(): array {
		return array(
			'content' => Input_Controls::content( __( 'First Name', 'metform' ) ),
			'style'   => Input_Controls::style( 'input' ),
		);
	}

	public function render_markup( array $settings, Render_Context $context ): string {
		$id = $context->get_element_id();
		// Every sibling Shared_Input_Controls text field (Input, Number,
		// Email, Telephone, Mobile, Password, Textarea) echoes this into
		// its <input> — this block was missing it, so the "Validation"
		// section its own Input_Controls::content() panel exposes
		// (character/word length min/max, regex pattern) was fully
		// configurable in the editor but had zero effect on the rendered
		// field: nothing ever enforced it.
		$validation_attrs = Input_Controls::validation_attributes( $settings );

		ob_start();
		?>
		<input id="<?php echo esc_attr( $id ); ?>" type="text" name="<?php echo esc_attr( $settings['name'] ); ?>" class="mfb-field__input" value="<?php echo esc_attr( (string) ( $settings['defaultValue'] ?? '' ) ); ?>" placeholder="<?php echo esc_attr( $settings['placeholder'] ); ?>" <?php echo $validation_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		return (string) ob_get_clean();
	}
}
