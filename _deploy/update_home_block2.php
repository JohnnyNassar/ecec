<?php
/**
 * Restructure home page (post 119) Block 2 HTML widget into a 2-column row.
 * Only touches the inner HTML of widget id=570034a (Block 2's only widget).
 * Idempotent: if the markup is already updated, exits without changes.
 */
require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Must be logged in as admin.' );
}

$pid          = 119;
$target_id    = '570034a';
$new_html     = '<section class="ecec-home-intro"><div class="ecec-home-intro__row"><h2 class="ecec-home-intro__heading">We Design the Future</h2><div class="ecec-home-intro__body"><p>ECEC, a prominent engineering consultancy based in Dubai, UAE, expands its presence with offices strategically located in Riyadh, KSA, and Amman, Jordan. Our team comprises a dynamic mix of professionals representing various nationalities</p></div></div></section>';

$data_raw = get_post_meta( $pid, '_elementor_data', true );
$data = is_array( $data_raw ) ? $data_raw : json_decode( $data_raw, true );
if ( ! is_array( $data ) ) { echo "ERROR: post {$pid} has no Elementor data.\n"; exit( 1 ); }

$found = false;
$already = false;
function patch( &$els, $needle, $new_html, &$found, &$already ) {
	foreach ( $els as &$el ) {
		if ( ( $el['id'] ?? '' ) === $needle ) {
			$found = true;
			$current = $el['settings']['html'] ?? '';
			if ( strpos( $current, 'ecec-home-intro__row' ) !== false ) {
				$already = true;
			}
			$el['settings']['html'] = $new_html;
			return;
		}
		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			patch( $el['elements'], $needle, $new_html, $found, $already );
			if ( $found ) { return; }
		}
	}
}
patch( $data, $target_id, $new_html, $found, $already );

if ( ! $found ) { echo "ERROR: widget {$target_id} not found in post {$pid}.\n"; exit( 1 ); }
if ( $already ) { echo "NOTE: widget {$target_id} already has the new __row markup. Re-saving anyway (idempotent).\n"; }

update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );

// Clear Elementor caches so the new markup takes effect immediately.
delete_post_meta( $pid, '_elementor_element_cache' );
delete_post_meta( $pid, '_elementor_css' );

echo "DONE. Block 2 widget {$target_id} updated on post {$pid}.\n";
echo "Elementor element + CSS caches cleared for this post.\n";
