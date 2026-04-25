<?php
/**
 * Configure post 382 (About Us) to render the Qode theme's standard
 * page-title bar (chairs hero, 470px tall, white title) — replicates the
 * Emaurri demo's hero block.
 *
 * Also removes the duplicate "About Us" h1 from Elementor section 1
 * (per user: no need to keep "About Us" as a title of the page).
 *
 * Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$pid = 382;

// 1. Set qodef title bar config
update_post_meta( $pid, 'qodef_title_layout', 'standard' );
update_post_meta( $pid, 'qodef_page_title_height', '470' );
echo "Set qodef_title_layout=standard, qodef_page_title_height=470 on post {$pid}.\n";

// 2. Remove the Elementor heading widget that says "About Us" in section 1
$data_raw = get_post_meta( $pid, '_elementor_data', true );
$data = is_array( $data_raw ) ? $data_raw : json_decode( $data_raw, true );
if ( ! is_array( $data ) ) { echo "ERROR: post {$pid} has no Elementor data.\n"; exit( 1 ); }

$removed = 0;
function strip_about_h1( &$els, &$removed ) {
	foreach ( $els as $i => $el ) {
		if ( ( $el['widgetType'] ?? '' ) === 'heading' ) {
			$title = trim( $el['settings']['title'] ?? '' );
			if ( strcasecmp( $title, 'ABOUT US' ) === 0 || strcasecmp( $title, 'About Us' ) === 0 ) {
				unset( $els[ $i ] );
				$removed++;
				continue;
			}
		}
		if ( ! empty( $el['elements'] ) ) { strip_about_h1( $els[ $i ]['elements'], $removed ); }
	}
	$els = array_values( $els );
}
strip_about_h1( $data, $removed );

if ( $removed > 0 ) {
	update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $pid, '_elementor_element_cache' );
	delete_post_meta( $pid, '_elementor_css' );
	echo "Removed {$removed} 'About Us' heading widget(s) from Elementor data.\n";
} else {
	echo "No 'About Us' heading widget found to remove (already removed?).\n";
}

echo "\nDone.\n";
