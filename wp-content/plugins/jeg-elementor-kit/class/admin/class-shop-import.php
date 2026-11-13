<?php
/** Validated Shop package context and import-owned WooCommerce identity. */
namespace Jeg\Elementor_Kit\Admin;
defined( 'ABSPATH' ) || exit;

class Shop_Import {

	/** Negotiated with the catalog; legacy success envelopes remain JSON strings. */
	public static function capabilities() { return array( 'jkit_shop_template' => 1, 'jkit_product_categories' => 1 ); }

	private static function context_key( $demo ) { return 'jkit_shop_context_' . md5( $demo ); }
	private static function lock_key( $demo ) { return 'jkit_shop_lock_' . md5( $demo ); }
	public static function has_context( $demo ) { return (bool) get_option( self::context_key( $demo ) ); }

	public static function lock( $demo ) {
		$token = wp_generate_uuid4();
		if ( ! add_option( self::lock_key( $demo ), $token, '', false ) ) { throw new \RuntimeException( 'A Shop import operation is already reserved for this demo. Wait for it to finish; a stopped reservation needs operator review.' ); }
		return $token;
	}

	public static function unlock( $demo, $token ) {
		if ( $token && get_option( self::lock_key( $demo ) ) === $token ) { delete_option( self::lock_key( $demo ) ); }
	}

	private static function fail() { throw new \RuntimeException( 'Invalid or unsupported Shop template package. Re-export it with a compatible exporter.' ); }

	private static function list_of_keys( $list ) {
		if ( ! is_array( $list ) || ! $list || count( $list ) > 5000 || array_keys( $list ) !== range( 0, count( $list ) - 1 ) ) { self::fail(); }
		foreach ( $list as $key ) { if ( ! is_string( $key ) || ! preg_match( '/^[a-z][a-z0-9_-]*-[1-9][0-9]*$/D', $key ) ) { self::fail(); } }
		if ( count( array_unique( $list ) ) !== count( $list ) ) { self::fail(); }
	}

	private static function stem( $value ) {
		if ( ! is_string( $value ) || '' === $value || strlen( $value ) > 200 || basename( $value ) !== $value || false !== strpos( $value, '\\' ) || in_array( $value, array( '.', '..' ), true ) || strpbrk( $value, '?#' ) ) { self::fail(); }
	}

	private static function url( $url, $site = null ) {
		$p = is_string( $url ) ? wp_parse_url( $url ) : false;
		if ( ! $p || 'https' !== ( $p['scheme'] ?? '' ) || empty( $p['host'] ) || isset( $p['user'] ) || isset( $p['pass'] ) || isset( $p['query'] ) || isset( $p['fragment'] ) ) { self::fail(); }
		if ( $site && ( strtolower( $p['host'] ) !== strtolower( $site['host'] ) || ( $p['port'] ?? 443 ) !== ( $site['port'] ?? 443 ) || 0 !== strpos( trailingslashit( $p['path'] ?? '/' ), trailingslashit( $site['path'] ?? '/' ) ) ) ) { self::fail(); }
		return $p;
	}

