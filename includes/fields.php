<?php
/**
 * Field schema shared by meta boxes, term fields and register_meta().
 *
 * @package GP_House_Core
 */

defined( 'ABSPATH' ) || exit;

/** @return array<string, array<string, array>> Object type => meta key => field. */
function gp_core_fields() {
	return array(
		'gp_stay'        => array(
			'gp_tagline'       => array( 'type' => 'text', 'label' => __( 'Tagline (e.g. Riverside Sanctuary)', 'gp-house-core' ) ),
			'gp_price'         => array( 'type' => 'number', 'label' => __( 'Price per night (USD)', 'gp-house-core' ) ),
			'gp_reviews'       => array( 'type' => 'number', 'label' => __( 'Number of reviews', 'gp-house-core' ) ),
			'gp_amenities'     => array( 'type' => 'lines', 'label' => __( 'Highlights — one per line as icon|text. Icons: pool, utensils, flower', 'gp-house-core' ) ),
			'gp_thumb_id'      => array( 'type' => 'image', 'label' => __( 'Small thumbnail (map pin & list)', 'gp-house-core' ) ),
			'gp_main_image_id' => array( 'type' => 'image', 'label' => __( 'Map card image (optional, defaults to featured image)', 'gp-house-core' ) ),
			'gp_featured'      => array( 'type' => 'checkbox', 'label' => __( 'Show in “Stay Somewhere Special”', 'gp-house-core' ) ),
			'gp_show_on_map'   => array( 'type' => 'checkbox', 'label' => __( 'Show on the Explore Destinations map', 'gp-house-core' ) ),
			'gp_map_active'    => array( 'type' => 'checkbox', 'label' => __( 'Open this stay on the map by default', 'gp-house-core' ) ),
			'gp_pin_x'         => array( 'type' => 'number', 'label' => __( 'Map pin X (px from left, map is 1440 × 768)', 'gp-house-core' ) ),
			'gp_pin_y'         => array( 'type' => 'number', 'label' => __( 'Map pin Y (px from top)', 'gp-house-core' ) ),
			'gp_pin_mx'        => array( 'type' => 'number', 'label' => __( 'Mobile map pin X (px, map card is 362 × 370)', 'gp-house-core' ) ),
			'gp_pin_my'        => array( 'type' => 'number', 'label' => __( 'Mobile map pin Y (px)', 'gp-house-core' ) ),
		),
		'gp_testimonial' => array(
			'gp_location' => array( 'type' => 'text', 'label' => __( 'Guest location (e.g. London, United Kingdom)', 'gp-house-core' ) ),
		),
		'post'           => array(
			'gp_read_time'        => array( 'type' => 'text', 'label' => __( 'Read time (e.g. 6 min read)', 'gp-house-core' ) ),
			'gp_author_name'      => array( 'type' => 'text', 'label' => __( 'Byline name (defaults to the post author)', 'gp-house-core' ) ),
			'gp_author_role'      => array( 'type' => 'text', 'label' => __( 'Byline role (e.g. Local Guides)', 'gp-house-core' ) ),
			'gp_author_avatar_id' => array( 'type' => 'image', 'label' => __( 'Byline photo', 'gp-house-core' ) ),
		),
		'term:gp_destination' => array(
			'gp_card_title' => array( 'type' => 'text', 'label' => __( 'Card title (e.g. Phuket Beach)', 'gp-house-core' ) ),
			'gp_subtitle'   => array( 'type' => 'text', 'label' => __( 'Card subtitle', 'gp-house-core' ) ),
			'gp_image_id'   => array( 'type' => 'image', 'label' => __( 'Card image (portrait 360 × 580)', 'gp-house-core' ) ),
			'gp_order'      => array( 'type' => 'number', 'label' => __( 'Order', 'gp-house-core' ) ),
		),
	);
}

