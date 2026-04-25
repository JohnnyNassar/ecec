<?php
/**
 * Rewire main nav menu (term_id 2):
 *  - Item #127 "About Us (Archive)" → re-point to post 382, rename to "About Us"
 *  - Move Why ECEC (#423) to position right after About Us
 *  - Renumber: Home(1) About Us(2) Why ECEC(3) Projects(4) Our Services(5)
 *               People(6) Contact Us(7)
 *
 * Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$menu_id = 2;

// 1. Update About Us menu item to point to post 382 with clean title
$about_item_id = 127;
$new_about_post = 382;
update_post_meta( $about_item_id, '_menu_item_object_id', $new_about_post );
wp_update_post( [ 'ID' => $about_item_id, 'post_title' => 'About Us' ] );
echo "Updated menu item {$about_item_id}: object_id → {$new_about_post}, title → 'About Us'\n";

// 2. Set explicit menu_order on every item (defines the order)
$ordering = [
	126 => 1,  // Home
	127 => 2,  // About Us (now pointing to 382)
	423 => 3,  // Why ECEC (was 7)
	128 => 4,  // Projects (was 3)
	129 => 5,  // Our Services (was 4)
	130 => 6,  // People (was 5)
	131 => 7,  // Contact Us (was 6)
];
foreach ( $ordering as $item_id => $order ) {
	wp_update_post( [ 'ID' => $item_id, 'menu_order' => $order ] );
}
echo "Reordered menu items.\n";

// 3. Verify
echo "\n=== Updated menu state ===\n";
$items = wp_get_nav_menu_items( $menu_id );
foreach ( $items as $it ) {
	$obj_post = get_post( $it->object_id );
	echo sprintf( "  [order=%d] item#%d  title='%s'  -> post #%s '%s'\n",
		$it->menu_order, $it->ID, $it->title, $it->object_id, $obj_post ? $obj_post->post_title : '?'
	);
}

echo "\nDone.\n";
