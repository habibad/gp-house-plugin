<?php
/**
 * Post types and taxonomy.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

function gp_core_register_content_types() {
	register_post_type( 'gp_stay', array(
		'labels'        => array(
			'name'          => __( 'Stays', 'gp-house-core' ),
			'singular_name' => __( 'Stay', 'gp-house-core' ),
			'add_new_item'  => __( 'Add New Stay', 'gp-house-core' ),
			'edit_item'     => __( 'Edit Stay', 'gp-house-core' ),
			'all_items'     => __( 'All Stays', 'gp-house-core' ),
		),
		'public'        => true,
		'has_archive'   => 'stays',
		'rewrite'       => array( 'slug' => 'stays', 'with_front' => false ),
		'menu_icon'     => 'dashicons-building',
		'menu_position' => 5,
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'gp_testimonial', array(
		'labels'              => array(
			'name'          => __( 'Guest Stories', 'gp-house-core' ),
			'singular_name' => __( 'Guest Story', 'gp-house-core' ),
			'add_new_item'  => __( 'Add Guest Story', 'gp-house-core' ),
			'edit_item'     => __( 'Edit Guest Story', 'gp-house-core' ),
		),
		'public'              => false,
		'show_ui'             => true,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-format-quote',
		'menu_position'       => 6,
		'show_in_rest'        => true,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'gp_room', array(
		'labels'        => array(
			'name'          => __( 'Rooms & Suites', 'gp-house-core' ),
			'singular_name' => __( 'Room', 'gp-house-core' ),
			'add_new_item'  => __( 'Add New Room', 'gp-house-core' ),
			'edit_item'     => __( 'Edit Room', 'gp-house-core' ),
			'all_items'     => __( 'All Rooms', 'gp-house-core' ),
		),
		'description'   => __( 'Bookable room types. Each maps to one eZee room type.', 'gp-house-core' ),
		'public'        => true,
		'has_archive'   => false,
		'rewrite'       => array( 'slug' => 'rooms', 'with_front' => false ),
		'menu_icon'     => 'dashicons-admin-home',
		'menu_position' => 5,
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'editor', 'excerpt', 'page-attributes' ),
	) );

	// Stays page filters: "Room", "Bed" dropdowns and the amenity chips (Pool, Breakfast…).
	$filters = array(
		'gp_room_type' => array( __( 'Room Types', 'gp-house-core' ), __( 'Room Type', 'gp-house-core' ) ),
		'gp_bed_type'  => array( __( 'Bed Types', 'gp-house-core' ), __( 'Bed Type', 'gp-house-core' ) ),
		'gp_room_tag'  => array( __( 'Amenity Filters', 'gp-house-core' ), __( 'Amenity Filter', 'gp-house-core' ) ),
	);
	foreach ( $filters as $tax => $names ) {
		register_taxonomy( $tax, 'gp_room', array(
			'labels'            => array( 'name' => $names[0], 'singular_name' => $names[1] ),
			'public'            => false,
			'show_ui'           => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
		) );
	}

	register_post_type( 'gp_offer', array(
		'labels'        => array(
			'name'          => __( 'Offers', 'gp-house-core' ),
			'singular_name' => __( 'Offer', 'gp-house-core' ),
			'add_new_item'  => __( 'Add New Offer', 'gp-house-core' ),
			'edit_item'     => __( 'Edit Offer', 'gp-house-core' ),
			'all_items'     => __( 'All Offers', 'gp-house-core' ),
		),
		'description'   => __( 'Promotions shown on the Offers page. The promo code is applied in eZee.', 'gp-house-core' ),
		'public'        => false,
		'show_ui'       => true,
		'menu_icon'     => 'dashicons-tickets-alt',
		'menu_position' => 7,
		'show_in_rest'  => true,
		'supports'      => array( 'title', 'excerpt', 'thumbnail', 'page-attributes' ),
	) );

	register_taxonomy( 'gp_offer_type', 'gp_offer', array(
		'labels'            => array( 'name' => __( 'Offer Types', 'gp-house-core' ), 'singular_name' => __( 'Offer Type', 'gp-house-core' ) ),
		'public'            => false,
		'show_ui'           => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
	) );

	register_taxonomy( 'gp_destination', array( 'gp_stay', 'gp_room', 'post' ), array(
		'labels'            => array(
			'name'          => __( 'Destinations', 'gp-house-core' ),
			'singular_name' => __( 'Destination', 'gp-house-core' ),
			'add_new_item'  => __( 'Add Destination', 'gp-house-core' ),
		),
		'public'            => true,
		'hierarchical'      => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'destinations', 'with_front' => false ),
	) );
}
add_action( 'init', 'gp_core_register_content_types' );

/** Admin title placeholders. */
add_filter( 'enter_title_here', function ( $text, $post ) {
	return 'gp_testimonial' === $post->post_type ? __( 'Guest name', 'gp-house-core' ) : $text;
}, 10, 2 );

/*
 * Journal stories are regular posts served under /journal/<slug>/ (Figma "Journal / Overview").
 * The /journal/ listing itself is a page using the theme's page-journal.php.
 */
add_action( 'init', function () {
	add_rewrite_rule( '^journal/([^/]+)/?$', 'index.php?name=$matches[1]', 'top' );
} );

add_filter( 'post_link', function ( $link, $post ) {
	if ( 'post' !== $post->post_type || 'publish' !== $post->post_status || ! get_option( 'permalink_structure' ) ) {
		return $link;
	}
	return home_url( user_trailingslashit( 'journal/' . $post->post_name ) );
}, 10, 2 );

/* Old /<slug>/ story links keep working: send them to the /journal/ address. */
add_action( 'template_redirect', function () {
	if ( is_singular( 'post' ) && get_option( 'permalink_structure' ) ) {
		$path = trim( (string) wp_parse_url( add_query_arg( array() ), PHP_URL_PATH ), '/' );
		$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$path = ltrim( substr( $path, strlen( $base ) ), '/' );
		if ( 0 !== strpos( $path, 'journal/' ) ) {
			wp_safe_redirect( get_permalink(), 301 );
			exit;
		}
	}
} );
