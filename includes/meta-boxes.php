<?php
/**
 * Meta boxes (posts) and term fields (destinations).
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes', function () {
	$titles = array(
		'gp_stay'        => __( 'Stay details', 'gp-house-core' ),
		'gp_testimonial' => __( 'Guest details', 'gp-house-core' ),
		'post'           => __( 'Journal story details', 'gp-house-core' ),
	);
	foreach ( $titles as $type => $title ) {
		add_meta_box( 'gp-core-details', $title, 'gp_core_render_meta_box', $type, 'normal', 'high' );
	}
} );

function gp_core_render_meta_box( WP_Post $post ) {
	wp_nonce_field( 'gp_core_save', 'gp_core_nonce' );
	echo '<div class="gp-core-grid">';
	foreach ( gp_core_fields()[ $post->post_type ] as $key => $field ) {
		gp_core_render_field( $key, $field, get_post_meta( $post->ID, $key, true ) );
	}
	echo '</div>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	$fields = gp_core_fields()[ $post->post_type ] ?? null;
	if (
		! $fields
		|| ! isset( $_POST['gp_core_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['gp_core_nonce'] ), 'gp_core_save' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}
	foreach ( gp_core_submitted_values( $fields ) as $key => $value ) {
		if ( '' === $value || array() === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}, 10, 2 );

/* Destination term fields ------------------------------------------------- */

add_action( 'gp_destination_add_form_fields', function () {
	wp_nonce_field( 'gp_core_save', 'gp_core_nonce' );
	foreach ( gp_core_fields()['term:gp_destination'] as $key => $field ) {
		echo '<div class="form-field">';
		gp_core_render_field( $key, $field, '' );
		echo '</div>';
	}
} );

add_action( 'gp_destination_edit_form_fields', function ( WP_Term $term ) {
	wp_nonce_field( 'gp_core_save', 'gp_core_nonce' );
	foreach ( gp_core_fields()['term:gp_destination'] as $key => $field ) {
		echo '<tr class="form-field"><th scope="row"></th><td>';
		gp_core_render_field( $key, $field, get_term_meta( $term->term_id, $key, true ) );
		echo '</td></tr>';
	}
} );

$gp_core_save_term = function ( $term_id ) {
	if (
		! isset( $_POST['gp_core_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( $_POST['gp_core_nonce'] ), 'gp_core_save' )
		|| ! current_user_can( 'manage_categories' )
	) {
		return;
	}
	foreach ( gp_core_submitted_values( gp_core_fields()['term:gp_destination'] ) as $key => $value ) {
		if ( '' === $value ) {
			delete_term_meta( $term_id, $key );
		} else {
			update_term_meta( $term_id, $key, $value );
		}
	}
};
add_action( 'created_gp_destination', $gp_core_save_term );
add_action( 'edited_gp_destination', $gp_core_save_term );

/* Admin list columns for stays -------------------------------------------- */

add_filter( 'manage_gp_stay_posts_columns', function ( $cols ) {
	$cols['gp_price']    = __( 'Price', 'gp-house-core' );
	$cols['gp_featured'] = __( 'Featured', 'gp-house-core' );
	$cols['gp_map']      = __( 'On map', 'gp-house-core' );
	return $cols;
} );

add_action( 'manage_gp_stay_posts_custom_column', function ( $col, $post_id ) {
	if ( 'gp_price' === $col ) {
		$price = get_post_meta( $post_id, 'gp_price', true );
		echo $price ? esc_html( '$' . number_format_i18n( (float) $price ) ) : '—';
	} elseif ( 'gp_featured' === $col ) {
		echo get_post_meta( $post_id, 'gp_featured', true ) ? '✓' : '—';
	} elseif ( 'gp_map' === $col ) {
		echo get_post_meta( $post_id, 'gp_show_on_map', true ) ? '✓' : '—';
	}
}, 10, 2 );
