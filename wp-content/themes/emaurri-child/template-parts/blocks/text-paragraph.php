<?php
/**
 * Block: text-paragraph
 * Fields: body (rich text)
 */
$block = isset( $args['block'] ) ? $args['block'] : [];
$body  = isset( $block['body'] ) ? $block['body'] : '';
if ( trim( wp_strip_all_tags( $body ) ) === '' ) { return; }
?>
<section class="ecec-block ecec-block-text-paragraph">
	<div class="ecec-block-text-paragraph__inner">
		<?php echo wp_kses_post( wpautop( $body ) ); ?>
	</div>
</section>