	public static function validate_manifest( $m ) {
		if ( ! is_array( $m ) || 1 !== ( $m['version'] ?? null ) || ! is_string( $m['package'] ?? null ) || ! preg_match( '/^[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}$/D', $m['package'] ) ) { self::fail(); }
		$site = self::url( $m['source_site'] ?? null );
		$l = $m['layout'] ?? null;
		if ( ! is_array( $l ) || 'page' !== ( $l['post_type'] ?? null ) || ! is_int( $l['id'] ?? null ) || $l['id'] < 1 || 'page-' . $l['id'] !== ( $l['record_key'] ?? null ) ) { self::fail(); }
		self::stem( $l['slug'] ?? null ); self::stem( $l['file_slug'] ?? null ); self::url( $l['url'] ?? null, $site );
		$identity = $m['identity'] ?? null;
		if ( ! is_array( $identity ) || ! array_key_exists( 'source', $identity ) || ! is_string( $identity['title'] ?? null ) || '' === trim( $identity['title'] ) ) { self::fail(); }
		self::stem( $identity['slug'] ?? null );
		$aliases = array( $l['url'] );
		if ( null !== $identity['source'] ) {
			$s = $identity['source'];
			if ( ! is_array( $s ) || 'page' !== ( $s['post_type'] ?? null ) || ! is_int( $s['id'] ?? null ) || $s['id'] < 1 ) { self::fail(); }
			self::stem( $s['slug'] ?? null ); self::url( $s['url'] ?? null, $site ); $aliases[] = $s['url'];
		}
		if ( array_values( array_unique( $aliases ) ) !== ( $m['url_aliases'] ?? null ) ) { self::fail(); }
		self::list_of_keys( $m['expected_records'] ?? null ); self::list_of_keys( $m['archive_order'] ?? null );
		if ( $m['archive_order'][0] !== $l['record_key'] || array_diff( $m['archive_order'], $m['expected_records'] ) ) { self::fail(); }
	}

	/** Preparing validates context without creating posts or requiring not-yet-installed plugins. */
	public static function prepare_config( $demo, $config ) {
		if ( ! is_array( $config['required_capabilities'] ?? array() ) ) { self::fail(); }
		foreach ( $config['required_capabilities'] ?? array() as $name => $version ) { if ( ! isset( self::capabilities()[ $name ] ) || self::capabilities()[ $name ] !== $version ) { self::fail(); } }
		$marked = array_key_exists( 'jkit_shop_transport', $config ) || array_key_exists( 'jkit_shop_template', $config['required_capabilities'] ?? array() );
		if ( ! $marked ) {
			if ( get_option( self::context_key( $demo ) ) ) {
				$inventory = get_option( essential_import_demo_key( $demo ), array() );
				if ( ! empty( $inventory['shop']['identity'] ) || ! empty( $inventory['shop']['records'] ) ) { throw new \RuntimeException( 'Clean up the previous Shop package before importing a different package for this demo.' ); }
				delete_option( self::context_key( $demo ) );
			}
			return;
		}
		if ( 1 !== ( $config['required_capabilities']['jkit_shop_template'] ?? null ) ) { self::fail(); }
		$m = $config['jkit_shop_transport'] ?? null; self::validate_manifest( $m );
		if ( count( $config['contents'] ) !== count( $m['expected_records'] ) ) { self::fail(); }
		$files = array();
		foreach ( $config['contents'] as $row ) {
			self::stem( $row['slug'] ?? null );
			if ( isset( $files[ $row['slug'] ] ) ) { self::fail(); }
			$files[ $row['slug'] ] = true;
		}
		if ( ! isset( $files[ $m['layout']['file_slug'] ] ) ) { self::fail(); }
		$required = array( 'elementor/elementor.php', 'jeg-elementor-kit/jeg-elementor-kit.php', 'jeg-kit-pro/jeg-kit-pro.php', 'woocommerce/woocommerce.php' );
		foreach ( $config['plugins'] as $plugin ) {
			if ( is_array( $plugin ) && isset( $plugin['file'], $plugin['slug'] ) && dirname( $plugin['file'] ) === $plugin['slug'] ) { $required = array_diff( $required, array( $plugin['file'] ) ); }
		}
		if ( $required ) { throw new \RuntimeException( 'The Shop package is missing required plugin dependencies. Re-export the complete package.' ); }
		$token = self::lock( $demo );
		try {
			$inventory = get_option( essential_import_demo_key( $demo ), array() );
			$state = $inventory['shop'] ?? array();
			if ( $state && ( $state['manifest'] ?? null ) !== $m ) { throw new \RuntimeException( 'Clean up the previous Shop package before importing a changed package for this demo.' ); }
			self::check_archives( $m );
			update_option( self::context_key( $demo ), array( 'manifest' => $m, 'user' => get_current_user_id(), 'files' => array_keys( $files ), 'required_capabilities' => $config['required_capabilities'] ), false );
			if ( ( get_option( self::context_key( $demo ) )['manifest'] ?? null ) !== $m ) { throw new \RuntimeException( 'The Shop package context could not be saved.' ); }
		} finally { self::unlock( $demo, $token ); }
	}

