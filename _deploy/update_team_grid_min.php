<?php
/**
 * Update post 123's [ecec_team_grid] shortcode to add min="6" so the grid
 * renders 6 cards (2 real + 4 placeholders). Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$pid = 123;
$data_raw = get_post_meta( $pid, '_elementor_data', true );
$data = is_array( $data_raw ) ? $data_raw : json_decode( $data_raw, true );

$updated = 0;
function update_shortcode( &$els, &$updated ) {
	foreach ( $els as &$el ) {
		if ( ( $el['widgetType'] ?? '' ) === 'shortcode' ) {
			$sc = $el['settings']['shortcode'] ?? '';
			if ( strpos( $sc, 'ecec_team_grid' ) !== false ) {
				// Replace [ecec_team_grid columns="3"] with min="6" added (or update existing min)
				if ( preg_match( '/min="\d+"/', $sc ) ) {
					$sc = preg_replace( '/min="\d+"/', 'min="6"', $sc );
				} else {
					$sc = preg_replace( '/\[ecec_team_grid([^\]]*)\]/', '[ecec_team_grid$1 min="6"]', $sc );
				}
				$el['settings']['shortcode'] = $sc;
				$updated++;
				echo "Updated shortcode → {$sc}\n";
			}
		}
		if ( ! empty( $el['elements'] ) ) { update_shortcode( $el['elements'], $updated ); }
	}
}
update_shortcode( $data, $updated );

if ( $updated > 0 ) {
	update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	delete_post_meta( $pid, '_elementor_element_cache' );
	delete_post_meta( $pid, '_elementor_css' );
}
echo "Done. Updated {$updated} shortcode(s).\n";
