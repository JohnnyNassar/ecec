<?php
// Remove the [ecec_year_timeline] shortcode widget from the Projects page (post 122)
// Elementor data. Idempotent: exits cleanly if already removed.
//
// Run locally:  php _deploy/remove_year_timeline_from_projects.php
// Run on VPS:   upload to /var/www/html/ecec/_tmp/ then: php /var/www/html/ecec/_tmp/remove_year_timeline_from_projects.php

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$post_id = 122;
$raw = get_post_meta( $post_id, '_elementor_data', true );
$data = is_array( $raw ) ? $raw : json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

function ecec_yt_strip( &$elements ) {
	$removed = 0;
	foreach ( $elements as $k => $child ) {
		$sc = $child['settings']['shortcode'] ?? '';
		if ( strpos( $sc, 'ecec_year_timeline' ) !== false ) {
			unset( $elements[ $k ] );
			$removed++;
			continue;
		}
		if ( ! empty( $child['elements'] ) ) {
			$removed += ecec_yt_strip( $elements[ $k ]['elements'] );
		}
	}
	$elements = array_values( $elements );
	return $removed;
}

$count = ecec_yt_strip( $data );
if ( $count === 0 ) {
	echo "ALREADY REMOVED (0 occurrences)\n";
	exit( 0 );
}

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

$upload = wp_upload_dir();
$css = trailingslashit( $upload['basedir'] ) . 'elementor/css/post-' . $post_id . '.css';
if ( file_exists( $css ) ) { @unlink( $css ); echo "removed $css\n"; }

echo "removed $count occurrence(s) of [ecec_year_timeline] from post $post_id\n";