function gp_core_sanitize( $field, $value ) {
	switch ( $field['type'] ) {
		case 'number':
			return '' === $value || null === $value ? '' : (string) (float) $value;
		case 'image':
			return $value ? (string) absint( $value ) : '';
		case 'checkbox':
			return $value ? '1' : '';
		case 'lines':
			$lines = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
			return array_values( array_filter( array_map( 'sanitize_text_field', $lines ) ) );
		default:
			return sanitize_text_field( (string) $value );
	}
}

add_action( 'init', function () {
	foreach ( gp_core_fields() as $object => $fields ) {
		foreach ( $fields as $key => $field ) {
			$args = array(
				'single'            => true,
				'show_in_rest'      => 'lines' === $field['type'] ? array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ) ) : true,
				'type'              => 'lines' === $field['type'] ? 'array' : 'string',
				'sanitize_callback' => function ( $value ) use ( $field ) {
					return gp_core_sanitize( $field, $value );
				},
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			);
			if ( str_starts_with( $object, 'term:' ) ) {
				register_term_meta( substr( $object, 5 ), $key, $args );
			} else {
				register_post_meta( $object, $key, $args );
			}
		}
	}
}, 20 );

/** Render one field row. */
function gp_core_render_field( $key, $field, $value ) {
	$id = 'gp-field-' . $key;
	echo '<div class="gp-core-field gp-core-field--' . esc_attr( $field['type'] ) . '">';

	switch ( $field['type'] ) {
		case 'checkbox':
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="gp_core[%2$s]" value="1"%3$s> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $key ),
				checked( $value, '1', false ),
				esc_html( $field['label'] )
			);
			break;

		case 'lines':
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			printf( '<textarea id="%s" name="gp_core[%s]" rows="4" class="widefat">%s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( implode( "\n", (array) $value ) ) );
			break;

		case 'image':
			$url = $value ? wp_get_attachment_image_url( (int) $value, 'thumbnail' ) : '';
			printf( '<label>%s</label>', esc_html( $field['label'] ) );
			printf(
				'<div class="gp-core-image" data-gp-image><img src="%1$s" alt=""%2$s><input type="hidden" name="gp_core[%3$s]" value="%4$s"><button type="button" class="button" data-gp-image-pick>%5$s</button> <button type="button" class="button-link" data-gp-image-clear%6$s>%7$s</button></div>',
				esc_url( $url ),
				$url ? '' : ' hidden',
				esc_attr( $key ),
				esc_attr( $value ),
				esc_html__( 'Select image', 'gp-house-core' ),
				$url ? '' : ' hidden',
				esc_html__( 'Remove', 'gp-house-core' )
			);
			break;

		default:
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			printf(
				'<input type="%1$s" id="%2$s" name="gp_core[%3$s]" value="%4$s" class="widefat"%5$s>',
				'number' === $field['type'] ? 'number' : 'text',
				esc_attr( $id ),
				esc_attr( $key ),
				esc_attr( $value ),
				'number' === $field['type'] ? ' step="any"' : ''
			);
	}

	echo '</div>';
}

/** Read submitted values for a field group. */
function gp_core_submitted_values( $fields ) {
	$raw = isset( $_POST['gp_core'] ) && is_array( $_POST['gp_core'] ) ? wp_unslash( $_POST['gp_core'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput -- verified by callers, sanitized below.
	$out = array();
	foreach ( $fields as $key => $field ) {
		$out[ $key ] = gp_core_sanitize( $field, $raw[ $key ] ?? '' );
	}
	return $out;
}

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php', 'term.php', 'edit-tags.php' ), true ) ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_script( 'gp-core-admin', GP_CORE_URL . 'assets/admin.js', array(), GP_CORE_VERSION, true );
	wp_add_inline_style( 'common', '.gp-core-field{margin:0 0 14px}.gp-core-field>label{display:block;font-weight:600;margin-bottom:4px}.gp-core-field--checkbox>label{font-weight:400}.gp-core-image img{display:block;max-width:120px;height:auto;border-radius:6px;margin-bottom:6px}.gp-core-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:4px 20px}' );
} );
