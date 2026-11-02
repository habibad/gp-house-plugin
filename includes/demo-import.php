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
	$counts = array( 'destinations' => 0, 'stays' => 0, 'testimonials' => 0, 'stories' => 0 );

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

	// Journal stories.
	foreach ( $content['journal'] as $j ) {
		$id = gp_core_upsert_post( array(
			'post_type'     => 'post',
			'post_status'   => 'publish',
			'post_name'     => $j['slug'],
			'post_title'    => $j['title'],
			'post_excerpt'  => $j['excerpt'],
			'post_content'  => $j['excerpt'],
			'post_date'     => $j['date'],
			'post_date_gmt' => get_gmt_from_date( $j['date'] ),
		) );
		if ( is_wp_error( $id ) ) {
			continue;
		}
		wp_set_object_terms( $id, $j['destination'], 'gp_destination' );
		update_post_meta( $id, 'gp_read_time', $j['read_time'] );
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

	update_option( 'gp_core_demo_imported', time(), false );

	return array(
		'ok'      => true,
		/* translators: 1: destinations, 2: stays, 3: guest stories, 4: journal stories */
		'message' => sprintf( __( 'Imported %1$d destinations, %2$d stays, %3$d guest stories and %4$d journal stories.', 'gp-house-core' ), $counts['destinations'], $counts['stays'], $counts['testimonials'], $counts['stories'] ),
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
