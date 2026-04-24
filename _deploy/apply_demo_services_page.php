<?php
/**
 * Import the Emaurri demo /our-services/ page's Elementor data into our
 * existing "our-services-preview" WordPress page, substituting ECEC
 * narrative verbatim and a local image in place of the demo's attachments.
 *
 * Source:      _theme_backup/emaurri/xml export/emaurri-export.xml (post_name=our-services)
 * Extracted:   _deploy/demo_our_services_elementor_data.json (raw Elementor JSON)
 * Target page: slug "our-services-preview" (created earlier; ID 168 on local)
 *
 * Modes (exactly one):
 *   dry       — preview replacements, no writes
 *   confirm   — apply replacements
 *   undo      — clear _elementor_data on the target page (fall back to custom template / legacy)
 *
 * Run via HTTP (admin only) or CLI (`sudo -u www-data php ...`).
 */

require_once __DIR__ . '/../wp-load.php';

$is_cli = ( php_sapi_name() === 'cli' );
if ( $is_cli ) {
	foreach ( $argv as $a ) {
		if ( $a === 'dry' )     { $_GET['dry']     = 1; }
		if ( $a === 'confirm' ) { $_GET['confirm'] = 1; }
		if ( $a === 'undo' )    { $_GET['undo']    = 1; }
	}
} else {
	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Log in as administrator.' );
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
}

$is_dry     = ! empty( $_GET['dry'] );
$is_confirm = ! empty( $_GET['confirm'] );
$is_undo    = ! empty( $_GET['undo'] );
$mode_count = (int) $is_dry + (int) $is_confirm + (int) $is_undo;
if ( $mode_count !== 1 ) {
	echo "Usage: ?dry=1 | ?confirm=1 | ?undo=1\n";
	exit;
}

// Target page by slug.
$target = get_page_by_path( 'our-services-preview', OBJECT, 'page' );
if ( ! ( $target instanceof WP_Post ) ) {
	echo "FAIL: page with slug 'our-services-preview' does not exist on this site.\n";
	exit( 1 );
}
echo "Target: page #{$target->ID} '{$target->post_title}' (status: {$target->post_status})\n";

if ( $is_undo ) {
	if ( $is_confirm === false && $is_dry === false ) {} // no-op
	delete_post_meta( $target->ID, '_elementor_data' );
	delete_post_meta( $target->ID, '_elementor_edit_mode' );
	delete_post_meta( $target->ID, '_elementor_template_type' );
	delete_post_meta( $target->ID, '_elementor_version' );
	delete_post_meta( $target->ID, '_wp_page_template' );
	delete_post_meta( $target->ID, '_elementor_css' );
	echo "UNDONE. Elementor meta cleared on page #{$target->ID}.\n";
	exit;
}

// Load the demo Elementor JSON.
$json_path = __DIR__ . '/demo_our_services_elementor_data.json';
if ( ! file_exists( $json_path ) ) {
	echo "FAIL: missing $json_path (extract from XML export first)\n";
	exit( 1 );
}
$raw = file_get_contents( $json_path );
$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) {
	echo "FAIL: demo JSON didn't parse.\n";
	exit( 1 );
}

// ─── ECEC content (verbatim from ecec.co except [P] numeric placeholders) ───
$hero_title = "At ECEC, we recognize that engineering transcends mere technicality; it embodies creativity and collaboration.";
$hero_text  = "Our pledge is to deliver innovative, sustainable, and socially responsible solutions that enrich the communities we engage with. Continually striving to redefine engineering design and construction paradigms, our team remains at the forefront of industry trends and best practices, pushing the boundaries of what's achievable.";
$button_text = 'explore';
$button_link = home_url( '/projects/' );

$progress_intro = "With our expert team and offices strategically situated in Dubai, Amman, and Riyadh, we are dedicated to providing world-class engineering solutions.";

// Progress bars (3)
$progress_bars = [
	[ 'number' => '80', 'title' => 'projects' ],   // [P]
	[ 'number' => '60', 'title' => 'clients' ],    // [P]
	[ 'number' => '10', 'title' => 'sectors' ],    // real: 10 sectors served
];

// Counters (4)
$counters = [
	[ 'start' => '0', 'end' => '25', 'text' => 'Years experience' ],  // [P] also fixing demo's "expirience" typo
	[ 'start' => '0', 'end' => '60', 'text' => 'Expert partners' ],   // [P]
	[ 'start' => '0', 'end' => '50', 'text' => 'Clients served' ],    // [P]
	[ 'start' => '0', 'end' => '12', 'text' => 'Active projects' ],   // [P]
];

