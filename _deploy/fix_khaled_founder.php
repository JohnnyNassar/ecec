<?php
/**
 * Set Khaled Al Assi as Founder tier (with bio if empty). Idempotent.
 *
 * Khaled's CPT record exists (post #166) but his tier was never set to
 * `founder`, so [ecec_team_leadership] excluded him. This script:
 *   - locates the Khaled record by title
 *   - forces _ecec_team_tier=founder
 *   - sets _ecec_team_role=Founder | Principal (if empty)
 *   - sets _ecec_team_bio to the mockup narrative (only if empty — never overwrites client edits)
 */

$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$BIO = "Khaled Al Assi leads ECEC across its Dubai, Riyadh, and Amman offices. He brings a multidisciplinary engineering perspective to every engagement \u{2014} combining MEP, structural, ICT, and sustainability expertise with a focus on operational performance and long-term value for clients. Under his direction the firm has delivered work across the GCC for hospitality, healthcare, aviation, residential, and data center clients.";

$rows = get_posts( array(
	'post_type'   => 'ecec_team_member',
	'post_status' => array( 'publish', 'draft' ),
	'numberposts' => 1,
	's'           => 'Khaled Al Assi',
) );
if ( ! $rows ) { echo "No Khaled record found.\n"; exit( 1 ); }
$pid = (int) $rows[0]->ID;
echo "Khaled record: #{$pid}\n";

update_post_meta( $pid, '_ecec_team_tier', 'founder' );
echo "Set tier=founder\n";

$role = get_post_meta( $pid, '_ecec_team_role', true );
if ( ! $role ) {
	update_post_meta( $pid, '_ecec_team_role', 'Founder | Principal' );
	echo "Set role='Founder | Principal' (was empty)\n";
} else {
	echo "Role already set: '{$role}' (kept)\n";
}

$existing_bio = get_post_meta( $pid, '_ecec_team_bio', true );
if ( ! $existing_bio ) {
	update_post_meta( $pid, '_ecec_team_bio', $BIO );
	echo "Set bio (" . strlen( $BIO ) . " chars, was empty)\n";
} else {
	echo "Bio already set (" . strlen( $existing_bio ) . " chars, kept)\n";
}

// menu_order=1 ensures he renders before Nabeel
wp_update_post( array( 'ID' => $pid, 'menu_order' => 1 ) );
echo "Set menu_order=1\n";
echo "\nDone.\n";
