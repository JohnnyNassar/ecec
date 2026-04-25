<?php
/**
 * Import the *new* client logos from docs/New Logos/ into the media library
 * and REPLACE the `ecec_client_logo_ids` option, so both the home marquee
 * and the new About Us logos strip render the new set.
 *
 * Old logo attachments are NOT deleted — left in media library in case they're
 * referenced elsewhere. Only the option is overwritten.
 *
 * Idempotent: re-running detects existing attachments by title and reuses IDs.
 *
 * CLI: sudo -u www-data php _deploy/import_new_client_logos.php
 */
require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

// Use path without spaces (SCP/Posh-SSH can't reliably handle paths with spaces)
$src_dir = trailingslashit( ABSPATH ) . 'docs/NewLogos';
if ( ! is_dir( $src_dir ) ) {
	echo "FAIL: source directory not found: $src_dir\n";
	echo "Upload docs/New Logos/ to the VPS first, then re-run.\n";
	exit( 1 );
}

$files = array_values( array_filter( scandir( $src_dir ), function ( $f ) {
	return preg_match( '/\.(png|jpe?g|svg|webp|gif)$/i', $f );
} ) );
sort( $files ); // numeric order: 01, 02, ..., 39
echo "Found " . count( $files ) . " new logo files.\n";

$old_ids = get_option( 'ecec_client_logo_ids', [] );
echo "Previous logo IDs in option: " . ( is_array( $old_ids ) ? count( $old_ids ) : 0 ) . "\n";

$new_ids  = [];
$imported = 0;
$skipped  = 0;
foreach ( $files as $file ) {
	$path  = $src_dir . '/' . $file;
	$title = pathinfo( $file, PATHINFO_FILENAME );

	// Skip if attachment with same title already exists (re-run safety)
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

	$tmp = wp_tempnam( $file );
	if ( ! $tmp ) { echo "  FAIL tempnam for $file\n"; continue; }
	if ( ! copy( $path, $tmp ) ) { echo "  FAIL copy $file\n"; unlink( $tmp ); continue; }

	$file_array = [ 'name' => $file, 'tmp_name' => $tmp ];
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

echo "\nImported: $imported, reused-existing: $skipped, total stored IDs: " . count( $new_ids ) . "\n";
echo "Option 'ecec_client_logo_ids' replaced with new set.\n";
echo "Both home marquee + about-us logos will render the new logos automatically.\n";
