<?php
/**
 * Fix two data gaps on portfolio-items site-wide:
 *
 *  1. Set qodef_title_layout='breadcrumbs' on every published portfolio-item
 *     so the page-title bar (with breadcrumb) renders consistently.
 *
 *  2. For projects WITHOUT qodef_portfolio_info_items, auto-fill a minimal
 *     sidebar from data we already have:
 *       - Location  ← portfolio-location taxonomy terms
 *       - Sector    ← portfolio-category taxonomy terms
 *       - Completion← post_date year (only 2010–2024 to skip client-create dates)
 *     Format matches existing qodef serialization (array of arrays with
 *     qodef_info_item_label / value / link / target keys).
 *
 * Idempotent. Skips items that already have qodef_portfolio_info_items.
 *
 * Modes (CLI args): dry | confirm | undo
 *   dry      = print would-do summary
 *   confirm  = perform writes (default if no arg)
 *   undo     = remove qodef_portfolio_info_items only on items marked
 *              _ecec_sidebar_autofilled_at (leaves manually-edited sidebars)
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$mode = isset( $argv[1] ) ? strtolower( $argv[1] ) : 'confirm';
if ( ! in_array( $mode, [ 'dry', 'confirm', 'undo' ], true ) ) { $mode = 'confirm'; }
echo "MODE: {$mode}\n\n";

$posts = get_posts( [
	'post_type'   => 'portfolio-item',
	'post_status' => 'publish',
	'numberposts' => -1,
	'orderby'     => 'ID',
	'order'       => 'ASC',
] );
echo "Total published portfolio-items: " . count( $posts ) . "\n\n";

$counts = [
	'breadcrumb_set'      => 0,
	'breadcrumb_skipped'  => 0,
	'sidebar_autofilled'  => 0,
	'sidebar_already'     => 0,
	'sidebar_no_data'     => 0,
	'undone'              => 0,
];

// ── UNDO ──────────────────────────────────────────────────────────────
if ( $mode === 'undo' ) {
	foreach ( $posts as $p ) {
		$marker = get_post_meta( $p->ID, '_ecec_sidebar_autofilled_at', true );
		if ( ! $marker ) { continue; }
		delete_post_meta( $p->ID, 'qodef_portfolio_info_items' );
		delete_post_meta( $p->ID, '_ecec_sidebar_autofilled_at' );
		echo "  UNDO #{$p->ID} '{$p->post_title}'\n";
		$counts['undone']++;
	}
	echo "\nDone. Undid {$counts['undone']} sidebar autofills.\n";
	exit;
}

// ── DRY / CONFIRM ──────────────────────────────────────────────────────
foreach ( $posts as $p ) {
	$pid = $p->ID;

	// 1. Breadcrumb config
	$has_layout = get_post_meta( $pid, 'qodef_title_layout', true );
	if ( $has_layout !== 'breadcrumbs' ) {
		if ( $mode === 'confirm' ) {
			update_post_meta( $pid, 'qodef_title_layout', 'breadcrumbs' );
		}
		$counts['breadcrumb_set']++;
	} else {
		$counts['breadcrumb_skipped']++;
	}

	// 2. Sidebar autofill — only if currently empty
	$existing = get_post_meta( $pid, 'qodef_portfolio_info_items', true );
	if ( ! empty( $existing ) && is_array( $existing ) ) {
		$counts['sidebar_already']++;
		continue;
	}

	// Build rows from data we have
	$rows = [];

	// Location (portfolio-location taxonomy)
	$loc_terms = get_the_terms( $pid, 'portfolio-location' );
	if ( ! is_wp_error( $loc_terms ) && ! empty( $loc_terms ) ) {
		$rows[] = [
			'qodef_info_item_label'  => 'Location',
			'qodef_info_item_value'  => implode( ', ', wp_list_pluck( $loc_terms, 'name' ) ),
			'qodef_info_item_link'   => '',
			'qodef_info_item_target' => '_blank',
		];
	}

	// Sector (portfolio-category taxonomy)
	$cat_terms = get_the_terms( $pid, 'portfolio-category' );
	if ( ! is_wp_error( $cat_terms ) && ! empty( $cat_terms ) ) {
		$rows[] = [
			'qodef_info_item_label'  => 'Sector',
			'qodef_info_item_value'  => implode( ', ', wp_list_pluck( $cat_terms, 'name' ) ),
			'qodef_info_item_link'   => '',
			'qodef_info_item_target' => '_blank',
		];
	}

	// Completion year (only original imported projects, skip client-add dates)
	if ( $p->post_date ) {
		$year = (int) mysql2date( 'Y', $p->post_date );
		if ( $year >= 2010 && $year <= 2024 ) {
			$rows[] = [
				'qodef_info_item_label'  => 'Completion',
				'qodef_info_item_value'  => (string) $year,
				'qodef_info_item_link'   => '',
				'qodef_info_item_target' => '_blank',
			];
		}
	}

	if ( count( $rows ) === 0 ) {
		$counts['sidebar_no_data']++;
		echo "  SKIP #{$pid} '{$p->post_title}' — no taxonomy/year data to derive sidebar from\n";
		continue;
	}

	if ( $mode === 'confirm' ) {
		update_post_meta( $pid, 'qodef_portfolio_info_items', $rows );
		update_post_meta( $pid, '_ecec_sidebar_autofilled_at', gmdate( 'c' ) );
	}
	$counts['sidebar_autofilled']++;
	echo "  AUTOFILL #{$pid} '{$p->post_title}' — " . count( $rows ) . " rows\n";
}

echo "\n===== TOTALS =====\n";
foreach ( $counts as $k => $v ) {
	printf( "  %-22s %d\n", $k . ':', $v );
}
echo "\nMode was: {$mode}\n";
if ( $mode === 'dry' ) { echo "(no DB writes — re-run with 'confirm' or no arg)\n"; }
