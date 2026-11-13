<?php
namespace MetForm\Core\FormBuilder;

defined( 'ABSPATH' ) || exit;

/**
 * The list of block instances the form builder registers.
 *
 * Adding a block is a one-line addition here — no other file needs to
 * change. See PHP_RENDERING_MIGRATION_PLAN.md §3.2 and §5.5 step 4.
 *
 * Third-party plugins can add, remove, or reorder blocks via the
 * `metform/form_builder/blocks` filter instead of editing this
 * file directly. See the filter docblock below for usage.
 *
 * MetForm Pro-origin blocks (Calculation, Color Picker, Credit Card, Image
 * Select, Like/Dislike, Mobile, Signature, Simple Repeater, Toggle Select)
 * have moved to metform-pro — see PRO_BLOCK_SEPARATION_PLAN.md. MetForm Pro
 * appends their instances through `metform/form_builder/blocks`.
 */
class Block_Manifest {
	/** @return Block_Base[] Block instances, in registration/menu order. */
	public static function get(): array {
		$blocks = array(
			new Blocks\Input\Input(),
			new Blocks\Button\Button(),
			new Blocks\Title\Title(),
			new Blocks\Subtitle\Subtitle(),
			new Blocks\Paragraph\Paragraph(),
			new Blocks\Hiddenfield\Hiddenfield(),
			new Blocks\Email\Email(),
			new Blocks\Textarea\Textarea(),
			new Blocks\Message\Message(),
			new Blocks\Telephone\Telephone(),
			new Blocks\Password\Password(),
			new Blocks\Urlinput\Urlinput(),
			new Blocks\SwitchField\SwitchField(),
			new Blocks\Date\Date(),
			new Blocks\Time\Time(),
			new Blocks\Number\Number(),
			new Blocks\Select\Select(),
			new Blocks\Checkbox\Checkbox(),
			new Blocks\Radio\Radio(),
			new Blocks\Optin\Optin(),
			new Blocks\Gdprconsent\Gdprconsent(),
			new Blocks\Rangeslider\Rangeslider(),
			new Blocks\Container\Container(),
			new Blocks\Multistep\Multistep(),
			new Blocks\Rating\Rating(),
			new Blocks\Fileupload\Fileupload(),
			new Blocks\Firstnamelisting\Firstnamelisting(),
			new Blocks\Lastnamelisting\Lastnamelisting(),
			new Blocks\Summary\Summary(),
			new Blocks\Simplecaptcha\Simplecaptcha(),
			new Blocks\Recaptcha\Recaptcha(),
		);

		/**
		 * Filters the list of Form Builder block instances to register.
		 *
		 * Third-party plugins can hook this to add their own Block_Base
		 * instances, or to remove/reorder core blocks. Each entry must be
		 * an instance of a class extending `MetForm\Core\FormBuilder\Block_Base`
		 * — `Block_Registry::init()` passes every entry directly to
		 * `Block_Registry::register()`, which type-hints `Block_Base` and
		 * will fatal on anything else.
		 *
		 * Note: because blocks here are already instantiated, adding a
		 * block via this filter still constructs every core block above
		 * first — there's no way to skip that construction cost from
		 * inside the filter itself.
		 *
		 * @since 4.1.9
		 *
		 * @param Block_Base[] $blocks Block instances, in registration/menu order.
		 */
		return apply_filters( 'metform/form_builder/blocks', $blocks );
	}
}
