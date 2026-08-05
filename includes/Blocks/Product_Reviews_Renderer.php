<?php
/**
 * Shared rendering for the Product Reviews block, used both by its
 * initial server-render and the paginated REST endpoint that powers its
 * "Load more" button — so both use identical markup from one place.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches and renders individual product review cards.
 */
class Product_Reviews_Renderer {

	/**
	 * Returns a page of a product's real (rated) reviews.
	 *
	 * @param int $product_id Product post ID.
	 * @param int $offset     Number of reviews to skip.
	 * @param int $limit      Maximum number of reviews to return.
	 * @return array[] Each item: `comment` (WP_Comment), `rating` (float).
	 */
	public static function get_reviews( $product_id, $offset, $limit ) {
		$comments = get_comments(
			array(
				'post_id'    => $product_id,
				'status'     => 'approve',
				'orderby'    => 'comment_date_gmt',
				'order'      => 'DESC',
				'offset'     => max( 0, $offset ),
				'number'     => max( 1, $limit ),
				// Identifies real reviews (vs. a plain comment, if comments are
				// otherwise enabled on the product) by the presence of a
				// rating, the same signal WooCommerce itself stores per review.
				'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- reviews have no other reliable identifying signal on this install.
					array(
						'key'     => 'rating',
						'compare' => 'EXISTS',
					),
				),
			)
		);

		$reviews = array();

		foreach ( $comments as $comment ) {
			$reviews[] = array(
				'comment' => $comment,
				'rating'  => (float) get_comment_meta( $comment->comment_ID, 'rating', true ),
			);
		}

		return $reviews;
	}

	/**
	 * Renders a single review card.
	 *
	 * @param \WP_Comment $comment         The review comment.
	 * @param float       $rating          The review's star rating.
	 * @param bool        $ratings_enabled Whether star ratings are enabled site-wide.
	 * @return string
	 */
	public static function render_card( $comment, $rating, $ratings_enabled ) {
		$author   = $comment->comment_author;
		$verified = wc_review_is_from_verified_owner( $comment->comment_ID );

		ob_start();
		?>
		<div class="noorifa-core-product-reviews__card">
			<?php if ( $ratings_enabled ) : ?>
				<?php echo wc_get_rating_html( $rating ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>
			<?php endif; ?>
			<p class="noorifa-core-product-reviews__content">
				<?php echo wp_kses_post( get_comment_text( $comment ) ); ?>
			</p>
			<div class="noorifa-core-product-reviews__footer">
				<span class="noorifa-core-product-reviews__avatar noorifa-core-product-reviews__avatar--palette-<?php echo esc_attr( self::get_avatar_palette_index( $author ) ); ?>">
					<?php echo esc_html( self::get_initials( $author ) ); ?>
				</span>
				<span class="noorifa-core-product-reviews__reviewer">
					<span class="noorifa-core-product-reviews__name"><?php echo esc_html( $author ); ?></span>
					<?php if ( $verified ) : ?>
						<span class="noorifa-core-product-reviews__verified">
							<?php esc_html_e( 'Verified Purchase', 'noorifa-core' ); ?>
						</span>
					<?php endif; ?>
				</span>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns two-letter initials for a reviewer name.
	 *
	 * @param string $name Display name.
	 * @return string
	 */
	public static function get_initials( $name ) {
		$words = array_filter( preg_split( '/\s+/', trim( (string) $name ) ) );

		if ( empty( $words ) ) {
			return '?';
		}

		if ( 1 === count( $words ) ) {
			return strtoupper( mb_substr( reset( $words ), 0, 1 ) );
		}

		$first = reset( $words );
		$last  = end( $words );

		return strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) );
	}

	/**
	 * Deterministically maps a name to one of a small fixed avatar color palette.
	 *
	 * @param string $name Display name.
	 * @return int Palette index, 0-5.
	 */
	public static function get_avatar_palette_index( $name ) {
		return abs( crc32( (string) $name ) ) % 6;
	}
}
