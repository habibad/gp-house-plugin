<?php
/**
 * Demo content import (Tools → GP House Demo).
 *
 * The active theme supplies the content through the `gp_house_demo_content`
 * filter. Import is idempotent: items are matched by slug, images by source file.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

/** Copy a theme image into the Media Library once; returns attachment ID. */
function gp_core_import_image( $base, $relative ) {
	if ( ! $relative ) {
		return 0;
	}
	$map = get_option( 'gp_core_demo_media', array() );
	if ( ! empty( $map[ $relative ] ) && get_post( $map[ $relative ] ) ) {
		return (int) $map[ $relative ];
	}
	$path = $base . $relative;
	if ( ! file_exists( $path ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$upload = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}
	$type = wp_check_filetype( $upload['file'] );
	$id   = wp_insert_attachment( array(
		'post_mime_type' => $type['type'],
		'post_title'     => sanitize_title( pathinfo( $path, PATHINFO_FILENAME ) ),
		'post_status'    => 'inherit',
	), $upload['file'] );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );

	$map[ $relative ] = $id;
	update_option( 'gp_core_demo_media', $map, false );
	return (int) $id;
}

/** Insert or update a post matched by slug + type. */
function gp_core_upsert_post( $postarr ) {
	$existing = get_page_by_path( $postarr['post_name'], OBJECT, $postarr['post_type'] );
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
	}
	return wp_insert_post( wp_slash( $postarr ), true );
}

