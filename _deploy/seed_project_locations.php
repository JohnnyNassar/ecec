<?php
/**
 * Seed portfolio-location terms and assign one to each of the 54 portfolio items.
 * Idempotent: safe to re-run. Reassigns per the mapping every time.
 *
 * Source of mapping:
 *   - Live ecec.co evidence where I found it (Wyndham, Lime Box, Desert Rock, etc.)
 *   - Inference from client/project names for the rest
 *   - Explicit user decisions: Emaar Majestic Vista Villas -> KSA,
 *     Sixty Iconic / AUIB x2 -> Other (Egypt, Iraq)
 *
 * Run via:  php _deploy/seed_project_locations.php
 */

// Bootstrap WP
$root = dirname(__DIR__);
if (!defined('ABSPATH')) {
	$_SERVER['HTTP_HOST']    = $_SERVER['HTTP_HOST']    ?? 'localhost';
	$_SERVER['REQUEST_URI']  = $_SERVER['REQUEST_URI']  ?? '/ecec/';
	require $root . '/wp-load.php';
}

if (!taxonomy_exists('portfolio-location')) {
	fwrite(STDERR, "ERROR: taxonomy 'portfolio-location' is not registered. Make sure the child theme is active.\n");
	exit(1);
}

// 1. Seed terms (idempotent)
$terms = array(
	'saudi-arabia' => 'Saudi Arabia',
	'uae'          => 'UAE',
	'jordan'       => 'Jordan',
	'other'        => 'Other',
);
foreach ($terms as $slug => $name) {
	if (!term_exists($slug, 'portfolio-location')) {
		$res = wp_insert_term($name, 'portfolio-location', array('slug' => $slug));
		if (is_wp_error($res)) {
			fwrite(STDERR, "Failed to create term $name: " . $res->get_error_message() . "\n");
			continue;
		}
		echo "Created term: $name ($slug)\n";
	} else {
		echo "Term exists: $name ($slug)\n";
	}
}

// 2. Mapping: post_id => location slug
$mapping = array(
	// KSA (13)
	12  => 'saudi-arabia',  // Al Riyadh North Mall
	18  => 'saudi-arabia',  // Nugoosh Park
	54  => 'saudi-arabia',  // Opera Rehabilitation
	56  => 'saudi-arabia',  // Saudi French Hospital
	58  => 'saudi-arabia',  // AMAALA Beach Resort
	60  => 'saudi-arabia',  // AMAALA Wellness Core Resort
	74  => 'saudi-arabia',  // Wyndham Garden Hotel (confirmed via live page)
	92  => 'saudi-arabia',  // Downtown Al Ahsa
	100 => 'saudi-arabia',  // Emaar Majestic Vista Villas (user decision)
	104 => 'saudi-arabia',  // Multaka Residences
	108 => 'saudi-arabia',  // Desert Rock Resort
	112 => 'saudi-arabia',  // KAIA Terminal Complex
	118 => 'saudi-arabia',  // Hitteen Park

	// UAE (38)
	14  => 'uae',           // DIP Sports Hub
	16  => 'uae',           // Jafza Traders Market
	22  => 'uae',           // YAS Gardens
	24  => 'uae',           // Khazna DC DH03
	26  => 'uae',           // Al Ain Data Center
	28  => 'uae',           // Khazna DC DC-to-AC
	30  => 'uae',           // Amity School (default, reassign in admin if wrong)
	32  => 'uae',           // Artal School (default, reassign in admin if wrong)
	38  => 'uae',           // SEE Institute
	40  => 'uae',           // DEWA JAPS
	42  => 'uae',           // DEWA Technical Office
	44  => 'uae',           // DEWA Workshop & Offices
	46  => 'uae',           // DP World Expo Pavilion
	48  => 'uae',           // Dubai Police Station
	50  => 'uae',           // Two Irrigation Reservoirs
	52  => 'uae',           // Mediclinic Al Jowhara
	62  => 'uae',           // Avani Palm View Hotel
	64  => 'uae',           // Fairmont Hotel
	66  => 'uae',           // Indigo Hotel
	68  => 'uae',           // Lime Box Laguna (confirmed via live page)
	70  => 'uae',           // Onre Hotel
	72  => 'uae',           // Studio One Hotel
	76  => 'uae',           // Amazon DXB3 Hazmat
	78  => 'uae',           // Amazon Fulfillment Center
	80  => 'uae',           // Central Kitchen DIP
	82  => 'uae',           // Emirates Central Kitchen
	84  => 'uae',           // Komatsu ME Jafza
	86  => 'uae',           // Multi Tenant Warehouses Jafza
	88  => 'uae',           // Marine Research Phase-02
	90  => 'uae',           // W Motors Factory
	94  => 'uae',           // Al Garhoud Residential
	96  => 'uae',           // Al Ghaf Woods
	98  => 'uae',           // Al Raha Residential
	102 => 'uae',           // Mina Rashid - Sirdhana
	106 => 'uae',           // Regalia Residential Tower
	110 => 'uae',           // Expo DEWA Happiness Center
	114 => 'uae',           // Royal Atlantis Resort
	116 => 'uae',           // Gallery Mall

	// Other (3) — outside the KSA/UAE/Jordan scope
	20  => 'other',         // Sixty Iconic (Cairo, Egypt — confirmed via live page)
	34  => 'other',         // AUIB Model Schools (Baghdad, Iraq — confirmed via live page)
	36  => 'other',         // AUIB Lecture Halls (Baghdad, Iraq — confirmed via live page)
);

// 3. Apply mapping
$ok = 0; $skip = 0; $err = 0;
foreach ($mapping as $pid => $slug) {
	$p = get_post($pid);
	if (!$p || $p->post_type !== 'portfolio-item') {
		fwrite(STDERR, "  skip: post $pid is not a portfolio-item\n");
		$skip++; continue;
	}
	$res = wp_set_object_terms($pid, array($slug), 'portfolio-location', false);
	if (is_wp_error($res)) {
		fwrite(STDERR, "  ERR $pid ({$p->post_title}): " . $res->get_error_message() . "\n");
		$err++;
	} else {
		echo sprintf("  %-3d %-22s %s\n", $pid, $slug, $p->post_title);
		$ok++;
	}
}

// 4. Sanity: any portfolio-item WITHOUT a location?
$unmapped = get_posts(array(
	'post_type'      => 'portfolio-item',
	'posts_per_page' => -1,
	'post_status'    => 'publish',
	'tax_query'      => array(array(
		'taxonomy' => 'portfolio-location',
		'operator' => 'NOT EXISTS',
	)),
));
if ($unmapped) {
	echo "\nWARNING: " . count($unmapped) . " portfolio-item(s) have no location:\n";
	foreach ($unmapped as $u) echo "  $u->ID  $u->post_title\n";
}

echo "\nDone. assigned=$ok skipped=$skip errors=$err\n";
