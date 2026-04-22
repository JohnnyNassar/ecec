<?php
// Insert [ecec_project_search] shortcode widget above the year timeline on the
// Projects page (post 122). Order inside the container ends up:
//   [ecec_project_search]
//   [ecec_year_timeline]
//   [emaurri_core_portfolio_list ...]
// Idempotent: skips if already present.

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$post_id = 122;
$raw = get_post_meta( $post_id, '_elementor_data', true );
$data = is_array( $raw ) ? $raw : json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

function ecec_ps_new_shortcode_widget( $code ) {
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

	// Is this the container with the portfolio list?
	$has_list = false;
	foreach ( $container['elements'] as $child ) {
		if ( strpos( $child['settings']['shortcode'] ?? '', 'emaurri_core_portfolio_list' ) !== false ) {
			$has_list = true; break;
		}
	}
	if ( ! $has_list ) continue;

	// Already present?
	foreach ( $container['elements'] as $existing ) {
		if ( strpos( $existing['settings']['shortcode'] ?? '', 'ecec_project_search' ) !== false ) {
			echo "ALREADY PRESENT, skipping\n";
			exit( 0 );
		}
	}
	array_unshift( $container['elements'], ecec_ps_new_shortcode_widget( '[ecec_project_search]' ) );
	$inserted = true;
	break;
}
unset( $container );

if ( ! $inserted ) { echo "portfolio list container not found\n"; exit( 1 ); }

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

// Also clear the Elementor CSS file on disk if present
$upload = wp_upload_dir();
$css = trailingslashit( $upload['basedir'] ) . 'elementor/css/post-' . $post_id . '.css';
if ( file_exists( $css ) ) {
	@unlink( $css );
	echo "removed $css\n";
}

echo "inserted [ecec_project_search] into projects page (post $post_id)\n";
