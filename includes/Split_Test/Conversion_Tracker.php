<?php
/**
 * Attributes add-to-cart and purchase events to a split test variant.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Split_Test;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Carries the visitor's split test variant from cart item data onto the
 * order line item, so purchase attribution survives async/guest checkout
 * flows where the original visitor's cookie may not be available in the
 * request that finally marks the order paid (payment gateway webhook, an
 * admin manually advancing the order status, a "Pay for order" email link
 * opened in a different browser).
 */
class Conversion_Tracker {

	use Singleton;

	/**
	 * Cart item data / order item meta key carrying the variant layout ID.
	 */
	const META_KEY = 'noorifa_core_split_layout_id';

	/**
	 * Order meta key marking a purchase event already logged for this order.
	 */
	const RECORDED_META_KEY = '_noorifa_core_split_recorded';

	/**
	 * Hooks the WooCommerce cart/checkout/order events.
	 */
	protected function __construct() {
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'stash_variant_on_cart_item' ), 10, 2 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'log_add_to_cart' ), 10, 6 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'persist_variant_on_order_item' ), 10, 4 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_log_purchase' ), 10, 4 );
	}

	/**
	 * Stashes the visitor's current variant onto the cart item being added.
	 *
	 * Re-derives the variant via Bucketer (not just reading the cookie
	 * directly), so a "quick add" from a shop grid — reached without ever
	 * viewing the product page — still gets consistently bucketed.
	 *
	 * @param array $cart_item_data Existing cart item data.
	 * @param int   $product_id     Product ID.
	 * @return array
	 */
	public function stash_variant_on_cart_item( $cart_item_data, $product_id ) {
		$layout_id = Bucketer::instance()->get_or_assign_variant_layout_id( $product_id );

		if ( $layout_id ) {
			$cart_item_data[ self::META_KEY ] = $layout_id;
		}

		return $cart_item_data;
	}

	/**
	 * Logs an add-to-cart event for the variant stashed on this cart item.
	 *
	 * @param string $cart_item_key  Cart item key.
	 * @param int    $product_id     Product ID.
	 * @param int    $quantity       Quantity added.
	 * @param int    $variation_id   Variation ID.
	 * @param array  $variation      Variation attributes.
	 * @param array  $cart_item_data Cart item data.
	 * @return void
	 */
	public function log_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		if ( empty( $cart_item_data[ self::META_KEY ] ) ) {
			return;
		}

		Stats::instance()->log_event( $product_id, (int) $cart_item_data[ self::META_KEY ], Stats::EVENT_ADD_TO_CART );
	}

	/**
	 * Copies the cart item's variant onto the resulting order line item.
	 *
	 * @param \WC_Order_Item_Product $item          Order line item.
	 * @param string                 $cart_item_key Cart item key.
	 * @param array                  $values        Cart item values.
	 * @return void
	 */
	public function persist_variant_on_order_item( $item, $cart_item_key, $values ) {
		if ( empty( $values[ self::META_KEY ] ) ) {
			return;
		}

		// Underscore-prefixed: internal attribution data, kept out of the
		// customer-facing order details/emails.
		$item->add_meta_data( '_' . self::META_KEY, (int) $values[ self::META_KEY ], true );
	}

	/**
	 * Logs one purchase event per split-tested line item the first time an
	 * order reaches any paid status.
	 *
	 * Hooks `woocommerce_order_status_changed` rather than
	 * `woocommerce_payment_complete` because Cash on Delivery orders never
	 * call `payment_complete()` — they're moved straight to
	 * `processing`/`on-hold`, which `wc_get_is_paid_statuses()` still
	 * covers. The `_noorifa_core_split_recorded` flag keeps this counting
	 * exactly once per order even though it may pass through several
	 * paid-adjacent transitions afterward.
	 *
	 * @param int       $order_id Order ID.
	 * @param string    $from     Previous status.
	 * @param string    $to       New status.
	 * @param \WC_Order $order    Order object.
	 * @return void
	 */
	public function maybe_log_purchase( $order_id, $from, $to, $order ) {
		if ( ! in_array( $to, wc_get_is_paid_statuses(), true ) ) {
			return;
		}

		if ( $order->get_meta( self::RECORDED_META_KEY, true ) ) {
			return;
		}

		foreach ( $order->get_items() as $item ) {
			$layout_id = (int) $item->get_meta( '_' . self::META_KEY, true );

			if ( $layout_id ) {
				Stats::instance()->log_event( $item->get_product_id(), $layout_id, Stats::EVENT_PURCHASE );
			}
		}

		$order->update_meta_data( self::RECORDED_META_KEY, '1' );
		$order->save();
	}
}