// Accordion — 7 services from ecec.co/our-services/ verbatim
$services = [
	[ 'title' => 'STRUCTURAL ENGINEERING',                    'body' => 'Our team of experienced structural engineers provides innovative and cost-effective solutions for a wide range of structural projects. We specialize in the design and analysis of buildings, bridges, towers, and other structures.' ],
	[ 'title' => 'MEP ENGINEERING',                            'body' => 'We offer MEP design and engineering services that cover all aspects of building services, including HVAC systems, electrical systems, plumbing systems, fire protection systems, and more.' ],
	[ 'title' => 'ACOUSTIC ENGINEERING',                       'body' => 'Our acoustic engineers provide expert advice on noise and vibration control, acoustic design, and environmental acoustics. We offer comprehensive solutions that optimize acoustical performance in buildings, transportation systems, and other structures.' ],
	[ 'title' => 'BUILDING INFORMATION MODELING SERVICES',     'body' => 'Our BIM services cover the entire project lifecycle, from concept design to construction and maintenance. We use the latest BIM software to create 3D models that allow for accurate visualization, clash detection, and coordination.' ],
	[ 'title' => 'ICT SOLUTIONS AND SERVICES',                 'body' => 'We offer a wide range of ICT solutions and services, including network design and implementation, software development, cybersecurity, and IT consulting. Our team is committed to providing cutting-edge technology solutions that enhance productivity and efficiency.' ],
	[ 'title' => 'SECURITY CONSULTING AND ENGINEERING',        'body' => 'Our security experts provide comprehensive security consulting and engineering services, including risk assessments, security system design, and physical security solutions. We work with clients to develop customized security solutions that meet their unique needs and mitigate potential threats.' ],
	[ 'title' => 'SUPPLEMENTARY ENGINEERING SERVICES',         'body' => 'We work closely with our network of associates to provide supplementary engineering services, including fire and life safety consultancy, specialist lighting consultancy, vertical transportation, and waste management. Our team ensures that all aspects of the project are completed to the highest standards of quality and efficiency.' ],
];

// Local image (placeholder for the 3 demo images — client swaps in Elementor later)
$local_image_id  = 107; // Desert Rock Resort featured image
$local_image_url = wp_get_attachment_image_url( $local_image_id, 'full' );
if ( ! $local_image_url ) {
	echo "WARN: attachment #$local_image_id not found on this site; demo URLs will remain. Consider picking another placeholder ID.\n";
	$local_image_id  = 0;
	$local_image_url = '';
}

// ─── Walk the Elementor data and swap content ───
$report = [];

