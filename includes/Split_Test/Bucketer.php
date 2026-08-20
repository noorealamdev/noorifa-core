<?php
/**
 * Visitor-to-variant assignment for split tests.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Split_Test;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Layouts\Post_Type as Layouts_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of truth for which layout variant a visitor sees for a
 * given product's split test, persisted via a 30-day cookie so the same
 * visitor keeps seeing the same variant across sessions. Used identically
 * by the page-view bucketing path and the add-to-cart attribution path, so
 * there is exactly one place that reads/writes the cookie.
 */
class Bucketer {

	use Singleton;

	/**
	 * Product meta key: split test status ('running'|'ended').
	 */
	const STATUS_META_KEY = '_noorifa_core_split_test_status';

	/**
	 * Product meta key: layout A post ID.
	 */
	const LAYOUT_A_META_KEY = '_noorifa_core_split_test_layout_a';

	/**
	 * Product meta key: layout B post ID.
	 */
	const LAYOUT_B_META_KEY = '_noorifa_core_split_test_layout_b';

	/**
	 * Product meta key: when the test started (timestamp).
	 */
	const STARTED_META_KEY = '_noorifa_core_split_test_started';

	/**
	 * Product meta key: when the test ended (timestamp).
	 */
	const ENDED_META_KEY = '_noorifa_core_split_test_ended';

	/**
	 * Product meta key: the declared winning layout ID.
	 */
	const WINNER_META_KEY = '_noorifa_core_split_test_winner';

	/**
	 * Prefix for the per-product variant-assignment cookie.
	 */
	const COOKIE_PREFIX = 'noorifa_core_ab_';

	/**
	 * How long a visitor's variant assignment is remembered.
	 */
	const COOKIE_DURATION = 30 * DAY_IN_SECONDS;

	/**
	 * Whether a product currently has a running split test with two
	 * published layouts.
	 *
	 * @param int $product_id Product ID.
	 * @return bool
	 */
	public function is_active( $product_id ) {
		if ( 'running' !== get_post_meta( $product_id, self::STATUS_META_KEY, true ) ) {
			return false;
		}

		list( $layout_a, $layout_b ) = $this->get_variant_ids( $product_id );

		return $this->is_published_layout( $layout_a ) && $this->is_published_layout( $layout_b );
	}

	/**
	 * Returns the two layout IDs configured for a product's split test,
	 * regardless of whether the test is currently running.
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: int, 1: int}
	 */
	public function get_variant_ids( $product_id ) {
		return array(
			(int) get_post_meta( $product_id, self::LAYOUT_A_META_KEY, true ),
			(int) get_post_meta( $product_id, self::LAYOUT_B_META_KEY, true ),
		);
	}

	/**
	 * Returns the layout ID this visitor should see, assigning and
	 * cookieing a fresh 50/50 pick when they don't already have a valid
	 * one for the product's current test.
	 *
	 * A cookie left over from an earlier, different test on this same
	 * product matches neither current variant, so it's treated as absent
	 * and reassigned — self-healing instead of silently mixing two tests'
	 * numbers together.
	 *
	 * @param int $product_id Product ID.
	 * @return int Layout post ID, or 0 when no test is active.
	 */
	public function get_or_assign_variant_layout_id( $product_id ) {
		if ( ! $this->is_active( $product_id ) ) {
			return 0;
		}

		list( $layout_a, $layout_b ) = $this->get_variant_ids( $product_id );

		$cookie_name = self::COOKIE_PREFIX . $product_id;
		$existing    = isset( $_COOKIE[ $cookie_name ] ) ? absint( $_COOKIE[ $cookie_name ] ) : 0;

		if ( $layout_a === $existing || $layout_b === $existing ) {
			return $existing;
		}

		$assigned = ( 0 === wp_rand( 0, 1 ) ) ? $layout_a : $layout_b;

		if ( ! headers_sent() ) {
			setcookie(
				$cookie_name,
				(string) $assigned,
				array(
					'expires'  => time() + self::COOKIE_DURATION,
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		}

		// So the rest of this same request sees the fresh assignment too.
		$_COOKIE[ $cookie_name ] = (string) $assigned;

		return $assigned;
	}

	/**
	 * Whether a layout ID points to a published Product Layout post.
	 *
	 * @param int $layout_id Layout post ID.
	 * @return bool
	 */
	private function is_published_layout( $layout_id ) {
		return $layout_id
			&& Layouts_Post_Type::SLUG === get_post_type( $layout_id )
			&& 'publish' === get_post_status( $layout_id );
	}
}
