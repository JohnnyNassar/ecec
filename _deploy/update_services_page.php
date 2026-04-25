<?php
/**
 * Swap post 121 (Our Services) services widget from inline-HTML text-editor
 * → Elementor shortcode widget running [ecec_services_list].
 *
 * Target widget: id=8bb4499 inside container af9f65e.
 * Preserves: container 8e68422 (SECTORS WE SERVE) and container bfc516a
 *            (PORTFOLIO OF SERVICES heading).
 *
 * Backs up the existing _elementor_data to _deploy/post121_elementor_backup_<ts>.json
 * before write. Idempotent — running again with shortcode already in place is a no-op.
 *
 * Clears Elementor element + CSS caches after write.
 */

require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) && ! isset( $_GET['force'] ) ) {
	wp_die( 'Admin only, or pass ?force=1 (local). Aborting.' );
}

$post_id = 121;
$post    = get_post( $post_id );
if ( ! $post || $post->post_type !== 'page' ) {
	echo "ERROR: post {$post_id} not found or wrong type.\n";
	exit( 1 );
}

$el_raw = get_post_meta( $post_id, '_elementor_data', true );
$data   = json_decode( $el_raw, true );
if ( ! is_array( $data ) ) {
	echo "ERROR: _elementor_data is not valid JSON array on post {$post_id}.\n";
	exit( 1 );
}

// Backup
$ts         = date( 'Ymd_Hi' );
$backup_dir = __DIR__;
$backup_fn  = "$backup_dir/post121_elementor_backup_{$ts}.json";
file_put_contents( $backup_fn, wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
echo "Backup: {$backup_fn}\n";

// Walk the tree to find widget 8bb4499 (services inline HTML) → replace with shortcode widget.
$target_widget_id = '8bb4499';
$found  = false;
$walk = function ( array &$nodes ) use ( &$walk, $target_widget_id, &$found ) {
	foreach ( $nodes as &$node ) {
		if ( ! is_array( $node ) ) { continue; }
		if (
			isset( $node['id'], $node['elType'] )
			&& $node['id'] === $target_widget_id
			&& $node['elType'] === 'widget'
		) {
			// Already a shortcode widget for us? Skip.
			if ( isset( $node['widgetType'] ) && $node['widgetType'] === 'shortcode'
				&& isset( $node['settings']['shortcode'] )
				&& strpos( $node['settings']['shortcode'], '[ecec_services_list' ) !== false
			) {
				$found = true;
				echo "Widget {$target_widget_id} already set to [ecec_services_list] — no change needed.\n";
				return;
			}
			// Replace: keep id, switch type + settings.
			$node['widgetType']       = 'shortcode';
			$node['settings']         = [ 'shortcode' => '[ecec_services_list]' ];
			$node['elements']         = [];
			$node['isInner']          = false;
			$found = true;
			echo "Rewrote widget {$target_widget_id} → shortcode [ecec_services_list].\n";
			return;
		}
		if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
			$walk( $node['elements'] );
			if ( $found ) { return; }
		}
	}
};
$walk( $data );

if ( ! $found ) {
	echo "WARNING: target widget {$target_widget_id} not found. Post structure may have changed — inspect _deploy/post121_elementor_backup_{$ts}.json.\n";
	exit( 2 );
}

// Write
$new_raw = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
update_post_meta( $post_id, '_elementor_data', wp_slash( $new_raw ) );
echo "Wrote _elementor_data (" . strlen( $new_raw ) . " bytes).\n";

// Clear Elementor caches on this post + purge compiled CSS file for post 121.
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );
$css_file = WP_CONTENT_DIR . "/uploads/elementor/css/post-{$post_id}.css";
if ( file_exists( $css_file ) ) {
	unlink( $css_file );
	echo "Deleted {$css_file}\n";
}
echo "\nView: " . get_permalink( $post_id ) . "\n";
