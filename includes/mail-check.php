<?php
/**
 * Email delivery check — Tools → GP House Email.
 *
 * Account e-mails (confirm email, reset password) and form messages go out through wp_mail(), which
 * the SMTP plugin (FluentSMTP) takes over. This screen shows whether one is active and lets an
 * administrator send a test message to see the real result.
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

/** Name of the active SMTP plugin, or ''. */
function gp_core_smtp_plugin() {
	$known = array(
		'fluent-smtp/fluent-smtp.php' => 'FluentSMTP',
		'wp-mail-smtp/wp_mail_smtp.php' => 'WP Mail SMTP',
		'post-smtp/postman-smtp.php' => 'Post SMTP',
	);
	foreach ( $known as $file => $name ) {
		if ( in_array( $file, (array) get_option( 'active_plugins', array() ), true ) ) {
			return $name;
		}
	}
	return '';
}

add_action( 'admin_menu', function () {
	add_management_page( __( 'GP House Email', 'gp-house-core' ), __( 'GP House Email', 'gp-house-core' ), 'manage_options', 'gp-house-email', 'gp_core_mail_page' );
} );

add_action( 'admin_notices', function () {
	if ( ! current_user_can( 'manage_options' ) || gp_core_smtp_plugin() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>' . esc_html__( 'GP House: no SMTP plugin is active, so sign-up confirmation and password-reset emails may not be delivered. Install and configure FluentSMTP, then use Tools → GP House Email to send a test.', 'gp-house-core' ) . '</p></div>';
} );

function gp_core_mail_page() {
	$result = null;
	$error  = '';
	if ( isset( $_POST['gp_core_mail_test'] ) && check_admin_referer( 'gp_core_mail_test' ) && current_user_can( 'manage_options' ) ) {
		$to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';
		if ( ! is_email( $to ) ) {
			$error = __( 'Enter a valid email address.', 'gp-house-core' );
		} else {
			$failure = static function ( $err ) use ( &$error ) {
				$error = $err->get_error_message();
			};
			add_action( 'wp_mail_failed', $failure );
			$result = gp_core_send_mail(
				$to,
				__( 'GP House test email', 'gp-house-core' ),
				__( 'Email delivery works', 'gp-house-core' ),
				array( __( 'This is a test message from your GP House website. If you can read this, account confirmation and password-reset emails will reach your guests.', 'gp-house-core' ) ),
				__( 'Open The Website', 'gp-house-core' ),
				home_url( '/' )
			);
			remove_action( 'wp_mail_failed', $failure );
		}
	}
	$plugin = gp_core_smtp_plugin();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'GP House Email', 'gp-house-core' ); ?></h1>
		<p>
			<strong><?php esc_html_e( 'SMTP plugin:', 'gp-house-core' ); ?></strong>
			<?php echo $plugin ? esc_html( $plugin . ' — ' . __( 'active', 'gp-house-core' ) ) : esc_html__( 'none active (mail uses the server default and may be blocked)', 'gp-house-core' ); ?>
		</p>
		<p><?php esc_html_e( 'From name / address and the SMTP login are set in the SMTP plugin (Settings → FluentSMTP).', 'gp-house-core' ); ?></p>
		<?php if ( true === $result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'The server accepted the message. Check the inbox (and spam folder).', 'gp-house-core' ); ?></p></div>
		<?php elseif ( null !== $result || $error ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $error ?: __( 'Sending failed.', 'gp-house-core' ) ); ?></p></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'gp_core_mail_test' ); ?>
			<p><label for="gp-mail-to"><?php esc_html_e( 'Send a test email to', 'gp-house-core' ); ?></label><br>
			<input type="email" id="gp-mail-to" name="to" class="regular-text" value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>"></p>
			<p><button class="button button-primary" type="submit" name="gp_core_mail_test" value="1"><?php esc_html_e( 'Send test email', 'gp-house-core' ); ?></button></p>
		</form>
	</div>
	<?php
}

// Friendly sender name on every message (the address itself comes from the SMTP plugin).
add_filter( 'wp_mail_from_name', function ( $name ) {
	return 'WordPress' === $name ? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) : $name;
} );
