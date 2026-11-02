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

	register_taxonomy( 'gp_destination', array( 'gp_stay', 'post' ), array(
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
