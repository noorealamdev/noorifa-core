<?php
/**
 * "Preview" row action for Product Layout posts.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Preview" link to each Product Layout's row actions, rendering
 * the layout on a real product page for that one request only — without
 * assigning it to any product — so a merchant can see how it actually
 * looks (including real WooCommerce data) before applying it live.
 */
class Preview {

	use Singleton;

	/**
	 * Query var carrying the layout ID to preview.
	 */
	const QUERY_VAR = 'noorifa_core_preview';

	/**
	 * Hooks the row action.
	 */
	protected function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
	}

	/**
	 * Adds the "Preview" link to a Product Layout row.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Post $post    The row's post.
	 * @return array
	 */
	public function add_row_action( $actions, $post ) {
		if ( Post_Type::SLUG !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$preview_product_id = $this->get_preview_product_id();

		if ( ! $preview_product_id ) {
			return $actions;
		}

		$url = add_query_arg(
			array(
				self::QUERY_VAR => $post->ID,
				'_wpnonce'      => wp_create_nonce( 'noorifa_core_preview_layout_' . $post->ID ),
			),
			get_permalink( $preview_product_id )
		);

		$actions['noorifa_core_preview'] = sprintf(
			'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_url( $url ),
			esc_html__( 'Preview', 'noorifa-core' )
		);

		return $actions;
	}

	/**
	 * Returns the layout ID a valid, nonce-verified preview request is
	 * asking to see, or 0 when no override applies.
	 *
	 * Deliberately does not require the layout to be published — the
	 * whole point is letting a merchant see a draft before it goes live.
	 *
	 * @return int
	 */
	public function get_requested_layout_id() {
		if ( ! isset( $_GET[ self::QUERY_VAR ], $_GET['_wpnonce'] ) ) {
			return 0;
		}

		$layout_id = absint( $_GET[ self::QUERY_VAR ] );

		if ( ! $layout_id || Post_Type::SLUG !== get_post_type( $layout_id ) ) {
			return 0;
		}

		if ( ! current_user_can( 'edit_post', $layout_id ) ) {
			return 0;
		}

		$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'noorifa_core_preview_layout_' . $layout_id ) ) {
			return 0;
		}

		return $layout_id;
	}

	/**
	 * Picks a real product to preview layouts against — the one most
	 * recently modified, on the assumption it's likely what's actively
	 * being worked on.
	 *
	 * @return int Product ID, or 0 when the store has no published products.
	 */
	private function get_preview_product_id() {
		$products = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		return $products ? (int) $products[0] : 0;
	}
}
