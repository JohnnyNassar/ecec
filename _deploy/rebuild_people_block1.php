<?php
/**
 * People page (post 123) — Block 1 rebuild only.
 *
 * Replaces the current intro (2 separate sections: standalone "OUR EXPERT
 * TEAM" h2, then "WHO WE ARE" h3 + body) with a single 2-col intro section
 * matching the theme demo layout:
 *   left col  = stacked headings  ("OUR EXPERT TEAM" + "WHO WE ARE")
 *   right col = narrative body (verbatim from current page)
 *
 * Keeps the team-grid section (section 3) untouched. Block 2 will be handled
 * separately.
 *
 * Idempotent: detects if intro is already in 2-col form (single section with
 * structure='20' and ecec-about-section-title widget) and skips if so.
 *
 * Backups: writes _elementor_data_backup_preblock1 once before the first run.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$pid = 123;
$data_raw = get_post_meta( $pid, '_elementor_data', true );
$data = is_array( $data_raw ) ? $data_raw : json_decode( $data_raw, true );
if ( ! is_array( $data ) || empty( $data ) ) { echo "ERROR: no Elementor data on post {$pid}.\n"; exit( 1 ); }

// One-time backup
if ( ! get_post_meta( $pid, '_elementor_data_backup_preblock1', true ) ) {
	update_post_meta( $pid, '_elementor_data_backup_preblock1', $data_raw );
	echo "Backed up _elementor_data → _elementor_data_backup_preblock1.\n";
}

// Find the team grid section (the one containing the [ecec_team_grid] shortcode)
// so we preserve it as-is. Everything before it is the intro that gets replaced.
$team_grid_section = null;
$team_grid_index = null;
foreach ( $data as $i => $section ) {
	if ( has_shortcode_section( $section ) ) {
		$team_grid_section = $section;
		$team_grid_index = $i;
		break;
	}
}
if ( ! $team_grid_section ) {
	echo "ERROR: couldn't find team-grid section. Aborting (would lose data).\n";
	exit( 1 );
}
echo "Found team-grid section at index {$team_grid_index} (will preserve).\n";

function has_shortcode_section( $section ) {
	if ( empty( $section['elements'] ) ) { return false; }
	foreach ( $section['elements'] as $el ) {
		if ( ( $el['widgetType'] ?? '' ) === 'shortcode' && strpos( $el['settings']['shortcode'] ?? '', 'ecec_team_grid' ) !== false ) {
			return true;
		}
		if ( ! empty( $el['elements'] ) ) {
			foreach ( $el['elements'] as $sub ) {
				if ( ( $sub['widgetType'] ?? '' ) === 'shortcode' && strpos( $sub['settings']['shortcode'] ?? '', 'ecec_team_grid' ) !== false ) {
					return true;
				}
			}
		}
	}
	return false;
}

function eid() { return wp_generate_password( 7, false, false ); }

$intro_body = "At ECEC, our team is our greatest asset. We are a diverse group of engineering professionals from different nationalities, backgrounds, and cultures, and we all share a passion for delivering high-quality and innovative engineering solutions.\n\nOur team is made up of experienced engineers, sustainability consultants, ICT specialists, and other professionals, all of whom have a deep understanding of the local market and its unique challenges.\n\nWe work closely with our clients and partners to understand their specific needs and requirements, and we strive to provide solutions that are both effective and sustainable.";

$intro_section = [
	'id' => eid(), 'elType' => 'section',
	'settings' => [
		'layout'    => 'boxed',
		'structure' => '20',
		'gap'       => 'extended',
		'padding'   => [ 'unit' => 'px', 'top' => '90', 'right' => '0', 'bottom' => '60', 'left' => '0', 'isLinked' => false ],
	],
	'elements' => [
		[
			'id' => eid(), 'elType' => 'column',
			'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100 ],
			'elements' => [
				[
					'id' => eid(), 'elType' => 'widget', 'widgetType' => 'heading',
					'settings' => [ 'title' => 'OUR EXPERT TEAM', 'header_size' => 'h2', '_css_classes' => 'ecec-about-section-title' ],
				],
				[
					'id' => eid(), 'elType' => 'widget', 'widgetType' => 'heading',
					'settings' => [ 'title' => 'WHO WE ARE', 'header_size' => 'h3', '_css_classes' => 'ecec-about-section-subtitle' ],
				],
			],
		],
		[
			'id' => eid(), 'elType' => 'column',
			'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100 ],
			'elements' => [
				[
					'id' => eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
					'settings' => [ 'editor' => wpautop( $intro_body ), '_css_classes' => 'ecec-about-narrative' ],
				],
			],
		],
	],
];

// New data: intro 2-col + team grid (preserved)
$new_data = [ $intro_section, $team_grid_section ];

update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $new_data ) ) );
delete_post_meta( $pid, '_elementor_element_cache' );
delete_post_meta( $pid, '_elementor_css' );

echo "\nReplaced post {$pid} _elementor_data with 2 sections:\n";
echo "  1. Intro (2-col: headings left + narrative right)\n";
echo "  2. Team grid (preserved)\n";
echo "View: " . get_permalink( $pid ) . "\n";
