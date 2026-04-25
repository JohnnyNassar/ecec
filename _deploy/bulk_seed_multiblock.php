<?php
/**
 * Bulk-seed the 7-block-with-placeholders layout (same as Royal Atlantis #114)
 * across all single-image portfolio-items, EXCEPT hand-curated ones.
 *
 * Per-project block layout:
 *   1. text-paragraph   — existing post_content verbatim
 *   2. image-text-split — REAL featured image + generic ECEC engineering copy
 *   3. image-pair       — 2 placeholders (no captions)
 *   4. full-image       — 1 placeholder (no caption)
 *   5. project-data     — rows from qodef_portfolio_info_items + auto Location + Completion
 *   6. pull-quote       — generic ECEC engineering quote
 *   7. gallery          — 3 placeholders, columns=3
 *
 * Skip list (already curated): 108 Desert Rock, 114 Royal Atlantis.
 *
 * Modes (CLI args or query params):
 *   dry  | ?dry=1     — print would-do summary, no DB writes
 *   confirm | (none)  — perform writes
 *   undo | ?undo=1    — restore _ecec_blocks_backup_premultiblock for every
 *                       project marked _ecec_blocks_bulkseeded_at
 *
 * Idempotency / safety:
 *   - On first write per project, current _ecec_blocks is copied to
 *     _ecec_blocks_backup_premultiblock (matches Royal Atlantis backup key).
 *   - Sets _ecec_blocks_bulkseeded_at = ISO timestamp marker (used by undo).
 *   - Re-running confirm overwrites with the latest layout (does NOT touch backup).
 */

require __DIR__ . '/../wp-load.php';

// ----- arg parsing (CLI + HTTP) -----
$mode = 'confirm';
if ( php_sapi_name() === 'cli' ) {
	$argv1 = isset( $argv[1] ) ? strtolower( $argv[1] ) : '';
	if ( in_array( $argv1, [ 'dry', 'confirm', 'undo' ], true ) ) { $mode = $argv1; }
} else {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Must be logged in as admin. Aborting.' );
	}
	if ( ! empty( $_GET['dry'] ) )   { $mode = 'dry'; }
	if ( ! empty( $_GET['undo'] ) )  { $mode = 'undo'; }
}

$skip_ids = [ 108, 114 ]; // Desert Rock, Royal Atlantis (curated)

// ----- generic ECEC copy (used on every bulk-seeded project) -----
$generic_split_overline = 'ENGINEERING APPROACH';
$generic_split_heading  = 'Built around the brief';
$generic_split_body     = "Each ECEC engagement is shaped by the constraints, programme, and ambition of the project itself. From early-stage MEP coordination through to delivery, our work prioritises engineering decisions that serve the design intent and the operator's long-term needs.";

$generic_quote_text     = 'Good engineering is invisible to the user and indispensable to the operator.';
$generic_quote_author   = 'ECEC Engineering Team';

// ----- collect candidate posts -----
$posts = get_posts( [
	'post_type'   => 'portfolio-item',
	'post_status' => 'publish',
	'numberposts' => -1,
	'orderby'     => 'ID',
	'order'       => 'ASC',
] );

echo "MODE: {$mode}\n";
echo "Total published portfolio-items: " . count( $posts ) . "\n";
echo "Skipping curated: " . implode( ',', $skip_ids ) . "\n\n";

$counts = [
	'targeted' => 0,
	'skipped_curated' => 0,
	'no_featured' => 0,
	'written' => 0,
	'unchanged_dryrun' => 0,
	'undone' => 0,
];

// ===== UNDO MODE =====
if ( $mode === 'undo' ) {
	foreach ( $posts as $p ) {
		$pid = $p->ID;
		$marker = get_post_meta( $pid, '_ecec_blocks_bulkseeded_at', true );
		if ( ! $marker ) { continue; }
		$backup = get_post_meta( $pid, '_ecec_blocks_backup_premultiblock', true );
		if ( $backup ) {
			update_post_meta( $pid, '_ecec_blocks', wp_slash( $backup ) );
		} else {
			delete_post_meta( $pid, '_ecec_blocks' );
		}
		delete_post_meta( $pid, '_ecec_blocks_bulkseeded_at' );
		echo "  UNDO #{$pid} '{$p->post_title}' — restored from backup\n";
		$counts['undone']++;
	}
	echo "\nDone. Restored {$counts['undone']} projects.\n";
	exit;
}

