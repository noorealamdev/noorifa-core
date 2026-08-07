<?php
/**
 * Review Carousel block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$noorifa_core_product_id = absint( $attributes['productId'] );
$noorifa_core_selected   = array_map( 'absint', (array) $attributes['selectedReviews'] );

if ( ! $noorifa_core_product_id || empty( $noorifa_core_selected ) ) {
	return;
}

$noorifa_core_product = wc_get_product( $noorifa_core_product_id );

if ( ! $noorifa_core_product ) {
	return;
}

$noorifa_core_comments = get_comments(
	array(
		'post_id'     => $noorifa_core_product_id,
		'status'      => 'approve',
		'comment__in' => $noorifa_core_selected,
	)
);

// Reorders results to match the curated selection order (get_comments()
// doesn't preserve comment__in order), dropping any picked review that's
// since been unapproved or deleted.
$noorifa_core_comments_by_id = array();

foreach ( $noorifa_core_comments as $noorifa_core_comment ) {
	$noorifa_core_comments_by_id[ (int) $noorifa_core_comment->comment_ID ] = $noorifa_core_comment;
}

$noorifa_core_ordered = array();

foreach ( $noorifa_core_selected as $noorifa_core_id ) {
	if ( isset( $noorifa_core_comments_by_id[ $noorifa_core_id ] ) ) {
		$noorifa_core_ordered[] = $noorifa_core_comments_by_id[ $noorifa_core_id ];
	}
}

if ( empty( $noorifa_core_ordered ) ) {
	return;
}

$noorifa_core_ratings_enabled = wc_review_ratings_enabled();
$noorifa_core_multiple        = count( $noorifa_core_ordered ) > 1;

// The "Card background" control tints the review cards via a CSS variable
// the styles read (see style.scss) — kept as a variable rather than a
// wrapper background so the color lands on the cards, not behind them.
$noorifa_core_wrapper_args = array();
if ( ! empty( $attributes['cardBackground'] ) ) {
	$noorifa_core_wrapper_args['style'] = '--noorifa-review-card-bg:' . esc_attr( $attributes['cardBackground'] ) . ';';
}
$noorifa_core_wrapper = get_block_wrapper_attributes( $noorifa_core_wrapper_args );

// Self-contained max-width instead of relying on the theme/template to
// constrain block content — this block can land in templates that render
// blocks without any width-limiting ancestor.
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div
		class="noorifa-core-review-carousel<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<div class="noorifa-core-review-carousel__stage">
			<div class="noorifa-core-review-carousel__viewport">
				<div class="noorifa-core-review-carousel__track">
					<?php foreach ( $noorifa_core_ordered as $noorifa_core_comment ) : ?>
						<?php
						$noorifa_core_rating   = (float) get_comment_meta( $noorifa_core_comment->comment_ID, 'rating', true );
						$noorifa_core_verified = wc_review_is_from_verified_owner( $noorifa_core_comment->comment_ID );
						?>
						<div class="noorifa-core-review-carousel__slide">
							<div class="noorifa-core-review-carousel__card">
								<?php if ( $noorifa_core_ratings_enabled ) : ?>
									<?php echo wc_get_rating_html( $noorifa_core_rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
								<?php endif; ?>
								<p class="noorifa-core-review-carousel__content">
									<?php echo wp_kses_post( get_comment_text( $noorifa_core_comment ) ); ?>
								</p>
								<div class="noorifa-core-review-carousel__footer">
									<?php if ( $noorifa_core_verified ) : ?>
										<span class="noorifa-core-review-carousel__verified">
											<?php esc_html_e( 'Verified Purchase', 'noorifa-core' ); ?>
										</span>
									<?php endif; ?>
									<span class="noorifa-core-review-carousel__name">
										&#8211;<?php echo esc_html( $noorifa_core_comment->comment_author ); ?>
									</span>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( $noorifa_core_multiple ) : ?>
				<button type="button" class="noorifa-core-review-carousel__arrow noorifa-core-review-carousel__arrow--prev" aria-label="<?php echo esc_attr__( 'Previous review', 'noorifa-core' ); ?>">
					<span aria-hidden="true">&#10094;</span>
				</button>
				<button type="button" class="noorifa-core-review-carousel__arrow noorifa-core-review-carousel__arrow--next" aria-label="<?php echo esc_attr__( 'Next review', 'noorifa-core' ); ?>">
					<span aria-hidden="true">&#10095;</span>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $noorifa_core_multiple ) : ?>
			<div class="noorifa-core-review-carousel__dots">
				<?php foreach ( $noorifa_core_ordered as $noorifa_core_index => $noorifa_core_comment ) : ?>
					<?php
					/* translators: %d: review number in the carousel. */
					$noorifa_core_dot_label = sprintf( __( 'Go to review %d', 'noorifa-core' ), $noorifa_core_index + 1 );
					?>
					<button
						type="button"
						class="noorifa-core-review-carousel__dot<?php echo 0 === $noorifa_core_index ? ' is-active' : ''; ?>"
						data-slide-index="<?php echo esc_attr( $noorifa_core_index ); ?>"
						aria-label="<?php echo esc_attr( $noorifa_core_dot_label ); ?>"
					></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
