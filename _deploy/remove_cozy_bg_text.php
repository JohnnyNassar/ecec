<?php
/**
 * Remove the qodef "cozy" background watermark from the Services page.
 * Strips the qodef_row_background_text setting wherever it equals "cozy"
 * (case-insensitive) on post 121's Elementor data.
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$target = get_page_by_path( 'our-services' );
if ( ! $target ) { echo "FAIL: no page with slug 'our-services'\n"; exit( 1 ); }
$post_id = (int) $target->ID;
echo "Target: post {$post_id} '{$target->post_title}' (slug=our-services)\n";
$raw     = get_post_meta( $post_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) { echo "no elementor data on {$post_id}\n"; exit( 1 ); }

$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

$hits = 0;
$walk = function( &$node ) use ( &$walk, &$hits ) {
	if ( ! is_array( $node ) ) return;
	if ( isset( $node['settings'] ) && is_array( $node['settings'] ) ) {
		$s =& $node['settings'];
		if ( isset( $s['qodef_row_background_text'] ) && strcasecmp( (string) $s['qodef_row_background_text'], 'cozy' ) === 0 ) {
			$s['qodef_row_background_text'] = '';
			$hits++;
		}
	}
	if ( isset( $node['elements'] ) && is_array( $node['elements'] ) ) {
		foreach ( $node['elements'] as &$child ) $walk( $child );
	}
};
foreach ( $data as &$top ) $walk( $top );

if ( $hits ) {
	update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $data ) ) );
	echo "Cleared {$hits} 'cozy' background text setting(s) in _elementor_data.\n";
} else {
	echo "_elementor_data: no 'cozy' setting found.\n";
}

// Qode theme stores section-level extras (bg_text/bg_margin/bg_color/etc.) in
// a SEPARATE serialized meta keyed by Elementor section ID. The frontend
// JS reads from THIS meta, not from _elementor_data — so we have to clear
// it too. Pattern: array<sectionId, array<int, array{bg_text:string,...}>>
$qm = get_post_meta( $post_id, 'qodef_elementor_section_data_meta', true );
$qm_hits = 0;
if ( is_array( $qm ) ) {
	foreach ( $qm as $section_id => &$variants ) {
		if ( ! is_array( $variants ) ) continue;
		foreach ( $variants as &$row ) {
			if ( ! is_array( $row ) ) continue;
			if ( isset( $row['bg_text'] ) && strcasecmp( (string) $row['bg_text'], 'cozy' ) === 0 ) {
				$row['bg_text'] = '';
				$qm_hits++;
			}
		}
		unset( $row );
	}
	unset( $variants );
	if ( $qm_hits ) {
		update_post_meta( $post_id, 'qodef_elementor_section_data_meta', $qm );
		echo "Cleared {$qm_hits} 'cozy' bg_text(s) in qodef_elementor_section_data_meta.\n";
	} else {
		echo "qodef_elementor_section_data_meta: no 'cozy' bg_text found.\n";
	}
} else {
	echo "qodef_elementor_section_data_meta: absent or not an array.\n";
}

delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

if ( $hits === 0 && $qm_hits === 0 ) {
	echo "No 'cozy' references found anywhere on post {$post_id}.\n";
}
