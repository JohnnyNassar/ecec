<?php
/**
 * Bump the "sectors" progress-bar number in /our-services/ Elementor data.
 *
 * The widget's `number` setting is both the displayed value AND the bar fill
 * percent (0-100). With number=10 the bar fills only 10% — visually weak next
 * to the 80/60 bars. This script rewrites it to 100 to represent full sector
 * coverage. Client can fine-tune via Elementor UI after.
 *
 * CLI: `sudo -u www-data php _deploy/bump_services_sector_bar.php [new_number]`
 * HTTP: ?confirm=1 (admin only). Pass ?number=N to set a specific value.
 */

require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );
$new_number = 100;
if ( $is_cli ) {
	if ( isset( $argv[1] ) && ctype_digit( (string) $argv[1] ) ) { $new_number = (int) $argv[1]; }
} else {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }
	header( 'Content-Type: text/plain; charset=utf-8' );
	if ( empty( $_GET['confirm'] ) ) { echo "Add ?confirm=1 (or ?number=N) to apply.\n"; exit; }
	if ( isset( $_GET['number'] ) && ctype_digit( (string) $_GET['number'] ) ) { $new_number = (int) $_GET['number']; }
}

$target = get_page_by_path( 'our-services', OBJECT, 'page' );
if ( ! ( $target instanceof WP_Post ) ) {
	echo "FAIL: /our-services/ page not found.\n"; exit( 1 );
}
echo "Target: #{$target->ID} '{$target->post_title}'\n";
echo "New number: $new_number\n";

$raw = get_post_meta( $target->ID, '_elementor_data', true );
$data = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );
if ( ! is_array( $data ) ) {
	echo "FAIL: _elementor_data unreadable.\n"; exit( 1 );
}

$changes = [];
$walk = function ( &$el ) use ( &$walk, $new_number, &$changes ) {
	if ( isset( $el['widgetType'] ) && $el['widgetType'] === 'emaurri_core_progress_bar' ) {
		$title = $el['settings']['title'] ?? '';
		if ( strtolower( trim( $title ) ) === 'sectors' ) {
			$old = $el['settings']['number'] ?? '?';
			$el['settings']['number'] = (string) $new_number;
			$changes[] = "progress_bar sectors: $old -> $new_number";
		}
	}
	if ( isset( $el['elements'] ) && is_array( $el['elements'] ) ) {
		foreach ( $el['elements'] as &$child ) { $walk( $child ); }
	}
};
foreach ( $data as &$section ) { $walk( $section ); }

if ( empty( $changes ) ) {
	echo "No 'sectors' progress bar found. Aborting.\n"; exit( 1 );
}

foreach ( $changes as $c ) { echo "  - $c\n"; }

$new_json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
update_post_meta( $target->ID, '_elementor_data', wp_slash( $new_json ) );

// Clear Elementor caches on the target.
delete_post_meta( $target->ID, '_elementor_css' );
delete_post_meta( $target->ID, '_elementor_element_cache' );

echo "Done.\n";
