<?php
/**
 * Seed _ecec_blocks on one portfolio-item for frontend PoC testing.
 * Default target: "Desert Rock Resort". Pass ?title=Foo to override.
 *
 * Builds a representative block sequence using the project's own featured
 * image + images from its qodef_portfolio_media repeater — so no media-library
 * guessing. Idempotent: re-running overwrites the blocks.
 *
 * Local run:
 *   http://localhost/ecec/wp-content/themes/emaurri-child/../../../_deploy/seed_test_project_blocks.php
 *   (or browser: http://localhost/ecec/_deploy/seed_test_project_blocks.php)
 */

require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) && ! isset( $_GET['force'] ) ) {
	wp_die( 'Must be logged in as admin, or pass ?force=1 (local only). Aborting.' );
}

$title = isset( $_GET['title'] ) ? sanitize_text_field( $_GET['title'] ) : 'Desert Rock Resort';

$posts = get_posts( [
	'post_type'   => 'portfolio-item',
	'post_status' => 'any',
	'title'       => $title,
	'numberposts' => 1,
] );
// Fallback: partial title match via s=
if ( empty( $posts ) ) {
	$posts = get_posts( [
		'post_type'   => 'portfolio-item',
		'post_status' => 'any',
		's'           => $title,
		'numberposts' => 1,
	] );
}
if ( empty( $posts ) ) {
	echo "ERROR: no portfolio-item found matching '" . esc_html( $title ) . "'.\n";
	exit;
}
$post = $posts[0];
$pid  = $post->ID;

$featured_id = (int) get_post_thumbnail_id( $pid );
$media       = get_post_meta( $pid, 'qodef_portfolio_media', true );
$gallery_ids = [];
if ( is_array( $media ) ) {
	foreach ( $media as $row ) {
		if ( isset( $row['qodef_portfolio_media_image']['id'] ) ) {
			$id = (int) $row['qodef_portfolio_media_image']['id'];
			if ( $id ) { $gallery_ids[] = $id; }
		}
	}
}

echo "Found portfolio-item #{$pid} '" . esc_html( $post->post_title ) . "'.\n";
echo "  Featured image: " . ( $featured_id ?: 'NONE' ) . "\n";
echo "  Gallery ids: " . ( $gallery_ids ? implode( ',', $gallery_ids ) : 'NONE' ) . "\n";

$first_img  = $gallery_ids[0] ?? $featured_id;
$second_img = $gallery_ids[1] ?? $featured_id;
$third_img  = $gallery_ids[2] ?? $featured_id;

$blocks = [
	[
		'type' => 'text-paragraph',
		'body' => wp_strip_all_tags( $post->post_content )
			? $post->post_content
			: 'Introduction text about the project goes here. Describe the brief, the context, and what made this engagement distinctive. This paragraph is the first thing readers see after the hero image, so it sets the tone for everything that follows.',
	],
	[
		'type'       => 'image-text-split',
		'image_id'   => $first_img,
		'overline'   => 'VISION & CONTEXT',
		'heading'    => 'A landmark shaped by its landscape',
		'body'       => 'The engineering brief required systems that respected the dramatic desert terrain while delivering a hospitality experience at the top of the luxury scale. Every decision — from MEP routing to thermal comfort to water strategy — was taken in response to the site, not imposed upon it.',
		'image_side' => 'left',
	],
	[
		'type'           => 'image-pair',
		'image_id_left'  => $second_img,
		'image_id_right' => $third_img,
		'caption_left'   => 'Main resort volume seen from the valley approach.',
		'caption_right'  => 'Guest suite cluster integrated into the rock face.',
	],
	[
		'type'     => 'full-image',
		'image_id' => $featured_id,
		'caption'  => 'Context view of the broader site.',
	],
	[
		'type'     => 'project-data',
		'overline' => 'PROJECT DATA',
		'heading'  => 'At a glance',
		'rows'     => [
			[ 'label' => 'Client',     'value' => 'Red Sea Global' ],
			[ 'label' => 'Location',   'value' => 'Saudi Arabia' ],
			[ 'label' => 'Scope',      'value' => 'MEP · ICT · Sustainability' ],
			[ 'label' => 'Completion', 'value' => '2024' ],
		],
	],
	[
		'type'        => 'pull-quote',
		'quote'       => 'Good engineering is invisible to the guest and indispensable to the operator. On Desert Rock, both happened at the same time.',
		'attribution' => 'Project Director, ECEC',
	],
	[
		'type'      => 'gallery',
		'image_ids' => array_values( array_unique( array_filter( array_merge( $gallery_ids, [ $featured_id ] ) ) ) ),
		'columns'   => 3,
	],
];

update_post_meta( $pid, '_ecec_blocks', wp_slash( wp_json_encode( $blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );

echo "\nSaved " . count( $blocks ) . " block(s) to _ecec_blocks on post {$pid}.\n";
echo "View: " . get_permalink( $pid ) . "\n";
