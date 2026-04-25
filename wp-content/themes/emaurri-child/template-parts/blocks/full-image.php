<?php
/**
 * Block: full-image
 * Fields: image_id, caption (optional)
 */
$block    = isset( $args['block'] ) ? $args['block'] : [];
$image_id = isset( $block['image_id'] ) ? (int) $block['image_id'] : 0;
$caption  = isset( $block['caption'] ) ? $block['caption'] : '';
?>
<figure class="ecec-block ecec-block-full-image">
	<?php if ( $image_id ) : ?>
		<?php echo wp_get_attachment_image( $image_id, 'full', false, [ 'class' => 'ecec-block-full-image__img' ] ); ?>
	<?php else : ?>
		<div class="ecec-block-placeholder ecec-block-placeholder--full-image">
			<p class="ecec-block-placeholder__size">1200 &times; 400</p>
			<p class="ecec-block-placeholder__hint">Image placeholder &mdash; replace via admin</p>
		</div>
	<?php endif; ?>
	<?php if ( $caption !== '' ) : ?>
		<figcaption class="ecec-block-full-image__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
</figure>