	/** Only records of the same package can coexist with a new converted Shop. */
	private static function check_archives( $m ) {
		$posts = get_posts( array( 'post_type' => 'jkit-template', 'post_status' => 'publish', 'numberposts' => -1, 'meta_key' => 'jkit-template-type', 'meta_value' => 'jkit-archive-template' ) );
		foreach ( $posts as $post ) {
			$key = get_post_meta( $post->ID, 'jkit_shop_record', true );
			if ( get_post_meta( $post->ID, 'jkit_shop_package', true ) !== $m['package'] || ! in_array( $key, $m['archive_order'], true ) ) { throw new \RuntimeException( 'Existing published archive templates conflict with this Shop import. Use a clean site or review the existing templates first.' ); }
		}
	}

	public static function validate_payload( $demo, $data ) {
		$context = get_option( self::context_key( $demo ), array() );
		if ( ! is_array( $data ) || ! isset( $context['manifest'] ) || ( $context['user'] ?? null ) !== get_current_user_id() || ( $data['jkit_shop_transport'] ?? null ) !== $context['manifest'] ) { throw new \RuntimeException( 'Prepare the same Shop package in this session before importing its content.' ); }
		$m = $context['manifest']; self::validate_manifest( $m );
		if ( 'product' === ( $data['type'] ?? '' ) && 1 === ( $context['required_capabilities']['jkit_product_categories'] ?? null ) && ! array_key_exists( 'product_categories', $data ) ) { throw new \RuntimeException( 'Product category transport is missing from this package record. Re-export the package.' ); }
		$key = $data['jkit_record_key'] ?? null;
		if ( ! in_array( $key, $m['expected_records'], true ) || ! is_string( $data['type'] ?? null ) || ! is_string( $data['title'] ?? null ) || ! is_array( $data['metadata'] ?? null ) || ! array_key_exists( 'content', $data ) || ! array_key_exists( 'page_settings', $data ) || ( ! is_array( $data['page_settings'] ) && '' !== $data['page_settings'] ) ) { self::fail(); }
		if ( ! in_array( $data['type'], array( 'page', 'post', 'product', 'jkit-header', 'jkit-footer', 'jkit-template', 'jkit-mega-menu', 'elementor_library', 'metform-form', 'nav_menu' ), true ) || ! is_string( $data['metadata']['template_type'] ?? null ) || ! is_string( $data['metadata']['wp_page_template'] ?? null ) || ( ! is_array( $data['content'] ) && ! is_string( $data['content'] ) && 'nav_menu' !== $data['type'] ) ) { self::fail(); }
		self::stem( $data['slug'] ?? null );
		if ( 'nav_menu' === $data['type'] ) {
			if ( ! is_array( $data['nav_menu_item_setting'] ?? null ) ) { self::fail(); }
			$ids = $parents = array();
			foreach ( $data['nav_menu_item_setting'] as $item ) {
				if ( ! is_array( $item ) || ! is_int( $item['item_id'] ?? null ) || $item['item_id'] < 1 || isset( $ids[ $item['item_id'] ] ) || ! is_int( $item['parent_id'] ?? null ) || $item['parent_id'] < 0 || ! is_string( $item['title'] ?? null ) || ! is_string( $item['object'] ?? null ) || ! is_string( $item['type'] ?? null ) || ! is_string( $item['slug'] ?? null ) ) { self::fail(); }
				$ids[ $item['item_id'] ] = true;
				$parents[ $item['item_id'] ] = $item['parent_id'];
				if ( 'custom' === $item['object'] && ! is_string( $item['url'] ?? null ) ) { self::fail(); }
				if ( isset( $item['jkit_shop_reference'] ) ) {
					if ( 'identity' !== $item['jkit_shop_reference'] ) { self::fail(); }
					self::link_url( 'https://fixture.invalid/', $item['jkit_shop_reference_suffix'] ?? '' );
				} elseif ( isset( $item['jkit_shop_reference_suffix'] ) ) { self::fail(); }
			}
			foreach ( $data['nav_menu_item_setting'] as $item ) { if ( $item['parent_id'] && ( ! isset( $ids[ $item['parent_id'] ] ) || $item['parent_id'] === $item['item_id'] ) ) { self::fail(); } }
			foreach ( $parents as $id => $parent ) {
				$seen = array( $id => true );
				while ( $parent ) { if ( isset( $seen[ $parent ] ) ) { self::fail(); } $seen[ $parent ] = true; $parent = $parents[ $parent ]; }
			}
		}
		$archive = 'jkit-template' === $data['type'] && 'jkit-archive-template' === ( $data['metadata']['jkit-template-type'] ?? '' );
		if ( $archive !== in_array( $key, $m['archive_order'], true ) ) { self::fail(); }
		if ( $archive && ( ! is_array( $data['jkit-condition'] ?? null ) || ! $data['jkit-condition'] ) ) { self::fail(); }
		if ( $key === $m['layout']['record_key'] && ( ! $archive || $data['slug'] !== $m['layout']['slug'] || 'page' !== ( $data['metadata']['template_type'] ?? '' ) || 'builder' !== ( $data['metadata']['elementor_document'] ?? '' ) || 'elementor_header_footer' !== ( $data['metadata']['wp_page_template'] ?? '' ) || array( array( 'location' => 'shop_page', 'enclose' => 'include' ) ) !== ( $data['jkit-condition'] ?? null ) || ! is_array( $data['content'] ) || ! $data['content'] || ! empty( $data['homepage'] ) ) ) { self::fail(); }
		return $m;
	}

