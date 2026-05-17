<?php
/**
 * Rebuild the People page (post 123) per the 2026-05 mockup:
 *
 *   1. Empty gray hero band (full-width section, .ecec-people-hero CSS class)
 *   2. "OUR LEADERS" heading
 *   3. [ecec_team_leadership]                — alternating leader rows
 *   4. "Business Development" label + [ecec_team_department dept="..."] grid
 *   5. "Design Team"          label + grid
 *   6. "Project Management"   label + grid
 *   7. "Administration"       label + grid
 *
 * Idempotent: stashes the previous _elementor_data under
 * _elementor_data_backup_prerebuild_v2 the first time it runs.
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$target = get_page_by_path( 'people' );
if ( ! $target ) { echo "FAIL: no page with slug 'people'\n"; exit( 1 ); }
$post_id = (int) $target->ID;
echo "Target: post {$post_id} '{$target->post_title}' (slug=people)\n";

$BACKUP_KEY = '_elementor_data_backup_prerebuild_v2';

$raw = get_post_meta( $post_id, '_elementor_data', true );
if ( ! is_string( $raw ) || $raw === '' ) { echo "no elementor data on {$post_id}\n"; exit( 1 ); }

if ( ! get_post_meta( $post_id, $BACKUP_KEY, true ) ) {
	update_post_meta( $post_id, $BACKUP_KEY, $raw );
	echo "Stashed pre-rebuild Elementor data to meta '{$BACKUP_KEY}'.\n";
} else {
	echo "Backup already present; not overwriting.\n";
}

// ───────────────────── builders ─────────────────────
$eid = function () { return substr( md5( uniqid( '', true ) ), 0, 7 ); };

$section_full = function ( $children, $css_class = '', $padding = null ) use ( $eid ) {
	$settings = array(
		'layout'           => 'full_width',
		'gap'              => 'no',
		'css_classes'      => $css_class,
	);
	if ( $padding ) {
		$settings['padding'] = array_merge(
			array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ),
			$padding
		);
	}
	return array(
		'id'       => $eid(),
		'elType'   => 'section',
		'settings' => $settings,
		'elements' => array( array(
			'id'       => $eid(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => 100, '_inline_size' => null ),
			'elements' => $children,
		) ),
	);
};

$heading = function ( $title, $tag = 'h2', $css_class = '' ) use ( $eid ) {
	return array(
		'id'         => $eid(),
		'elType'     => 'widget',
		'widgetType' => 'heading',
		'settings'   => array(
			'title'        => $title,
			'header_size'  => $tag,
			'_css_classes' => $css_class,
		),
	);
};

$shortcode = function ( $code ) use ( $eid ) {
	return array(
		'id'         => $eid(),
		'elType'     => 'widget',
		'widgetType' => 'shortcode',
		'settings'   => array( 'shortcode' => $code ),
	);
};

// ───────────────────── compose ─────────────────────
$el_data = array();

// 1. Hero band — empty gray, CSS controls height + background
$el_data[] = $section_full( array(), 'ecec-people-hero' );

// 2. OUR LEADERS heading + 3. leaders shortcode
$el_data[] = $section_full(
	array(
		$heading( 'OUR LEADERS', 'h2', 'ecec-people-leaders-heading' ),
		$shortcode( '[ecec_team_leadership]' ),
	),
	'',
	array( 'top' => '10', 'bottom' => '20' )
);

// 4-7. Department blocks
$departments = array(
	array( 'slug' => 'business-development', 'label' => 'Business Development', 'min' => 3 ),
	array( 'slug' => 'design-team',          'label' => 'Design Team',          'min' => 9 ),
	array( 'slug' => 'project-management',   'label' => 'Project Management',   'min' => 3 ),
	array( 'slug' => 'administration',       'label' => 'Administration',       'min' => 3 ),
);
foreach ( $departments as $d ) {
	$el_data[] = $section_full(
		array(
			$heading( $d['label'], 'h3', 'ecec-people-dept-label' ),
			$shortcode( sprintf( '[ecec_team_department dept="%s" columns="4" min="%d"]', $d['slug'], $d['min'] ) ),
		),
		'',
		array( 'top' => '0', 'bottom' => '40' )
	);
}

// ───────────────────── save ─────────────────────
update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

echo "Rebuilt post {$post_id} with " . count( $el_data ) . " top-level sections.\n";
echo "  1. Hero band (ecec-people-hero)\n";
echo "  2. OUR LEADERS + leaders rows\n";
foreach ( $departments as $i => $d ) {
	printf( "  %d. %s (min=%d)\n", $i + 3, $d['label'], $d['min'] );
}
echo "\nView: " . get_permalink( $post_id ) . "\n";
echo "Undo: restore meta '{$BACKUP_KEY}' → '_elementor_data'\n";
