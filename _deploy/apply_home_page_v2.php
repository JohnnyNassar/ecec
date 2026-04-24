<?php
/**
 * Rewrite the home page (post 119) with the 5-block v2 layout:
 *
 *   1. Portfolio carousel ([ecec_portfolio_carousel])
 *   2. "We Design the Future" intro (HTML block)
 *   3. Main-home RevSlider ([rev_slider alias="main-home"])
 *   4. Clients marquee ([ecec_clients_marquee])
 *   5. Featured projects ([ecec_featured_projects columns="3" limit="3"])
 *
 * Snapshots the existing Elementor data before overwriting. Undo restores
 * from the latest snapshot.
 *
 * CLI: `sudo -u www-data php _deploy/apply_home_page_v2.php confirm`
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
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }
	header( 'Content-Type: text/plain; charset=utf-8' );
}

$is_dry     = ! empty( $_GET['dry'] );
$is_confirm = ! empty( $_GET['confirm'] );
$is_undo    = ! empty( $_GET['undo'] );
if ( (int) $is_dry + (int) $is_confirm + (int) $is_undo !== 1 ) {
	echo "Usage: dry | confirm | undo [backup=/path/to/snapshot.json]\n";
	exit;
}

$target_id = 119; // home page ID (same on local + VPS)
$target = get_post( $target_id );
if ( ! $target ) {
	// Try lookup by slug "home" if ID 119 isn't home on this site
	$target = get_page_by_path( 'home', OBJECT, 'page' );
	if ( $target ) { $target_id = $target->ID; }
}
if ( ! $target ) { echo "FAIL: home page not found.\n"; exit( 1 ); }

$ELEM_KEYS = [
	'_elementor_data',
	'_elementor_edit_mode',
	'_elementor_template_type',
	'_elementor_version',
	'_wp_page_template',
	'_elementor_controls_usage',
	'_elementor_page_assets',
];

if ( $is_undo ) {
	$backup = $_GET['backup'] ?? '';
	if ( ! $backup || ! file_exists( $backup ) ) {
		echo "UNDO requires backup=/path/to/snapshot.json (must exist).\n"; exit( 1 );
	}
	$snap = json_decode( file_get_contents( $backup ), true );
	if ( ! is_array( $snap ) || empty( $snap['target_id'] ) ) {
		echo "Snapshot unreadable.\n"; exit( 1 );
	}
	$tid = (int) $snap['target_id'];
	foreach ( $ELEM_KEYS as $k ) {
		delete_post_meta( $tid, $k );
		if ( array_key_exists( $k, $snap['meta'] ) && $snap['meta'][ $k ] !== '' ) {
			update_post_meta( $tid, $k, maybe_unserialize( $snap['meta'][ $k ] ) );
		}
	}
	delete_post_meta( $tid, '_elementor_css' );
	delete_post_meta( $tid, '_elementor_element_cache' );
	echo "Restored home page #$tid from $backup\n";
	exit;
}

echo "Target: #{$target_id} '{$target->post_title}'  slug={$target->post_name}  status={$target->post_status}\n";

// Snapshot existing meta.
$meta = [];
foreach ( $ELEM_KEYS as $k ) { $meta[ $k ] = get_post_meta( $target_id, $k, true ); }
$ts = gmdate( 'Ymd_His' );
$candidates = [ '/root/backups', __DIR__, sys_get_temp_dir() ];
$backup_dir = null;
foreach ( $candidates as $c ) {
	if ( $c && is_dir( $c ) && is_writable( $c ) ) { $backup_dir = $c; break; }
}
if ( ! $backup_dir ) { echo "FAIL: no writable backup dir.\n"; exit( 1 ); }
$backup_path = rtrim( $backup_dir, '/' ) . "/home-prerun-$ts.json";

// Unique short IDs for new Elementor nodes (hash of a label; keeps them
// reproducible across envs).
$idgen = function ( $label ) {
	return substr( hash( 'sha1', 'ecec-home-v2-' . $label ), 0, 7 );
};

// Build the Elementor data array.
$data = [
	// --- Section 1: Blog carousel (emaurri_core_blog_list widget, slider
	//     behavior, pagination dots — mirrors the Emaurri demo /blog-home/
	//     first section). Shows the latest 4 published blog posts; the
	//     post_ids filter is intentionally absent so new posts auto-appear.
	[
		'id' => $idgen( 'sec1' ),
		'elType' => 'section',
		'settings' => [
			'layout' => 'full_width',
			'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px', 'isLinked' => true ],
		],
		'elements' => [ [
			'id' => $idgen( 'sec1-col' ),
			'elType' => 'column',
			'settings' => [ '_column_size' => 100 ],
			'elements' => [ [
				'id' => $idgen( 'sec1-bloglist' ),
				'elType' => 'widget',
				'widgetType' => 'emaurri_core_blog_list',
				'settings' => [
					'behavior'          => 'slider',
					'columns'           => '1',
					'space'             => 'no',
					'slider_navigation' => 'no',
					'slider_pagination' => 'yes',
					'posts_per_page'    => '4',
					'title_tag'         => 'h3',
					'skin'              => 'light',
					'orderby'           => 'date',
					'_margin'           => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ],
					'_padding'          => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ],
				],
			] ],
		] ],
	],

	// --- Section 2: Intro "We Design the Future" ---
	[
		'id' => $idgen( 'sec2' ),
		'elType' => 'section',
		'settings' => [
			'layout' => 'boxed',
			'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px', 'isLinked' => true ],
		],
		'elements' => [ [
			'id' => $idgen( 'sec2-col' ),
			'elType' => 'column',
			'settings' => [ '_column_size' => 100 ],
			'elements' => [ [
				'id' => $idgen( 'sec2-html' ),
				'elType' => 'widget',
				'widgetType' => 'html',
				'settings' => [
					'html' => '<section class="ecec-home-intro">'
						. '<h2 class="ecec-home-intro__heading">We Design the Future</h2>'
						. '<p class="ecec-home-intro__body">ECEC, a prominent engineering consultancy based in Dubai, UAE, expands its presence with offices strategically located in Riyadh, KSA, and Amman, Jordan. Our team comprises a dynamic mix of professionals representing various nationalities.</p>'
						. '</section>',
				],
			] ],
		] ],
	],

	// --- Section 3: Main-home RevSlider ---
	[
		'id' => $idgen( 'sec3' ),
		'elType' => 'section',
		'settings' => [
			'layout' => 'full_width',
			'padding' => [ 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'unit' => 'px', 'isLinked' => true ],
		],
		'elements' => [ [
			'id' => $idgen( 'sec3-col' ),
			'elType' => 'column',
			'settings' => [ '_column_size' => 100 ],
			'elements' => [ [
				'id' => $idgen( 'sec3-shortcode' ),
				'elType' => 'widget',
				'widgetType' => 'shortcode',
				'settings' => [
					'shortcode' => '[rev_slider alias="main-home"]',
				],
			] ],
		] ],
	],

	// --- Section 4: Clients marquee ---
	[
		'id' => $idgen( 'sec4' ),
		'elType' => 'section',
		'settings' => [
			'layout' => 'full_width',
			'padding' => [ 'top' => '40', 'right' => '0', 'bottom' => '40', 'left' => '0', 'unit' => 'px', 'isLinked' => false ],
		],
		'elements' => [ [
			'id' => $idgen( 'sec4-col' ),
			'elType' => 'column',
			'settings' => [ '_column_size' => 100 ],
			'elements' => [ [
				'id' => $idgen( 'sec4-shortcode' ),
				'elType' => 'widget',
				'widgetType' => 'shortcode',
				'settings' => [
					'shortcode' => '[ecec_clients_marquee]',
				],
			] ],
		] ],
	],

	// --- Section 5: Featured projects ---
	[
		'id' => $idgen( 'sec5' ),
		'elType' => 'section',
		'settings' => [
			'layout' => 'boxed',
			'padding' => [ 'top' => '80', 'right' => '0', 'bottom' => '80', 'left' => '0', 'unit' => 'px', 'isLinked' => false ],
		],
		'elements' => [ [
			'id' => $idgen( 'sec5-col' ),
			'elType' => 'column',
			'settings' => [ '_column_size' => 100 ],
			'elements' => [ [
				'id' => $idgen( 'sec5-heading' ),
				'elType' => 'widget',
				'widgetType' => 'heading',
				'settings' => [
					'title' => 'Featured Projects',
					'align' => 'center',
					'header_size' => 'h2',
				],
			], [
				'id' => $idgen( 'sec5-shortcode' ),
				'elType' => 'widget',
				'widgetType' => 'shortcode',
				'settings' => [
					'shortcode' => '[ecec_featured_projects columns="3" limit="3"]',
				],
			] ],
		] ],
	],
];

$new_json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
echo "New _elementor_data length: " . strlen( $new_json ) . " chars\n";

if ( $is_dry ) { echo "\nDRY RUN — no writes. Preview of structure:\n"; echo "  5 sections, widgets: carousel, html, revslider, marquee, featured\n"; exit; }

// Write snapshot.
$snap_obj = [
	'written_at' => gmdate( 'c' ),
	'target_id'  => $target_id,
	'meta'       => $meta,
];
if ( false === file_put_contents( $backup_path, wp_json_encode( $snap_obj, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) ) ) {
	echo "FAIL: could not write snapshot $backup_path\n"; exit( 1 );
}
echo "Snapshot: $backup_path\n";

// Apply.
update_post_meta( $target_id, '_elementor_data', wp_slash( $new_json ) );
update_post_meta( $target_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $target_id, '_elementor_template_type', 'wp-page' );
if ( empty( get_post_meta( $target_id, '_elementor_version', true ) ) ) {
	update_post_meta( $target_id, '_elementor_version', '3.0.0' );
}
// Use full-width page template so the RevSlider and marquee span edge to edge.
update_post_meta( $target_id, '_wp_page_template', 'page-full-width.php' );

// Clear Elementor caches.
delete_post_meta( $target_id, '_elementor_css' );
delete_post_meta( $target_id, '_elementor_element_cache' );

echo "\nApplied. Permalink: " . get_permalink( $target_id ) . "\n";
echo "Edit in Elementor: " . admin_url( "post.php?post={$target_id}&action=elementor" ) . "\n";
echo "Rollback: sudo -u www-data php " . __FILE__ . " undo backup=$backup_path\n";