	public static function validate_download( $demo, $file, $data ) {
		$m = self::validate_payload( $demo, $data );
		$context = get_option( self::context_key( $demo ) );
		if ( ! in_array( $file, $context['files'], true ) || ( $data['jkit_record_key'] === $m['layout']['record_key'] ) !== ( $file === $m['layout']['file_slug'] ) ) { throw new \RuntimeException( 'The Shop content file does not match its prepared package.' ); }
	}

	/** A suffix can extend only a WordPress-generated permalink, never replace its origin. */
	public static function link_url( $url, $suffix ) {
		if ( ! is_string( $suffix ) || ( '' !== $suffix && ! in_array( $suffix[0], array( '?', '#' ), true ) ) || preg_match( '/[\x00-\x20\x7f]/', $suffix ) || false !== strpos( $suffix, '{{' ) ) { throw new \RuntimeException( 'Invalid Shop link suffix. Re-export the affected link.' ); }
		if ( '' === $suffix ) { return $url; }
		$url = explode( '#', $url, 2 )[0];
		return $url . ( '?' === $suffix[0] && false !== strpos( $url, '?' ) ? '&' . substr( $suffix, 1 ) : $suffix );
	}

	/** Only the explicit URL-object token is resolved; unrelated text and external URLs survive. */
	public static function resolve_links( $value, $url ) {
		if ( is_array( $value ) ) {
			if ( isset( $value['url'] ) && '{{jkit:shop:identity:url}}' === $value['url'] ) {
				$value['url'] = self::link_url( $url, $value['jkit_shop_reference_suffix'] ?? '' );
				unset( $value['jkit_shop_reference_suffix'] );
			} elseif ( array_key_exists( 'jkit_shop_reference_suffix', $value ) ) { throw new \RuntimeException( 'A Shop link marker is missing its supported URL token.' ); }
			foreach ( $value as &$child ) { $child = self::resolve_links( $child, $url ); } unset( $child );
		} elseif ( is_string( $value ) && false !== strpos( $value, '{{jkit:shop:' ) ) { throw new \RuntimeException( 'A Shop token occurs outside a supported link object. Review the embedded reference.' ); }
		return $value;
	}

