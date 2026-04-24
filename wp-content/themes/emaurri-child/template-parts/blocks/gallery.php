<?php
/**
 * Block: gallery (STUB — v2)
 * Fields: image_ids (array of attachment IDs), columns (2 or 3, default 3)
 */
$block     = isset( $args['block'] ) ? $args['block'] : [];
$image_ids = isset( $block['image_ids'] ) && is_array( $block['image_ids'] ) ? array_map( 'intval', $block['image_ids'] ) : [];
$columns   = isset( $block['columns'] ) && (int) $block['columns'] === 2 ? 2 : 3;
if ( empty( $image_ids ) ) { return; }
?>
<section class="ecec-block ecec-block-gallery ecec-block-gallery--cols-<?php echo esc_attr( $columns ); ?>">
	<div class="ecec-block-gallery__grid">
		<?php foreach ( $image_ids as $img_id ) :
			if ( ! $img_id ) { continue; }
			?>
			<figure class="ecec-block-gallery__item">
				<?php echo wp_get_attachment_image( $img_id, 'large', false, [ 'class' => 'ecec-block-gallery__img' ] ); ?>
			</figure>
		<?php endforeach; ?>
	</div>
</section>
