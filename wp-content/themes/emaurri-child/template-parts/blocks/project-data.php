<?php
/**
 * Block: project-data (STUB — v2)
 * Fields: overline, heading, rows (array of [label, value])
 */
$block    = isset( $args['block'] ) ? $args['block'] : [];
$overline = isset( $block['overline'] ) ? $block['overline'] : 'PROJECT DATA';
$heading  = isset( $block['heading'] ) ? $block['heading'] : '';
$rows     = isset( $block['rows'] ) && is_array( $block['rows'] ) ? $block['rows'] : [];
if ( empty( $rows ) && $heading === '' ) { return; }
?>
<section class="ecec-block ecec-block-project-data">
	<?php if ( $overline !== '' ) : ?>
		<div class="ecec-block-project-data__overline"><?php echo esc_html( $overline ); ?></div>
	<?php endif; ?>
	<?php if ( $heading !== '' ) : ?>
		<h2 class="ecec-block-project-data__heading"><?php echo esc_html( $heading ); ?></h2>
	<?php endif; ?>
	<?php if ( ! empty( $rows ) ) : ?>
		<dl class="ecec-block-project-data__list">
			<?php foreach ( $rows as $row ) :
				$label = isset( $row['label'] ) ? $row['label'] : '';
				$value = isset( $row['value'] ) ? $row['value'] : '';
				if ( $label === '' && $value === '' ) { continue; }
				?>
				<div class="ecec-block-project-data__row">
					<dt class="ecec-block-project-data__label"><?php echo esc_html( $label ); ?></dt>
					<dd class="ecec-block-project-data__value"><?php echo esc_html( $value ); ?></dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>
</section>