	/** Menus are populated after all page references exist, so request order is irrelevant. */
	private static function populate_menus( $demo, &$inventory, $posts ) {
		$state = $inventory['shop']; $package = $state['manifest']['package'];
		foreach ( $state['records'] as $key => $record ) {
			if ( 'nav_menu' !== $record['type'] ) { continue; }
			$items = wp_get_nav_menu_items( $record['id'], array( 'post_status' => 'any' ) ) ?: array();
			foreach ( $items as $item ) {
				if ( get_post_meta( $item->ID, 'jkit_shop_package', true ) !== $package ) { throw new \RuntimeException( 'An imported menu has operator-added items. Review it before resuming Shop finalization.' ); }
			}
			foreach ( $items as $item ) { if ( ! wp_delete_post( $item->ID, true ) ) { throw new \RuntimeException( 'An owned menu item could not be rebuilt.' ); } }
			$context = array( 'id' => $state['identity']['id'], 'url' => $state['identity']['url'], 'package' => $package, 'posts' => $posts );
			essential_import_menu( $record['title'], $record['menu_items'], $demo, $context, $record['id'] );
		}
	}

	/** Called on the final request, including an identical retry of an already inserted record. */
	public static function finalize( $demo, &$inventory ) {
		$state = $inventory['shop']; $m = $state['manifest']; $records = $state['records'];
		if ( count( $records ) !== count( $m['expected_records'] ) || array_diff( $m['expected_records'], array_keys( $records ) ) ) { throw new \RuntimeException( 'Shop package content is incomplete. Import every expected record before finalization.' ); }
		self::check_archives( $m );
		$posts = array();
		foreach ( $records as $key => $record ) {
			$menu = 'nav_menu' === $record['type'];
			$object = $menu ? wp_get_nav_menu_object( $record['id'] ) : get_post( $record['id'] );
			$owner = $menu ? get_term_meta( $record['id'], 'jkit_shop_package', true ) : get_post_meta( $record['id'], 'jkit_shop_package', true );
			if ( ! $object || is_wp_error( $object ) || empty( $record['complete'] ) || $owner !== $m['package'] || ( ! $menu && 'publish' !== $object->post_status ) ) { throw new \RuntimeException( 'Shop finalization requires every owned record to be present and complete.' ); }
			if ( ! $menu ) {
				$raw = get_post_meta( $record['id'], '_elementor_data', true );
				$content = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
				if ( 'builder' === get_post_meta( $record['id'], '_elementor_edit_mode', true ) && ! is_array( $content ) ) { throw new \RuntimeException( 'An imported Elementor document is invalid.' ); }
				if ( is_array( $content ) ) {
					$resolved = self::resolve_links( $content, $state['identity']['url'] );
					if ( $resolved !== $content ) {
						update_post_meta( $record['id'], '_elementor_data', wp_slash( wp_json_encode( $resolved ) ) );
						if ( json_decode( get_post_meta( $record['id'], '_elementor_data', true ), true ) !== $resolved ) { throw new \RuntimeException( 'Resolved Shop document links could not be saved.' ); }
					}
				} else { self::resolve_links( $raw, $state['identity']['url'] ); }
				$settings = get_post_meta( $record['id'], '_elementor_page_settings', true );
				$resolved = self::resolve_links( $settings, $state['identity']['url'] );
				if ( $resolved !== $settings ) {
					update_post_meta( $record['id'], '_elementor_page_settings', wp_slash( $resolved ) );
					if ( get_post_meta( $record['id'], '_elementor_page_settings', true ) !== $resolved ) { throw new \RuntimeException( 'Resolved Shop page settings could not be saved.' ); }
				}
			}
			if ( in_array( $record['type'], array( 'page', 'post', 'product' ), true ) ) {
				if ( isset( $posts[ $record['type'] ][ $record['slug'] ] ) ) { throw new \RuntimeException( 'Ambiguous same-type menu target slugs require export review.' ); }
				$posts[ $record['type'] ][ $record['slug'] ] = $record['id'];
			}
		}
		if ( empty( $state['finalized'] ) ) { self::populate_menus( $demo, $inventory, $posts ); }
		if ( empty( $state['finalized'] ) && isset( $inventory['elementor_pro_pending'] ) ) {
			// Native Pro menu action URLs now exist; resolve their own marked references too.
			if ( ! Elementor_Pro_Import::finalize( $inventory['elementor_pro_pending'] ) ) { throw new \RuntimeException( 'Native Elementor references are incomplete in this Shop package.' ); }
			$inventory['elementor_pro_pending']['completed'] = true;
		}
		foreach ( $m['archive_order'] as $priority => $key ) {
			$id = $records[ $key ]['id']; $post = get_post( $id );
			if ( ! $post || 'jkit-template' !== $post->post_type || 'jkit-archive-template' !== get_post_meta( $id, 'jkit-template-type', true ) ) { self::fail(); }
			if ( ! empty( $state['finalized'] ) && (int) $post->menu_order !== $priority ) { throw new \RuntimeException( 'Archive priority changed after Shop finalization. Review it before retrying.' ); }
			if ( (int) $post->menu_order !== $priority ) {
				$result = wp_update_post( array( 'ID' => $id, 'menu_order' => $priority ), true );
				if ( is_wp_error( $result ) || ! $result || (int) get_post( $id )->menu_order !== $priority ) { throw new \RuntimeException( 'Shop archive priorities could not be saved.' ); }
			}
		}
		$inventory['shop']['finalized'] = true;
		update_option( essential_import_demo_key( $demo ), $inventory );
		if ( empty( get_option( essential_import_demo_key( $demo ) )['shop']['finalized'] ) ) { throw new \RuntimeException( 'Shop finalization state could not be saved.' ); }
	}