/** @return array{ok: bool, message: string} */
function gp_core_import_demo() {
	$content = apply_filters( 'gp_house_demo_content', array() );
	if ( empty( $content['base'] ) ) {
		return array( 'ok' => false, 'message' => __( 'The active theme does not provide demo content. Activate the GP House theme first.', 'gp-house-core' ) );
	}
	$base   = trailingslashit( $content['base'] );
	$counts = array( 'destinations' => 0, 'stays' => 0, 'rooms' => 0, 'testimonials' => 0, 'stories' => 0, 'offers' => 0 );

	// Destinations.
	foreach ( $content['destinations'] as $order => $d ) {
		$term = term_exists( $d['slug'], 'gp_destination' );
		if ( ! $term ) {
			$term = wp_insert_term( $d['name'], 'gp_destination', array( 'slug' => $d['slug'] ) );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$term_id = (int) $term['term_id'];
		update_term_meta( $term_id, 'gp_card_title', $d['card_title'] );
		update_term_meta( $term_id, 'gp_subtitle', $d['subtitle'] );
		update_term_meta( $term_id, 'gp_order', (string) ( $order + 1 ) );
		$image = gp_core_import_image( $base, $d['image'] );
		if ( $image ) {
			update_term_meta( $term_id, 'gp_image_id', (string) $image );
		}
		if ( ! empty( $content['destination_pages'][ $d['slug'] ] ) ) {
			$pg = $content['destination_pages'][ $d['slug'] ];
			update_term_meta( $term_id, 'gp_page_hero_text', $pg['hero_text'] );
			update_term_meta( $term_id, 'gp_page_hero_id', (string) gp_core_import_image( $base, $pg['hero'] ) );
			update_term_meta( $term_id, 'gp_insider_image_id', (string) gp_core_import_image( $base, $pg['insider_image'] ) );
			update_term_meta( $term_id, 'gp_page_hero_shaded', empty( $pg['hero_shaded'] ) ? '' : '1' );
			update_term_meta( $term_id, 'gp_page_hero_mobile_id', (string) gp_core_import_image( $base, $pg['hero_mobile'] ?? '' ) );
			update_term_meta( $term_id, 'gp_insider_mobile_id', (string) gp_core_import_image( $base, $pg['insider_mobile'] ?? '' ) );
			update_term_meta( $term_id, 'gp_insider_items', array_map( function ( $i ) {
				return implode( ' | ', $i );
			}, $pg['insider'] ) );
			update_term_meta( $term_id, 'gp_faqs', array_map( function ( $q ) {
				return implode( ' | ', $q );
			}, $pg['faqs'] ) );
		}
		if ( ! empty( $content['regions'][ $d['slug'] ] ) ) {
			$r = $content['regions'][ $d['slug'] ];
			update_term_meta( $term_id, 'gp_region_title', $r['title'] );
			update_term_meta( $term_id, 'gp_region_text', $r['text'] );
			update_term_meta( $term_id, 'gp_from_price', (string) $r['price'] );
			update_term_meta( $term_id, 'gp_region_image_id', (string) gp_core_import_image( $base, $r['image'] ) );
		}
		++$counts['destinations'];
	}

	// Stays.
	foreach ( $content['stays'] as $order => $s ) {
		$id = gp_core_upsert_post( array(
			'post_type'    => 'gp_stay',
			'post_status'  => 'publish',
			'post_name'    => $s['slug'],
			'post_title'   => $s['title'],
			'post_excerpt' => $s['excerpt'],
			'post_content' => $s['excerpt'],
			'menu_order'   => $order,
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $s['destination'], 'gp_destination' );
		$meta = array(
			'gp_tagline'     => $s['tagline'],
			'gp_price'       => (string) $s['price'],
			'gp_reviews'     => (string) $s['reviews'],
			'gp_amenities'   => $s['amenities'],
			'gp_featured'    => empty( $s['featured'] ) ? '' : '1',
			'gp_show_on_map' => empty( $s['map'] ) ? '' : '1',
			'gp_map_active'  => empty( $s['map_active'] ) ? '' : '1',
			'gp_pin_x'       => isset( $s['pin'][0] ) ? (string) $s['pin'][0] : '',
			'gp_pin_y'       => isset( $s['pin'][1] ) ? (string) $s['pin'][1] : '',
			'gp_pin_mx'      => isset( $s['pin_mobile'][0] ) ? (string) $s['pin_mobile'][0] : '',
			'gp_pin_my'      => isset( $s['pin_mobile'][1] ) ? (string) $s['pin_mobile'][1] : '',
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		$featured = gp_core_import_image( $base, $s['image'] );
		if ( $featured ) {
			set_post_thumbnail( $id, $featured );
		}
		$thumb = gp_core_import_image( $base, $s['thumb'] );
		if ( $thumb ) {
			update_post_meta( $id, 'gp_thumb_id', (string) $thumb );
		}
		if ( ! empty( $s['image_main'] ) ) {
			update_post_meta( $id, 'gp_main_image_id', (string) gp_core_import_image( $base, $s['image_main'] ) );
		}
		++$counts['stays'];
	}

	// Rooms & suites (Stays page).
	$filter_terms = array(
		'gp_room_type' => array( 'deluxe' => 'Deluxe Rooms', 'suite' => 'Suites', 'villa' => 'Pool Villas', 'residence' => 'Family Residences' ),
		'gp_bed_type'  => array( 'king' => 'King Bed', 'twin' => 'Twin Beds' ),
		'gp_room_tag'  => array( 'pool' => 'Pool', 'breakfast' => 'Breakfast', 'spa' => 'Spa', 'balcony' => 'Balcony' ),
	);
	foreach ( $filter_terms as $tax => $terms ) {
		foreach ( $terms as $slug => $name ) {
			if ( ! term_exists( $slug, $tax ) ) {
				wp_insert_term( $name, $tax, array( 'slug' => $slug ) );
			}
		}
	}
	foreach ( $content['rooms'] ?? array() as $order => $r ) {
		$id = gp_core_upsert_post( array(
			'post_type'    => 'gp_room',
			'post_status'  => 'publish',
			'post_name'    => $r['slug'],
			'post_title'   => $r['title'],
			'post_excerpt' => $r['excerpt'],
			'post_content' => $r['excerpt'],
			'menu_order'   => $order,
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $r['destination'], 'gp_destination' );
		wp_set_object_terms( $id, $r['type'], 'gp_room_type' );
		wp_set_object_terms( $id, $r['beds'], 'gp_bed_type' );
		wp_set_object_terms( $id, $r['tags'], 'gp_room_tag' );
		$gallery = array();
		foreach ( $r['gallery'] as $n ) {
			$gallery[] = gp_core_import_image( $base, "stays/{$r['slug']}-{$n}.jpg" );
		}
		$meta = array(
			'gp_highlights'     => $r['highlights'],
			'gp_area'           => $r['area'],
			'gp_guests_label'   => $r['guests'],
			'gp_max_guests'     => (string) $r['max_guests'],
			'gp_bed_label'      => $r['bed'],
			'gp_view'           => $r['view'],
			'gp_price'          => (string) $r['price'],
			'gp_gallery'        => implode( ',', array_filter( $gallery ) ),
			'gp_gallery_active' => (string) $r['active'],
			'gp_property'       => $r['property'] ?? '',
			'gp_hero_text'      => $r['hero_text'] ?? '',
			'gp_concept'        => $r['concept'] ?? '',
			'gp_features'       => $r['features'] ?? array(),
			'gp_amenities_intro' => $r['amenities_intro'] ?? '',
		);
		$detail = array();
		foreach ( $r['detail_gallery'] ?? array() as $n ) {
			$detail[] = gp_core_import_image( $base, "stays/{$r['slug']}-{$n}.jpg" );
		}
		foreach ( array( 'gp_hero_image_id' => 'hero', 'gp_hero_mobile_id' => 'hero-mobile' ) as $key => $suffix ) {
			$meta[ $key ] = (string) gp_core_import_image( $base, "stays/{$r['slug']}-{$suffix}.jpg" );
		}
		if ( isset( $r['detail_gallery'], $r['detail_active'] ) ) {
			$meta['gp_main_mobile_id'] = (string) gp_core_import_image( $base, "stays/{$r['slug']}-{$r['detail_gallery'][ $r['detail_active'] ]}-mobile.jpg" );
		}
		$meta['gp_detail_gallery'] = implode( ',', array_filter( $detail ) );
		$meta['gp_detail_active']  = isset( $r['detail_active'] ) ? (string) $r['detail_active'] : '';
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		++$counts['rooms'];
	}

	// Offers.
	foreach ( array( 'room-only' => 'Room Only', 'packages' => 'Packages' ) as $slug => $name ) {
		if ( ! term_exists( $slug, 'gp_offer_type' ) ) {
			wp_insert_term( $name, 'gp_offer_type', array( 'slug' => $slug ) );
		}
	}
	foreach ( $content['offers'] ?? array() as $order => $o ) {
		$id = gp_core_upsert_post( array(
			'post_type'    => 'gp_offer',
			'post_status'  => 'publish',
			'post_name'    => $o['slug'],
			'post_title'   => $o['title'],
			'post_excerpt' => $o['excerpt'],
			'menu_order'   => $order,
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $o['type'], 'gp_offer_type' );
		update_post_meta( $id, 'gp_offer_code', $o['code'] );
		update_post_meta( $id, 'gp_offer_save', $o['save'] );
		update_post_meta( $id, 'gp_offer_highlights', $o['highlights'] );
		$image = gp_core_import_image( $base, $o['image'] );
		if ( $image ) {
			set_post_thumbnail( $id, $image );
		}
		update_post_meta( $id, 'gp_offer_mobile_id', (string) gp_core_import_image( $base, $o['image_mobile'] ?? '' ) );
		++$counts['offers'];
	}

	// Testimonials (slug is generated from position so re-import updates in place).
	foreach ( $content['testimonials'] as $order => $t ) {
		$id = gp_core_upsert_post( array(
			'post_type'    => 'gp_testimonial',
			'post_status'  => 'publish',
			'post_name'    => 'guest-story-' . ( $order + 1 ),
			'post_title'   => $t['name'],
			'post_content' => $t['quote'],
			'menu_order'   => $order,
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		update_post_meta( $id, 'gp_location', $t['location'] );
		$avatar = gp_core_import_image( $base, $t['avatar'] );
		if ( $avatar ) {
			set_post_thumbnail( $id, $avatar );
		}
		++$counts['testimonials'];
	}

	// Journal topics (categories, in Figma tab order).
	foreach ( $content['journal_topics'] ?? array() as $slug => $name ) {
		if ( ! get_term_by( 'slug', $slug, 'category' ) ) {
			wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		}
	}

	// Journal stories.
	$sticky = array();
	foreach ( $content['journal'] as $j ) {
		// Story body: {{img:path}} tokens become imported image URLs.
		$body = preg_replace_callback( '/\{\{img:([^}]+)\}\}/', function ( $m ) use ( $base ) {
			$att = gp_core_import_image( $base, $m[1] );
			return $att ? (string) wp_get_attachment_image_url( $att, 'large' ) : '';
		}, $j['content'] ?? '' );
		$id = gp_core_upsert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_name'     => $j['slug'],
			'post_title'    => $j['title'],
			'post_excerpt'  => $j['excerpt'],
			'post_content'  => $body ? $body : $j['excerpt'],
			'post_date'     => $j['date'],
			'post_date_gmt' => get_gmt_from_date( $j['date'] ),
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $j['destination'], 'gp_destination' );
		if ( ! empty( $j['topic'] ) ) {
			$topic = get_term_by( 'slug', $j['topic'], 'category' );
			if ( $topic ) {
				wp_set_post_categories( $id, array( (int) $topic->term_id ) );
			}
		}
		if ( ! empty( $j['sticky'] ) ) {
			$sticky[] = $id;
		}
		update_post_meta( $id, 'gp_story_image_id', empty( $j['story_image'] ) ? '' : (string) gp_core_import_image( $base, $j['story_image'] ) );
		update_post_meta( $id, 'gp_author_photo_id', empty( $j['author_photo'] ) ? '' : (string) gp_core_import_image( $base, $j['author_photo'] ) );
		update_post_meta( $id, 'gp_read_time', $j['read_time'] );
		update_post_meta( $id, 'gp_home_featured', empty( $j['home'] ) ? '' : '1' );
		update_post_meta( $id, 'gp_card_image_id', empty( $j['card_image'] ) ? '' : (string) gp_core_import_image( $base, $j['card_image'] ) );
		update_post_meta( $id, 'gp_author_name', $j['author_name'] );
		update_post_meta( $id, 'gp_author_role', $j['author_role'] );
		$image = gp_core_import_image( $base, $j['image'] );
		if ( $image ) {
			set_post_thumbnail( $id, $image );
		}
		$avatar = gp_core_import_image( $base, $j['author_avatar'] );
		if ( $avatar ) {
			update_post_meta( $id, 'gp_author_avatar_id', (string) $avatar );
		}
		++$counts['stories'];
	}

	if ( $sticky ) {
		update_option( 'sticky_posts', array_values( array_unique( array_merge( array_map( 'intval', (array) get_option( 'sticky_posts', array() ) ), $sticky ) ) ) );
	}

	// Pages whose templates live in the theme (page-<slug>.php).
	foreach ( $content['pages'] ?? array() as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) {
			wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => (string) ( $content['page_content'][ $slug ] ?? '' ) ) );
		}
	}

	update_option( 'gp_core_demo_imported', time(), false );
	flush_rewrite_rules();

	return array(
		'ok'      => true,
		/* translators: 1: destinations, 2: stays, 3: rooms, 4: offers, 5: guest stories, 6: journal stories */
		'message' => sprintf( __( 'Imported %1$d destinations, %2$d stays, %3$d rooms, %4$d offers, %5$d guest stories and %6$d journal stories.', 'gp-house-core' ), $counts['destinations'], $counts['stays'], $counts['rooms'], $counts['offers'], $counts['testimonials'], $counts['stories'] ),
	);
}

/* Admin page --------------------------------------------------------------- */

add_action( 'admin_menu', function () {
	add_management_page( __( 'GP House Demo', 'gp-house-core' ), __( 'GP House Demo', 'gp-house-core' ), 'manage_options', 'gp-house-demo', 'gp_core_demo_page' );
} );

function gp_core_demo_page() {
	$result = null;
	if ( isset( $_POST['gp_core_import'] ) && check_admin_referer( 'gp_core_import' ) && current_user_can( 'manage_options' ) ) {
		$result = gp_core_import_demo();
	}
	$imported = get_option( 'gp_core_demo_imported' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GP House demo content', 'gp-house-core' ); ?></h1>
		<?php if ( $result ) : ?>
			<div class="notice notice-<?php echo $result['ok'] ? 'success' : 'error'; ?>"><p><?php echo esc_html( $result['message'] ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Imports the destinations, stays, guest stories and journal stories shown in the Figma design, with their images, so you can edit them in wp-admin. Running it again updates the same items instead of duplicating them.', 'gp-house-core' ); ?></p>
		<?php if ( $imported ) : ?>
			<p><em><?php printf( esc_html__( 'Last imported: %s', 'gp-house-core' ), esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $imported ) ) ); ?></em></p>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'gp_core_import' ); ?>
			<?php submit_button( $imported ? __( 'Re-import demo content', 'gp-house-core' ) : __( 'Import demo content', 'gp-house-core' ), 'primary', 'gp_core_import' ); ?>
		</form>
	</div>
	<?php
}

add_action( 'admin_notices', function () {
	if ( get_option( 'gp_core_demo_imported' ) || ! current_user_can( 'manage_options' ) || 'tools_page_gp-house-demo' === get_current_screen()->id ) {
		return;
	}
	printf(
		'<div class="notice notice-info"><p>%s <a href="%s">%s</a></p></div>',
		esc_html__( 'GP House Core is active.', 'gp-house-core' ),
		esc_url( admin_url( 'tools.php?page=gp-house-demo' ) ),
		esc_html__( 'Import the demo content from the Figma design →', 'gp-house-core' )
	);
} );
