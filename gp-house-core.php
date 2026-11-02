<?php
/**
 * Plugin Name: GP House Core
 * Description: Content model for the GP House site — Stays, Destinations, Guest Testimonials and Journal story details — plus a one-click import of the Figma demo content.
 * Version:     1.0.0
 * Author:      GP House
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: gp-house-core
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'GP_CORE_VERSION', '1.0.0' );
define( 'GP_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'GP_CORE_URL', plugin_dir_url( __FILE__ ) );

require GP_CORE_DIR . 'includes/post-types.php';
require GP_CORE_DIR . 'includes/fields.php';
require GP_CORE_DIR . 'includes/meta-boxes.php';
require GP_CORE_DIR . 'includes/demo-import.php';

register_activation_hook( __FILE__, function () {
	gp_core_register_content_types();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
