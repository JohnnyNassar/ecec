<?php
/**
 * Rebuild About Us page using Emaurri demo layout — section/column schema
 * (matches the Elementor version on this VPS, NOT the newer container/flex one).
 *
 * Schema reference (from working Services page #328):
 *   sections: elType=section, settings={layout, structure, padding}
 *   structure values: "20"=2-col(50/50), "40"=4-col(25/25/25/25), unset=1-col
 *   columns: elType=column, settings={_column_size, _inline_size_tablet:100, ...}
 *   widgets: elType=widget, widgetType, settings
 *
 * - Cleans up failed previous attempt (deletes draft post slug "about-us-broken-*")
 * - Archives post 120 → "about-us-archive"
 * - Creates new "About Us" page with 6 sections
 * - Idempotent: detects new post by slug + reuses
 *
 * CLI usage:
 *   sudo -u www-data php /var/www/html/ecec/_tmp/rebuild_about_us.php
 */

require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

// ─── Cleanup any previous failed attempts ─────────────────────────────────
$broken = get_posts( [
	'post_type'   => 'page',
	'post_status' => [ 'draft', 'publish' ],
	'name'        => 'about-us-broken-*',
	'numberposts' => 5,
] );
// Posts are matched by exact slug above; do a wildcard scan instead
global $wpdb;
$broken_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='page' AND post_name LIKE 'about-us-broken-%'" );
foreach ( $broken_ids as $bid ) {
	wp_delete_post( (int) $bid, true );
	echo "Deleted previous broken page #{$bid}.\n";
}

// ─── Archive post 120 if not already ──────────────────────────────────────
$old = get_post( 120 );
if ( ! $old ) { echo "ERROR: post 120 not found.\n"; exit( 1 ); }
if ( $old->post_name !== 'about-us-archive' ) {
	$existing_eldata = get_post_meta( 120, '_elementor_data', true );
	if ( $existing_eldata && ! get_post_meta( 120, '_elementor_data_backup_prerebuild', true ) ) {
		update_post_meta( 120, '_elementor_data_backup_prerebuild', $existing_eldata );
		echo "Backed up post 120 _elementor_data to _elementor_data_backup_prerebuild.\n";
	}
	wp_update_post( [ 'ID' => 120, 'post_title' => 'About Us (Archive)', 'post_name' => 'about-us-archive' ] );
	echo "Renamed post 120 → 'About Us (Archive)'.\n";
} else {
	echo "Post 120 already archived. Skipping rename.\n";
}

