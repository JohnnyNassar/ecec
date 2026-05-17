<?php
$_SERVER['HTTP_HOST']   = 'localhost';
$_SERVER['REQUEST_URI'] = '/ecec/';
require __DIR__ . '/../wp-load.php';

$raw = get_post_meta( 123, '_elementor_data', true );
$data = json_decode( $raw, true );
if ( ! is_array( $data ) ) { echo "decode fail\n"; exit; }

echo "Top-level sections: " . count( $data ) . "\n\n";

$summarize = function( $node, $depth = 0 ) use ( &$summarize ) {
	$indent = str_repeat( '  ', $depth );
	$id     = $node['id']         ?? '?';
	$type   = $node['elType']     ?? '?';
	$wt     = $node['widgetType'] ?? '';
	$cls    = $node['settings']['_element_id'] ?? ( $node['settings']['css_classes'] ?? '' );
	$short  = '';
	if ( $wt === 'heading' ) $short = ' "' . substr( ( $node['settings']['title'] ?? '' ), 0, 60 ) . '"';
	if ( $wt === 'shortcode' ) $short = ' [' . substr( ( $node['settings']['shortcode'] ?? '' ), 0, 80 ) . ']';
	if ( $wt === 'text-editor' ) $short = ' editor:' . substr( strip_tags( $node['settings']['editor'] ?? '' ), 0, 60 );
	echo $indent . "{$type}" . ( $wt ? "/{$wt}" : '' ) . " id={$id}" . ( $cls ? " cls={$cls}" : '' ) . "{$short}\n";
	if ( ! empty( $node['elements'] ) && is_array( $node['elements'] ) ) {
		foreach ( $node['elements'] as $c ) $summarize( $c, $depth + 1 );
	}
};
foreach ( $data as $sec ) $summarize( $sec );
