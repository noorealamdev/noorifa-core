<?php
/**
 * Product Gallery Carousel block server render.
 *
 * Renders the current product's own images (featured + gallery) as a
 * self-contained carousel, instead of wrapping WooCommerce's own
 * flexslider-based gallery output.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

global $product;
$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $product is WooCommerce's own global.

if ( ! $product ) {
	return;
}

$noorifa_core_image_ids = array();
$noorifa_core_featured  = $product->get_image_id();

if ( $noorifa_core_featured ) {
	$noorifa_core_image_ids[] = (int) $noorifa_core_featured;
}

foreach ( $product->get_gallery_image_ids() as $noorifa_core_gallery_id ) {
	$noorifa_core_image_ids[] = (int) $noorifa_core_gallery_id;
}

$noorifa_core_image_ids = array_values( array_unique( array_filter( $noorifa_core_image_ids ) ) );

$noorifa_core_show_thumbs = ! isset( $attributes['showThumbnails'] ) || (bool) $attributes['showThumbnails'];
$noorifa_core_multiple    = count( $noorifa_core_image_ids ) > 1;
$noorifa_core_thumb_size  = wc_get_image_size( 'gallery_thumbnail' );
$noorifa_core_wrapper     = get_block_wrapper_attributes();
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="noorifa-core-product-gallery-carousel">
		<div class="noorifa-core-product-gallery-carousel__stage">
			<div class="swiper noorifa-core-product-gallery-carousel__viewport">
				<div class="swiper-wrapper noorifa-core-product-gallery-carousel__track">
					<?php if ( empty( $noorifa_core_image_ids ) ) : ?>
						<div class="swiper-slide noorifa-core-product-gallery-carousel__slide">
							<div class="noorifa-core-product-gallery-carousel__image-wrap">
								<?php echo wc_placeholder_img( 'woocommerce_single' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
							</div>
						</div>
					<?php else : ?>
						<?php foreach ( $noorifa_core_image_ids as $noorifa_core_id ) : ?>
							<div class="swiper-slide noorifa-core-product-gallery-carousel__slide" data-image-id="<?php echo esc_attr( $noorifa_core_id ); ?>">
								<div class="noorifa-core-product-gallery-carousel__image-wrap">
									<?php
									echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
										$noorifa_core_id,
										'woocommerce_single',
										false,
										array(
											'class'     => 'noorifa-core-product-gallery-carousel__image',
											// Otherwise the browser's native "drag this image out"
											// gesture competes with (and can override) our own
											// pointer-based carousel drag.
											'draggable' => 'false',
										)
									);
									?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( $noorifa_core_multiple ) : ?>
				<button type="button" class="noorifa-core-product-gallery-carousel__arrow noorifa-core-product-gallery-carousel__arrow--prev" aria-label="<?php echo esc_attr__( 'Previous image', 'noorifa-core' ); ?>">
					<span aria-hidden="true">&#10094;</span>
				</button>
				<button type="button" class="noorifa-core-product-gallery-carousel__arrow noorifa-core-product-gallery-carousel__arrow--next" aria-label="<?php echo esc_attr__( 'Next image', 'noorifa-core' ); ?>">
					<span aria-hidden="true">&#10095;</span>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $noorifa_core_multiple && $noorifa_core_show_thumbs ) : ?>
			<div class="swiper noorifa-core-product-gallery-carousel__thumbs">
				<div class="swiper-wrapper">
					<?php foreach ( $noorifa_core_image_ids as $noorifa_core_index => $noorifa_core_id ) : ?>
						<?php
						/* translators: %d: image number in the gallery. */
						$noorifa_core_thumb_label = sprintf( __( 'Show image %d', 'noorifa-core' ), $noorifa_core_index + 1 );
						?>
						<div class="swiper-slide noorifa-core-product-gallery-carousel__thumb-slide">
							<button
								type="button"
								class="noorifa-core-product-gallery-carousel__thumb<?php echo 0 === $noorifa_core_index ? ' is-active' : ''; ?>"
								data-slide-index="<?php echo esc_attr( $noorifa_core_index ); ?>"
								aria-label="<?php echo esc_attr( $noorifa_core_thumb_label ); ?>"
							>
								<?php
								echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
									$noorifa_core_id,
									array( $noorifa_core_thumb_size['width'], $noorifa_core_thumb_size['height'] ),
									false,
									array( 'class' => 'noorifa-core-product-gallery-carousel__thumb-image' )
								);
								?>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php elseif ( $noorifa_core_multiple ) : ?>
			<div class="noorifa-core-product-gallery-carousel__dots">
				<?php foreach ( $noorifa_core_image_ids as $noorifa_core_index => $noorifa_core_id ) : ?>
					<?php
					/* translators: %d: image number in the gallery. */
					$noorifa_core_dot_label = sprintf( __( 'Show image %d', 'noorifa-core' ), $noorifa_core_index + 1 );
					?>
					<button
						type="button"
						class="noorifa-core-product-gallery-carousel__dot<?php echo 0 === $noorifa_core_index ? ' is-active' : ''; ?>"
						data-slide-index="<?php echo esc_attr( $noorifa_core_index ); ?>"
						aria-label="<?php echo esc_attr( $noorifa_core_dot_label ); ?>"
					></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