// ─── Find or create new About Us page ─────────────────────────────────────
$new = get_page_by_path( 'about-us', OBJECT, 'page' );
if ( ! $new ) {
	$new_id = wp_insert_post( [
		'post_title'  => 'About Us',
		'post_name'   => 'about-us',
		'post_type'   => 'page',
		'post_status' => 'publish',
		'post_author' => 1,
	] );
	if ( is_wp_error( $new_id ) ) { echo "ERROR creating page: " . $new_id->get_error_message() . "\n"; exit( 1 ); }
	echo "Created new About Us page: ID {$new_id}\n";
} else {
	$new_id = $new->ID;
	echo "Found existing About Us page: ID {$new_id}\n";
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function eid() { return wp_generate_password( 7, false, false ); }
$num_url = function ( $n ) { return home_url( "/wp-content/uploads/about-numbers/No{$n}.png" ); };
$placeholder = function ( $w, $h ) {
	$wh = "{$w} &times; {$h}";
	return '<div class="ecec-block-placeholder ecec-block-placeholder--about" style="aspect-ratio: ' . $w . ' / ' . $h . ';"><p class="ecec-block-placeholder__size">' . $wh . '</p><p class="ecec-block-placeholder__hint">Image placeholder &mdash; replace via admin</p></div>';
};

// ─── Narrative copy (verbatim from old post 120) ──────────────────────────
$intro_body = "ECEC, a prominent engineering consultancy based in Dubai, UAE, expands its presence with offices strategically located in Riyadh, KSA, and Amman, Jordan. Focused on delivering tailored engineering solutions, we are committed to driving business development and fostering growth across the Middle East and North Africa region.\n\nWe offer an extensive array of engineering services encompassing structural engineering, MEP design and engineering, ICT engineering, sustainability consulting and engineering, acoustic engineering, BIM services, and additional supplementary engineering solutions.\n\nOur team comprises a dynamic mix of professionals representing various nationalities, fostering an environment where we excel in collaborating with individuals from diverse cultural and background contexts. We view diversity as a pivotal asset driving engineering innovation, empowering us to tackle challenges with a fresh and unique perspective.\n\nWith our expert team and offices strategically situated in Dubai, Amman, and Riyadh, we are dedicated to providing world-class engineering solutions promptly and cost-effectively. Our commitment extends to delivering excellent customer service and consistently exceeding the expectations of our valued partners.";
$who_we_are = "At ECEC, we recognize that engineering transcends mere technicality; it embodies creativity and collaboration, necessitating unwavering passion and commitment.\n\nOur pledge is to deliver innovative, sustainable, and socially responsible solutions that enrich the communities we engage with.\n\nContinually striving to redefine engineering design and construction paradigms, our team remains at the forefront of industry trends and best practices.\n\nWe value the enduring relationships cultivated with our clients and partners, committing ourselves to ongoing enhancement and innovation in every endeavor.\n\nThe Amman office serves as an integral extension of our operations, offering a convenient hub for collaboration with regional clients and partners while bolstering our team's capacity to deliver exceptional engineering solutions.";
$strategic_body = "Our forte lies in crafting designs that not only align with but surpass international standards. With extensive expertise, we ensure superior quality at every phase of the design and construction journey.\n\nOur adept team excels in delivering top-tier service while adeptly navigating local dynamics, ensuring timely and budget-conscious project completion.\n\nWith our roots firmly embedded in the UAE and history of work within the region, our combined team has a deep understanding of the region and understands the detailed steps required to see projects from conception through to completion.";
$strategic_subhead_body = "At ECEC, our primary focus is on providing exceptional and tailored services that create tangible value for our clients.";
$vision_body = "Our vision entails delivering knowledge-centric services that effectively address our clients' requirements through seamless and comprehensive solutions.\n\nRecognizing that success hinges on a deep comprehension of project scope and context, we prioritize early collaboration with clients and design teams to lay the foundation for successful outcomes.";

$highlights = [
	[ 'num' => 1, 'heading' => 'Since 2016',                  'body' => 'Established track record since 2016, serving diverse sectors and geographies.' ],
	[ 'num' => 2, 'heading' => 'Multidisciplinary Expertise', 'body' => 'Architectural Design, Master Planning, Civil & Road Engineering, Structural Engineering, Infrastructure & Utilities, MEP Systems, ICT, and Sustainable Design.' ],
	[ 'num' => 3, 'heading' => 'Smart Engineering',           'body' => 'Advanced digital tools for enhanced accuracy, efficiency and cost effectiveness.' ],
	[ 'num' => 4, 'heading' => 'Excellence & Reliability',    'body' => 'Committed to best-in-class standards, timely delivery and measurable outcomes.' ],
];

// ─── Section/column/widget builders ──────────────────────────────────────
$section1col = function ( $widgets, $padding = [ 'top' => '80', 'bottom' => '80' ] ) {
	$pad = array_merge( [ 'unit' => 'px', 'right' => '0', 'left' => '0', 'isLinked' => false ], $padding );
	return [
		'id' => eid(), 'elType' => 'section',
		'settings' => [ 'layout' => 'boxed', 'padding' => $pad ],
		'elements' => [
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 100, '_inline_size' => null ],
				'elements' => $widgets,
			],
		],
	];
};

$section2col = function ( $left_widgets, $right_widgets, $padding = [ 'top' => '80', 'bottom' => '80' ] ) {
	$pad = array_merge( [ 'unit' => 'px', 'right' => '0', 'left' => '0', 'isLinked' => false ], $padding );
	return [
		'id' => eid(), 'elType' => 'section',
		'settings' => [ 'layout' => 'boxed', 'structure' => '20', 'padding' => $pad, 'gap' => 'extended' ],
		'elements' => [
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100 ],
				'elements' => $left_widgets,
			],
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 50, '_inline_size' => null, '_inline_size_tablet' => 100 ],
				'elements' => $right_widgets,
			],
		],
	];
};

