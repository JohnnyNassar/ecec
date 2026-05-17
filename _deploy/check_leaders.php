<?php
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$q = new WP_Query( array(
	'post_type'      => 'ecec_team_member',
	'post_status'    => array( 'publish', 'draft' ),
	'posts_per_page' => -1,
	'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
) );

foreach ( $q->posts as $p ) {
	$tier  = get_post_meta( $p->ID, '_ecec_team_tier', true );
	$role  = get_post_meta( $p->ID, '_ecec_team_role', true );
	$dept  = get_post_meta( $p->ID, '_ecec_team_department', true );
	$bio   = get_post_meta( $p->ID, '_ecec_team_bio', true );
	$has_img = has_post_thumbnail( $p->ID ) ? 'IMG' : '---';
	printf(
		"#%-3d %-8s %-10s %-20s tier=%-10s dept=%-22s bio=%-3s '%s'\n",
		$p->ID, $p->post_status, $p->menu_order, $has_img, $tier ?: '(none)', $dept ?: '(none)', $bio ? 'YES' : 'no', $p->post_title
	);
}
