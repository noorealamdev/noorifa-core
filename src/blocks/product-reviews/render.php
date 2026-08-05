<?php
/**
 * Product Reviews block server render.
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

$noorifa_core_ratings_enabled = wc_review_ratings_enabled();
$noorifa_core_average         = (float) $product->get_average_rating();
$noorifa_core_rating_counts   = $product->get_rating_counts();

/*
 * get_review_count() counts every approved comment on the product, which
 * can be higher than the number that actually carry a rating (e.g. legacy
 * comments imported without one) — using it as the percentage denominator
 * would make the breakdown bars never add up to 100%. The sum of
 * get_rating_counts() is the real total of *rated* reviews, so every
 * number shown (count, bars, percentages) stays internally consistent.
 */
$noorifa_core_review_count = array_sum( $noorifa_core_rating_counts );

$noorifa_core_breakdown = array();

for ( $noorifa_core_star = 5; $noorifa_core_star >= 1; $noorifa_core_star-- ) {
	$noorifa_core_star_count                       = isset( $noorifa_core_rating_counts[ $noorifa_core_star ] ) ? (int) $noorifa_core_rating_counts[ $noorifa_core_star ] : 0;
	$noorifa_core_breakdown[ $noorifa_core_star ] = array(
		'count'      => $noorifa_core_star_count,
		'percentage' => $noorifa_core_review_count ? round( ( $noorifa_core_star_count / $noorifa_core_review_count ) * 100 ) : 0,
	);
}

$noorifa_core_per_page = max( 1, absint( $attributes['reviewsToShow'] ) );

// Fetches one extra review to detect whether a "Load more" button is
// needed, without a second query.
$noorifa_core_reviews  = \Noorifa\Core\Blocks\Product_Reviews_Renderer::get_reviews( $product->get_id(), 0, $noorifa_core_per_page + 1 );
$noorifa_core_has_more = count( $noorifa_core_reviews ) > $noorifa_core_per_page;
$noorifa_core_reviews  = array_slice( $noorifa_core_reviews, 0, $noorifa_core_per_page );

$noorifa_core_wrapper = get_block_wrapper_attributes();

