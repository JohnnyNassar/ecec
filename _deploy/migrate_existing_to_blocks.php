<?php
/**
 * Migrate existing portfolio-item content into the _ecec_blocks format.
 *
 * For every portfolio-item that doesn't already have _ecec_blocks:
 *   - If post_content is non-empty → push a text-paragraph block
 *   - If qodef_portfolio_media has images → push a gallery block (3 cols)
 * Frontend output stays visually identical (the template's legacy fallback
 * renders the same two pieces through the same CSS classes); the migration
 * just makes these available as editable blocks in the drag-sort UI.
 *
 * Modes (must specify exactly one):
 *   ?dry=1      — preview; no writes
 *   ?confirm=1  — apply; writes _ecec_blocks + a provenance marker
 *   ?undo=1     — delete blocks on every project the script migrated
 *                 (uses the marker, so handcrafted projects like Desert Rock
 *                 Resort are never touched)
 *
 * Idempotent: projects that already have _ecec_blocks are skipped.
 *
 * Run via browser (admin must be logged in):
 *   http://localhost/ecec/_deploy/migrate_existing_to_blocks.php?dry=1
 */

// Path assumes this file lives in /_deploy/ one level below the WP root.
require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );

if ( $is_cli ) {
	// CLI: read mode from argv (`dry`, `confirm`, `undo`) — no HTTP auth.
	foreach ( $argv as $a ) {
		if ( $a === 'dry' )     { $_GET['dry']     = 1; }
		if ( $a === 'confirm' ) { $_GET['confirm'] = 1; }
		if ( $a === 'undo' )    { $_GET['undo']    = 1; }
	}
} else {
	// HTTP: require admin login.
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Log in as an administrator before running this script.' );
	}
	header( 'Content-Type: text/html; charset=utf-8' );
}

$is_dry     = ! empty( $_GET['dry'] );
$is_confirm = ! empty( $_GET['confirm'] );
$is_undo    = ! empty( $_GET['undo'] );
$mode_count = (int) $is_dry + (int) $is_confirm + (int) $is_undo;

if ( $mode_count !== 1 ) {
	if ( ! $is_cli ) { echo '<pre>'; }
	echo "Specify exactly one mode:\n";
	echo "  ?dry=1      — preview (no writes)\n";
	echo "  ?confirm=1  — apply\n";
	echo "  ?undo=1     — roll back previous migration\n";
	if ( ! $is_cli ) { echo '</pre>'; }
	exit;
}

$MARKER_KEY = '_ecec_blocks_migrated_at';
$BLOCKS_KEY = '_ecec_blocks';

$q = new WP_Query( [
	'post_type'      => 'portfolio-item',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'orderby'        => 'ID',
	'order'          => 'ASC',
	'fields'         => 'ids',
	'no_found_rows'  => true,
] );

if ( ! $is_cli ) { echo '<pre style="font-family: Menlo, Consolas, monospace; font-size: 13px; padding: 20px; line-height: 1.5;">'; }
echo str_repeat( '=', 78 ) . "\n";
echo "Mode: " . ( $is_dry ? 'DRY RUN (no writes)' : ( $is_confirm ? 'LIVE RUN' : 'UNDO' ) ) . "\n";
echo "Total portfolio-items found: " . count( $q->posts ) . "\n";
echo str_repeat( '=', 78 ) . "\n\n";

if ( $is_undo ) {
	$undone = 0;
	$skipped_handcrafted = 0;
	foreach ( $q->posts as $pid ) {
		$marker = get_post_meta( $pid, $MARKER_KEY, true );
		if ( empty( $marker ) ) {
			$skipped_handcrafted++;
			continue;
		}
		delete_post_meta( $pid, $BLOCKS_KEY );
		delete_post_meta( $pid, $MARKER_KEY );
		$undone++;
		echo sprintf( "UNDONE  #%-4d  %s\n", $pid, get_the_title( $pid ) );
	}
	echo "\n";
	echo "UNDONE:                 {$undone}\n";
	echo "Skipped (handcrafted):  {$skipped_handcrafted}\n";
	if ( ! $is_cli ) { echo '</pre>'; }
	exit;
}

