<?php
/**
 * Mark an initial set of 6 projects as featured by setting _ecec_featured=1.
 * Idempotent: re-running is a no-op.
 *
 * Client can freely toggle featured status from the WP admin checkbox after this.
 *
 * Run:  php _deploy/seed_featured_projects.php
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

// Spread: KSA + UAE, across sustainability / hospitality / aviation / residential / education.
// menu_order controls display order (lower = earlier in the Featured grid).
$seed = array(
	108 => array( 'label' => 'Desert Rock Resort',             'order' => 1 ),
	114 => array( 'label' => 'Royal Atlantis Resort',          'order' => 2 ),
	112 => array( 'label' => 'KAIA Terminal Complex',          'order' => 3 ),
	60  => array( 'label' => 'AMAALA Wellness Core Resort',    'order' => 4 ),
	106 => array( 'label' => 'Regalia Residential Tower',      'order' => 5 ),
	38  => array( 'label' => 'SEE Institute',                  'order' => 6 ),
);

$ok = 0; $skip = 0; $err = 0;
foreach ( $seed as $pid => $meta ) {
	$p = get_post( $pid );
	if ( ! $p || $p->post_type !== 'portfolio-item' ) {
		fwrite( STDERR, "  skip: post $pid is not a portfolio-item\n" );
		$skip++; continue;
	}
	update_post_meta( $pid, '_ecec_featured', '1' );
	// Set menu_order only if it's currently 0 (default) — don't overwrite client tweaks.
	if ( (int) $p->menu_order === 0 ) {
		wp_update_post( array( 'ID' => $pid, 'menu_order' => $meta['order'] ) );
	}
	echo sprintf( "  %-3d order=%d  %s\n", $pid, $meta['order'], $meta['label'] );
	$ok++;
}

// Sanity
$featured = get_posts( array(
	'post_type'      => 'portfolio-item',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'meta_query'     => array( array( 'key' => '_ecec_featured', 'value' => '1' ) ),
	'fields'         => 'ids',
) );
echo "\nDone. assigned=$ok skipped=$skip errors=$err\n";
echo "Total featured projects now: " . count( $featured ) . "\n";
