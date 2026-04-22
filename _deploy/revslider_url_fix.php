<?php
// Post-import URL fix for RevSlider + any missed escaped URLs
define('ABSPATH', '/var/www/html/ecec/');
define('WPINC', 'wp-includes');
$_SERVER['HTTP_HOST'] = '207.180.196.39';
$_SERVER['REQUEST_URI'] = '/ecec/';
require_once ABSPATH . 'wp-load.php';

global $wpdb;

$patterns = [
    ['localhost/ecec',    '207.180.196.39/ecec'],
    ['localhost\\/ecec',  '207.180.196.39\\/ecec'],
    ['localhost\\\\/ecec','207.180.196.39\\\\/ecec'],
];

$tables = [
    'wp_revslider_slides'         => ['params','layers','settings'],
    'wp_revslider_sliders'        => ['params','settings'],
    'wp_revslider_static_slides'  => ['params','layers','settings'],
];

$total = 0;
foreach ($tables as $table => $cols) {
    foreach ($cols as $col) {
        foreach ($patterns as [$from, $to]) {
            $sql = "UPDATE {$table} SET {$col} = REPLACE({$col}, %s, %s)";
            $n = $wpdb->query($wpdb->prepare($sql, $from, $to));
            if ($n > 0) {
                echo "  {$table}.{$col} [".addslashes($from)."]: {$n} rows\n";
                $total += $n;
            }
        }
    }
}

// wp_posts + wp_postmeta + wp_options are already handled by the SQL dump sed,
// but catch any stragglers (belt + braces)
foreach (['wp_posts' => ['post_content','guid'], 'wp_postmeta' => ['meta_value'], 'wp_options' => ['option_value']] as $table => $cols) {
    foreach ($cols as $col) {
        foreach ($patterns as [$from, $to]) {
            $sql = "UPDATE {$table} SET {$col} = REPLACE({$col}, %s, %s)";
            $n = $wpdb->query($wpdb->prepare($sql, $from, $to));
            if ($n > 0) {
                echo "  {$table}.{$col} [".addslashes($from)."]: {$n} rows\n";
                $total += $n;
            }
        }
    }
}

echo "TOTAL URL REPLACEMENTS: {$total}\n";

// Fix post_author=0
$fixed = $wpdb->query("UPDATE wp_posts SET post_author=1 WHERE post_author=0");
echo "post_author=0 fixed: {$fixed} rows\n";

// Clear Elementor caches
$cleared = $wpdb->query("DELETE FROM wp_postmeta WHERE meta_key IN ('_elementor_element_cache','_elementor_css')");
echo "Elementor meta cache cleared: {$cleared} rows\n";
