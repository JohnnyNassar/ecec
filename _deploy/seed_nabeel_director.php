<?php
/**
 * Seed Nabeel Abu Eideh as a Director-tier team member.
 *
 * Idempotent: if a published team-member post with that name already exists,
 * it's updated in place rather than duplicated. Bio + role + tier are always
 * (re-)written so the seed matches the mockup spec.
 *
 * The client can edit the bio / role / photo via WP Admin → Team Members
 * afterwards.
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$NAME = 'Nabeel Abu Eideh';
$ROLE = 'Technical Director';
$TIER = 'director';
$BIO  = "With over 20 years of experience in MEP design and project management, I have led engineering teams to deliver complex projects with a strong focus on efficiency and quality. My expertise includes project specifications, contract packages, HVAC, firefighting systems, medical gases, and drainage, along with solid site supervision experience. I also bring strong energy management knowledge in the Middle East, emphasizing sustainability and optimized performance.";

$existing = get_posts( array(
	'post_type'      => 'ecec_team_member',
	'post_status'    => array( 'publish', 'draft' ),
	'numberposts'    => 1,
	'title'          => $NAME,
) );

if ( $existing ) {
	$pid = (int) $existing[0]->ID;
	echo "Found existing team member #{$pid} '{$NAME}' — updating meta.\n";
} else {
	$pid = wp_insert_post( array(
		'post_type'   => 'ecec_team_member',
		'post_title'  => $NAME,
		'post_status' => 'publish',
		'menu_order'  => 2,
	) );
	if ( is_wp_error( $pid ) ) { echo "Insert failed: " . $pid->get_error_message() . "\n"; exit( 1 ); }
	echo "Created team member #{$pid} '{$NAME}'.\n";
}

update_post_meta( $pid, '_ecec_team_role', $ROLE );
update_post_meta( $pid, '_ecec_team_tier', $TIER );
update_post_meta( $pid, '_ecec_team_bio',  $BIO );
// Director tier ignores department, but clear any stale value just in case.
update_post_meta( $pid, '_ecec_team_department', '' );

echo "Set role={$ROLE}, tier={$TIER}, bio (" . strlen( $BIO ) . " chars).\n";
echo "Done. Upload a photo via WP Admin → Team Members → Nabeel Abu Eideh.\n";