$section4col = function ( $card_widget_groups, $padding = [ 'top' => '80', 'bottom' => '80' ] ) {
	$pad = array_merge( [ 'unit' => 'px', 'right' => '0', 'left' => '0', 'isLinked' => false ], $padding );
	$cols = [];
	foreach ( $card_widget_groups as $widgets ) {
		$cols[] = [
			'id' => eid(), 'elType' => 'column',
			'settings' => [ '_column_size' => 25, '_inline_size' => null, '_inline_size_tablet' => 50 ],
			'elements' => $widgets,
		];
	}
	return [
		'id' => eid(), 'elType' => 'section',
		'settings' => [ 'layout' => 'boxed', 'structure' => '40', 'padding' => $pad, 'gap' => 'extended', '_css_classes' => 'ecec-about-highlights' ],
		'elements' => $cols,
	];
};

$heading = function ( $title, $size = 'h2', $align = '', $css_class = '' ) {
	$s = [ 'title' => $title, 'header_size' => $size ];
	if ( $align ) { $s['align'] = $align; }
	if ( $css_class ) { $s['_css_classes'] = $css_class; }
	return [ 'id' => eid(), 'elType' => 'widget', 'widgetType' => 'heading', 'settings' => $s ];
};

$text = function ( $body, $css_class = '' ) {
	$s = [ 'editor' => wpautop( $body ) ];
	if ( $css_class ) { $s['_css_classes'] = $css_class; }
	return [ 'id' => eid(), 'elType' => 'widget', 'widgetType' => 'text-editor', 'settings' => $s ];
};

$html_widget = function ( $html, $css_class = '' ) {
	$s = [ 'html' => $html ];
	if ( $css_class ) { $s['_css_classes'] = $css_class; }
	return [ 'id' => eid(), 'elType' => 'widget', 'widgetType' => 'html', 'settings' => $s ];
};

$image_widget = function ( $url, $css_class = '' ) {
	$s = [ 'image' => [ 'url' => $url, 'id' => 0 ], 'image_size' => 'full' ];
	if ( $css_class ) { $s['_css_classes'] = $css_class; }
	return [ 'id' => eid(), 'elType' => 'widget', 'widgetType' => 'image', 'settings' => $s ];
};

// ─── Build the 6 sections ─────────────────────────────────────────────────
$el_data = [];

// 1. Intro 2-col: heading left + ECEC narrative right (matches demo's intro pattern)
$el_data[] = $section2col(
	[ $heading( 'ABOUT ECEC', 'h2', '', 'ecec-about-hero-subtitle' ) ],
	[ $text( $intro_body, 'ecec-about-narrative' ) ],
	[ 'top' => '90', 'bottom' => '60' ]
);

// 2. Client logos marquee — uses the same shared option as the home marquee
$el_data[] = $section1col( [
	[
		'id' => eid(), 'elType' => 'widget', 'widgetType' => 'shortcode',
		'settings' => [ 'shortcode' => '[ecec_clients_marquee]' ],
	],
], [ 'top' => '20', 'bottom' => '40' ] );

// 3. 2-col: image + WHO WE ARE
$el_data[] = $section2col(
	[ $html_widget( $placeholder( 600, 700 ) ) ],
	[
		$heading( 'WHO WE ARE', 'h2', '', 'ecec-about-section-title' ),
		$text( $who_we_are, 'ecec-about-narrative' ),
	]
);

// 4. 4-col Key Highlights cards
$cards = [];
foreach ( $highlights as $h ) {
	$cards[] = [
		$image_widget( $num_url( $h['num'] ), 'ecec-about-highlight-num' ),
		$heading( $h['heading'], 'h3', '', 'ecec-about-highlight-heading' ),
		$text( $h['body'], 'ecec-about-highlight-body' ),
	];
}
$el_data[] = $section4col( $cards );

// (Sections 5 + 6 removed — strict demo fidelity, OUR STRATEGIC ADVANTAGES + VISION
//  AND UNDERSTANDING dropped because the demo has no slot for them. The narratives
//  remain in the post 120 archive _elementor_data_backup_prerebuild meta if needed.)

// ─── Save ─────────────────────────────────────────────────────────────────
update_post_meta( $new_id, '_elementor_edit_mode', 'builder' );
update_post_meta( $new_id, '_elementor_template_type', 'wp-page' );
update_post_meta( $new_id, '_elementor_version', '3.18.0' );
update_post_meta( $new_id, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );
delete_post_meta( $new_id, '_elementor_element_cache' );
delete_post_meta( $new_id, '_elementor_css' );

echo "\nDone. Post {$new_id} populated with " . count( $el_data ) . " sections.\n";
echo "View: " . get_permalink( $new_id ) . "\n";
