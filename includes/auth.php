<?php
/**
 * Member accounts — custom sign-in, registration, e-mail verification, forgot / reset password
 * and sign-out. Nothing here uses wp-login.php; the screens are the theme's /sign-in/, /join/,
 * /forgot-password/, /reset-password/ and /verify-email/ pages (and the modal built from them).
 *
 * REST (POST, JSON):  /wp-json/gp/v1/auth/{login|register|resend|forgot|reset}
 * GET links:          /verify-email/?uid=&key=   ·   admin-post.php?action=gp_logout&_wpnonce=
 *
 * E-mail goes out through wp_mail(), i.e. whatever SMTP plugin is installed.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

const GP_AUTH_MIN_PASSWORD = 8;

/* URLs -------------------------------------------------------------------- */

/** Public URL of an auth screen: signin | join | forgot | reset | verify. */
function gp_core_auth_url( $view = 'signin', $args = array() ) {
	$paths = array(
		'signin' => '/sign-in/',
		'join'   => '/join/',
		'forgot' => '/forgot-password/',
		'reset'  => '/reset-password/',
		'verify' => '/verify-email/',
	);
	$url = home_url( $paths[ $view ] ?? '/sign-in/' );
	return $args ? add_query_arg( $args, $url ) : $url;
}

// Everything WordPress would send to wp-login.php goes to the custom screens instead.
add_filter( 'login_url', function ( $login_url, $redirect ) {
	return gp_core_auth_url( 'signin', $redirect ? array( 'redirect_to' => rawurlencode( $redirect ) ) : array() );
}, 10, 2 );
add_filter( 'lostpassword_url', function () {
	return gp_core_auth_url( 'forgot' );
} );
add_filter( 'register_url', function () {
	return gp_core_auth_url( 'join' );
} );
add_filter( 'logout_url', function ( $url, $redirect ) {
	return gp_core_logout_url( $redirect ?: home_url( '/' ) );
}, 10, 2 );

/** Nonce-protected sign-out link. */
function gp_core_logout_url( $redirect = '' ) {
	$url = add_query_arg( array( 'action' => 'gp_logout', 'redirect_to' => $redirect ? rawurlencode( $redirect ) : false ), admin_url( 'admin-post.php' ) );
	return wp_nonce_url( $url, 'gp_logout' );
}

/* Helpers ----------------------------------------------------------------- */

/** Allow $max hits per $window seconds for a key; returns false once exceeded. */
function gp_core_rate_limit( $key, $max, $window ) {
	$k    = 'gp_rl_' . md5( $key );
	$hits = (int) get_transient( $k );
	if ( $hits >= $max ) {
		return false;
	}
	set_transient( $k, $hits + 1, $window );
	return true;
}

function gp_core_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
}

/** JSON-ready result. */
function gp_core_auth_result( $ok, $message, $extra = array(), $status = 200 ) {
	return new WP_REST_Response( array_merge( array( 'ok' => (bool) $ok, 'message' => $message ), $extra ), $status );
}

function gp_core_auth_error( $message, $field = '', $status = 400, $extra = array() ) {
	return gp_core_auth_result( false, $message, array_merge( $field ? array( 'field' => $field ) : array(), $extra ), $status );
}

/** Same-site redirect target only. */
function gp_core_safe_redirect( $url, $fallback = '' ) {
	$fallback = $fallback ?: home_url( '/profile/' );
	$url      = $url ? wp_validate_redirect( esc_url_raw( rawurldecode( $url ) ), $fallback ) : $fallback;
	return $url;
}

/** Accounts created through the join form stay locked until the e-mail is confirmed. */
function gp_core_is_unverified( $user ) {
	return '0' === (string) get_user_meta( $user->ID, 'gp_email_verified', true );
}

/* E-mail ------------------------------------------------------------------ */