// Self-contained max-width instead of relying on the theme/template to
// constrain block content — this block can land in templates that render
// blocks without any width-limiting ancestor. The modal further below sits
// outside this wrapper since it's position:fixed and already ignores
// ancestor width.
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>

	<div
		class="noorifa-core-product-reviews__inner<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<div class="noorifa-core-product-reviews__summary">
			<div class="noorifa-core-product-reviews__summary-main">
				<?php if ( $noorifa_core_ratings_enabled ) : ?>
					<div class="noorifa-core-product-reviews__average"><?php echo esc_html( number_format_i18n( $noorifa_core_average, 1 ) ); ?></div>
					<?php echo wc_get_rating_html( $noorifa_core_average, $noorifa_core_review_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
				<?php endif; ?>
				<p class="noorifa-core-product-reviews__count">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: number of reviews. */
							_n( 'Based on %s review', 'Based on %s reviews', $noorifa_core_review_count, 'noorifa-core' ),
							number_format_i18n( $noorifa_core_review_count )
						)
					);
					?>
				</p>
				<?php if ( comments_open() ) : ?>
					<button
						type="button"
						class="noorifa-core-product-reviews__write-link"
						data-review-modal-target="noorifa-core-review-modal-<?php echo esc_attr( $product->get_id() ); ?>"
					>
						<?php esc_html_e( 'Write a Review', 'noorifa-core' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<?php if ( $noorifa_core_ratings_enabled && $noorifa_core_review_count ) : ?>
				<div class="noorifa-core-product-reviews__breakdown">
					<?php foreach ( $noorifa_core_breakdown as $noorifa_core_star => $noorifa_core_row ) : ?>
						<div class="noorifa-core-product-reviews__breakdown-row">
							<span class="noorifa-core-product-reviews__breakdown-label">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %d: star rating, 1-5. */
										_n( '%d star', '%d stars', $noorifa_core_star, 'noorifa-core' ),
										$noorifa_core_star
									)
								);
								?>
							</span>
							<span class="noorifa-core-product-reviews__breakdown-track">
								<span class="noorifa-core-product-reviews__breakdown-fill" style="width:<?php echo esc_attr( $noorifa_core_row['percentage'] ); ?>%"></span>
							</span>
							<span class="noorifa-core-product-reviews__breakdown-percentage"><?php echo esc_html( $noorifa_core_row['percentage'] ); ?>%</span>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $noorifa_core_reviews ) ) : ?>
			<div class="noorifa-core-product-reviews__grid columns-<?php echo esc_attr( absint( $attributes['columns'] ) ); ?>">
				<?php foreach ( $noorifa_core_reviews as $noorifa_core_review ) : ?>
					<?php
					// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- render_card() escapes its own output internally.
					echo \Noorifa\Core\Blocks\Product_Reviews_Renderer::render_card(
						$noorifa_core_review['comment'],
						$noorifa_core_review['rating'],
						$noorifa_core_ratings_enabled
					);
					// phpcs:enable
					?>
				<?php endforeach; ?>
			</div>
			<?php if ( $noorifa_core_has_more ) : ?>
				<div class="noorifa-core-product-reviews__load-more-wrap">
					<button
						type="button"
						class="noorifa-core-product-reviews__load-more"
						data-rest-url="<?php echo esc_url( rest_url( 'noorifa-core/v1/product-reviews/' . $product->get_id() ) ); ?>"
						data-offset="<?php echo esc_attr( $noorifa_core_per_page ); ?>"
						data-per-page="<?php echo esc_attr( $noorifa_core_per_page ); ?>"
						data-loading-text="<?php echo esc_attr__( 'Loading…', 'noorifa-core' ); ?>"
					>
						<?php esc_html_e( 'Load more reviews', 'noorifa-core' ); ?>
					</button>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p class="noorifa-core-product-reviews__empty"><?php esc_html_e( 'There are no reviews yet.', 'noorifa-core' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( comments_open() ) : ?>
		<div
			id="noorifa-core-review-modal-<?php echo esc_attr( $product->get_id() ); ?>"
			class="noorifa-core-product-reviews__modal"
			hidden
			aria-hidden="true"
		>
			<div class="noorifa-core-product-reviews__modal-backdrop" data-review-modal-close></div>
			<div class="noorifa-core-product-reviews__modal-panel" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__( 'Write a review', 'noorifa-core' ); ?>">
				<button type="button" class="noorifa-core-product-reviews__modal-close" data-review-modal-close aria-label="<?php echo esc_attr__( 'Close', 'noorifa-core' ); ?>">
					&times;
				</button>
			<?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>
				<div id="review_form_wrapper">
					<div id="review_form">
						<?php
						$noorifa_core_commenter    = wp_get_current_commenter();
						$noorifa_core_comment_form = array(
							/* translators: %s is product title */
							'title_reply'         => $noorifa_core_review_count ? esc_html__( 'Add a review', 'noorifa-core' ) : sprintf( esc_html__( 'Be the first to review "%s"', 'noorifa-core' ), esc_html( get_the_title() ) ),
							/* translators: %s is product title */
							'title_reply_to'      => esc_html__( 'Leave a Reply to %s', 'noorifa-core' ),
							'title_reply_before'  => '<span id="reply-title" class="comment-reply-title" role="heading" aria-level="3">',
							'title_reply_after'   => '</span>',
							'comment_notes_after' => '',
							'label_submit'        => esc_html__( 'Submit', 'noorifa-core' ),
							'logged_in_as'        => '',
							'comment_field'       => '',
						);

						$noorifa_core_name_email_required = (bool) get_option( 'require_name_email', 1 );
						$noorifa_core_fields              = array(
							'author' => array(
								'label'        => __( 'Name', 'noorifa-core' ),
								'type'         => 'text',
								'value'        => $noorifa_core_commenter['comment_author'],
								'required'     => $noorifa_core_name_email_required,
								'autocomplete' => 'name',
							),
							'email'  => array(
								'label'        => __( 'Email', 'noorifa-core' ),
								'type'         => 'email',
								'value'        => $noorifa_core_commenter['comment_author_email'],
								'required'     => $noorifa_core_name_email_required,
								'autocomplete' => 'email',
							),
						);

						$noorifa_core_comment_form['fields'] = array();

						foreach ( $noorifa_core_fields as $noorifa_core_key => $noorifa_core_field ) {
							$noorifa_core_field_html  = '<p class="comment-form-' . esc_attr( $noorifa_core_key ) . '">';
							$noorifa_core_field_html .= '<label for="' . esc_attr( $noorifa_core_key ) . '">' . esc_html( $noorifa_core_field['label'] );

							if ( $noorifa_core_field['required'] ) {
								$noorifa_core_field_html .= '&nbsp;<span class="required">*</span>';
							}

							$noorifa_core_field_html .= '</label><input id="' . esc_attr( $noorifa_core_key ) . '" name="' . esc_attr( $noorifa_core_key ) . '" type="' . esc_attr( $noorifa_core_field['type'] ) . '" autocomplete="' . esc_attr( $noorifa_core_field['autocomplete'] ) . '" value="' . esc_attr( $noorifa_core_field['value'] ) . '" size="30" ' . ( $noorifa_core_field['required'] ? 'required' : '' ) . ' /></p>';

							$noorifa_core_comment_form['fields'][ $noorifa_core_key ] = $noorifa_core_field_html;
						}

						$noorifa_core_account_page_url = wc_get_page_permalink( 'myaccount' );
						if ( $noorifa_core_account_page_url ) {
							/* translators: %1$s/%2$s: opening/closing link tags */
							$noorifa_core_comment_form['must_log_in'] = '<p class="must-log-in">' . sprintf( esc_html__( 'You must be %1$slogged in%2$s to post a review.', 'noorifa-core' ), '<a href="' . esc_url( $noorifa_core_account_page_url ) . '">', '</a>' ) . '</p>';
						}

						if ( $noorifa_core_ratings_enabled ) {
							$noorifa_core_comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating" id="comment-form-rating-label">' . esc_html__( 'Your rating', 'noorifa-core' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label><select name="rating" id="rating" ' . ( wc_review_ratings_required() ? 'required' : '' ) . '>
								<option value="">' . esc_html__( 'Rate…', 'noorifa-core' ) . '</option>
								<option value="5">' . esc_html__( 'Perfect', 'noorifa-core' ) . '</option>
								<option value="4">' . esc_html__( 'Good', 'noorifa-core' ) . '</option>
								<option value="3">' . esc_html__( 'Average', 'noorifa-core' ) . '</option>
								<option value="2">' . esc_html__( 'Not that bad', 'noorifa-core' ) . '</option>
								<option value="1">' . esc_html__( 'Very poor', 'noorifa-core' ) . '</option>
							</select></div>';
						}

						$noorifa_core_comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your review', 'noorifa-core' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required></textarea></p>';

						// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- intentionally reusing WooCommerce's own review-form filter name (not a new hook) so plugins/themes that already customize the native review form via this filter keep working here too.
						comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $noorifa_core_comment_form ) );
						?>
					</div>
				</div>
			<?php else : ?>
				<p class="noorifa-core-product-reviews__verification-required">
					<?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'noorifa-core' ); ?>
				</p>
			<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
