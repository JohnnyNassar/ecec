<?php
// 1. Seed the Team Members CPT with Khaled Al Assi (if empty).
// 2. Replace the hardcoded Elementor card on the People page with
//    a single [ecec_team_grid] shortcode widget.
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

// ─── seed ───
$existing = get_posts( array(
	'post_type'      => 'ecec_team_member',
	'post_status'    => array( 'publish', 'draft' ),
	'posts_per_page' => 1,
) );
if ( empty( $existing ) ) {
	$khaled_id = wp_insert_post( array(
		'post_type'   => 'ecec_team_member',
		'post_title'  => 'Khaled Al Assi',
		'post_status' => 'publish',
		'menu_order'  => 1,
	) );
	update_post_meta( $khaled_id, '_ecec_team_role', 'Founder | Principal' );
	update_post_meta( $khaled_id, '_ecec_team_locations', 'Dubai | Riyadh | Amman' );
	// Image id 10 is the khaled-al-assi.png attachment from the old page
	set_post_thumbnail( $khaled_id, 10 );
	echo "seeded Khaled as team-member id {$khaled_id}\n";
} else {
	echo "team-members already seeded, skipping\n";
}

// ─── swap Elementor card ───
$post_id = 123;
$raw = get_post_meta( $post_id, '_elementor_data', true );
$data = is_array( $raw ) ? $raw : json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

$replaced = false;
foreach ( $data as &$container ) {
	if ( ( $container['id'] ?? '' ) !== '5614a79' ) continue;
	// Already swapped?
	foreach ( (array) ( $container['elements'] ?? array() ) as $e ) {
		$sc = $e['settings']['shortcode'] ?? '';
		if ( strpos( $sc, 'ecec_team_grid' ) !== false ) {
			echo "people page already swapped, skipping\n";
			exit( 0 );
		}
	}
	$container['elements'] = array(
		array(
			'id'         => substr( md5( 'ecec_team_grid_' . microtime( true ) ), 0, 8 ),
			'elType'     => 'widget',
			'widgetType' => 'shortcode',
			'settings'   => array( 'shortcode' => '[ecec_team_grid columns="3"]' ),
			'elements'   => array(),
		),
	);
	$replaced = true;
	break;
}
unset( $container );
if ( ! $replaced ) { echo "container 5614a79 not found on people page\n"; exit( 1 ); }

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

echo "people page (123) card swapped for [ecec_team_grid]\n";
