<?php
/**
 * Block: image-text-split
 * Fields: image_id, overline (small caps), heading, body, image_side ('left'|'right')
 */
$block      = isset( $args['block'] ) ? $args['block'] : [];
$image_id   = isset( $block['image_id'] ) ? (int) $block['image_id'] : 0;
$overline   = isset( $block['overline'] ) ? $block['overline'] : '';
$heading    = isset( $block['heading'] ) ? $block['heading'] : '';
$body       = isset( $block['body'] ) ? $block['body'] : '';
$image_side = isset( $block['image_side'] ) && $block['image_side'] === 'right' ? 'right' : 'left';

if ( ! $image_id && trim( wp_strip_all_tags( $heading . $body ) ) === '' ) { return; }
?>
<section class="ecec-block ecec-block-split ecec-block-split--image-<?php echo esc_attr( $image_side ); ?>">
	<div class="ecec-block-split__grid">
		<div class="ecec-block-split__media">
			<?php if ( $image_id ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'large', false, [ 'class' => 'ecec-block-split__img' ] ); ?>
			<?php endif; ?>
		</div>
		<div class="ecec-block-split__text">
			<?php if ( $overline !== '' ) : ?>
				<div class="ecec-block-split__overline"><?php echo esc_html( $overline ); ?></div>
			<?php endif; ?>
			<?php if ( $heading !== '' ) : ?>
				<h2 class="ecec-block-split__heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( $body !== '' ) : ?>
				<div class="ecec-block-split__body"><?php echo wp_kses_post( wpautop( $body ) ); ?></div>
			<?php endif; ?>
		</div>
	</div>
</section>
