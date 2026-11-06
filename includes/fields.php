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
		'gp_room'        => array(
			'gp_highlights'     => array( 'type' => 'lines', 'label' => __( 'Highlights (one per line, shown with a check icon)', 'gp-house-core' ) ),
			'gp_area'           => array( 'type' => 'text', 'label' => __( 'Area (e.g. 52 sqm)', 'gp-house-core' ) ),
			'gp_guests_label'   => array( 'type' => 'text', 'label' => __( 'Guests (e.g. Max 2 Adults)', 'gp-house-core' ) ),
			'gp_max_guests'     => array( 'type' => 'number', 'label' => __( 'Max guests (number, used by search)', 'gp-house-core' ) ),
			'gp_bed_label'      => array( 'type' => 'text', 'label' => __( 'Bed (e.g. King, King or Twin)', 'gp-house-core' ) ),
			'gp_view'           => array( 'type' => 'text', 'label' => __( 'View', 'gp-house-core' ) ),
			'gp_price'          => array( 'type' => 'number', 'label' => __( 'Price per night from (USD)', 'gp-house-core' ) ),
			'gp_gallery'        => array( 'type' => 'gallery', 'label' => __( 'Photo gallery (first photo is the card image)', 'gp-house-core' ) ),
			'gp_gallery_active' => array( 'type' => 'number', 'label' => __( 'Gallery strip: position of the highlighted thumbnail (0 = first)', 'gp-house-core' ) ),
			'gp_hero_image_id'  => array( 'type' => 'image', 'label' => __( 'Room page hero (1440 × 562; empty = highlighted gallery photo)', 'gp-house-core' ) ),
			'gp_hero_mobile_id' => array( 'type' => 'image', 'label' => __( 'Room page hero — mobile crop (402 × 414, optional)', 'gp-house-core' ) ),
			'gp_main_mobile_id' => array( 'type' => 'image', 'label' => __( 'Room page first photo — mobile crop (362 × 248, optional)', 'gp-house-core' ) ),
			'gp_property'       => array( 'type' => 'text', 'label' => __( 'Property name (room page kicker, e.g. GP House Bangkok Riverside)', 'gp-house-core' ) ),
			'gp_hero_text'      => array( 'type' => 'text', 'label' => __( 'Room page hero line', 'gp-house-core' ) ),
			'gp_concept'        => array( 'type' => 'textarea', 'label' => __( 'Suite Concept & Architecture', 'gp-house-core' ) ),
			'gp_features'       => array( 'type' => 'lines', 'label' => __( 'Design & Sanctuary Features (one per line)', 'gp-house-core' ) ),
			'gp_amenities_intro' => array( 'type' => 'text', 'label' => __( 'In-room amenities intro', 'gp-house-core' ) ),
			'gp_amenity_groups' => array( 'type' => 'lines', 'label' => __( 'In-room amenities — one per line as Group | Item (empty = standard list)', 'gp-house-core' ) ),
			'gp_detail_gallery' => array( 'type' => 'gallery', 'label' => __( 'Room page photo strip (empty = use the gallery above)', 'gp-house-core' ) ),
			'gp_detail_active'  => array( 'type' => 'number', 'label' => __( 'Room page: photo shown first (0 = first)', 'gp-house-core' ) ),
			'gp_booking_note'   => array( 'type' => 'text', 'label' => __( 'Booking card note (empty = site default)', 'gp-house-core' ) ),
			'gp_ezee_hotel'     => array( 'type' => 'text', 'label' => __( 'eZee Hotel Code', 'gp-house-core' ) ),
			'gp_ezee_room'      => array( 'type' => 'text', 'label' => __( 'eZee Room Type ID', 'gp-house-core' ) ),
		),
		'gp_offer'       => array(
			'gp_offer_code'       => array( 'type' => 'text', 'label' => __( 'Promo code (shown on the image, e.g. ISLANDRETREAT)', 'gp-house-core' ) ),
			'gp_offer_save'       => array( 'type' => 'text', 'label' => __( 'Saving label (e.g. Save 25%)', 'gp-house-core' ) ),
			'gp_offer_highlights' => array( 'type' => 'lines', 'label' => __( 'Inclusions (one per line, up to 5)', 'gp-house-core' ) ),
			'gp_offer_url'        => array( 'type' => 'text', 'label' => __( '"Book With Code" link (empty = Stays page with the code)', 'gp-house-core' ) ),
			'gp_offer_mobile_id'  => array( 'type' => 'image', 'label' => __( 'Mobile image crop (336 × 260, optional)', 'gp-house-core' ) ),
		),
		'gp_testimonial' => array(
			'gp_location' => array( 'type' => 'text', 'label' => __( 'Guest location (e.g. London, United Kingdom)', 'gp-house-core' ) ),
		),
		'post'           => array(
			'gp_read_time'        => array( 'type' => 'text', 'label' => __( 'Read time (e.g. 6 min read)', 'gp-house-core' ) ),
			'gp_author_name'      => array( 'type' => 'text', 'label' => __( 'Byline name (defaults to the post author)', 'gp-house-core' ) ),
			'gp_author_role'      => array( 'type' => 'text', 'label' => __( 'Byline role (e.g. Local Guides)', 'gp-house-core' ) ),
			'gp_home_featured'    => array( 'type' => 'checkbox', 'label' => __( 'Show in the home page Journal section', 'gp-house-core' ) ),
			'gp_card_image_id'    => array( 'type' => 'image', 'label' => __( 'Card image (destination page; empty = featured image)', 'gp-house-core' ) ),
			'gp_author_avatar_id' => array( 'type' => 'image', 'label' => __( 'Byline photo', 'gp-house-core' ) ),
			'gp_story_image_id'   => array( 'type' => 'image', 'label' => __( 'Story page header image (empty = featured image)', 'gp-house-core' ) ),
			'gp_author_photo_id'  => array( 'type' => 'image', 'label' => __( 'Author portrait on the story page (empty = byline photo)', 'gp-house-core' ) ),
			'gp_author_facebook'  => array( 'type' => 'text', 'label' => __( 'Author Facebook URL', 'gp-house-core' ) ),
			'gp_author_instagram' => array( 'type' => 'text', 'label' => __( 'Author Instagram URL', 'gp-house-core' ) ),
			'gp_author_x'         => array( 'type' => 'text', 'label' => __( 'Author X URL', 'gp-house-core' ) ),
			'gp_author_linkedin'  => array( 'type' => 'text', 'label' => __( 'Author LinkedIn URL', 'gp-house-core' ) ),
		),
		'term:gp_destination' => array(
			'gp_card_title' => array( 'type' => 'text', 'label' => __( 'Card title (e.g. Phuket Beach)', 'gp-house-core' ) ),
			'gp_subtitle'   => array( 'type' => 'text', 'label' => __( 'Card subtitle', 'gp-house-core' ) ),
			'gp_image_id'   => array( 'type' => 'image', 'label' => __( 'Card image (portrait 360 × 580)', 'gp-house-core' ) ),
			'gp_region_title'    => array( 'type' => 'text', 'label' => __( 'Destinations page card title (empty = hide card)', 'gp-house-core' ) ),
			'gp_region_text'     => array( 'type' => 'textarea', 'label' => __( 'Destinations page card text', 'gp-house-core' ) ),
			'gp_from_price'      => array( 'type' => 'number', 'label' => __( 'From price per night (USD)', 'gp-house-core' ) ),
			'gp_region_image_id' => array( 'type' => 'image', 'label' => __( 'Destinations page card image (332 × 240)', 'gp-house-core' ) ),
			'gp_page_hero_id'     => array( 'type' => 'image', 'label' => __( 'Destination page hero (1440 × 562)', 'gp-house-core' ) ),
			'gp_page_hero_shaded' => array( 'type' => 'checkbox', 'label' => __( 'Hero image already includes the dark shade (skip the extra overlay)', 'gp-house-core' ) ),
			'gp_page_hero_mobile_id' => array( 'type' => 'image', 'label' => __( 'Destination page hero — mobile crop (402 × 414, optional)', 'gp-house-core' ) ),
			'gp_insider_mobile_id'   => array( 'type' => 'image', 'label' => __( 'Insider Secrets image — mobile crop (362 × 250, optional)', 'gp-house-core' ) ),
			'gp_page_hero_text'   => array( 'type' => 'textarea', 'label' => __( 'Destination page hero text', 'gp-house-core' ) ),
			'gp_insider_image_id' => array( 'type' => 'image', 'label' => __( 'Insider Secrets image (552 × 647)', 'gp-house-core' ) ),
			'gp_insider_items'    => array( 'type' => 'lines', 'label' => __( 'Insider Secrets — one per line as icon | Title | Text (icons: viewpoint, season, gastronomy)', 'gp-house-core' ) ),
			'gp_faqs'             => array( 'type' => 'lines', 'label' => __( 'FAQs — one per line as Question | Answer', 'gp-house-core' ) ),
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
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'gallery':
			$ids = is_array( $value ) ? $value : explode( ',', (string) $value );
			return implode( ',', array_filter( array_map( 'absint', $ids ) ) );
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

		case 'textarea':
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			printf( '<textarea id="%s" name="gp_core[%s]" rows="3" class="widefat">%s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( (string) $value ) );
			break;

		case 'lines':
			printf( '<label for="%s">%s</label>', esc_attr( $id ), esc_html( $field['label'] ) );
			printf( '<textarea id="%s" name="gp_core[%s]" rows="4" class="widefat">%s</textarea>', esc_attr( $id ), esc_attr( $key ), esc_textarea( implode( "\n", (array) $value ) ) );
			break;

		case 'gallery':
			printf( '<label>%s</label>', esc_html( $field['label'] ) );
			echo '<div class="gp-core-gallery" data-gp-gallery><div class="gp-core-gallery__list">';
			foreach ( array_filter( explode( ',', (string) $value ) ) as $att ) {
				printf( '<img src="%s" alt="">', esc_url( (string) wp_get_attachment_image_url( (int) $att, 'thumbnail' ) ) );
			}
			printf( '</div><input type="hidden" name="gp_core[%s]" value="%s"><button type="button" class="button" data-gp-gallery-pick>%s</button></div>', esc_attr( $key ), esc_attr( $value ), esc_html__( 'Select photos', 'gp-house-core' ) );
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
	wp_add_inline_style( 'common', '.gp-core-field{margin:0 0 14px}.gp-core-field>label{display:block;font-weight:600;margin-bottom:4px}.gp-core-field--checkbox>label{font-weight:400}.gp-core-image img{display:block;max-width:120px;height:auto;border-radius:6px;margin-bottom:6px}.gp-core-gallery__list{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:6px}.gp-core-gallery__list img{width:72px;height:56px;object-fit:cover;border-radius:6px}.gp-core-field--gallery,.gp-core-field--lines,.gp-core-field--textarea{grid-column:1/-1}.gp-core-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:4px 20px}' );
} );
