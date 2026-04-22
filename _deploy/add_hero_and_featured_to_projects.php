<?php
/**
 * Build the lwkp-style Projects page layout by injecting Elementor elements
 * into post 122. Idempotent: detects and skips if already applied.
 *
 * Final structure (top to bottom):
 *   1. Hero container (full-width, bg image, h1 + subtitle)
 *   2. Tabs container (FEATURED | RECENT PROJECTS anchors, right-aligned)
 *   3. Featured Projects container (#featured-projects anchor target)
 *        - "Featured Projects" heading
 *        - [ecec_featured_projects]
 *   4. Recent Projects container (#recent-projects anchor target)
 *        - "PORTFOLIO" overline
 *        - "Our Recent Projects" heading
 *        - [ecec_project_search]
 *        - [emaurri_core_portfolio_list ...]
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$post_id   = 122;
$raw       = get_post_meta( $post_id, '_elementor_data', true );
$data      = is_array( $raw ) ? $raw : json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit( 1 ); }

// Hero source image: Desert Rock Resort featured image
$hero_attachment_id = get_post_thumbnail_id( 108 );
$hero_url           = wp_get_attachment_image_url( $hero_attachment_id, 'full' );
if ( ! $hero_url ) { echo "hero image not found\n"; exit( 1 ); }

// Idempotency check — look for our hero marker
foreach ( $data as $c ) {
	if ( ( $c['settings']['_element_id'] ?? '' ) === 'projects-hero' ) {
		echo "ALREADY PRESENT, skipping injection\n";
		exit( 0 );
	}
}

function ecec_eid() { return substr( md5( microtime( true ) . mt_rand() ), 0, 8 ); }

$hero_container = array(
	'id'       => ecec_eid(),
	'elType'   => 'container',
	'settings' => array(
		'_element_id'                 => 'projects-hero',
		'content_width'               => 'full',
		'flex_direction'              => 'column',
		'flex_justify_content'        => 'center',
		'flex_align_items'            => 'center',
		'min_height'                  => array( 'unit' => 'vh', 'size' => 60 ),
		'padding'                     => array( 'unit' => 'px', 'top' => '120', 'right' => '60', 'bottom' => '120', 'left' => '60', 'isLinked' => '' ),
		'background_background'       => 'classic',
		'background_image'            => array( 'url' => $hero_url, 'id' => $hero_attachment_id ),
		'background_position'         => 'center center',
		'background_repeat'           => 'no-repeat',
		'background_size'             => 'cover',
		'background_overlay_background' => 'classic',
		'background_overlay_color'    => '#000000',
		'background_overlay_opacity'  => array( 'unit' => 'px', 'size' => 0.5 ),
	),
	'elements' => array(
		// H1 headline
		array(
			'id'       => ecec_eid(),
			'elType'   => 'widget',
			'widgetType' => 'heading',
			'settings' => array(
				'title'       => 'Engineering the Systems Behind Great Places',
				'header_size' => 'h1',
				'align'       => 'center',
				'title_color' => '#ffffff',
				'typography_typography'    => 'custom',
				'typography_font_family'   => 'Plus Jakarta Sans',
				'typography_font_size'     => array( 'unit' => 'px', 'size' => 54 ),
				'typography_font_weight'   => '600',
				'typography_line_height'   => array( 'unit' => 'em', 'size' => 1.15 ),
				'_css_classes'             => 'ecec-hero-title',
			),
			'elements' => array(),
		),
		// Subtitle
		array(
			'id'       => ecec_eid(),
			'elType'   => 'widget',
			'widgetType' => 'heading',
			'settings' => array(
				'title'       => 'For over two decades, ECEC has delivered MEP, ICT, and sustainability engineering for the region\'s most defining hospitality, healthcare, and infrastructure projects.',
				'header_size' => 'p',
				'align'       => 'center',
				'title_color' => '#e7e8ea',
				'typography_typography'    => 'custom',
				'typography_font_family'   => 'Argentum Sans',
				'typography_font_size'     => array( 'unit' => 'px', 'size' => 16 ),
				'typography_line_height'   => array( 'unit' => 'em', 'size' => 1.6 ),
				'_css_classes'             => 'ecec-hero-subtitle',
				'_margin'                  => array( 'unit' => 'px', 'top' => '18', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => '' ),
				'_padding'                 => array( 'unit' => 'px', 'top' => '0', 'right' => '10%', 'bottom' => '0', 'left' => '10%', 'isLinked' => '' ),
			),
			'elements' => array(),
		),
	),
);

// Tabs strip (anchor links, styled via child theme CSS)
$tabs_container = array(
	'id'       => ecec_eid(),
	'elType'   => 'container',
	'settings' => array(
		'content_width' => 'boxed',
		'padding'       => array( 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => '' ),
	),
	'elements' => array(
		array(
			'id'       => ecec_eid(),
			'elType'   => 'widget',
			'widgetType' => 'html',
			'settings' => array(
				'html' => '<nav class="ecec-projects-tabs" aria-label="Projects sections">'
					. '<a href="#featured-projects">Featured</a>'
					. '<a href="#recent-projects">Recent Projects</a>'
					. '</nav>',
			),
			'elements' => array(),
		),
	),
);

// Featured Projects container
$featured_container = array(
	'id'       => ecec_eid(),
	'elType'   => 'container',
	'settings' => array(
		'_element_id' => 'featured-projects',
		'content_width' => 'boxed',
		'padding' => array( 'unit' => 'px', 'top' => '60', 'right' => '0', 'bottom' => '20', 'left' => '0', 'isLinked' => '' ),
	),
	'elements' => array(
		array(
			'id'       => ecec_eid(),
			'elType'   => 'widget',
			'widgetType' => 'heading',
			'settings' => array(
				'title'       => 'Featured Projects',
				'header_size' => 'h2',
				'align'       => 'left',
				'typography_typography'  => 'custom',
				'typography_font_family' => 'Plus Jakarta Sans',
				'typography_font_size'   => array( 'unit' => 'px', 'size' => 40 ),
				'typography_font_weight' => '600',
			),
			'elements' => array(),
		),
		array(
			'id'       => ecec_eid(),
			'elType'   => 'widget',
			'widgetType' => 'shortcode',
			'settings' => array(
				'shortcode' => '[ecec_featured_projects columns="2"]',
			),
			'elements' => array(),
		),
	),
);

// Update existing recent container — set anchor ID and prepend headings
$recent_container_idx = null;
foreach ( $data as $i => $c ) {
	if ( ( $c['elType'] ?? '' ) !== 'container' ) continue;
	$has_list = false;
	foreach ( $c['elements'] as $child ) {
		if ( strpos( $child['settings']['shortcode'] ?? '', 'emaurri_core_portfolio_list' ) !== false ) {
			$has_list = true; break;
		}
	}
	if ( $has_list ) { $recent_container_idx = $i; break; }
}
if ( $recent_container_idx === null ) { echo "recent container not found\n"; exit( 1 ); }

$data[ $recent_container_idx ]['settings']['_element_id']   = 'recent-projects';
$data[ $recent_container_idx ]['settings']['padding']       = array( 'unit' => 'px', 'top' => '40', 'right' => '0', 'bottom' => '60', 'left' => '0', 'isLinked' => '' );
$data[ $recent_container_idx ]['settings']['content_width'] = 'boxed';

$portfolio_overline = array(
	'id'       => ecec_eid(),
	'elType'   => 'widget',
	'widgetType' => 'heading',
	'settings' => array(
		'title'       => 'PORTFOLIO',
		'header_size' => 'p',
		'align'       => 'left',
		'title_color' => '#999999',
		'typography_typography'     => 'custom',
		'typography_font_family'    => 'Plus Jakarta Sans',
		'typography_font_size'      => array( 'unit' => 'px', 'size' => 14 ),
		'typography_font_weight'    => '600',
		'typography_letter_spacing' => array( 'unit' => 'px', 'size' => 3 ),
		'typography_text_transform' => 'uppercase',
	),
	'elements' => array(),
);
$our_recent_heading = array(
	'id'       => ecec_eid(),
	'elType'   => 'widget',
	'widgetType' => 'heading',
	'settings' => array(
		'title'       => 'Our Recent Projects',
		'header_size' => 'h2',
		'align'       => 'left',
		'typography_typography'  => 'custom',
		'typography_font_family' => 'Plus Jakarta Sans',
		'typography_font_size'   => array( 'unit' => 'px', 'size' => 40 ),
		'typography_font_weight' => '600',
		'_margin'                => array( 'unit' => 'px', 'top' => '4', 'right' => '0', 'bottom' => '30', 'left' => '0', 'isLinked' => '' ),
	),
	'elements' => array(),
);

// Prepend headings into the recent container, if not already there
$already_has_overline = false;
foreach ( $data[ $recent_container_idx ]['elements'] as $child ) {
	if ( ( $child['settings']['title'] ?? '' ) === 'PORTFOLIO' || ( $child['settings']['title'] ?? '' ) === 'Our Recent Projects' ) {
		$already_has_overline = true; break;
	}
}
if ( ! $already_has_overline ) {
	array_unshift( $data[ $recent_container_idx ]['elements'], $portfolio_overline, $our_recent_heading );
}

// Final data = hero + tabs + featured + (existing containers, incl. updated recent)
$new_data = array_merge( array( $hero_container, $tabs_container, $featured_container ), $data );

update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $new_data ) ) );
delete_post_meta( $post_id, '_elementor_element_cache' );
delete_post_meta( $post_id, '_elementor_css' );

$upload = wp_upload_dir();
$css = trailingslashit( $upload['basedir'] ) . 'elementor/css/post-' . $post_id . '.css';
if ( file_exists( $css ) ) { @unlink( $css ); echo "removed $css\n"; }

echo "injected hero + tabs + featured section into post $post_id\n";
echo "hero image: $hero_url (attachment $hero_attachment_id)\n";
