<?php
/**
 * Block: full-image
 * Fields: image_id, caption (optional)
 */
$block    = isset( $args['block'] ) ? $args['block'] : [];
$image_id = isset( $block['image_id'] ) ? (int) $block['image_id'] : 0;
$caption  = isset( $block['caption'] ) ? $block['caption'] : '';
if ( ! $image_id ) { return; }
?>
<figure class="ecec-block ecec-block-full-image">
	<?php echo wp_get_attachment_image( $image_id, 'full', false, [ 'class' => 'ecec-block-full-image__img' ] ); ?>
	<?php if ( $caption !== '' ) : ?>
		<figcaption class="ecec-block-full-image__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
</figure>
