<?php
namespace MetForm\Core\FormBuilder\Document;

use MetForm\Core\Entries\Map_Block_Document;

defined( 'ABSPATH' ) || exit;

/** Provides the persistence boundary for Form Builder documents. */
final class Repository {
	public const META_KEY = '_metform_builder_document';

	public static function get_default(): array {
		return array(
			'version'  => '1.0.0',
			'elements' => array(),
		);
	}

	public static function initialize( int $post_id ): void {
		update_post_meta( $post_id, '_metform_editor_mode', 'metform' );
		update_post_meta( $post_id, self::META_KEY, self::get_default() );
	}

	public static function get( $form_id ): ?array {
		$form_id  = absint( $form_id );
		$document = get_post_meta( $form_id, self::META_KEY, true );

		return is_array( $document ) ? $document : null;
	}

	public static function is_builder_form( $post_id ): bool {
		$post_id  = absint( $post_id );
		$document = self::get( $post_id );

		return 'metform' === get_post_meta( $post_id, '_metform_editor_mode', true )
			|| ( is_array( $document ) && ! empty( $document['elements'] ) );
	}

	public static function get_fields( $form_id ) {
		$form_id = absint( $form_id );
		$document = self::get( $form_id );

		if ( ! is_array( $document ) || empty( $document['elements'] ) ) {
			return null;
		}

		return Map_Block_Document::fields( $document );
	}
}
