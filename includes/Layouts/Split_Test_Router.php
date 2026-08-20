<?php
/**
 * Buckets front-end visitors into a split test variant and logs impressions.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Split_Test\Bucketer;
use Noorifa\Core\Split_Test\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Pure observer on `template_include`: never selects a template itself,
 * just resolves (and, on a fresh visit, assigns/cookies) which layout
 * variant this visitor sees for the product's active split test, logs one
 * impression, and caches the result for Resolver to read within the same
 * request — so the header/enqueued-assets code and the template that
 * actually renders never disagree on which variant this visit got.
 */
class Split_Test_Router {

	use Singleton;

	/**
	 * Per-request cache of resolved variant layout IDs, keyed by product ID.
	 *
	 * @var array<int, int>
	 */
	private $resolved = array();

	/**
	 * Hooks the bucketing observer.
	 */
	protected function __construct() {
		// Priority 90: must run before Template_Override's 100, so the
		// variant is resolved (and cached for Resolver to read) before the
		// template file that actually renders it gets chosen.
		add_filter( 'template_include', array( $this, 'maybe_bucket_and_log' ), 90 );
	}

	/**
	 * Buckets the current visitor and logs an impression, without ever
	 * changing which template file is used.
	 *
	 * @param string $template The template WordPress resolved.
	 * @return string Unchanged.
	 */
	public function maybe_bucket_and_log( $template ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $template;
		}

		// A deliberate admin preview isn't a real visit — don't bucket,
		// cookie, or count it.
		if ( Preview::instance()->get_requested_layout_id() ) {
			return $template;
		}

		// Excludes logged-in staff browsing the storefront from polluting
		// visitor-facing stats.
		if ( current_user_can( 'edit_posts' ) ) {
			return $template;
		}

		$product_id = get_queried_object_id();
		$layout_id  = Bucketer::instance()->get_or_assign_variant_layout_id( $product_id );

		if ( ! $layout_id ) {
			return $template;
		}

		$this->resolved[ $product_id ] = $layout_id;

		Stats::instance()->log_event( $product_id, $layout_id, Stats::EVENT_IMPRESSION );

		return $template;
	}

	/**
	 * Returns this request's already-resolved variant for a product, or 0.
	 *
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public function get_resolved_layout_id( $product_id ) {
		return isset( $this->resolved[ $product_id ] ) ? $this->resolved[ $product_id ] : 0;
	}
}
