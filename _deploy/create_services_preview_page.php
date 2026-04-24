<?php
/**
 * Create the /our-services-preview/ WordPress page if it doesn't exist.
 * Idempotent — running twice is a no-op.
 *
 * Run via browser (admin logged in) or CLI (`sudo -u www-data php ...`).
 */

require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );
if ( ! $is_cli ) {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Log in as an administrator before running this script.' );
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
}

$slug = 'our-services-preview';

// 1. Existing page with this slug?
$existing = get_page_by_path( $slug, OBJECT, 'page' );
if ( $existing instanceof WP_Post ) {
	echo "Page already exists: #{$existing->ID} '{$existing->post_title}' (status: {$existing->post_status})\n";
	echo "URL: " . get_permalink( $existing->ID ) . "\n";
	exit;
}

// 2. Create it.
$new_id = wp_insert_post( [
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Our Services (Preview)',
	'post_name'    => $slug,
	'post_content' => '<!-- Rendered by page-our-services-preview.php in the emaurri-child theme. -->',
	'post_author'  => 1,
	'comment_status' => 'closed',
	'ping_status'    => 'closed',
], true );

if ( is_wp_error( $new_id ) ) {
	echo "FAILED: " . $new_id->get_error_message() . "\n";
	exit( 1 );
}

// Mark as "hidden from menus by default" — not added to any nav.
echo "CREATED page #{$new_id} with slug '{$slug}'\n";
echo "URL: " . get_permalink( $new_id ) . "\n";
echo "WordPress will render it using page-our-services-preview.php via template hierarchy.\n";
