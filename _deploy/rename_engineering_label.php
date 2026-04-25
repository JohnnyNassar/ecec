<?php
/**
 * Rename portfolio-info sidebar label "Engineering" → "Services Provided"
 * across every portfolio-item. Case-insensitive match on the label,
 * value left unchanged. Idempotent — re-running is a no-op.
 *
 * Usage:
 *   D:\xampp\php\php.exe D:\xampp\htdocs\ecec\_deploy\rename_engineering_label.php [--dry-run]
 *   (or via browser with ?force=1&dry-run= as query params)
 */

require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) && ! isset( $_GET['force'] ) ) {
	wp_die( 'Admin only, or pass ?force=1 (local). Aborting.' );
}

$dry_run = in_array( '--dry-run', $argv ?? [], true ) || isset( $_GET['dry-run'] );
$old     = 'engineering';
$new     = 'Services Provided';

// Optional scope: --post-id=N (CLI) or ?post_id=N (HTTP). No filter = all.
$scope_id = 0;
if ( isset( $argv ) ) {
	foreach ( $argv as $a ) {
		if ( preg_match( '/^--post-id=(\d+)$/', $a, $m ) ) { $scope_id = (int) $m[1]; }
	}
}
if ( isset( $_GET['post_id'] ) ) { $scope_id = (int) $_GET['post_id']; }

$query_args = [
	'post_type'   => 'portfolio-item',
	'post_status' => 'any',
	'numberposts' => -1,
	'fields'      => 'ids',
];
if ( $scope_id > 0 ) {
	$query_args['include'] = [ $scope_id ];
	echo "Scope: post id {$scope_id} only.\n";
}

$posts = get_posts( $query_args );

echo "Scanning " . count( $posts ) . " portfolio-item(s). Dry-run: " . ( $dry_run ? 'YES' : 'no' ) . "\n";

$updated_posts = 0;
$updated_rows  = 0;
$no_match      = [];

foreach ( $posts as $pid ) {
	$items = get_post_meta( $pid, 'qodef_portfolio_info_items', true );
	if ( ! is_array( $items ) || empty( $items ) ) {
		$no_match[] = $pid;
		continue;
	}
	$changed = false;
	foreach ( $items as &$item ) {
		if ( ! is_array( $item ) ) { continue; }
		$label = isset( $item['qodef_info_item_label'] ) ? (string) $item['qodef_info_item_label'] : '';
		if ( strcasecmp( trim( $label ), $old ) === 0 ) {
			$item['qodef_info_item_label'] = $new;
			$changed = true;
			$updated_rows++;
		}
	}
	unset( $item );

	if ( $changed ) {
		$updated_posts++;
		$title = get_the_title( $pid );
		echo "  #{$pid}  {$title}  → rewrote 'Engineering' label\n";
		if ( ! $dry_run ) {
			update_post_meta( $pid, 'qodef_portfolio_info_items', $items );
		}
	}
}

echo "\n";
echo "Updated posts: {$updated_posts}\n";
echo "Updated rows : {$updated_rows}\n";
echo "Posts with no qodef_portfolio_info_items meta: " . count( $no_match );
if ( ! empty( $no_match ) ) { echo ' (ids: ' . implode( ',', array_slice( $no_match, 0, 20 ) ) . ( count( $no_match ) > 20 ? ',…' : '' ) . ')'; }
echo "\n";
if ( $dry_run ) {
	echo "\nDry-run: no changes written. Re-run without --dry-run to apply.\n";
}
