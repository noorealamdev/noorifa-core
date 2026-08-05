<?php
/**
 * Product Reviews pagination REST controller.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Rest;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Blocks\Product_Reviews_Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Serves additional pages of a product's reviews for the Product Reviews
 * block's "Load more" button.
 */
class Product_Reviews_Controller {

	use Singleton;

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'noorifa-core/v1';

	/**
	 * Hooks route registration.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the product reviews route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/product-reviews/(?P<product_id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_reviews' ),
					'permission_callback' => '__return_true', // Reviews are public content, same as the block's own initial render.
					'args'                => array(
						'offset'   => array(
							'type'    => 'integer',
							'default' => 0,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 12,
						),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/product-reviews/(?P<product_id>\d+)/list',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_reviews_list' ),
					'permission_callback' => array( $this, 'list_permission_check' ),
				),
			)
		);
	}

	/**
	 * Restricts the raw review list (used by the Review Carousel block's
	 * editor picker) to users who can edit content.
	 *
	 * @return bool
	 */
	public function list_permission_check() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Returns a product's approved, rated reviews as plain data, for the
	 * Review Carousel block's editor-side selection checklist.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_reviews_list( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new \WP_Error( 'noorifa_core_woocommerce_required', __( 'WooCommerce is required.', 'noorifa-core' ), array( 'status' => 400 ) );
		}

		$product_id = absint( $request['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return new \WP_Error( 'noorifa_core_invalid_product', __( 'Invalid product.', 'noorifa-core' ), array( 'status' => 404 ) );
		}

		$reviews = Product_Reviews_Renderer::get_reviews( $product_id, 0, 200 );
		$list    = array();

		foreach ( $reviews as $review ) {
			$comment = $review['comment'];
			$excerpt = wp_strip_all_tags( get_comment_text( $comment ) );

			if ( mb_strlen( $excerpt ) > 90 ) {
				$excerpt = mb_substr( $excerpt, 0, 90 ) . '…';
			}

			$list[] = array(
				'id'       => (int) $comment->comment_ID,
				'author'   => $comment->comment_author,
				'excerpt'  => $excerpt,
				'rating'   => $review['rating'],
				'verified' => wc_review_is_from_verified_owner( $comment->comment_ID ),
			);
		}

		return rest_ensure_response( $list );
	}

	/**
	 * Returns a rendered page of a product's reviews.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_reviews( $request ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return new \WP_Error( 'noorifa_core_woocommerce_required', __( 'WooCommerce is required.', 'noorifa-core' ), array( 'status' => 400 ) );
		}

		$product_id = absint( $request['product_id'] );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			return new \WP_Error( 'noorifa_core_invalid_product', __( 'Invalid product.', 'noorifa-core' ), array( 'status' => 404 ) );
		}

		$offset   = absint( $request->get_param( 'offset' ) );
		$per_page = max( 1, min( 50, absint( $request->get_param( 'per_page' ) ) ) );

		$ratings_enabled = wc_review_ratings_enabled();

		// Fetches one extra review to detect whether another page exists,
		// without a second query.
		$reviews  = Product_Reviews_Renderer::get_reviews( $product_id, $offset, $per_page + 1 );
		$has_more = count( $reviews ) > $per_page;
		$reviews  = array_slice( $reviews, 0, $per_page );

		$html = '';

		foreach ( $reviews as $review ) {
			$html .= Product_Reviews_Renderer::render_card( $review['comment'], $review['rating'], $ratings_enabled );
		}

		return rest_ensure_response(
			array(
				'html'    => $html,
				'hasMore' => $has_more,
			)
		);
	}
}
