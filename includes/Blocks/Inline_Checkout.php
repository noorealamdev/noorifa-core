<?php
/**
 * Order handler for the Inline Checkout block.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Creates a real WooCommerce Cash on Delivery order from the Inline
 * Checkout block's AJAX submission, then returns the order-received URL for
 * the front-end to redirect to. The block posts only the chosen product id,
 * quantity and the shopper's name/address/phone; every price is resolved
 * here from WooCommerce, never trusted from the request.
 */
class Inline_Checkout {

	use Singleton;

	/**
	 * The wc-ajax action name (matches render.php's data-endpoint).
	 */
	const ACTION = 'noorifa_core_inline_checkout';

	/**
	 * Hooks the AJAX handler. WooCommerce fires `wc_ajax_{action}` for both
	 * logged-in and guest requests, so a single hook covers everyone.
	 */
	protected function __construct() {
		add_action( 'wc_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Processes the order submission.
	 *
	 * @return void
	 */
	public function handle() {
		if ( ! function_exists( 'wc_create_order' ) ) {
			$this->error( __( 'Ordering is unavailable right now.', 'noorifa-core' ) );
		}

		if ( ! check_ajax_referer( self::ACTION, 'nonce', false ) ) {
			$this->error( __( 'Your session expired. Please refresh the page and try again.', 'noorifa-core' ) );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$quantity   = isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1;
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$address    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

		$quantity = max( 1, $quantity );

		if ( '' === $name ) {
			$this->error( __( 'Please enter your name.', 'noorifa-core' ), 'name' );
		}

		if ( '' === $address ) {
			$this->error( __( 'Please enter your address.', 'noorifa-core' ), 'address' );
		}

		if ( ! preg_match( '/^01\d{9}$/', $phone ) ) {
			$this->error( __( 'Please enter a valid 11 digit phone number.', 'noorifa-core' ), 'phone' );
		}

		$product = $product_id ? wc_get_product( $product_id ) : false;

		if ( ! $product || ! $product->is_purchasable() ) {
			$this->error( __( 'The selected package is no longer available.', 'noorifa-core' ) );
		}

		if ( ! $product->is_in_stock() ) {
			$this->error( __( 'The selected package is out of stock.', 'noorifa-core' ) );
		}

		try {
			$order = $this->create_order( $product, $quantity, $name, $address, $phone );
		} catch ( \Exception $e ) {
			$this->error( __( 'We could not place your order. Please try again.', 'noorifa-core' ) );
		}

		wp_send_json_success(
			array(
				'redirect' => $order->get_checkout_order_received_url(),
			)
		);
	}

	/**
	 * Builds and saves the WooCommerce order.
	 *
	 * @param \WC_Product $product  The purchased product.
	 * @param int         $quantity Quantity.
	 * @param string      $name     Customer name.
	 * @param string      $address  Customer address.
	 * @param string      $phone    Customer phone.
	 * @return \WC_Order
	 * @throws \Exception If order creation fails.
	 */
	private function create_order( $product, $quantity, $name, $address, $phone ) {
		$order = wc_create_order( array( 'customer_id' => get_current_user_id() ) );

		if ( is_wp_error( $order ) ) {
			throw new \Exception( 'order-create-failed' );
		}

		$order->add_product( $product, $quantity );

		$order->set_address(
			array(
				'first_name' => $name,
				'last_name'  => '',
				'phone'      => $phone,
				'address_1'  => $address,
				'country'    => WC()->countries->get_base_country(),
			),
			'billing'
		);

		$order->set_payment_method( $this->get_cod_gateway() );

		if ( is_callable( array( $order, 'set_created_via' ) ) ) {
			$order->set_created_via( 'noorifa-core-inline-checkout' );
		}

		$order->calculate_totals();

		/**
		 * Filters the status a new Inline Checkout (COD) order is set to.
		 *
		 * @param string    $status  Default 'processing'.
		 * @param \WC_Order $order   The order being placed.
		 */
		$status = apply_filters( 'noorifa-core/inline_checkout_order_status', 'processing', $order ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- intentional plugin-namespaced hook.

		$order->update_status(
			$status,
			__( 'Order placed via Inline Checkout (Cash on Delivery).', 'noorifa-core' )
		);

		return $order;
	}

	/**
	 * Returns the Cash on Delivery gateway object when available, falling
	 * back to the plain 'cod' id so the order still records a sane payment
	 * method if the gateway is disabled.
	 *
	 * @return \WC_Payment_Gateway|string
	 */
	private function get_cod_gateway() {
		$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();

		if ( isset( $gateways['cod'] ) ) {
			return $gateways['cod'];
		}

		return 'cod';
	}

	/**
	 * Sends a JSON error response and stops execution.
	 *
	 * @param string $message Human-readable error message.
	 * @param string $field   Optional field key the error relates to.
	 * @return void
	 */
	private function error( $message, $field = '' ) {
		wp_send_json_error(
			array(
				'message' => $message,
				'field'   => $field,
			)
		);
	}
}
