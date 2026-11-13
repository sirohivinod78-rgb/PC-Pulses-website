<?php
/** Versioned product category transport; existing destination terms are never edited. */
namespace Jeg\Elementor_Kit\Admin;
defined( 'ABSPATH' ) || exit;

class Product_Terms_Import {
	private static function fail( $message ) { throw new \RuntimeException( $message ); }

	public static function validate( $transport ) {
		if ( ! taxonomy_exists( 'product_cat' ) ) { self::fail( 'Activate WooCommerce before importing product categories.' ); }
		if ( ! is_array( $transport ) || 1 !== ( $transport['version'] ?? null ) || ! is_array( $transport['terms'] ?? null ) || count( $transport['terms'] ) > 1000 ) { self::fail( 'Invalid product category transport. Re-export the package.' ); }
		$seen = array();
		foreach ( $transport['terms'] as $term ) { self::validate_term( $term, array(), $seen ); }
	}

	private static function validate_term( $term, $path, &$seen ) {
		if ( ! is_array( $term ) || count( $path ) >= 32 || ! is_string( $term['slug'] ?? null ) || '' === $term['slug'] || sanitize_title( $term['slug'] ) !== $term['slug'] || in_array( $term['slug'], $path, true ) || ! is_string( $term['name'] ?? null ) || '' === trim( $term['name'] ) || ! is_string( $term['description'] ?? null ) || ! is_string( $term['thumbnail'] ?? null ) ) { self::fail( 'Invalid product category or cyclic hierarchy. Re-export the package.' ); }
		if ( '' !== $term['thumbnail'] && ( ! wp_http_validate_url( $term['thumbnail'] ) ) ) { self::fail( 'Invalid product category thumbnail URL.' ); }
		if ( isset( $seen[ $term['slug'] ] ) && $seen[ $term['slug'] ] !== $term ) { self::fail( 'Conflicting product category definitions for ' . $term['slug'] ); }
		$seen[ $term['slug'] ] = $term;
		if ( null !== ( $term['parent'] ?? null ) ) { self::validate_term( $term['parent'], array_merge( $path, array( $term['slug'] ) ), $seen ); }
	}

	private static function fingerprint( $id ) {
		$term = get_term( $id, 'product_cat' );
		return $term && ! is_wp_error( $term ) ? hash( 'sha256', wp_json_encode( array( $term->name, $term->slug, $term->description, (int) $term->parent, (int) get_term_meta( $id, 'thumbnail_id', true ) ) ) ) : '';
	}

	public static function apply( $transport, $post_id, $demo, &$inventory, $image ) {
		self::validate( $transport );
		$ids = array();
		foreach ( $transport['terms'] as $term ) { $ids[] = self::term( $term, $demo, $inventory, $image ); }
		$result = wp_set_post_terms( $post_id, array_values( array_unique( $ids ) ), 'product_cat', false );
		if ( is_wp_error( $result ) ) { self::fail( $result->get_error_message() ); }
	}

	private static function term( $row, $demo, &$inventory, $image ) {
		$parent = empty( $row['parent'] ) ? 0 : self::term( $row['parent'], $demo, $inventory, $image );
		$term = get_term_by( 'slug', $row['slug'], 'product_cat' );
		if ( $term && (int) $term->parent !== $parent ) { self::fail( 'Destination product category hierarchy conflicts for ' . $row['slug'] . '. Review the existing category before retrying.' ); }
		if ( ! $term ) {
			$result = wp_insert_term( wp_slash( $row['name'] ), 'product_cat', array( 'slug' => $row['slug'], 'description' => wp_slash( $row['description'] ), 'parent' => $parent ) );
			if ( is_wp_error( $result ) ) { self::fail( $result->get_error_message() ); }
			$id = (int) $result['term_id'];
			$owner = wp_generate_uuid4();
			update_term_meta( $id, '_jkit_import_owner', $owner );
			$inventory['product_terms'][ $id ] = array( 'owner' => $owner, 'fingerprint' => self::fingerprint( $id ) );
			update_option( essential_import_demo_key( $demo ), $inventory );
		} else { $id = (int) $term->term_id; }
		$owned = $inventory['product_terms'][ $id ] ?? null;
		if ( $owned && ( get_term_meta( $id, '_jkit_import_owner', true ) !== $owned['owner'] || self::fingerprint( $id ) !== $owned['fingerprint'] ) ) { self::fail( 'An imported category changed after import: ' . $row['slug'] . '. Review it before retrying.' ); }
		if ( $owned && '' !== $row['thumbnail'] && ! get_term_meta( $id, 'thumbnail_id', true ) ) {
			$attachment = call_user_func( $image, $row['thumbnail'] );
			if ( is_wp_error( $attachment ) ) { self::fail( $attachment->get_error_message() ); }
			if ( ! empty( $attachment['count'] ) ) { $inventory['image'][ $attachment['id'] ] = $attachment['id']; }
			// Persist attachment ownership even if saving the term thumbnail fails.
			update_option( essential_import_demo_key( $demo ), $inventory );
			$updated = update_term_meta( $id, 'thumbnail_id', $attachment['id'] );
			if ( is_wp_error( $updated ) || (int) get_term_meta( $id, 'thumbnail_id', true ) !== (int) $attachment['id'] ) { self::fail( 'Unable to save category thumbnail: ' . $row['slug'] ); }
			$inventory['product_terms'][ $id ]['fingerprint'] = self::fingerprint( $id );
			update_option( essential_import_demo_key( $demo ), $inventory );
		}
		return $id;
	}

	/** Called after imported products are deleted; preserve changed/reused or newly used terms. */
	public static function cleanup( &$inventory ) {
		$terms = array_reverse( $inventory['product_terms'] ?? array(), true );
		foreach ( $terms as $id => $owned ) {
			if ( get_term_meta( $id, '_jkit_import_owner', true ) !== $owned['owner'] || self::fingerprint( $id ) !== $owned['fingerprint'] ) {
				unset( $inventory['image'][ (int) get_term_meta( $id, 'thumbnail_id', true ) ] );
				continue;
			}
			$objects = get_objects_in_term( $id, 'product_cat' );
			$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $id, 'hide_empty' => false, 'fields' => 'ids' ) );
			if ( is_wp_error( $objects ) || is_wp_error( $children ) ) { self::fail( 'Unable to verify imported category ownership during cleanup.' ); }
			if ( $objects || $children ) {
				unset( $inventory['image'][ (int) get_term_meta( $id, 'thumbnail_id', true ) ] );
				continue;
			}
			if ( ! $objects && ! $children ) {
				$result = wp_delete_term( $id, 'product_cat' );
				if ( is_wp_error( $result ) || false === $result ) { self::fail( 'Unable to remove an imported product category.' ); }
			}
		}
		unset( $inventory['product_terms'] );
	}
}