	/** Recover an insertion interrupted before its option inventory was saved. */
	private static function owned_post( $m, $key, $type ) {
		$posts = get_posts( array( 'post_type' => $type, 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ), 'numberposts' => 2, 'meta_query' => array( array( 'key' => 'jkit_shop_package', 'value' => $m['package'] ), array( 'key' => 'jkit_shop_record', 'value' => $key ) ) ) );
		if ( count( $posts ) > 1 ) { throw new \RuntimeException( 'Duplicate owned Shop objects need review before resuming.' ); }
		if ( $posts && 'publish' !== $posts[0]->post_status ) { throw new \RuntimeException( 'An owned Shop object changed status. Review it before resuming.' ); }
		return $posts ? (int) $posts[0]->ID : null;
	}

	public static function begin( $demo, $data, &$inventory ) {
		$m = self::validate_payload( $demo, $data );
		if ( ! defined( 'ELEMENTOR_VERSION' ) || ! defined( 'JEG_ELEMENTOR_KIT' ) || ! function_exists( 'WC' ) || ! function_exists( 'jkit_pro_get_archive_template' ) ) { throw new \RuntimeException( 'Activate Elementor, Jeg Kit, Jeg Kit Pro and WooCommerce before importing Shop content.' ); }
		self::check_archives( $m );
		$state = $inventory['shop'] ?? array();
		if ( $state && ( $state['manifest'] ?? null ) !== $m ) { self::fail(); }
		if ( ! $state ) { $state = array( 'manifest' => $m, 'records' => array() ); }
		$inventory['shop'] = $state;
		if ( empty( $state['identity'] ) ) {
			$previous = get_option( 'woocommerce_shop_page_id', null );
			$page = (int) $previous > 0 ? get_post( (int) $previous ) : null;
			$created = ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status;
			if ( ! $created && get_post_meta( $page->ID, 'jkit_shop_package', true ) ) { throw new \RuntimeException( 'The configured Shop page belongs to a recorded package. Review that package ownership before reusing it.' ); }
			if ( $created ) {
				$id = self::owned_post( $m, 'identity', 'page' );
				if ( ! $id ) { $id = wp_insert_post( wp_slash( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => sanitize_text_field( $m['identity']['title'] ), 'post_name' => sanitize_title( $m['identity']['slug'] ), 'post_content' => '', 'meta_input' => array( 'jkit_shop_package' => $m['package'], 'jkit_shop_record' => 'identity' ) ) ), true ); }
				if ( is_wp_error( $id ) || ! $id ) { throw new \RuntimeException( 'The WooCommerce Shop identity page could not be created.' ); }
			} else { $id = (int) $page->ID; }
			$inventory['shop']['identity'] = array( 'id' => $id, 'created' => $created, 'previous' => $previous, 'url' => get_permalink( $id ) );
			// Persist ownership before assigning the option or attempting the design insertion.
			update_option( essential_import_demo_key( $demo ), $inventory );
			if ( $created ) { update_option( 'woocommerce_shop_page_id', $id ); }
		}
		$identity = $inventory['shop']['identity']; $page = get_post( $identity['id'] );
		if ( ! $page || 'page' !== $page->post_type || 'publish' !== $page->post_status || (int) get_option( 'woocommerce_shop_page_id' ) !== $identity['id'] ) { throw new \RuntimeException( 'The destination Shop identity changed. Review it before resuming this package.' ); }
		if ( get_permalink( $page->ID ) !== ( $identity['url'] ?? null ) ) { throw new \RuntimeException( 'The destination Shop URL changed. Review its links before resuming.' ); }
		if ( $identity['created'] && get_post_meta( $page->ID, 'jkit_shop_package', true ) !== $m['package'] ) { throw new \RuntimeException( 'Shop identity ownership changed. Review it before resuming.' ); }
		$record = $inventory['shop']['records'][ $data['jkit_record_key'] ] ?? null;
		if ( ! $record && 'nav_menu' !== $data['type'] ) {
			$type = 'jkit-mega-menu' === $data['type'] ? 'elementor_library' : $data['type'];
			$id = self::owned_post( $m, $data['jkit_record_key'], $type );
			if ( $id ) {
				if ( get_post_meta( $id, 'jkit_shop_hash', true ) !== hash( 'sha256', wp_json_encode( $data ) ) ) { self::fail(); }
				self::record( $demo, $data, $id, $inventory );
				$record = $inventory['shop']['records'][ $data['jkit_record_key'] ];
			}
		}
		if ( $record ) {
			if ( $record['hash'] !== hash( 'sha256', wp_json_encode( $data ) ) ) { throw new \RuntimeException( 'A previously imported Shop package record changed. Re-export or clean up before retrying.' ); }
			$owner = 'nav_menu' === $record['type'] ? get_term_meta( $record['id'], 'jkit_shop_package', true ) : get_post_meta( $record['id'], 'jkit_shop_package', true );
			$exists = 'nav_menu' === $record['type'] ? wp_get_nav_menu_object( $record['id'] ) : get_post( $record['id'] );
			if ( ! $exists || is_wp_error( $exists ) || $owner !== $m['package'] ) { throw new \RuntimeException( 'An imported Shop record was removed or its ownership changed. Review it before retrying.' ); }
			if ( 'nav_menu' !== $record['type'] && ( 'publish' !== $exists->post_status || ( 'jkit-mega-menu' === $record['type'] ? 'elementor_library' : $record['type'] ) !== $exists->post_type ) ) { throw new \RuntimeException( 'An imported Shop record changed type or status. Review it before retrying.' ); }
			if ( $data['jkit_record_key'] === $m['layout']['record_key'] && ( get_post_meta( $record['id'], 'jkit-template-type', true ) !== 'jkit-archive-template' || get_post_meta( $record['id'], 'jkit-condition', true ) !== $data['jkit-condition'] ) ) { throw new \RuntimeException( 'The imported Shop template configuration changed. Review it before retrying.' ); }
			return $record['id'];
		}
		return null;
	}

	/** Save each new ID immediately; later feature errors must not orphan it. */
	public static function record( $demo, $data, $id, &$inventory ) {
		if ( is_wp_error( $id ) || ! $id ) { throw new \RuntimeException( 'Shop content could not be created. The recorded identity can be reviewed or cleaned up.' ); }
		$inventory['shop']['records'][ $data['jkit_record_key'] ] = array( 'id' => (int) $id, 'type' => $data['type'], 'slug' => $data['slug'], 'hash' => hash( 'sha256', wp_json_encode( $data ) ), 'complete' => false );
		if ( 'nav_menu' === $data['type'] ) {
			$inventory['shop']['records'][ $data['jkit_record_key'] ]['title'] = $data['title'];
			$inventory['shop']['records'][ $data['jkit_record_key'] ]['menu_items'] = $data['nav_menu_item_setting'];
		}
		update_option( essential_import_demo_key( $demo ), $inventory );
		if ( 'nav_menu' === $data['type'] ) { update_term_meta( $id, 'jkit_shop_package', $data['jkit_shop_transport']['package'] ); }
	}

	public static function complete_record( $demo, $data, &$inventory ) {
		$inventory['shop']['records'][ $data['jkit_record_key'] ]['complete'] = true;
		update_option( essential_import_demo_key( $demo ), $inventory );
	}

	/** Run before the legacy deletion inventory; never add reused identity to that list. */
	public static function cleanup( $demo, &$inventory ) {
		$state = $inventory['shop'] ?? array();
		if ( $state ) {
			$package = $state['manifest']['package'];
			foreach ( $state['records'] as $record ) {
				$id = $record['id'];
				$menu = 'nav_menu' === $record['type'];
				$owner = $menu ? get_term_meta( $id, 'jkit_shop_package', true ) : get_post_meta( $id, 'jkit_shop_package', true );
				if ( $package === $owner ) {
					$result = $menu ? wp_delete_nav_menu( $id ) : wp_delete_post( $id, true );
					if ( ! $result || is_wp_error( $result ) ) { throw new \RuntimeException( 'An owned Shop record could not be removed. Review the import inventory before retrying cleanup.' ); }
				}
				unset( $inventory['page'][ $id ], $inventory['menu'][ $id ], $inventory['mega-menu'][ $id ] );
			}
			$identity = $state['identity'] ?? null;
			if ( $identity ) {
				$id = $identity['id']; unset( $inventory['page'][ $id ] );
				if ( $identity['created'] && get_post_meta( $id, 'jkit_shop_package', true ) === $package ) {
					if ( (int) get_option( 'woocommerce_shop_page_id' ) === $id ) {
						if ( null === $identity['previous'] ) { delete_option( 'woocommerce_shop_page_id' ); }
						else { update_option( 'woocommerce_shop_page_id', $identity['previous'] ); }
					}
					if ( get_post_meta( $id, 'jkit_shop_package', true ) === $package && ! wp_delete_post( $id, true ) ) { throw new \RuntimeException( 'The owned Shop identity could not be removed.' ); }
				}
			}
			unset( $inventory['shop'] );
		}
		delete_option( self::context_key( $demo ) );
		update_option( essential_import_demo_key( $demo ), $inventory );
	}
}
