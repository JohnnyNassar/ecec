<?php
// Remove the dark "OUR OFFICES" standalone section from home page (post 119)
// Target: Elementor container id 3df4f2b
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$post_id = 119;
$target_id = '3df4f2b';

$raw = get_post_meta($post_id, '_elementor_data', true);
if (!$raw) { echo "NO DATA\n"; exit(1); }

// Elementor stores the data JSON-encoded (as a string) in postmeta
$data = is_array($raw) ? $raw : json_decode($raw, true);
if (!is_array($data)) { echo "JSON DECODE FAIL: ".json_last_error_msg()."\n"; exit(1); }

$before = count($data);
$data = array_values(array_filter($data, function($c) use ($target_id) {
    return ($c['id'] ?? '') !== $target_id;
}));
$after = count($data);

if ($after === $before) { echo "NOT FOUND: $target_id\n"; exit(1); }

// Re-encode and save. Elementor expects slashed JSON string on update_post_meta
$new = wp_slash(wp_json_encode($data));
$ok = update_post_meta($post_id, '_elementor_data', $new);

// Clear Elementor caches for this post
delete_post_meta($post_id, '_elementor_element_cache');
delete_post_meta($post_id, '_elementor_css');

echo "removed container $target_id; containers $before -> $after; saved=" . ($ok ? 'yes' : 'no') . "\n";
