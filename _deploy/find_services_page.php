<?php
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';
global $wpdb;
$rows = $wpdb->get_results( "SELECT ID, post_title, post_status, post_name FROM {$wpdb->posts} WHERE post_type='page' AND (post_name LIKE '%service%' OR post_title LIKE '%Service%')" );
foreach ( $rows as $r ) echo "{$r->ID}\t{$r->post_status}\t{$r->post_name}\t{$r->post_title}\n";
echo "\n--- posts with COZY in elementor data ---\n";
$rows2 = $wpdb->get_results( "SELECT p.ID, p.post_title, p.post_status, p.post_name FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID WHERE m.meta_key='_elementor_data' AND (m.meta_value LIKE '%\"cozy\"%' OR m.meta_value LIKE '%COZY%')" );
foreach ( $rows2 as $r ) echo "{$r->ID}\t{$r->post_status}\t{$r->post_name}\t{$r->post_title}\n";
