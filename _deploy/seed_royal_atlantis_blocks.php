<?php
/**
 * Seed _ecec_blocks on Royal Atlantis Resort (#114) with the full block layout.
 *
 * 7-block sequence with placeholder images for the slots we have no photo for
 * (Royal Atlantis has 1 real image — used in image-text-split only). The
 * partials render a gray "1200 × 400" / "600 × 450" placeholder when image_id
 * is 0, intended as a visual nag for the client to upload real photos.
 *
 *   1. text-paragraph   — existing post_content verbatim
 *   2. image-text-split — REAL featured image + Palm Jumeirah narrative
 *   3. image-pair       — placeholder + placeholder + captions
 *   4. full-image       — placeholder + caption
 *   5. project-data     — Client / Contractor / Location / Scope / Completion
 *   6. pull-quote       — hospitality/luxury angle
 *   7. gallery          — 3 placeholders (one row at columns=3)
 *
 * Idempotent: re-running overwrites _ecec_blocks. The legacy 1-block
 * migrated value is stored on a backup meta key first time only.
 *
 * Usage:
 *   CLI:  sudo -u www-data php /var/www/html/ecec/_tmp/seed_royal_atlantis_blocks.php
 *   HTTP: http://207.180.196.39/ecec/_tmp/seed_royal_atlantis_blocks.php  (admin login required)
 */

require __DIR__ . '/../wp-load.php';

if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Must be logged in as admin. Aborting.' );
}

$pid = 114; // Royal Atlantis Resort
$post = get_post( $pid );
if ( ! $post || $post->post_type !== 'portfolio-item' ) {
	echo "ERROR: post 114 is not a portfolio-item (or doesn't exist). Aborting.\n";
	exit( 1 );
}

$featured_id = (int) get_post_thumbnail_id( $pid );
if ( ! $featured_id ) {
	echo "ERROR: post 114 has no featured image. Aborting.\n";
	exit( 1 );
}

echo "Found portfolio-item #{$pid} '" . $post->post_title . "'.\n";
echo "  Featured image id: {$featured_id}\n";

// One-time backup of pre-existing migrated blocks (so we can rollback)
$existing = get_post_meta( $pid, '_ecec_blocks', true );
$backup_existing = get_post_meta( $pid, '_ecec_blocks_backup_premultiblock', true );
if ( $existing && ! $backup_existing ) {
	update_post_meta( $pid, '_ecec_blocks_backup_premultiblock', $existing );
	echo "  Backed up pre-existing _ecec_blocks to _ecec_blocks_backup_premultiblock.\n";
}

$blocks = [
	[
		'type' => 'text-paragraph',
		'body' => $post->post_content,
	],
	[
		'type'       => 'image-text-split',
		'image_id'   => $featured_id,
		'overline'   => 'VISION & CONTEXT',
		'heading'    => 'A landmark on the Palm',
		'body'       => 'Set on the crescent of Palm Jumeirah, the Royal Atlantis demanded engineering that could match the ambition of its architecture. ECEC delivered LEED Construction services across a 43-storey hotel tower and over 200 luxury residences — coordinating sustainability, waste, and indoor air quality programmes alongside one of the most complex hospitality builds in the region.',
		'image_side' => 'left',
	],
	[
		'type'           => 'image-pair',
		'image_id_left'  => 0,
		'image_id_right' => 0,
		'caption_left'   => 'Tower silhouette over the Palm crescent.',
		'caption_right'  => 'Luxury residential wing facing the open sea.',
	],
	[
		'type'     => 'full-image',
		'image_id' => 0,
		'caption'  => 'Site context across the Palm Jumeirah crescent.',
	],
	[
		'type'     => 'project-data',
		'overline' => 'PROJECT DATA',
		'heading'  => 'At a glance',
		'rows'     => [
			[ 'label' => 'Client',      'value' => 'ICD' ],
			[ 'label' => 'Contractor',  'value' => 'Specon Group' ],
			[ 'label' => 'Location',    'value' => 'Palm Jumeirah, Dubai, UAE' ],
			[ 'label' => 'Scope',       'value' => 'LEED Construction · Sustainability Services' ],
			[ 'label' => 'Completion',  'value' => '2024' ],
		],
	],
	[
		'type'        => 'pull-quote',
		'quote'       => 'On a project of this scale, sustainability is not a layer added at the end — it is a discipline coordinated alongside every system, every supplier, every day on site.',
		'attribution' => 'Project Director, ECEC',
	],
	[
		'type'      => 'gallery',
		'image_ids' => [ 0, 0, 0 ],
		'columns'   => 3,
	],
];

update_post_meta( $pid, '_ecec_blocks', wp_slash( wp_json_encode( $blocks, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) );

echo "\nSaved " . count( $blocks ) . " block(s) to _ecec_blocks on post {$pid}.\n";
echo "View: " . get_permalink( $pid ) . "\n";
