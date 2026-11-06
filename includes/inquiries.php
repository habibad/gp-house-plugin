<?php
/**
 * Owner inquiries (Investor Relations form): stored as private "Owner Inquiry"
 * entries and emailed to the site admin. No default WordPress form plugins.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'gp_inquiry', array(
		'labels'          => array( 'name' => __( 'Owner Inquiries', 'gp-house-core' ), 'singular_name' => __( 'Owner Inquiry', 'gp-house-core' ) ),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

/** Form fields: key => label. */
function gp_core_inquiry_fields() {
	return array(
		'name'     => __( 'Full name', 'gp-house-core' ),
		'company'  => __( 'Ownership entity / company', 'gp-house-core' ),
		'email'    => __( 'Email', 'gp-house-core' ),
		'phone'    => __( 'Phone / WhatsApp', 'gp-house-core' ),
		'property' => __( 'Property name', 'gp-house-core' ),
		'location' => __( 'Location / island', 'gp-house-core' ),
		'keys'     => __( 'Current room keys', 'gp-house-core' ),
		'model'    => __( 'Management model', 'gp-house-core' ),
		'goals'    => __( 'Overview & goals', 'gp-house-core' ),
	);
}

function gp_core_handle_inquiry() {
	$back = wp_get_referer() ?: home_url( '/investor-relations/' );
	$back = remove_query_arg( 'inquiry', $back );

	if ( ! isset( $_POST['gp_inquiry_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gp_inquiry_nonce'] ) ), 'gp_inquiry' ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'error', $back ) . '#inquiry' );
		exit;
	}
	// Honeypot: bots fill the hidden field.
	if ( ! empty( $_POST['website'] ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#inquiry' );
		exit;
	}

	$data = array();
	foreach ( array_keys( gp_core_inquiry_fields() ) as $key ) {
		$raw          = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		$data[ $key ] = 'goals' === $key ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
	}
	if ( '' === $data['name'] || ! is_email( $data['email'] ) ) {
		wp_safe_redirect( add_query_arg( 'inquiry', 'invalid', $back ) . '#inquiry' );
		exit;
	}

	$id = wp_insert_post( array( 'post_type' => 'gp_inquiry', 'post_status' => 'publish', 'post_title' => $data['property'] ? $data['property'] . ' — ' . $data['name'] : $data['name'] ) );
	$lines = array();
	foreach ( gp_core_inquiry_fields() as $key => $label ) {
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, 'gp_inq_' . $key, $data[ $key ] );
		}
		$lines[] = $label . ': ' . $data[ $key ];
	}
	wp_mail( get_option( 'admin_email' ), sprintf( '[%s] New owner inquiry — %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $data['name'] ), implode( "\n", $lines ), array( 'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>' ) );

	wp_safe_redirect( add_query_arg( 'inquiry', 'sent', $back ) . '#inquiry' );
	exit;
}
add_action( 'admin_post_nopriv_gp_inquiry', 'gp_core_handle_inquiry' );
add_action( 'admin_post_gp_inquiry', 'gp_core_handle_inquiry' );

/* Read-only details on the inquiry edit screen. */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'gp_inquiry_details', __( 'Inquiry details', 'gp-house-core' ), function ( $post ) {
		echo '<table class="widefat striped"><tbody>';
		foreach ( gp_core_inquiry_fields() as $key => $label ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) get_post_meta( $post->ID, 'gp_inq_' . $key, true ) ) ) );
		}
		echo '</tbody></table>';
	}, 'gp_inquiry' );
} );
