<?php
/**
 * Block: image-pair
 * Two side-by-side images, optional captions each.
 * Fields: image_id_left, image_id_right, caption_left (opt), caption_right (opt)
 */
$block        = isset( $args['block'] ) ? $args['block'] : [];
$left_id      = isset( $block['image_id_left'] )  ? (int) $block['image_id_left']  : 0;
$right_id     = isset( $block['image_id_right'] ) ? (int) $block['image_id_right'] : 0;
$left_cap     = isset( $block['caption_left'] )   ? $block['caption_left']  : '';
$right_cap    = isset( $block['caption_right'] )  ? $block['caption_right'] : '';
?>
<section class="ecec-block ecec-block-image-pair">
	<div class="ecec-block-image-pair__grid">
		<figure class="ecec-block-image-pair__cell">
			<?php if ( $left_id ) : ?>
				<?php echo wp_get_attachment_image( $left_id, 'large', false, [ 'class' => 'ecec-block-image-pair__img' ] ); ?>
			<?php else : ?>
				<div class="ecec-block-placeholder ecec-block-placeholder--pair">
					<p class="ecec-block-placeholder__size">600 &times; 450</p>
					<p class="ecec-block-placeholder__hint">Image placeholder</p>
				</div>
			<?php endif; ?>
			<?php if ( $left_cap !== '' ) : ?>
				<figcaption class="ecec-block-image-pair__caption"><?php echo esc_html( $left_cap ); ?></figcaption>
			<?php endif; ?>
		</figure>
		<figure class="ecec-block-image-pair__cell">
			<?php if ( $right_id ) : ?>
				<?php echo wp_get_attachment_image( $right_id, 'large', false, [ 'class' => 'ecec-block-image-pair__img' ] ); ?>
			<?php else : ?>
				<div class="ecec-block-placeholder ecec-block-placeholder--pair">
					<p class="ecec-block-placeholder__size">600 &times; 450</p>
					<p class="ecec-block-placeholder__hint">Image placeholder</p>
				</div>
			<?php endif; ?>
			<?php if ( $right_cap !== '' ) : ?>
				<figcaption class="ecec-block-image-pair__caption"><?php echo esc_html( $right_cap ); ?></figcaption>
			<?php endif; ?>
		</figure>
	</div>
</section>
