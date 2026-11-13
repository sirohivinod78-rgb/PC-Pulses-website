<?php
namespace MetForm\Core\FormBuilder\Blocks\Message;

use MetForm\Core\FormBuilder\Blocks\Textarea\Textarea;

defined( 'ABSPATH' ) || exit;

/**
 * Message — a ready-made "your message" field. Its own block type
 * (`message`), so it shows as "Message" in the sidebar, Navigator, Summary
 * and conditional logic, but everything else — controls, schema, validation
 * and markup (the same `mfb-field__textarea` <textarea>) — is inherited from
 * Textarea, so the two never drift apart and need no extra CSS/JS.
 *
 * Its default field Name (`mf-message`) comes from
 * src/form-builder/utils/fieldName.js, like every other field block.
 */
final class Message extends Textarea {
	public function get_name(): string {
		return 'message'; }
	public function get_title(): string {
		return __( 'Message', 'metform' ); }
	public function get_icon(): string {
		return 'MessageSquare'; }

	protected function get_default_label(): string {
		return __( 'Message', 'metform' );
	}
}