/** Branded HTML e-mail. */
function gp_core_send_mail( $to, $subject, $heading, $lines, $button_label, $button_url ) {
	$body = '<div style="background:#f0efeb;padding:32px 16px;font-family:Arial,Helvetica,sans-serif;">'
		. '<div style="max-width:520px;margin:0 auto;background:#fff;border-radius:20px;padding:32px;color:#011041;">'
		. '<p style="margin:0 0 20px;font-size:20px;font-weight:bold;letter-spacing:.5px;">GP<span style="color:#ffc646;">HOUSE</span></p>'
		. '<h1 style="margin:0 0 12px;font-size:22px;line-height:30px;">' . esc_html( $heading ) . '</h1>';
	foreach ( (array) $lines as $line ) {
		$body .= '<p style="margin:0 0 14px;font-size:15px;line-height:24px;color:#555b6e;">' . esc_html( $line ) . '</p>';
	}
	$body .= '<p style="margin:24px 0;"><a href="' . esc_url( $button_url ) . '" style="display:inline-block;background:#ffc646;color:#011041;text-decoration:none;font-weight:bold;padding:13px 26px;border-radius:8px;">' . esc_html( $button_label ) . '</a></p>'
		. '<p style="margin:0;font-size:12px;line-height:18px;color:#8a8fa0;">' . esc_html__( 'If the button does not work, copy this link into your browser:', 'gp-house-core' ) . '<br><a href="' . esc_url( $button_url ) . '" style="color:#011041;word-break:break-all;">' . esc_html( $button_url ) . '</a></p>'
		. '</div></div>';

	$html = static function () {
		return 'text/html';
	};
	add_filter( 'wp_mail_content_type', $html );
	$sent = wp_mail( $to, $subject, $body );
	remove_filter( 'wp_mail_content_type', $html );
	return $sent;
}

function gp_core_send_verification( $user ) {
	$key = wp_generate_password( 32, false, false );
	update_user_meta( $user->ID, 'gp_verify_hash', wp_hash_password( $key ) );
	update_user_meta( $user->ID, 'gp_verify_expires', time() + 2 * DAY_IN_SECONDS );
	$url = gp_core_auth_url( 'verify', array( 'uid' => $user->ID, 'key' => $key ) );
	return gp_core_send_mail(
		$user->user_email,
		__( 'Confirm your GP House Club email', 'gp-house-core' ),
		sprintf( /* translators: %s: first name */ __( 'Welcome, %s', 'gp-house-core' ), $user->first_name ?: $user->display_name ),
		array(
			__( 'Thank you for joining GP House Club. Please confirm your email address to activate your account and receive your 500 welcome points.', 'gp-house-core' ),
			__( 'This link is valid for 48 hours.', 'gp-house-core' ),
		),
		__( 'Confirm My Email', 'gp-house-core' ),
		$url
	);
}

