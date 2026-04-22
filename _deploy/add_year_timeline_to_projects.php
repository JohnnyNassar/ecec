<?php
// Insert [ecec_year_timeline] shortcode widget above the existing portfolio list
// on the Projects page (post 122).
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$post_id = 122;
$raw = get_post_meta( $post_id, '_elementor_data', true );
$data = is_array( $raw ) ? $raw : json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

// Find the container holding the portfolio list; insert new widget at its start
function ecec_new_shortcode_widget( $code ) {
	// Elementor id is an 8-char hex hash
	$id = substr( md5( $code . microtime( true ) ), 0, 8 );
	return array(
		'id'         => $id,
		'elType'     => 'widget',
		'widgetType' => 'shortcode',
		'settings'   => array( 'shortcode' => $code ),
		'elements'   => array(),
	);
}

$inserted = false;
foreach ( $data as &$container ) {
	if ( ( $container['elType'] ?? '' ) !== 'container' ) continue;
	if ( empty( $container['elements'] ) ) continue;
	foreach ( $container['elements'] as $child ) {
		$s = $child['settings']['shortcode'] ?? '';
		if ( strpos( $s, 'emaurri_core_portfolio_list' ) !== false ) {
			// Already has timeline?
			foreach ( $container['elements'] as $existing ) {
				if ( strpos( $existing['settings']['shortcode'] ?? '', 'ecec_year_timeline' ) !== false ) {
					echo "ALREADY PRESENT, skipping\n";
					exit( 0 );
				}
			}
			array_unshift( $container['elements'], ecec_new_shortcode_widget( '[ecec_year_timeline]' ) );
			$inserted = true;
			break 2;
		}
	}
}
unset( $container );

if ( ! $inserted ) { echo "portfolio list not found\n"; exit( 1 ); }

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

echo "inserted [ecec_year_timeline] into projects page\n";