$counts = [
	'migrated'  => [],
	'skipped'   => [],
	'empty'     => [],
];

foreach ( $q->posts as $pid ) {
	$title = get_the_title( $pid );

	$existing = get_post_meta( $pid, $BLOCKS_KEY, true );
	if ( ! empty( $existing ) ) {
		$counts['skipped'][] = [ $pid, $title, 'already has _ecec_blocks' ];
		continue;
	}

	$post    = get_post( $pid );
	$content = $post ? trim( $post->post_content ) : '';
	$media   = get_post_meta( $pid, 'qodef_portfolio_media', true );

	$blocks = [];

	if ( $content !== '' ) {
		$blocks[] = [
			'type' => 'text-paragraph',
			'body' => $content,
		];
	}

	$image_ids = [];
	if ( is_array( $media ) ) {
		foreach ( $media as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$img_id = isset( $row['qodef_portfolio_media_image']['id'] )
				? (int) $row['qodef_portfolio_media_image']['id']
				: 0;
			if ( $img_id > 0 ) {
				$image_ids[] = $img_id;
			}
		}
	}
	$image_ids = array_values( array_unique( $image_ids ) );

	if ( ! empty( $image_ids ) ) {
		$blocks[] = [
			'type'      => 'gallery',
			'image_ids' => $image_ids,
			'columns'   => 3,
		];
	}

	if ( empty( $blocks ) ) {
		$counts['empty'][] = [ $pid, $title, 'no post_content and no portfolio_media' ];
		continue;
	}

	// Build a short summary of what would be written.
	$summary_parts = [];
	foreach ( $blocks as $b ) {
		if ( $b['type'] === 'text-paragraph' ) {
			$len = mb_strlen( wp_strip_all_tags( $b['body'] ) );
			$summary_parts[] = "text({$len}c)";
		} elseif ( $b['type'] === 'gallery' ) {
			$summary_parts[] = 'gallery(' . count( $b['image_ids'] ) . ' imgs)';
		}
	}
	$summary = implode( ' + ', $summary_parts );

	if ( $is_confirm ) {
		update_post_meta(
			$pid,
			$BLOCKS_KEY,
			wp_slash( wp_json_encode( $blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) )
		);
		update_post_meta( $pid, $MARKER_KEY, gmdate( 'c' ) );
	}

	$counts['migrated'][] = [ $pid, $title, $summary ];
}

echo "MIGRATED " . ( $is_dry ? '(would migrate) ' : '' ) . ": " . count( $counts['migrated'] ) . "\n";
foreach ( $counts['migrated'] as $r ) {
	echo sprintf( "  #%-4d  %-55s  %s\n", $r[0], mb_strimwidth( $r[1], 0, 55, '…' ), $r[2] );
}

echo "\nSKIPPED (already has blocks): " . count( $counts['skipped'] ) . "\n";
foreach ( $counts['skipped'] as $r ) {
	echo sprintf( "  #%-4d  %-55s  %s\n", $r[0], mb_strimwidth( $r[1], 0, 55, '…' ), $r[2] );
}

echo "\nEMPTY (no content, no media): " . count( $counts['empty'] ) . "\n";
foreach ( $counts['empty'] as $r ) {
	echo sprintf( "  #%-4d  %-55s  %s\n", $r[0], mb_strimwidth( $r[1], 0, 55, '…' ), $r[2] );
}

echo "\n" . str_repeat( '=', 78 ) . "\n";
if ( $is_dry ) {
	echo "DRY RUN COMPLETE. Re-run with ?confirm=1 to apply.\n";
} else {
	echo "LIVE RUN COMPLETE.\n";
	echo "To roll back, hit: _deploy/migrate_existing_to_blocks.php?undo=1\n";
}
if ( ! $is_cli ) { echo '</pre>'; }