/* REST -------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	foreach ( array( 'login', 'register', 'resend', 'forgot', 'reset' ) as $action ) {
		register_rest_route( 'gp/v1', '/auth/' . $action, array(
			'methods'             => 'POST',
			'callback'            => 'gp_core_auth_' . $action,
			'permission_callback' => '__return_true', // Public by nature; protected by rate limits + the REST nonce check below.
		) );
	}
} );

// Reject cross-site posts: our forms always send the wp_rest nonce.
add_filter( 'rest_pre_dispatch', function ( $result, $server, $request ) {
	if ( 0 === strpos( $request->get_route(), '/gp/v1/auth/' ) ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return gp_core_auth_error( __( 'Your session expired. Please refresh the page and try again.', 'gp-house-core' ), '', 403 );
		}
	}
	return $result;
}, 10, 3 );

function gp_core_param( WP_REST_Request $r, $key ) {
	$v = $r->get_param( $key );
	return is_string( $v ) ? trim( wp_unslash( $v ) ) : '';
}

/** POST /auth/login */
function gp_core_auth_login( WP_REST_Request $r ) {
	$email = sanitize_email( gp_core_param( $r, 'email' ) );
	$pass  = (string) $r->get_param( 'password' );
	if ( ! is_email( $email ) ) {
		return gp_core_auth_error( __( 'Enter a valid email address.', 'gp-house-core' ), 'email' );
	}
	if ( '' === $pass ) {
		return gp_core_auth_error( __( 'Enter your password.', 'gp-house-core' ), 'password' );
	}
	$bucket = strtolower( $email ) . '|' . gp_core_client_ip();
	if ( ! gp_core_rate_limit( 'login|' . $bucket, 8, 15 * MINUTE_IN_SECONDS ) ) {
		return gp_core_auth_error( __( 'Too many attempts. Please wait 15 minutes or reset your password.', 'gp-house-core' ), '', 429 );
	}
	$user = get_user_by( 'email', $email );
	if ( ! $user || ! wp_check_password( $pass, $user->user_pass, $user->ID ) ) {
		return gp_core_auth_error( __( 'Incorrect email or password.', 'gp-house-core' ), 'password', 401 );
	}
	if ( gp_core_is_unverified( $user ) ) {
		return gp_core_auth_error( __( 'Please confirm your email first — we sent you a verification link.', 'gp-house-core' ), '', 403, array( 'code' => 'unverified', 'email' => $email ) );
	}
	wp_set_current_user( $user->ID );
	wp_set_auth_cookie( $user->ID, true );
	do_action( 'wp_login', $user->user_login, $user );
	delete_transient( 'gp_rl_' . md5( 'login|' . $bucket ) );
	$default = user_can( $user, 'edit_posts' ) && ! empty( $r->get_param( 'admin' ) ) ? admin_url() : home_url( '/profile/' );
	return gp_core_auth_result( true, __( 'Welcome back.', 'gp-house-core' ), array( 'redirect' => gp_core_safe_redirect( gp_core_param( $r, 'redirect_to' ), $default ) ) );
}

/** POST /auth/register */
function gp_core_auth_register( WP_REST_Request $r ) {
	if ( '' !== gp_core_param( $r, 'website' ) ) { // Honeypot.
		return gp_core_auth_result( true, __( 'Check your inbox to confirm your email.', 'gp-house-core' ), array( 'view' => 'check-email' ) );
	}
	$name  = sanitize_text_field( gp_core_param( $r, 'name' ) );
	$email = sanitize_email( gp_core_param( $r, 'email' ) );
	$phone = preg_replace( '/[^\d+\s\-()]/', '', gp_core_param( $r, 'phone' ) );
	$pass  = (string) $r->get_param( 'password' );

	if ( mb_strlen( $name ) < 2 ) {
		return gp_core_auth_error( __( 'Enter your full name.', 'gp-house-core' ), 'name' );
	}
	if ( ! is_email( $email ) ) {
		return gp_core_auth_error( __( 'Enter a valid email address.', 'gp-house-core' ), 'email' );
	}
	if ( '' !== $phone && strlen( preg_replace( '/\D/', '', $phone ) ) < 7 ) {
		return gp_core_auth_error( __( 'Enter a valid phone number.', 'gp-house-core' ), 'phone' );
	}
	if ( strlen( $pass ) < GP_AUTH_MIN_PASSWORD ) {
		/* translators: %d: minimum characters */
		return gp_core_auth_error( sprintf( __( 'Use at least %d characters for your password.', 'gp-house-core' ), GP_AUTH_MIN_PASSWORD ), 'password' );
	}
	if ( ! gp_core_rate_limit( 'register|' . gp_core_client_ip(), 6, HOUR_IN_SECONDS ) ) {
		return gp_core_auth_error( __( 'Too many sign-ups from this connection. Please try again later.', 'gp-house-core' ), '', 429 );
	}
	if ( email_exists( $email ) || username_exists( $email ) ) {
		return gp_core_auth_error( __( 'An account with this email already exists. Try signing in.', 'gp-house-core' ), 'email', 409 );
	}

	$parts   = preg_split( '/\s+/', $name, 2 );
	$user_id = wp_insert_user( array(
		'user_login'   => $email,
		'user_email'   => $email,
		'user_pass'    => $pass,
		'display_name' => $name,
		'first_name'   => $parts[0],
		'last_name'    => $parts[1] ?? '',
		'role'         => 'subscriber',
	) );
	if ( is_wp_error( $user_id ) ) {
		return gp_core_auth_error( __( 'We could not create your account. Please try again.', 'gp-house-core' ), '', 500 );
	}
	update_user_meta( $user_id, 'gp_email_verified', '0' );
	update_user_meta( $user_id, 'gp_phone', $phone );
	update_user_meta( $user_id, 'gp_points', 0 );
	update_user_meta( $user_id, 'gp_tier', 'Silver' );
	gp_core_send_verification( get_userdata( $user_id ) );

	return gp_core_auth_result( true, __( 'Account created. Check your inbox to confirm your email.', 'gp-house-core' ), array( 'view' => 'check-email', 'email' => $email ) );
}

