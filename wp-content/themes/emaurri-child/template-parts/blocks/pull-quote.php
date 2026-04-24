<?php
/**
 * Block: pull-quote (STUB — v2)
 * Fields: quote, attribution (optional)
 */
$block       = isset( $args['block'] ) ? $args['block'] : [];
$quote       = isset( $block['quote'] ) ? $block['quote'] : '';
$attribution = isset( $block['attribution'] ) ? $block['attribution'] : '';
if ( trim( wp_strip_all_tags( $quote ) ) === '' ) { return; }
?>
<blockquote class="ecec-block ecec-block-pull-quote">
	<p class="ecec-block-pull-quote__text"><?php echo wp_kses_post( $quote ); ?></p>
	<?php if ( $attribution !== '' ) : ?>
		<cite class="ecec-block-pull-quote__attribution"><?php echo esc_html( $attribution ); ?></cite>
	<?php endif; ?>
</blockquote>
