<?php
/**
 * Import client logo files from docs/ClientsLogos/ into the WordPress Media
 * Library and record the resulting attachment IDs in the option
 * `ecec_client_logo_ids` so the [ecec_clients_marquee] shortcode can render
 * them.
 *
 * Idempotent: skips files whose attachment-post already exists with a
 * matching title (we use the original filename as the attachment title).
 *
 * CLI: `sudo -u www-data php _deploy/import_client_logos.php`
 *
 * On VPS, the source folder docs/ClientsLogos/ won't be present (it's
 * gitignored / too big). Before running on VPS, rsync or SFTP the folder to
 * /var/www/html/ecec/docs/ClientsLogos/, then run the script, then delete it
 * again.
 */

require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$is_cli = ( php_sapi_name() === 'cli' );
if ( ! $is_cli ) {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }
	header( 'Content-Type: text/plain; charset=utf-8' );
}

// Source directory (relative to WP root, on local it's docs/ClientsLogos/).
$src_dir = trailingslashit( ABSPATH ) . 'docs/ClientsLogos';
if ( ! is_dir( $src_dir ) ) {
	echo "FAIL: source directory not found: $src_dir\n";
	echo "On VPS, upload docs/ClientsLogos/ first, then re-run this script.\n";
	exit( 1 );
}

$files = array_values( array_filter( scandir( $src_dir ), function ( $f ) {
	return preg_match( '/\.(png|jpe?g|svg|webp|gif)$/i', $f );
} ) );
sort( $files ); // stable order
echo "Found " . count( $files ) . " logo files.\n";

$ids      = get_option( 'ecec_client_logo_ids', [] );
if ( ! is_array( $ids ) ) { $ids = []; }
$imported = 0;
$skipped  = 0;
$new_ids  = [];

foreach ( $files as $file ) {
	$path  = $src_dir . '/' . $file;
	$title = pathinfo( $file, PATHINFO_FILENAME );

	// Skip if an attachment with this title already exists.
	$existing = get_posts( [
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'title'          => $title,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	] );
	if ( ! empty( $existing ) ) {
		$att_id = (int) $existing[0];
		$new_ids[] = $att_id;
		$skipped++;
		echo "  SKIP  #$att_id  $file (already imported)\n";
		continue;
	}

	// Copy into uploads/ via WP sideload.
	$tmp = wp_tempnam( $file );
	if ( ! $tmp ) { echo "  FAIL tempnam for $file\n"; continue; }
	if ( ! copy( $path, $tmp ) ) { echo "  FAIL copy $file\n"; unlink( $tmp ); continue; }

	$file_array = [
		'name'     => $file,
		'tmp_name' => $tmp,
	];
	$att_id = media_handle_sideload( $file_array, 0, $title );
	if ( is_wp_error( $att_id ) ) {
		echo "  FAIL sideload $file: " . $att_id->get_error_message() . "\n";
		@unlink( $tmp );
		continue;
	}
	$new_ids[] = (int) $att_id;
	$imported++;
	echo "  ADD   #$att_id  $file\n";
}

update_option( 'ecec_client_logo_ids', $new_ids );

echo "\nTotal imported: $imported, skipped: $skipped, stored IDs: " . count( $new_ids ) . "\n";
echo "Stored in option 'ecec_client_logo_ids'.\n";
echo "Use with shortcode:  [ecec_clients_marquee]\n";