/** POST /auth/resend — new verification mail. */
function gp_core_auth_resend( WP_REST_Request $r ) {
	$email = sanitize_email( gp_core_param( $r, 'email' ) );
	$ok    = __( 'If that account is waiting for confirmation, we just sent a new link.', 'gp-house-core' );
	if ( ! is_email( $email ) ) {
		return gp_core_auth_error( __( 'Enter a valid email address.', 'gp-house-core' ), 'email' );
	}
	if ( ! gp_core_rate_limit( 'resend|' . strtolower( $email ), 3, HOUR_IN_SECONDS ) ) {
		return gp_core_auth_error( __( 'Too many requests. Please try again in an hour.', 'gp-house-core' ), '', 429 );
	}
	$user = get_user_by( 'email', $email );
	if ( $user && gp_core_is_unverified( $user ) ) {
		gp_core_send_verification( $user );
	}
	return gp_core_auth_result( true, $ok );
}

/** POST /auth/forgot */
function gp_core_auth_forgot( WP_REST_Request $r ) {
	$email = sanitize_email( gp_core_param( $r, 'email' ) );
	if ( ! is_email( $email ) ) {
		return gp_core_auth_error( __( 'Enter a valid email address.', 'gp-house-core' ), 'email' );
	}
	if ( ! gp_core_rate_limit( 'forgot|' . strtolower( $email ) . '|' . gp_core_client_ip(), 4, HOUR_IN_SECONDS ) ) {
		return gp_core_auth_error( __( 'Too many requests. Please try again in an hour.', 'gp-house-core' ), '', 429 );
	}
	$user = get_user_by( 'email', $email );
	if ( $user && ! gp_core_is_unverified( $user ) ) {
		$key = get_password_reset_key( $user );
		if ( ! is_wp_error( $key ) ) {
			gp_core_send_mail(
				$user->user_email,
				__( 'Reset your GP House password', 'gp-house-core' ),
				__( 'Reset your password', 'gp-house-core' ),
				array(
					__( 'We received a request to reset the password for your GP House account. Choose a new password with the button below.', 'gp-house-core' ),
					__( 'If you did not ask for this, you can safely ignore this email — your password will stay the same.', 'gp-house-core' ),
				),
				__( 'Choose A New Password', 'gp-house-core' ),
				gp_core_auth_url( 'reset', array( 'key' => $key, 'login' => rawurlencode( $user->user_login ) ) )
			);
		}
	} elseif ( $user ) {
		gp_core_send_verification( $user ); // Not confirmed yet: send the confirmation link instead.
	}
	// Same answer whether or not the account exists.
	return gp_core_auth_result( true, __( 'If an account exists for that email, a reset link is on its way.', 'gp-house-core' ) );
}

