<?php
/**
 * Roll back the broken About Us rebuild:
 *  - Restore post 120 slug + title to original
 *  - Delete or hide the broken new page (post created with slug about-us)
 * Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

// Find broken new About Us page (the one with current slug "about-us")
$broken = get_page_by_path( 'about-us', OBJECT, 'page' );
if ( $broken && $broken->ID !== 120 ) {
	// Move its slug aside so post 120 can reclaim "about-us"
	wp_update_post( [ 'ID' => $broken->ID, 'post_status' => 'draft', 'post_name' => 'about-us-broken-' . $broken->ID ] );
	echo "Moved broken page #{$broken->ID} to draft, slug 'about-us-broken-{$broken->ID}'.\n";
}

// Restore post 120 title + slug
$old = get_post( 120 );
if ( $old ) {
	wp_update_post( [
		'ID'         => 120,
		'post_title' => 'About Us',
		'post_name'  => 'about-us',
	] );
	echo "Restored post 120 → 'About Us' / 'about-us'.\n";
	delete_post_meta( 120, '_elementor_element_cache' );
	delete_post_meta( 120, '_elementor_css' );
	echo "Cleared Elementor caches on post 120.\n";
} else {
	echo "ERROR: post 120 not found.\n";
}

echo "Done.\n";
