<?php
/**
 * Guest reviews — signed-in members write a review on /write-a-review/; it is stored as a *pending*
 * Guest Story (gp_testimonial). An administrator approves it (Guest Stories → Approve, or Publish)
 * and only then does it appear in the "Guest Stories" list on the home page.
 *
 * What is stored for the public card: name + email come from the account, location and the review
 * text from the form, the photo from the account or an optional upload.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

const GP_REVIEW_MIN_CHARS = 20;
const GP_REVIEW_MAX_CHARS = 700;

/** URL of the review form. */
function gp_core_review_url( $args = array() ) {
	$url = home_url( '/write-a-review/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

/** Portrait of a member (attachment ID), or 0. */
function gp_core_user_avatar_id( $user_id ) {
	return (int) get_user_meta( $user_id, 'gp_avatar_id', true );
}

/* Submit -------------------------------------------------------------------- */

add_action( 'admin_post_nopriv_gp_review_submit', function () {
	wp_safe_redirect( gp_core_auth_url( 'signin', array( 'redirect_to' => rawurlencode( gp_core_review_url() ) ) ) );
	exit;
} );
add_action( 'admin_post_gp_review_submit', 'gp_core_handle_review' );

function gp_core_review_back( $code ) {
	wp_safe_redirect( gp_core_review_url( array( 'review' => $code ) ) );
	exit;
}

function gp_core_handle_review() {
	if ( ! isset( $_POST['gp_review_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gp_review_nonce'] ) ), 'gp_review' ) ) {
		gp_core_review_back( 'expired' );
	}
	$user = wp_get_current_user();
	if ( ! $user->ID ) {
		gp_core_review_back( 'login' );
	}
	if ( ! empty( $_POST['website'] ) ) { // Honeypot.
		gp_core_review_back( 'sent' );
	}

	$location = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
	$stay     = isset( $_POST['stay'] ) ? sanitize_text_field( wp_unslash( $_POST['stay'] ) ) : '';
	$text     = isset( $_POST['review'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review'] ) ) : '';
	$text     = trim( preg_replace( '/\s+/', ' ', $text ) );
	$len      = mb_strlen( $text );

	if ( mb_strlen( $location ) < 3 || mb_strlen( $location ) > 80 ) {
		gp_core_review_back( 'location' );
	}
	if ( $len < GP_REVIEW_MIN_CHARS || $len > GP_REVIEW_MAX_CHARS ) {
		gp_core_review_back( 'text' );
	}
	if ( empty( $_POST['consent'] ) ) {
		gp_core_review_back( 'consent' );
	}
	$name = trim( $user->display_name ?: $user->first_name );
	if ( mb_strlen( $name ) < 2 ) {
		gp_core_review_back( 'name' );
	}

	// At most 3 reviews waiting for approval, and one submission every 5 minutes.
	$pending = get_posts( array( 'post_type' => 'gp_testimonial', 'post_status' => 'pending', 'author' => $user->ID, 'numberposts' => 4, 'fields' => 'ids' ) );
	if ( count( $pending ) >= 3 || ! gp_core_rate_limit( 'review|' . $user->ID, 1, 5 * MINUTE_IN_SECONDS ) ) {
		gp_core_review_back( 'limit' );
	}

	$post_id = wp_insert_post( array(
		'post_type'    => 'gp_testimonial',
		'post_status'  => 'pending',
		'post_author'  => $user->ID,
		'post_title'   => $name,
		'post_content' => $text,
	), true );
	if ( is_wp_error( $post_id ) ) {
		gp_core_review_back( 'error' );
	}
	update_post_meta( $post_id, 'gp_location', $location );
	update_post_meta( $post_id, 'gp_review_stay', mb_substr( $stay, 0, 80 ) );
	update_post_meta( $post_id, 'gp_review_user', $user->ID );
	update_post_meta( $post_id, 'gp_review_email', $user->user_email );
	update_user_meta( $user->ID, 'gp_location', $location ); // Pre-fills the next review / profile.

	// Photo: the member's portrait, or an optional upload (jpg / png / webp, 2 MB).
	$avatar_id = gp_core_user_avatar_id( $user->ID );
	if ( ! $avatar_id && ! empty( $_FILES['photo']['name'] ) && UPLOAD_ERR_OK === (int) $_FILES['photo']['error'] ) {
		$ok = in_array( $_FILES['photo']['type'], array( 'image/jpeg', 'image/png', 'image/webp' ), true ) && $_FILES['photo']['size'] <= 2 * MB_IN_BYTES;
		if ( $ok ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$att = media_handle_upload( 'photo', $post_id, array( 'post_title' => $name ), array( 'test_form' => false, 'mimes' => array( 'jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) ) );
			if ( ! is_wp_error( $att ) ) {
				$avatar_id = (int) $att;
				update_user_meta( $user->ID, 'gp_avatar_id', $avatar_id );
			}
		}
	}
	if ( $avatar_id ) {
		set_post_thumbnail( $post_id, $avatar_id );
	}

	// Tell the administrator.
	$edit = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
	wp_mail(
		get_option( 'admin_email' ),
		sprintf( '[%s] New guest review awaiting approval — %s', wp_specialchars_decode( get_bloginfo( 'name' ) ), $name ),
		implode( "\n", array(
			sprintf( 'From: %s <%s>', $name, $user->user_email ),
			'Location: ' . $location,
			'Stay: ' . ( $stay ?: '—' ),
			'',
			$text,
			'',
			'Review / approve: ' . $edit,
			'Approve now: ' . gp_core_review_approve_url( $post_id ),
		) )
	);

	gp_core_review_back( 'sent' );
}

/* Approval ------------------------------------------------------------------- */

function gp_core_review_approve_url( $post_id ) {
	return wp_nonce_url( admin_url( 'admin-post.php?action=gp_review_approve&post=' . (int) $post_id ), 'gp_review_approve_' . (int) $post_id );
}

add_action( 'admin_post_gp_review_approve', function () {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'publish_posts' ) || ! check_admin_referer( 'gp_review_approve_' . $post_id ) ) {
		wp_die( esc_html__( 'You are not allowed to approve this review.', 'gp-house-core' ), '', array( 'response' => 403 ) );
	}
	if ( 'gp_testimonial' === get_post_type( $post_id ) ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=gp_testimonial&gp_approved=1' ) );
	exit;
} );

// Let the guest know when their review goes live.
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( 'publish' !== $new || 'pending' !== $old || 'gp_testimonial' !== $post->post_type ) {
		return;
	}
	$user_id = (int) get_post_meta( $post->ID, 'gp_review_user', true );
	$user    = $user_id ? get_userdata( $user_id ) : false;
	if ( $user && function_exists( 'gp_core_send_mail' ) ) {
		gp_core_send_mail(
			$user->user_email,
			__( 'Your GP House review is now live', 'gp-house-core' ),
			__( 'Thank you for sharing your stay', 'gp-house-core' ),
			array( __( 'Your review has been approved and now appears in the Guest Stories section of our website.', 'gp-house-core' ) ),
			__( 'See It On The Website', 'gp-house-core' ),
			home_url( '/' )
		);
	}
}, 10, 3 );

/* Admin screens ---------------------------------------------------------------- */

// Row action "Approve" for pending stories.
add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'gp_testimonial' === $post->post_type && 'pending' === $post->post_status && current_user_can( 'publish_posts' ) ) {
		$actions = array( 'gp_approve' => '<a href="' . esc_url( gp_core_review_approve_url( $post->ID ) ) . '" style="color:#1f7a38;font-weight:600">' . esc_html__( 'Approve', 'gp-house-core' ) . '</a>' ) + $actions;
	}
	return $actions;
}, 10, 2 );

