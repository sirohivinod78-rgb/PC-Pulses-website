<?php
/** Native Pro package finalization, invoked only during demo content import. */
namespace Jeg\Elementor_Kit\Admin;
defined( 'ABSPATH' ) || exit;

class Elementor_Pro_Import {
	const TYPES = array( 'header', 'footer', 'archive', 'single', 'single-post', 'single-page', 'product', 'product-archive', 'error-404', 'popup', 'section' );

	public static function validate( $data ) {
		$transport = $data['elementor_pro_transport'];
		if ( ! is_array( $transport ) || ! in_array( $transport['version'] ?? null, array( 1, 2 ), true ) || empty( $transport['package'] ) || ! is_string( $transport['package'] ) || ! preg_match( '/^[a-f0-9-]{36}$/', $transport['package'] ) || empty( $transport['expected'] ) || ! is_array( $transport['expected'] ) || count( $transport['expected'] ) > 5000 || ! in_array( $transport['kind'] ?? '', array( 'post', 'menu' ), true ) || ! is_array( $transport['expected_menus'] ?? null ) || ! in_array( $transport['source_id'] ?? 0, 'menu' === ( $transport['kind'] ?? '' ) ? $transport['expected_menus'] : $transport['expected'], true ) ) {
			throw new \RuntimeException( 'Invalid Elementor Pro import transport.' );
		}
		foreach ( array_merge( $transport['expected'], $transport['expected_menus'] ) as $id ) {
			if ( ! is_int( $id ) || $id < 1 ) { throw new \RuntimeException( 'Invalid source ID in Elementor Pro package.' ); }
		}
		if ( count( $transport['expected_menus'] ) > 5000 || count( array_unique( $transport['expected'] ) ) !== count( $transport['expected'] ) || count( array_unique( $transport['expected_menus'] ) ) !== count( $transport['expected_menus'] ) ) {
			throw new \RuntimeException( 'Invalid duplicate or excessive Elementor Pro source IDs.' );
		}
		if ( ( false !== ( $transport['requires_pro'] ?? true ) || isset( $data['elementor_pro_template'] ) ) && ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			throw new \RuntimeException( 'Activate the required Elementor Pro plugin before importing this package.' );
		}
		if ( ! empty( $data['saved_library_template'] ) && ( 'post' !== $transport['kind'] || 'elementor_library' !== ( $data['type'] ?? '' ) || ! in_array( $data['metadata']['template_type'] ?? '', array( 'page', 'section' ), true ) || isset( $data['elementor_pro_template'] ) ) ) {
			throw new \RuntimeException( 'Invalid saved Elementor library template.' );
		}
		if ( isset( $data['elementor_pro_template'] ) && ( 'elementor_library' !== $data['type'] || ! in_array( $data['elementor_pro_template']['type'] ?? '', self::TYPES, true ) || $data['metadata']['template_type'] !== $data['elementor_pro_template']['type'] ) ) {
			throw new \RuntimeException( 'Invalid native Elementor Pro template type.' );
		}
		if ( isset( $data['elementor_pro_template'] ) ) {
			$template = $data['elementor_pro_template'];
			if ( in_array( $template['type'], array( 'product', 'product-archive' ), true ) && ! function_exists( 'WC' ) ) {
				throw new \RuntimeException( 'Activate the required WooCommerce plugin before importing product or product archive templates.' );
			}
			if ( ! is_array( $template['conditions'] ?? null ) || ! is_string( $template['location'] ?? null ) || ( ! is_array( $template['display_settings'] ?? null ) && '' !== ( $template['display_settings'] ?? null ) ) ) {
				throw new \RuntimeException( 'Invalid Elementor Pro template metadata.' );
			}
			foreach ( $template['conditions'] as $condition ) {
				if ( ! is_string( $condition ) || ! preg_match( '/^(include|exclude)\/[^\/]+(?:\/[^\/]*){0,2}$/', $condition ) ) { throw new \RuntimeException( 'Invalid Elementor Pro condition syntax.' ); }
			}
		}
	}

	/** Resolve only exporter-marked references; all other numbers and strings survive. */
	public static function resolve( $value, $map, $menus = array() ) {
		if ( is_array( $value ) ) {
			foreach ( $value as &$item ) { $item = self::resolve( $item, $map, $menus ); }
			unset( $item );
			return $value;
		}
		if ( ! is_string( $value ) ) { return $value; }
		$value = preg_replace_callback( '/\[elementor-tag[^\]]*name="popup"[^\]]*\]/', function( $m ) use ( $map, $menus ) {
			return preg_replace_callback( '/settings="([^"]*)"/', function( $setting ) use ( $map, $menus ) {
				$data = json_decode( urldecode( $setting[1] ), true );
				if ( ! is_array( $data ) ) { throw new \RuntimeException( 'Invalid imported dynamic popup tag settings.' ); }
				return 'settings="' . rawurlencode( wp_json_encode( self::resolve( $data, $map, $menus ) ) ) . '"';
			}, $m[0] );
		}, $value );
		$value = preg_replace_callback( '/(action=popup(?::|%3A)(?:open|toggle|close)&settings=)([A-Za-z0-9_%+\/=\-]+)/', function( $m ) use ( $map, $menus ) {
			$json = base64_decode( rawurldecode( $m[2] ), true );
			$settings = is_string( $json ) ? json_decode( $json, true ) : null;
			if ( ! is_array( $settings ) ) { throw new \RuntimeException( 'Invalid popup action during import.' ); }
			$settings = self::resolve( $settings, $map, $menus );
			return $m[1] . rawurlencode( base64_encode( wp_json_encode( $settings ) ) );
		}, $value );
		return preg_replace_callback( '/\{\{jkit-pro:(post|menu):([0-9]+)\}\}/', function( $m ) use ( $map, $menus ) {
			if ( 'menu' === $m[1] ) { $map = $menus; }
			if ( empty( $map[ $m[2] ] ) ) { throw new \RuntimeException( 'Unresolved Elementor Pro ' . $m[1] . ' reference #' . $m[2] ); }
			return (string) $map[ $m[2] ];
		}, $value );
	}

