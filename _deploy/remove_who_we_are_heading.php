<?php
/**
 * Remove the "WHO WE ARE" h3 heading widget from People page (post 123)
 * intro section. Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$pid = 123;
$data_raw = get_post_meta( $pid, '_elementor_data', true );
$data = is_array( $data_raw ) ? $data_raw : json_decode( $data_raw, true );

$removed = 0;
function strip_who_we_are( &$els, &$removed ) {
	foreach ( $els as $i => $el ) {
		if ( ( $el['widgetType'] ?? '' ) === 'heading' ) {
			$title = trim( $el['settings']['title'] ?? '' );
			if ( strcasecmp( $title, 'WHO WE ARE' ) === 0 ) {
				unset( $els[ $i ] );
				$removed++;
				continue;
			}
		}
		if ( ! empty( $el['elements'] ) ) { strip_who_we_are( $els[ $i ]['elements'], $removed ); }
	}
	$els = array_values( $els );
}
strip_who_we_are( $data, $removed );

if ( $removed > 0 ) {
	update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $pid, '_elementor_element_cache' );
	delete_post_meta( $pid, '_elementor_css' );
	echo "Removed {$removed} 'WHO WE ARE' heading(s) from post {$pid}.\n";
} else {
	echo "No 'WHO WE ARE' heading found (already removed?).\n";
}
