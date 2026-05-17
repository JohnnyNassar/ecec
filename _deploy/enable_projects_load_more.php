<?php
/**
 * Switch the Recent Projects grid on post 122 from no-pagination/54 to
 * load-more/12 so users see 4 rows (3-col grid) then click "Load More".
 *
 * Idempotent: scans the _elementor_data blob for the portfolio-list shortcode
 * and rewrites the two attributes in place. Safe to re-run.
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$target = get_page_by_path( 'projects' );
if ( ! $target ) { echo "FAIL: no page with slug 'projects'\n"; exit( 1 ); }
$post_id = (int) $target->ID;
echo "Target: post {$post_id} '{$target->post_title}' (slug=projects)\n";
$raw     = get_post_meta( $post_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) { echo "no elementor data\n"; exit( 1 ); }

$needle_pp   = 'posts_per_page=\"54\"';
$needle_pag  = 'pagination_type=\"no-pagination\"';
$replace_pp  = 'posts_per_page=\"12\"';
$replace_pag = 'pagination_type=\"load-more\"';

$has_pp  = strpos( $raw, $needle_pp )  !== false;
$has_pag = strpos( $raw, $needle_pag ) !== false;

if ( ! $has_pp && ! $has_pag ) {
	echo "Already switched (no match for old values) — nothing to do.\n";
	exit( 0 );
}

$new = $raw;
if ( $has_pp )  $new = str_replace( $needle_pp,  $replace_pp,  $new );
if ( $has_pag ) $new = str_replace( $needle_pag, $replace_pag, $new );

update_post_meta( $post_id, '_elementor_data', wp_slash( $new ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

echo "Updated post {$post_id}: posts_per_page=12, pagination_type=load-more\n";
