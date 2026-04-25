<?php
/**
 * Replace post 422's Elementor data with a single section containing the
 * [ecec_why] shortcode (which renders the proper sticky-scroll layout).
 * Idempotent.
 */
require __DIR__ . '/../wp-load.php';
if ( php_sapi_name() !== 'cli' && ! current_user_can( 'manage_options' ) ) { wp_die( 'admin only' ); }

$pid = 422;
function eid() { return wp_generate_password( 7, false, false ); }

// Use the same simple section/column shape that works on About Us — Elementor's
// frontend was failing to resolve the lazy widget token with layout=full_width.
$el_data = [
	[
		'id' => eid(), 'elType' => 'section',
		'settings' => [
			'layout'  => 'boxed',
			'padding' => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false ],
		],
		'elements' => [
			[
				'id' => eid(), 'elType' => 'column',
				'settings' => [ '_column_size' => 100, '_inline_size' => null ],
				'elements' => [
					[
						'id' => eid(), 'elType' => 'widget', 'widgetType' => 'shortcode',
						'settings' => [ 'shortcode' => '[ecec_why]' ],
					],
				],
			],
		],
	],
];

update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $el_data ) ) );
delete_post_meta( $pid, '_elementor_element_cache' );
delete_post_meta( $pid, '_elementor_css' );
echo "Replaced post {$pid} with single [ecec_why] shortcode.\n";
echo "View: " . get_permalink( $pid ) . "\n";