// ===== DRY / CONFIRM MODE =====
foreach ( $posts as $p ) {
	$pid = $p->ID;
	if ( in_array( $pid, $skip_ids, true ) ) {
		$counts['skipped_curated']++;
		continue;
	}

	$featured_id = (int) get_post_thumbnail_id( $pid );
	if ( ! $featured_id ) {
		$counts['no_featured']++;
		echo "  SKIP #{$pid} '{$p->post_title}' — no featured image\n";
		continue;
	}

	// Pull info items
	$info_items = get_post_meta( $pid, 'qodef_portfolio_info_items', true );
	$info_items = is_array( $info_items ) ? $info_items : [];

	// Auto-Location from taxonomy
	$loc_terms = get_the_terms( $pid, 'portfolio-location' );
	$location_str = '';
	if ( ! is_wp_error( $loc_terms ) && ! empty( $loc_terms ) ) {
		$location_str = implode( ', ', wp_list_pluck( $loc_terms, 'name' ) );
	}

	// Auto-Completion from post_date year — but only for the original imported
	// projects (post_date 2010–2024). Client-added projects have post_date in
	// 2026+ which is the WP create date, not the real completion year — skip.
	$completion_year = '';
	if ( $p->post_date ) {
		$year_int = (int) mysql2date( 'Y', $p->post_date );
		if ( $year_int >= 2010 && $year_int <= 2024 ) {
			$completion_year = (string) $year_int;
		}
	}

	// Build project-data rows: existing qodef rows first, then auto Location, then Completion
	$rows = [];
	foreach ( $info_items as $item ) {
		$label = isset( $item['qodef_info_item_label'] ) ? trim( $item['qodef_info_item_label'] ) : '';
		$value = isset( $item['qodef_info_item_value'] ) ? trim( $item['qodef_info_item_value'] ) : '';
		if ( $label === '' || $value === '' ) { continue; }
		$rows[] = [ 'label' => $label, 'value' => $value ];
	}
	if ( $location_str !== '' ) {
		// Don't duplicate if the qodef rows already have a Location label
		$has_location = false;
		foreach ( $rows as $r ) {
			if ( strcasecmp( $r['label'], 'Location' ) === 0 ) { $has_location = true; break; }
		}
		if ( ! $has_location ) {
			$rows[] = [ 'label' => 'Location', 'value' => $location_str ];
		}
	}
	if ( $completion_year ) {
		$has_completion = false;
		foreach ( $rows as $r ) {
			if ( strcasecmp( $r['label'], 'Completion' ) === 0 || strcasecmp( $r['label'], 'Year' ) === 0 ) { $has_completion = true; break; }
		}
		if ( ! $has_completion ) {
			$rows[] = [ 'label' => 'Completion', 'value' => $completion_year ];
		}
	}

	$blocks = [
		[ 'type' => 'text-paragraph', 'body' => (string) $p->post_content ],
		[
			'type'       => 'image-text-split',
			'image_id'   => $featured_id,
			'overline'   => $generic_split_overline,
			'heading'    => $generic_split_heading,
			'body'       => $generic_split_body,
			'image_side' => 'left',
		],
		[
			'type'           => 'image-pair',
			'image_id_left'  => 0,
			'image_id_right' => 0,
			'caption_left'   => '',
			'caption_right'  => '',
		],
		[
			'type'     => 'full-image',
			'image_id' => 0,
			'caption'  => '',
		],
		[
			'type'     => 'project-data',
			'overline' => 'PROJECT DATA',
			'heading'  => 'At a glance',
			'rows'     => $rows,
		],
		[
			'type'        => 'pull-quote',
			'quote'       => $generic_quote_text,
			'attribution' => $generic_quote_author,
		],
		[
			'type'      => 'gallery',
			'image_ids' => [ 0, 0, 0 ],
			'columns'   => 3,
		],
	];

	$counts['targeted']++;

	if ( $mode === 'dry' ) {
		echo "  DRY  #{$pid} '{$p->post_title}' — would write " . count( $blocks ) . " blocks; project-data rows=" . count( $rows ) . " (loc=" . ( $location_str ?: '-' ) . ", year=" . ( $completion_year ?: '-' ) . ")\n";
		$counts['unchanged_dryrun']++;
		continue;
	}

	// CONFIRM mode — backup once, write, mark
	$existing = get_post_meta( $pid, '_ecec_blocks', true );
	$backup = get_post_meta( $pid, '_ecec_blocks_backup_premultiblock', true );
	if ( $existing && ! $backup ) {
		update_post_meta( $pid, '_ecec_blocks_backup_premultiblock', $existing );
	}
	update_post_meta( $pid, '_ecec_blocks', wp_slash( wp_json_encode( $blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );
	update_post_meta( $pid, '_ecec_blocks_bulkseeded_at', gmdate( 'c' ) );
	echo "  WRITE #{$pid} '{$p->post_title}' — " . count( $blocks ) . " blocks, project-data rows=" . count( $rows ) . "\n";
	$counts['written']++;
}

echo "\n===== TOTALS =====\n";
foreach ( $counts as $k => $v ) {
	printf( "  %-20s %d\n", $k . ':', $v );
}
echo "\nMode was: {$mode}\n";
if ( $mode === 'dry' ) { echo "(no DB changes — re-run with 'confirm' arg or no ?dry param to actually write)\n"; }