	/** Every selected record participates, so forward references never use source IDs. */
	public static function record( $data, $post_id, $import_data ) {
		$t = $data['elementor_pro_transport'];
		$state = $import_data['elementor_pro_pending'] ?? array();
		if ( ( $state['package'] ?? '' ) !== $t['package'] || ! empty( $state['completed'] ) ) {
			$state = array( 'package' => $t['package'], 'expected' => $t['expected'], 'expected_menus' => $t['expected_menus'], 'map' => array(), 'menus' => array(), 'templates' => array() );
		}
		if ( $state['expected'] !== $t['expected'] || $state['expected_menus'] !== $t['expected_menus'] ) { throw new \RuntimeException( 'Elementor Pro package records disagree; restart with one consistent ZIP.' ); }
		$group = 'menu' === $t['kind'] ? 'menus' : 'map';
		$state[ $group ][ $t['source_id'] ] = (int) $post_id;
		if ( ! empty( $data['saved_library_template'] ) ) {
			wp_set_object_terms( $post_id, $data['metadata']['template_type'], \Elementor\TemplateLibrary\Source_Local::TAXONOMY_TYPE_SLUG );
		}
		if ( isset( $data['elementor_pro_template'] ) ) {
			$state['templates'][ $post_id ] = $data['elementor_pro_template'];
			// No conditions are installed until all references are resolved.
			update_post_meta( $post_id, '_elementor_conditions', array() );
			wp_set_object_terms( $post_id, $data['elementor_pro_template']['type'], \Elementor\TemplateLibrary\Source_Local::TAXONOMY_TYPE_SLUG );
		}
		$import_data['elementor_pro_pending'] = $state;
		return $import_data;
	}

	public static function finalize( $state ) {
		if ( count( $state['map'] ) !== count( $state['expected'] ) || count( $state['menus'] ) !== count( $state['expected_menus'] ) ) { return; }
		// Resolve and validate every record before installing any display conditions.
		$updates = array();
		foreach ( $state['map'] as $id ) {
			if ( ! get_post( $id ) ) { throw new \RuntimeException( 'An imported Elementor Pro package record is missing.' ); }
			$content = get_post_meta( $id, '_elementor_data', true );
			$settings = get_post_meta( $id, '_elementor_page_settings', true );
			$decoded = is_string( $content ) ? json_decode( $content, true ) : $content;
			$updates[ $id ] = array( is_array( $decoded ) ? self::resolve( $decoded, $state['map'], $state['menus'] ) : null, self::resolve( $settings, $state['map'], $state['menus'] ) );
		}
		$menu_updates = array();
		foreach ( $state['menus'] as $menu_id ) {
			foreach ( wp_get_nav_menu_items( $menu_id ) ?: array() as $item ) {
				$url = self::resolve( $item->url, $state['map'], $state['menus'] );
				if ( $url !== $item->url ) { $menu_updates[ $item->ID ] = $url; }
			}
		}
		$templates = self::resolve( $state['templates'], $state['map'], $state['menus'] );
		$manager = $templates ? \ElementorPro\Modules\ThemeBuilder\Module::instance()->get_conditions_manager() : null;
		if ( $templates && ! is_callable( array( $manager, 'save_conditions' ) ) ) { throw new \RuntimeException( 'Unsupported Elementor Pro condition API.' ); }
		$popup_documents = array();
		foreach ( $templates as $id => $template ) {
			if ( 'popup' === $template['type'] ) {
				$popup_documents[ $id ] = \Elementor\Plugin::$instance->documents->get( $id );
				if ( ! $popup_documents[ $id ] || ! is_callable( array( $popup_documents[ $id ], 'save_display_settings_data' ) ) ) { throw new \RuntimeException( 'Elementor Pro popup document unavailable.' ); }
			}
		}
		foreach ( $updates as $id => $update ) {
			if ( null !== $update[0] ) { update_post_meta( $id, '_elementor_data', wp_slash( wp_json_encode( $update[0] ) ) ); }
			update_post_meta( $id, '_elementor_page_settings', wp_slash( $update[1] ) );
		}
		foreach ( $menu_updates as $item_id => $url ) { update_post_meta( $item_id, '_menu_item_url', wp_slash( $url ) ); }
		foreach ( $templates as $id => $template ) {
			$conditions = array();
			foreach ( $template['conditions'] as $condition ) {
				if ( ! is_string( $condition ) ) { throw new \RuntimeException( 'Invalid imported Elementor Pro condition.' ); }
				list( $type, $name, $sub_name, $sub_id ) = array_pad( explode( '/', $condition ), 4, '' );
				$conditions[] = compact( 'type', 'name', 'sub_name', 'sub_id' );
			}
			if ( ! empty( $template['location'] ) ) { update_post_meta( $id, '_elementor_location', $template['location'] ); }
			if ( 'popup' === $template['type'] ) {
				$document = $popup_documents[ $id ];
				$document->save_display_settings_data( $template['display_settings'] ?: array( 'triggers' => array(), 'timing' => array() ) );
			}
			$manager->save_conditions( $id, $conditions );
		}
		return true;
	}
}
