<?php
/**
 * Promote the services preview into the live /our-services/ page.
 *
 * Source: page with slug "our-services-preview" (demo Elementor layout + ECEC content)
 * Target: page with slug "our-services"          (currently the approved simple list)
 *
 * On confirm:
 *   1. JSON-snapshot the target's existing post_meta to /root/backups/ (rollback source)
 *   2. Copy Elementor + qodef_* page meta from source -> target
 *   3. Delete the source page (so /our-services-preview/ stops existing)
 *   4. Clear Elementor CSS + element caches for the target
 *
 * On undo:
 *   Needs the backup JSON path as ?backup=/root/backups/our-services-prerun-<ts>.json
 *   - Restores every captured meta key on the target (overwriting current)
 *   - Recreates the preview page via the existing create_services_preview_page.php
 *     script if it no longer exists
 *
 * Modes (exactly one): ?dry=1  ?confirm=1  ?undo=1 [&backup=/path/to/snapshot.json]
 *
 * Run via HTTP (admin only) or CLI (`sudo -u www-data php ...`).
 */

require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );
if ( $is_cli ) {
	foreach ( $argv as $a ) {
		if ( $a === 'dry' )     { $_GET['dry']     = 1; }
		if ( $a === 'confirm' ) { $_GET['confirm'] = 1; }
		if ( $a === 'undo' )    { $_GET['undo']    = 1; }
		if ( strpos( $a, 'backup=' ) === 0 ) { $_GET['backup'] = substr( $a, 7 ); }
	}
} else {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Log in as administrator.' );
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
}

$is_dry     = ! empty( $_GET['dry'] );
$is_confirm = ! empty( $_GET['confirm'] );
$is_undo    = ! empty( $_GET['undo'] );
if ( (int) $is_dry + (int) $is_confirm + (int) $is_undo !== 1 ) {
	echo "Usage: dry | confirm | undo [backup=/path/to/snapshot.json]\n";
	exit;
}

$TARGET_SLUG = 'our-services';
$SOURCE_SLUG = 'our-services-preview';

// Meta keys that define the Elementor page render + Qode page settings.
$ELEMENTOR_KEYS = [
	'_elementor_data',
	'_elementor_edit_mode',
	'_elementor_template_type',
	'_elementor_version',
	'_wp_page_template',
	'_elementor_controls_usage',
];
$QODEF_KEYS = [
	'qodef_page_content_padding',
	'qodef_page_content_padding_mobile',
	'qodef_content_behind_header',
	'qodef_show_header_widget_areas',
	'qodef_top_area_header_in_grid',
	'qodef_enable_page_title',
	'qodef_title_layout',
	'qodef_set_page_title_area_in_grid',
	'qodef_page_title_height',
	'qodef_content_side_position',
	'qodef_content_width',
	'qodef_minimal_centered_header_in_grid',
	'qodef_sticky_header_enable_border',
	'rs_page_bg_color',
];
$ALL_KEYS = array_merge( $ELEMENTOR_KEYS, $QODEF_KEYS );

// ─────────────────────────────────────────────────────────────────
// UNDO mode
// ─────────────────────────────────────────────────────────────────
if ( $is_undo ) {
	$backup = isset( $_GET['backup'] ) ? (string) $_GET['backup'] : '';
	if ( ! $backup || ! file_exists( $backup ) ) {
		echo "UNDO requires backup=/path/to/snapshot.json (must exist).\n";
		exit( 1 );
	}
	$snap = json_decode( file_get_contents( $backup ), true );
	if ( ! is_array( $snap ) || empty( $snap['target_id'] ) ) {
		echo "Snapshot file unreadable or missing target_id.\n";
		exit( 1 );
	}
	$target_id = (int) $snap['target_id'];
	echo "Restoring meta on page #{$target_id} from $backup\n";
	foreach ( $ALL_KEYS as $k ) {
		if ( array_key_exists( $k, $snap['meta'] ) ) {
			// Re-apply the old value; delete any currently stored value first so
			// we don't duplicate.
			delete_post_meta( $target_id, $k );
			update_post_meta( $target_id, $k, maybe_unserialize( $snap['meta'][ $k ] ) );
			echo "  restored: $k\n";
		} else {
			delete_post_meta( $target_id, $k );
			echo "  cleared (no backup value): $k\n";
		}
	}
	delete_post_meta( $target_id, '_elementor_css' );
	delete_post_meta( $target_id, '_elementor_element_cache' );
	echo "Done. Elementor caches cleared.\n";
	echo "To recreate the preview page, run:\n";
	echo "  sudo -u www-data php _deploy/create_services_preview_page.php\n";
	echo "  sudo -u www-data php _deploy/apply_demo_services_page.php confirm\n";
	exit;
}

// ─────────────────────────────────────────────────────────────────
// Lookup pages
// ─────────────────────────────────────────────────────────────────
$target = get_page_by_path( $TARGET_SLUG, OBJECT, 'page' );
$source = get_page_by_path( $SOURCE_SLUG, OBJECT, 'page' );

