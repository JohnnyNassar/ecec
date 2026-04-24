<?php
/**
 * Single portfolio-item template (ECEC block system).
 *
 * Reads `_ecec_blocks` post meta (JSON array) and renders each entry via
 * template-parts/blocks/<type>.php. Falls back to the legacy description +
 * qodef_portfolio_media render when no blocks are set.
 */

get_header();

$post_id = get_the_ID();
$blocks_raw = get_post_meta( $post_id, '_ecec_blocks', true );
$blocks = [];
if ( ! empty( $blocks_raw ) ) {
	$decoded = is_array( $blocks_raw ) ? $blocks_raw : json_decode( $blocks_raw, true );
	if ( is_array( $decoded ) ) {
		$blocks = $decoded;
	}
}

$info_items = get_post_meta( $post_id, 'qodef_portfolio_info_items', true );
$info_items = is_array( $info_items ) ? $info_items : [];

$featured_id = get_post_thumbnail_id( $post_id );
$categories  = get_the_terms( $post_id, 'portfolio-category' );
$locations   = get_the_terms( $post_id, 'portfolio-location' );
?>

<main id="qodef-page-content" class="ecec-single-project">

	<?php // ===== Hero ===== ?>
	<section class="ecec-sp-hero<?php echo $featured_id ? ' ecec-sp-hero--has-image' : ''; ?>">
		<?php if ( $featured_id ) : ?>
			<div class="ecec-sp-hero__image"><?php echo wp_get_attachment_image( $featured_id, 'full', false, [ 'class' => 'ecec-sp-hero__img' ] ); ?></div>
			<div class="ecec-sp-hero__overlay" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="ecec-sp-hero__inner">
			<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
				<div class="ecec-sp-hero__overline">
					<?php echo esc_html( implode( ' · ', wp_list_pluck( $categories, 'name' ) ) ); ?>
				</div>
			<?php endif; ?>
			<h1 class="ecec-sp-hero__title"><?php the_title(); ?></h1>
			<?php if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) : ?>
				<div class="ecec-sp-hero__location">
					<?php echo esc_html( implode( ', ', wp_list_pluck( $locations, 'name' ) ) ); ?>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<?php // ===== Body: blocks + info sidebar ===== ?>
	<div class="ecec-sp-body">
		<div class="ecec-sp-body__blocks">
			<?php if ( ! empty( $blocks ) ) : ?>
				<?php foreach ( $blocks as $index => $block ) :
					$type = isset( $block['type'] ) ? sanitize_key( $block['type'] ) : '';
					if ( ! $type ) { continue; }
					get_template_part(
						'template-parts/blocks/' . $type,
						null,
						[ 'block' => $block, 'index' => $index, 'post_id' => $post_id ]
					);
				endforeach; ?>
			<?php else : ?>
				<?php // Legacy fallback: post_content + qodef_portfolio_media ?>
				<?php if ( get_the_content() ) : ?>
					<div class="ecec-block ecec-block-text-paragraph">
						<div class="ecec-block-text-paragraph__inner">
							<?php the_content(); ?>
						</div>
					</div>
				<?php endif; ?>
				<?php
				$media = get_post_meta( $post_id, 'qodef_portfolio_media', true );
				if ( is_array( $media ) && ! empty( $media ) ) :
					?>
					<div class="ecec-block ecec-block-gallery ecec-block-gallery--legacy">
						<div class="ecec-block-gallery__grid">
							<?php foreach ( $media as $row ) :
								$img_id = isset( $row['qodef_portfolio_media_image']['id'] ) ? (int) $row['qodef_portfolio_media_image']['id'] : 0;
								if ( ! $img_id ) { continue; }
								?>
								<figure class="ecec-block-gallery__item">
									<?php echo wp_get_attachment_image( $img_id, 'large' ); ?>
								</figure>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $info_items ) ) : ?>
			<aside class="ecec-sp-body__info" aria-label="<?php esc_attr_e( 'Project information', 'emaurri-child' ); ?>">
				<div class="ecec-sp-info">
					<div class="ecec-sp-info__overline"><?php esc_html_e( 'Project Info', 'emaurri-child' ); ?></div>
					<?php foreach ( $info_items as $item ) :
						$label  = isset( $item['qodef_info_item_label'] ) ? $item['qodef_info_item_label'] : '';
						$value  = isset( $item['qodef_info_item_value'] ) ? $item['qodef_info_item_value'] : '';
						$link   = isset( $item['qodef_info_item_link'] ) ? $item['qodef_info_item_link'] : '';
						$target = ! empty( $item['qodef_info_item_target'] ) ? $item['qodef_info_item_target'] : '_blank';
						if ( $value === '' && $link === '' ) { continue; }
						?>
						<div class="ecec-sp-info__row">
							<?php if ( $label !== '' ) : ?>
								<div class="ecec-sp-info__label"><?php echo esc_html( $label ); ?></div>
							<?php endif; ?>
							<div class="ecec-sp-info__value">
								<?php if ( $link !== '' ) : ?>
									<a href="<?php echo esc_url( $link ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="noopener"><?php echo wp_kses_post( $value ); ?></a>
								<?php else : ?>
									<?php echo wp_kses_post( $value ); ?>
								<?php endif; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</aside>
		<?php endif; ?>
	</div>

	<?php // ===== Back-to-projects ===== ?>
	<div class="ecec-sp-footer-nav">
		<a href="<?php echo esc_url( home_url( '/projects/' ) ); ?>" class="ecec-sp-back">&larr; <?php esc_html_e( 'Back to all projects', 'emaurri-child' ); ?></a>
	</div>

</main>

<?php
get_footer();
