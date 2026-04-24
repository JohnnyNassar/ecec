<?php
/**
 * Import the first N published demo blog posts from the Emaurri XML export,
 * so the home-page blog slider has content. Existing portfolio-item featured
 * images are reused as thumbnails (the demo's attachments live on the
 * qodeinteractive.com server and won't resolve on ECEC).
 *
 * Idempotent: if a post with the same slug already exists (post_type=post),
 * it's skipped.
 *
 * Copy is placeholder — client should rewrite each post via WP admin.
 *
 * CLI: `sudo -u www-data php _deploy/import_demo_blog_posts.php`
 */

require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );
if ( ! $is_cli ) {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }
	header( 'Content-Type: text/plain; charset=utf-8' );
}

$xml_path = ABSPATH . '_theme_backup/emaurri/xml export/emaurri-export.xml';
if ( ! file_exists( $xml_path ) ) {
	echo "FAIL: demo XML not found at $xml_path\n";
	echo "On VPS, upload _theme_backup/emaurri/xml export/emaurri-export.xml first.\n";
	exit( 1 );
}

$want = 6;          // how many posts to import
$xml = file_get_contents( $xml_path );

// Parse <item> blocks manually (regex-based to avoid SimpleXML dependency on namespaces).
$items = preg_split( '/<item>/', $xml );
array_shift( $items ); // leading part before first <item>

$created = [];
$skipped = [];

foreach ( $items as $it ) {
	if ( count( $created ) >= $want ) { break; }

	if ( ! preg_match( '/<wp:post_type>\s*<!\[CDATA\[([^\]]+)\]\]>/', $it, $pt ) || $pt[1] !== 'post' ) continue;
	if ( ! preg_match( '/<wp:status>\s*<!\[CDATA\[([^\]]+)\]\]>/', $it, $st ) || $st[1] !== 'publish' ) continue;

	$title = preg_match( '/<title>\s*<!\[CDATA\[([\s\S]*?)\]\]>\s*<\/title>/', $it, $m1 ) ? trim( $m1[1] ) : '';
	$slug  = preg_match( '/<wp:post_name>\s*<!\[CDATA\[([\s\S]*?)\]\]>/', $it, $m2 ) ? trim( $m2[1] ) : '';
	$content = preg_match( '/<content:encoded>\s*<!\[CDATA\[([\s\S]*?)\]\]>\s*<\/content:encoded>/', $it, $m3 ) ? $m3[1] : '';
	$excerpt = preg_match( '/<excerpt:encoded>\s*<!\[CDATA\[([\s\S]*?)\]\]>\s*<\/excerpt:encoded>/', $it, $m4 ) ? $m4[1] : '';

	if ( ! $title || ! $slug ) continue;

	$existing = get_page_by_path( $slug, OBJECT, 'post' );
	if ( $existing ) {
		$skipped[] = [ $existing->ID, $slug ];
		echo "  SKIP  #{$existing->ID}  $slug (already exists)\n";
		continue;
	}

	// Strip shortcodes from content (demo uses Emaurri shortcodes that won't
	// render the same way; keep it simple)
	$content = preg_replace( '/\[\/?[a-z_]+[^\]]*\]/i', '', $content );

	$new_id = wp_insert_post( [
		'post_type'    => 'post',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
		'post_excerpt' => $excerpt,
		'post_author'  => 1,
		'comment_status' => 'closed',
		'ping_status'    => 'closed',
	], true );

	if ( is_wp_error( $new_id ) ) {
		echo "  FAIL  $slug: " . $new_id->get_error_message() . "\n";
		continue;
	}

	// Pick a random portfolio-item featured image as thumbnail (so the slider
	// has imagery). Reused across posts is OK for placeholder purposes.
	$thumb_candidates = get_posts( [
		'post_type'      => 'portfolio-item',
		'posts_per_page' => 20,
		'orderby'        => 'rand',
		'fields'         => 'ids',
		'meta_query'     => [ [ 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ] ],
	] );
	if ( ! empty( $thumb_candidates ) ) {
		$proj_id  = $thumb_candidates[ array_rand( $thumb_candidates ) ];
		$thumb_id = get_post_thumbnail_id( $proj_id );
		if ( $thumb_id ) { set_post_thumbnail( $new_id, $thumb_id ); }
	}

	$created[] = [ $new_id, $slug, $title ];
	echo "  ADD   #$new_id  $slug  \"$title\"\n";
}

echo "\nCreated: " . count( $created ) . ", Skipped: " . count( $skipped ) . "\n";
if ( ! empty( $created ) ) {
	$ids = array_map( function ( $r ) { return $r[0]; }, $created );
	update_option( 'ecec_demo_blog_post_ids', $ids );
	echo "Stored IDs in ecec_demo_blog_post_ids option: " . implode( ', ', $ids ) . "\n";
}