if ( ! ( $target instanceof WP_Post ) ) {
	echo "FAIL: no page with slug '$TARGET_SLUG' on this site.\n"; exit( 1 );
}
if ( ! ( $source instanceof WP_Post ) ) {
	echo "FAIL: no page with slug '$SOURCE_SLUG' on this site. Run create_services_preview_page.php + apply_demo_services_page.php first.\n"; exit( 1 );
}

echo "Target (live):   #{$target->ID} '{$target->post_title}'   -> " . get_permalink( $target->ID ) . "\n";
echo "Source (demo):   #{$source->ID} '{$source->post_title}'   -> " . get_permalink( $source->ID ) . "\n";
echo "\n";

// ─────────────────────────────────────────────────────────────────
// Capture source + target meta
// ─────────────────────────────────────────────────────────────────
$source_meta = [];
foreach ( $ALL_KEYS as $k ) {
	$v = get_post_meta( $source->ID, $k, true );
	if ( $v !== '' ) { $source_meta[ $k ] = $v; }
}
echo "Source Elementor/qodef keys available: " . count( $source_meta ) . "\n";

$target_meta = [];
foreach ( $ALL_KEYS as $k ) {
	$v = get_post_meta( $target->ID, $k, true );
	$target_meta[ $k ] = $v; // may be '' (no value)
}

echo "\n=== Meta plan ===\n";
foreach ( $ALL_KEYS as $k ) {
	$before = $target_meta[ $k ];
	$after  = array_key_exists( $k, $source_meta ) ? $source_meta[ $k ] : '';
	$before_preview = is_string( $before ) ? substr( $before, 0, 60 ) : '(' . gettype( $before ) . ')';
	$after_preview  = is_string( $after )  ? substr( $after,  0, 60 ) : '(' . gettype( $after )  . ')';
	echo sprintf( "  %-38s  '%s' -> '%s'\n", $k, $before_preview, $after_preview );
}

if ( $is_dry ) {
	echo "\nDRY RUN — no writes.\n";
	exit;
}

// ─────────────────────────────────────────────────────────────────
// CONFIRM: backup + apply + delete source + clear caches
// ─────────────────────────────────────────────────────────────────

// 1. Write snapshot. Pick the first writable directory from a candidate list
//    so the script works as www-data on VPS and as the interactive user on
//    local XAMPP. Root-only /root/backups/ is skipped when stat fails.
$ts = gmdate( 'Ymd_His' );
$candidates = [ '/root/backups', __DIR__, sys_get_temp_dir() ];
$backup_dir = null;
foreach ( $candidates as $c ) {
	if ( $c && is_dir( $c ) && is_writable( $c ) ) {
		$backup_dir = $c;
		break;
	}
}
if ( ! $backup_dir ) {
	echo "FAIL: no writable directory for backup snapshot.\n"; exit( 1 );
}
$backup_path = rtrim( $backup_dir, '/' ) . "/our-services-prerun-$ts.json";
$snap_obj = [
	'written_at'  => gmdate( 'c' ),
	'target_id'   => $target->ID,
	'target_slug' => $TARGET_SLUG,
	'source_id'   => $source->ID,
	'source_slug' => $SOURCE_SLUG,
	'meta'        => $target_meta,
];
if ( false === file_put_contents( $backup_path, wp_json_encode( $snap_obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	echo "FAIL: could not write backup snapshot to $backup_path\n"; exit( 1 );
}
echo "Snapshot saved: $backup_path\n";

// 2. Copy meta from source -> target. For keys source doesn't have, delete
//    target's value so we don't leave old Elementor data behind.
foreach ( $ALL_KEYS as $k ) {
	delete_post_meta( $target->ID, $k );
	if ( isset( $source_meta[ $k ] ) ) {
		update_post_meta( $target->ID, $k, maybe_unserialize( $source_meta[ $k ] ) );
	}
}

// 3. Delete the source page (free up the /our-services-preview/ slug).
$deleted = wp_delete_post( $source->ID, true );
if ( $deleted === false || $deleted === null ) {
	echo "WARN: source page delete returned unexpected value. Check manually.\n";
} else {
	echo "Source page #{$source->ID} deleted (slug /our-services-preview/ no longer resolves).\n";
}

// 4. Clear Elementor caches for the target page.
delete_post_meta( $target->ID, '_elementor_css' );
delete_post_meta( $target->ID, '_elementor_element_cache' );

echo "\nPromotion complete.\n";
echo "Live URL: " . home_url( '/our-services/' ) . "\n";
echo "Edit in Elementor: " . admin_url( "post.php?post={$target->ID}&action=elementor" ) . "\n";
echo "\nRollback command:\n";
echo "  sudo -u www-data php " . __FILE__ . " undo backup=$backup_path\n";