/** POST /auth/reset */
function gp_core_auth_reset( WP_REST_Request $r ) {
	$login = gp_core_param( $r, 'login' );
	$key   = gp_core_param( $r, 'key' );
	$pass  = (string) $r->get_param( 'password' );
	$pass2 = (string) $r->get_param( 'password_confirm' );

	$user = check_password_reset_key( $key, $login );
	if ( is_wp_error( $user ) ) {
		return gp_core_auth_error( __( 'This reset link is invalid or has expired. Please request a new one.', 'gp-house-core' ), '', 410, array( 'code' => 'expired' ) );
	}
	if ( strlen( $pass ) < GP_AUTH_MIN_PASSWORD ) {
		/* translators: %d: minimum characters */
		return gp_core_auth_error( sprintf( __( 'Use at least %d characters for your password.', 'gp-house-core' ), GP_AUTH_MIN_PASSWORD ), 'password' );
	}
	if ( $pass !== $pass2 ) {
		return gp_core_auth_error( __( 'The two passwords do not match.', 'gp-house-core' ), 'password_confirm' );
	}
	reset_password( $user, $pass );
	update_user_meta( $user->ID, 'gp_email_verified', '1' ); // They proved they own the inbox.
	return gp_core_auth_result( true, __( 'Your password has been updated.', 'gp-house-core' ), array( 'redirect' => gp_core_auth_url( 'signin', array( 'notice' => 'password_reset' ) ) ) );
}

/* GET links --------------------------------------------------------------- */

// /verify-email/?uid=&key= → confirm, award the welcome points, then show the sign-in screen.
add_action( 'template_redirect', function () {
	if ( ! is_page( 'verify-email' ) || ! isset( $_GET['uid'], $_GET['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$uid  = absint( $_GET['uid'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$key  = sanitize_text_field( wp_unslash( $_GET['key'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$user = $uid ? get_userdata( $uid ) : false;
	$ok   = false;
	if ( $user ) {
		if ( ! gp_core_is_unverified( $user ) ) {
			$ok = true; // Already confirmed.
		} else {
			$hash    = (string) get_user_meta( $uid, 'gp_verify_hash', true );
			$expires = (int) get_user_meta( $uid, 'gp_verify_expires', true );
			if ( $hash && $expires > time() && wp_check_password( $key, $hash ) ) {
				update_user_meta( $uid, 'gp_email_verified', '1' );
				update_user_meta( $uid, 'gp_points', (int) get_user_meta( $uid, 'gp_points', true ) + (int) apply_filters( 'gp_house_welcome_points', 500 ) );
				delete_user_meta( $uid, 'gp_verify_hash' );
				delete_user_meta( $uid, 'gp_verify_expires' );
				do_action( 'gp_house_email_verified', $uid );
				$ok = true;
			}
		}
	}
	wp_safe_redirect( gp_core_auth_url( 'signin', array( 'notice' => $ok ? 'verified' : 'verify_failed' ) ) );
	exit;
} );

// Sign-out (nonce protected).
add_action( 'admin_post_gp_logout', 'gp_core_handle_logout' );
add_action( 'admin_post_nopriv_gp_logout', 'gp_core_handle_logout' );
function gp_core_handle_logout() {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gp_logout' ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
	wp_logout();
	$to = isset( $_GET['redirect_to'] ) ? gp_core_safe_redirect( sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) ), home_url( '/' ) ) : home_url( '/' );
	wp_safe_redirect( $to );
	exit;
}

/* Members stay out of wp-admin ------------------------------------------------ */

add_action( 'admin_init', function () {
	global $pagenow;
	// admin-post.php also fires admin_init: sign-out, profile save and the owner inquiry form must keep working for members.
	if ( wp_doing_ajax() || wp_doing_cron() || 'admin-post.php' === $pagenow || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
		wp_safe_redirect( home_url( '/profile/' ) );
		exit;
	}
} );
add_filter( 'show_admin_bar', function ( $show ) {
	return current_user_can( 'edit_posts' ) ? $show : false;
} );

// Unconfirmed accounts cannot sign in through any other route either.
add_filter( 'authenticate', function ( $user ) {
	if ( $user instanceof WP_User && gp_core_is_unverified( $user ) ) {
		return new WP_Error( 'gp_unverified', __( 'Please confirm your email first.', 'gp-house-core' ) );
	}
	return $user;
}, 99 );
