<?php
/**
 * Build a new "Why ECEC" page following the Emaurri vertical-divided-list demo.
 *
 * Per-row layout (5 rows, alternating image side):
 *   - 2-col section with horizontal divider above
 *   - Image side: placeholder box (replaceable via WP admin)
 *   - Text side:  big numbered title + "Explore" CTA → /projects/
 *
 * Idempotent: detects post by slug 'why-ecec' and reuses if found.
 *
 * Also adds "Why ECEC" to the primary nav menu, positioned right after About Us.
 *
 * CLI: sudo -u www-data php /var/www/html/ecec/_tmp/build_why_ecec.php
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

// ─── Find or create page ─────────────────────────────────────────────────
$existing = get_page_by_path( 'why-ecec', OBJECT, 'page' );
if ( ! $existing ) {
	$pid = wp_insert_post( [
		'post_title'  => 'Why ECEC',
		'post_name'   => 'why-ecec',
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_author' => 1,
	] );
	if ( is_wp_error( $pid ) ) { echo "ERROR: " . $pid->get_error_message() . "\n"; exit( 1 ); }
	echo "Created Why ECEC page: ID {$pid}\n";
} else {
	$pid = $existing->ID;
	echo "Found existing Why ECEC page: ID {$pid}\n";
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function eid() { return wp_generate_password( 7, false, false ); }

$placeholder = function ( $w, $h ) {
	return '<div class="ecec-block-placeholder ecec-why-placeholder" style="aspect-ratio: ' . $w . ' / ' . $h . ';"><p class="ecec-block-placeholder__size">' . $w . ' &times; ' . $h . '</p><p class="ecec-block-placeholder__hint">Image placeholder &mdash; replace via admin</p></div>';
};

$pillars = [
	[ 'num' => '01', 'title' => 'Proven Performance' ],
	[ 'num' => '02', 'title' => 'Data-Driven Efficiency' ],
	[ 'num' => '03', 'title' => 'Global Standards Local Insight' ],
	[ 'num' => '04', 'title' => 'Collaborative Engagement' ],
	[ 'num' => '05', 'title' => 'Comprehensive Expertise' ],
];

$el_data = [];

foreach ( $pillars as $i => $p ) {
	$image_left = ( $i % 2 === 0 ); // alternate: even rows image left
	$image_html = $placeholder( 600, 500 );

	$image_widget = [
		'id' => eid(), 'elType' => 'widget', 'widgetType' => 'html',
		'settings' => [ 'html' => $image_html, '_css_classes' => 'ecec-why-image' ],
	];
	$text_widgets = [
		[
			'id' => eid(), 'elType' => 'widget', 'widgetType' => 'heading',
			'settings' => [ 'title' => $p['num'] . '.', 'header_size' => 'h3', '_css_classes' => 'ecec-why-num' ],
		],
		[
			'id' => eid(), 'elType' => 'widget', 'widgetType' => 'heading',
			'settings' => [ 'title' => $p['title'], 'header_size' => 'h2', '_css_classes' => 'ecec-why-title' ],
		],
		[
			'id' => eid(), 'elType' => 'widget', 'widgetType' => 'button',
			'settings' => [
				'text'         => 'EXPLORE',
				'link'         => [ 'url' => home_url( '/projects/' ), 'is_external' => '', 'nofollow' => '' ],
				'align'        => $image_left ? 'left' : 'left',
				'_css_classes' => 'ecec-why-cta',
			],
		],
	];

	$left_col_elements  = $image_left ? [ $image_widget ] : $text_widgets;
	$right_col_elements = $image_left ? $text_widgets : [ $image_widget ];

	$el_data[] = [
		'id' => eid(), 'elType' => 'section',
		'settings' => [
			'layout'    => 'boxed',
			'structure' => '20',
			'gap'       => 'extended',
			'padding'   => [ 'unit' => 'px', 'top' => '90', 'right' => '0', 'bottom' => '90', 'left' => '0', 'isLinked' => false ],
			'_css_classes' => 'ecec-why-row ' . ( $image_left ? 'ecec-why-row--image-left' : 'ecec-why-row--image-right' ),
		],
		'elements' => [
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100, 'align' => 'center' ],
				'elements' => $left_col_elements,
			],
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100, 'align' => 'center' ],
				'elements' => $right_col_elements,
			],
		],
	];
}

update_post_meta( $pid, '_elementor_edit_mode', 'builder' );
update_post_meta( $pid, '_elementor_template_type', 'wp-page' );
update_post_meta( $pid, '_elementor_version', '3.18.0' );
update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );
delete_post_meta( $pid, '_elementor_element_cache' );
delete_post_meta( $pid, '_elementor_css' );

echo "Saved " . count( $el_data ) . " section(s) on post {$pid}.\n";
echo "View: " . get_permalink( $pid ) . "\n";

// ─── Add to primary nav menu, right after About Us ───────────────────────
$locations = get_nav_menu_locations();
$menu_id = 0;
// Try common locations
foreach ( [ 'top-navigation', 'main-navigation', 'primary', 'main', 'top' ] as $loc ) {
	if ( ! empty( $locations[ $loc ] ) ) { $menu_id = (int) $locations[ $loc ]; break; }
}
// Fallback: pick the menu that contains "About Us"
if ( ! $menu_id ) {
	$menus = wp_get_nav_menus();
	foreach ( $menus as $m ) {
		$items = wp_get_nav_menu_items( $m->term_id );
		if ( ! $items ) { continue; }
		foreach ( $items as $it ) {
			if ( strcasecmp( $it->title, 'About Us' ) === 0 ) {
				$menu_id = (int) $m->term_id;
				break 2;
			}
		}
	}
}
if ( ! $menu_id ) {
	echo "WARN: couldn't locate primary menu. Skipping nav add — add 'Why ECEC' manually via Appearance → Menus.\n";
} else {
	echo "Primary menu ID: {$menu_id}\n";
	$items = wp_get_nav_menu_items( $menu_id );
	// Skip if Why ECEC already in the menu
	$existing_item = null;
	$about_item = null;
	foreach ( $items as $it ) {
		if ( strcasecmp( $it->title, 'Why ECEC' ) === 0 ) { $existing_item = $it; }
		if ( strcasecmp( $it->title, 'About Us' ) === 0 ) { $about_item = $it; }
	}
	if ( $existing_item ) {
		echo "'Why ECEC' already in menu (item ID {$existing_item->ID}). Skipping.\n";
	} else {
		$position = $about_item ? ( $about_item->menu_order + 1 ) : 99;
		// Bump menu_order on items after About Us to make room
		foreach ( $items as $it ) {
			if ( $about_item && $it->menu_order > $about_item->menu_order ) {
				wp_update_post( [ 'ID' => $it->ID, 'menu_order' => $it->menu_order + 1 ] );
			}
		}
		$new_item_id = wp_update_nav_menu_item( $menu_id, 0, [
			'menu-item-title'     => 'Why ECEC',
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $pid,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $position,
		] );
		if ( is_wp_error( $new_item_id ) ) {
			echo "WARN: failed to add menu item: " . $new_item_id->get_error_message() . "\n";
		} else {
			echo "Added 'Why ECEC' menu item (ID {$new_item_id}) at position {$position}.\n";
		}
	}
}

echo "\nDone.\n";
