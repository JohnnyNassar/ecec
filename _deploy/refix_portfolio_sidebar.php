<?php
/**
 * Re-derive qodef_portfolio_info_items for projects we autofilled — using
 * wp_get_object_terms (bypasses the get_the_terms cache that lost portfolio-
 * category lookups during the first autofill run on the CLI).
 *
 * Only touches items marked _ecec_sidebar_autofilled_at — leaves manually
 * curated sidebars (incl. Desert Rock, Royal Atlantis) alone.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$posts = get_posts( [
	'post_type'   => 'portfolio-item',
	'post_status' => 'publish',
	'numberposts' => -1,
	'meta_key'    => '_ecec_sidebar_autofilled_at',
] );
echo "Re-fixing " . count( $posts ) . " autofilled projects.\n\n";

$fixed = 0;
foreach ( $posts as $p ) {
	$pid = $p->ID;
	$rows = [];

	// Direct SQL — wp_get_object_terms / get_the_terms both fail in this CLI
	// context for portfolio-* taxonomies (likely capability/cache filter).
	global $wpdb;
	$fetch_terms = function ( $taxonomy ) use ( $wpdb, $pid ) {
		return $wpdb->get_col( $wpdb->prepare(
			"SELECT t.name FROM {$wpdb->terms} t
			 JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			 JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			 WHERE tt.taxonomy = %s AND tr.object_id = %d",
			$taxonomy, $pid
		) );
	};
	$loc_names = $fetch_terms( 'portfolio-location' );
	if ( $loc_names ) {
		$rows[] = [
			'qodef_info_item_label'  => 'Location',
			'qodef_info_item_value'  => implode( ', ', $loc_names ),
			'qodef_info_item_link'   => '',
			'qodef_info_item_target' => '_blank',
		];
	}
	$cat_names = $fetch_terms( 'portfolio-category' );
	if ( $cat_names ) {
		$rows[] = [
			'qodef_info_item_label'  => 'Sector',
			'qodef_info_item_value'  => implode( ', ', $cat_names ),
			'qodef_info_item_link'   => '',
			'qodef_info_item_target' => '_blank',
		];
	}
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

	if ( count( $rows ) === 0 ) { continue; }

	update_post_meta( $pid, 'qodef_portfolio_info_items', $rows );
	echo "  REFIX #{$pid} '{$p->post_title}' — " . count( $rows ) . " rows\n";
	$fixed++;
}
echo "\nDone. Re-fixed {$fixed}.\n";