add_filter( 'manage_gp_testimonial_posts_columns', function ( $cols ) {
	$out = array();
	foreach ( $cols as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'title' === $key ) {
			$out['gp_loc']    = __( 'Location', 'gp-house-core' );
			$out['gp_review'] = __( 'Review', 'gp-house-core' );
			$out['gp_by']     = __( 'Member', 'gp-house-core' );
		}
	}
	return $out;
} );
add_action( 'manage_gp_testimonial_posts_custom_column', function ( $col, $post_id ) {
	if ( 'gp_loc' === $col ) {
		echo esc_html( (string) get_post_meta( $post_id, 'gp_location', true ) );
	} elseif ( 'gp_review' === $col ) {
		echo esc_html( wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 18 ) );
	} elseif ( 'gp_by' === $col ) {
		$uid = (int) get_post_meta( $post_id, 'gp_review_user', true );
		echo $uid ? '<a href="' . esc_url( get_edit_user_link( $uid ) ) . '">' . esc_html( (string) get_post_meta( $post_id, 'gp_review_email', true ) ) . '</a>' : '—';
	}
}, 10, 2 );

// Number of reviews waiting, next to the "Guest Stories" menu.
add_action( 'admin_menu', function () {
	global $menu;
	$counts = wp_count_posts( 'gp_testimonial' );
	$n      = (int) ( $counts->pending ?? 0 );
	if ( ! $n ) {
		return;
	}
	foreach ( $menu as $i => $item ) {
		if ( isset( $item[2] ) && 'edit.php?post_type=gp_testimonial' === $item[2] ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod count-' . $n . '"><span class="pending-count">' . number_format_i18n( $n ) . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
}, 99 );

add_action( 'admin_notices', function () {
	if ( isset( $_GET['gp_approved'] ) && 'edit-gp_testimonial' === ( get_current_screen()->id ?? '' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Review approved — it is now live on the website.', 'gp-house-core' ) . '</p></div>';
	}
} );

// Dashboard box with the reviews waiting for a decision.
add_action( 'wp_dashboard_setup', function () {
	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}
	wp_add_dashboard_widget( 'gp_pending_reviews', __( 'Guest reviews awaiting approval', 'gp-house-core' ), function () {
		$items = get_posts( array( 'post_type' => 'gp_testimonial', 'post_status' => 'pending', 'numberposts' => 8 ) );
		if ( ! $items ) {
			echo '<p>' . esc_html__( 'Nothing waiting. New reviews appear here.', 'gp-house-core' ) . '</p>';
			return;
		}
		echo '<ul>';
		foreach ( $items as $p ) {
			printf(
				'<li><strong>%1$s</strong>, %2$s — %3$s<br><a href="%4$s">%5$s</a> · <a href="%6$s" style="color:#1f7a38;font-weight:600">%7$s</a></li>',
				esc_html( get_the_title( $p ) ),
				esc_html( (string) get_post_meta( $p->ID, 'gp_location', true ) ),
				esc_html( wp_trim_words( wp_strip_all_tags( $p->post_content ), 16 ) ),
				esc_url( get_edit_post_link( $p->ID ) ),
				esc_html__( 'Read & edit', 'gp-house-core' ),
				esc_url( gp_core_review_approve_url( $p->ID ) ),
				esc_html__( 'Approve', 'gp-house-core' )
			);
		}
		echo '</ul>';
	} );
} );

// Who submitted it, on the edit screen.
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'gp_review_source', __( 'Submitted by', 'gp-house-core' ), function ( $post ) {
		$uid = (int) get_post_meta( $post->ID, 'gp_review_user', true );
		if ( ! $uid ) {
			echo '<p>' . esc_html__( 'Added by an administrator.', 'gp-house-core' ) . '</p>';
			return;
		}
		printf(
			'<p><a href="%1$s">%2$s</a><br>%3$s</p><p>%4$s %5$s</p><p>%6$s</p>',
			esc_url( get_edit_user_link( $uid ) ),
			esc_html( get_the_title( $post ) ),
			esc_html( (string) get_post_meta( $post->ID, 'gp_review_email', true ) ),
			esc_html__( 'Stay:', 'gp-house-core' ),
			esc_html( (string) ( get_post_meta( $post->ID, 'gp_review_stay', true ) ?: '—' ) ),
			'pending' === $post->post_status ? esc_html__( 'Press Publish to approve it.', 'gp-house-core' ) : ''
		);
	}, 'gp_testimonial', 'side' );
} );