function walk( &$el, &$report, $ctx ) {
	global $hero_title, $hero_text, $button_text, $button_link, $progress_intro,
		$progress_bars, $counters, $services, $local_image_id, $local_image_url;

	static $progress_idx = 0;
	static $counter_idx  = 0;

	if ( isset( $el['widgetType'] ) ) {
		$wt =& $el['widgetType'];
		$s  =& $el['settings'];

		switch ( $wt ) {
			case 'emaurri_core_single_image':
				if ( $local_image_id && isset( $s['image'] ) && is_array( $s['image'] ) ) {
					$old_id = $s['image']['id'] ?? null;
					$s['image']['id']  = $local_image_id;
					$s['image']['url'] = $local_image_url;
					$s['image']['size'] = '';
					$report[] = "Image: replaced demo attachment #$old_id -> local #$local_image_id";
				}
				break;

			case 'emaurri_core_section_title':
				if ( ! empty( $s['title'] ) && $s['title'] === 'We follow the trends of world interior design.' ) {
					$s['title'] = $hero_title;
					$report[] = "Hero title updated.";
				}
				if ( isset( $s['text'] ) && str_contains( (string) $s['text'], 'Etiam rhoncus' ) ) {
					$s['text'] = $hero_text;
					$report[] = "Hero body text updated.";
				}
				// "Progress." heading stays literal.
				break;

			case 'text-editor':
				if ( isset( $s['editor'] ) && str_contains( (string) $s['editor'], 'Lorem ipsum dolor sit amet, consectetuer elit' ) ) {
					$s['editor'] = '<p>' . $progress_intro . '</p>';
					$report[] = "Progress intro paragraph updated.";
				}
				break;

			case 'emaurri_core_button':
				if ( isset( $s['text'] ) ) {
					$s['text'] = $button_text;
				}
				if ( isset( $s['link'] ) && is_array( $s['link'] ) ) {
					$s['link']['url'] = $button_link;
				} elseif ( isset( $s['link'] ) ) {
					$s['link'] = $button_link;
				}
				$report[] = "CTA button set to '$button_text' -> $button_link";
				break;

			case 'emaurri_core_progress_bar':
				if ( $progress_idx < count( $progress_bars ) ) {
					$pb = $progress_bars[ $progress_idx ];
					$s['number'] = $pb['number'];
					$s['title']  = $pb['title'];
					$report[] = "Progress bar [$progress_idx]: {$pb['number']} / {$pb['title']}";
					$progress_idx++;
				}
				break;

			case 'emaurri_core_counter':
				if ( $counter_idx < count( $counters ) ) {
					$c = $counters[ $counter_idx ];
					$s['start_digit'] = $c['start'];
					$s['end_digit']   = $c['end'];
					$s['text']        = $c['text'];
					$report[] = "Counter [$counter_idx]: {$c['end']} / {$c['text']}";
					$counter_idx++;
				}
				break;

			case 'emaurri_core_accordion':
				// Replace child widget items with ECEC services (pad to 7).
				$new_items = [];
				foreach ( $services as $i => $svc ) {
					$new_items[] = [
						'_id'         => substr( md5( 'ecec-acc-' . $i ), 0, 7 ),
						'title'       => $svc['title'],
						'title_tag'   => 'p',
						'content'     => '<p style="font-size: 17px;">' . esc_html( $svc['body'] ) . '</p>',
					];
				}
				$s['elements_of_child_widget'] = $new_items;
				$report[] = "Accordion: replaced with " . count( $new_items ) . " ECEC services.";
				break;
		}
	}

	if ( isset( $el['elements'] ) && is_array( $el['elements'] ) ) {
		foreach ( $el['elements'] as &$child ) {
			walk( $child, $report, $ctx );
		}
		unset( $child );
	}
}

foreach ( $data as &$section ) {
	walk( $section, $report, null );
}
unset( $section );

echo "\n=== Replacement report ===\n";
foreach ( $report as $line ) { echo "  - $line\n"; }
echo "\n";

// Re-encode for storage. Elementor wants slashes escaped the way its exporter does;
// wp_slash + wp_json_encode with UNESCAPED_SLASHES is the safe pattern.
$new_json = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

echo "New _elementor_data length: " . strlen( $new_json ) . " chars\n";

if ( $is_dry ) {
	echo "\nDRY RUN — no writes. Re-run with 'confirm' to apply.\n";
	exit;
}

// Apply
update_post_meta( $target->ID, '_elementor_data', wp_slash( $new_json ) );
update_post_meta( $target->ID, '_elementor_edit_mode', 'builder' );
update_post_meta( $target->ID, '_elementor_template_type', 'wp-page' );
update_post_meta( $target->ID, '_elementor_version', '3.0.0' );
update_post_meta( $target->ID, '_wp_page_template', 'page-full-width.php' );

// Emaurri qodef page meta — port from the demo XML so title bar/padding match.
$qodef_meta = [
	'qodef_page_content_padding'         => '0 0 0 0',
	'qodef_page_content_padding_mobile'  => '0 0 0 0',
	'qodef_content_behind_header'        => 'no',
	'qodef_show_header_widget_areas'     => 'yes',
	'qodef_top_area_header_in_grid'      => 'yes',
	'qodef_enable_page_title'            => 'no',
	'qodef_title_layout'                 => 'standard',
	'qodef_set_page_title_area_in_grid'  => 'yes',
	'qodef_page_title_height'            => '250',
	'qodef_content_side_position'        => 'default',
	'qodef_content_width'                => 'default',
	'qodef_minimal_centered_header_in_grid' => 'yes',
	'qodef_sticky_header_enable_border'  => 'yes',
];
foreach ( $qodef_meta as $k => $v ) {
	update_post_meta( $target->ID, $k, $v );
}

// Clear Elementor CSS cache for the page so it rebuilds.
delete_post_meta( $target->ID, '_elementor_css' );
delete_post_meta( $target->ID, '_elementor_element_cache' );

echo "\nLIVE RUN COMPLETE.\n";
echo "Page permalink: " . get_permalink( $target->ID ) . "\n";
echo "Edit in Elementor: " . admin_url( "post.php?post={$target->ID}&action=elementor" ) . "\n";
echo "\nTo roll back: run this script with ?undo=1\n";
