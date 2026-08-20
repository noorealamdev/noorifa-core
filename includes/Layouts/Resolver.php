<?php
/**
 * Resolves which Product Layout applies to a given product.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Resolution order: product-level choice, then category-level default,
 * then the site-wide default; falls through to "none" (vanilla
 * WooCommerce rendering) when nothing is configured.
 */
class Resolver {

	use Singleton;

	/**
	 * Post meta key storing a product's chosen layout ID.
	 */
	const PRODUCT_META_KEY = '_noorifa_core_layout_id';

	/**
	 * Term meta key storing a product category's default layout ID.
	 */
	const CATEGORY_META_KEY = 'noorifa_core_layout_id';

	/**
	 * Option storing the site-wide default layout ID.
	 */
	const DEFAULT_OPTION = 'noorifa_core_default_layout_id';

	/**
	 * Returns the resolved Product Layout post for a product, or null.
	 *
	 * @param int $product_id Product post ID.
	 * @return \WP_Post|null
	 */
	public function get_layout( $product_id ) {
		$id = $this->get_layout_id( $product_id );

		return $id ? get_post( $id ) : null;
	}

	/**
	 * Returns the resolved Product Layout post ID for a product, or 0.
	 *
	 * @param int $product_id Product post ID.
	 * @return int
	 */
	public function get_layout_id( $product_id ) {
		$preview_id = Preview::instance()->get_requested_layout_id();

		if ( $preview_id ) {
			return $preview_id;
		}

		// Split_Test_Router is in this same namespace and resolves/logs a
		// visitor's variant earlier in the request (template_include
		// priority 90, before Template_Override's 100) — reading its
		// per-request cache here keeps this the single place every other
		// part of the plugin (Resolver callers, Assets\Manager, etc.)
		// asks for "the" layout, split test or not.
		$variant_id = Split_Test_Router::instance()->get_resolved_layout_id( $product_id );

		if ( $variant_id ) {
			return $variant_id;
		}

		$product_layout = (int) get_post_meta( $product_id, self::PRODUCT_META_KEY, true );

		if ( $this->is_published_layout( $product_layout ) ) {
			return $product_layout;
		}

		$terms = get_the_terms( $product_id, 'product_cat' );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$term_layout = (int) get_term_meta( $term->term_id, self::CATEGORY_META_KEY, true );

				if ( $this->is_published_layout( $term_layout ) ) {
					return $term_layout;
				}
			}
		}

		$default = (int) get_option( self::DEFAULT_OPTION, 0 );

		if ( $this->is_published_layout( $default ) ) {
			return $default;
		}

		return 0;
	}

	/**
	 * Whether a layout ID points to a published Product Layout post.
	 *
	 * @param int $layout_id Layout post ID.
	 * @return bool
	 */
	private function is_published_layout( $layout_id ) {
		return $layout_id
			&& Post_Type::SLUG === get_post_type( $layout_id )
			&& 'publish' === get_post_status( $layout_id );
	}
}
