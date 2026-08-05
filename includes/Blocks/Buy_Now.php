<?php
/**
 * "Buy Now" redirect for the Product Add to Cart block.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * The block's "Buy Now" button submits the same add-to-cart form as the
 * normal button, just with an extra field identifying which button was
 * clicked. WooCommerce still runs its own full add-to-cart handling
 * (stock checks, variations, notices); this only changes where the
 * customer lands afterwards.
 */
class Buy_Now {

	use Singleton;

	/**
	 * Request field name the "Buy Now" button submits.
	 */
	const FIELD = 'noorifa_core_buy_now';

	/**
	 * Hooks the redirect filter.
	 */
	protected function __construct() {
		add_filter( 'woocommerce_add_to_cart_redirect', array( $this, 'maybe_redirect_to_checkout' ) );
	}

	/**
	 * Sends the customer to checkout instead of back to the product page,
	 * only when the request came from the "Buy Now" button.
	 *
	 * @param string|false $url The redirect WooCommerce would otherwise use.
	 * @return string|false
	 */
	public function maybe_redirect_to_checkout( $url ) {
		if ( empty( $_REQUEST[ self::FIELD ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only branch selection, no state is changed by this check itself.
			return $url;
		}

		return wc_get_checkout_url();
	}
}
