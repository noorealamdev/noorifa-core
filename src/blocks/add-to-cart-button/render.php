<?php
/**
 * Add to Cart Button block server render.
 *
 * A styled button (matching the Advanced Button) that adds a chosen product
 * to the cart, or — when set to "Buy now" — adds it and redirects to
 * checkout via the plugin's Buy_Now flow. Simple, purchasable, in-stock
 * products use WooCommerce's own AJAX add-to-cart link (updates the cart
 * without a reload); anything else (variable, out of stock) links to the
 * product page so the shopper can pick options there, mirroring how
 * WooCommerce's own loop add-to-cart button behaves.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$noorifa_atc_product_id = isset( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
$noorifa_atc_product    = $noorifa_atc_product_id ? wc_get_product( $noorifa_atc_product_id ) : false;

if ( ! $noorifa_atc_product ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<div ' . get_block_wrapper_attributes() . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
		echo '<p style="padding:12px;border:1px dashed #ccc;">';
		esc_html_e( 'Add to Cart Button: choose a product in the block settings.', 'noorifa-core' );
		echo '</p></div>';
	}
	return;
}

$noorifa_atc_qty    = max( 1, isset( $attributes['quantity'] ) ? absint( $attributes['quantity'] ) : 1 );
$noorifa_atc_action = ( isset( $attributes['action'] ) && 'buy' === $attributes['action'] ) ? 'buy' : 'add';
$noorifa_atc_ajax   = $noorifa_atc_product->supports( 'ajax_add_to_cart' ) && $noorifa_atc_product->is_purchasable() && $noorifa_atc_product->is_in_stock();

// Button label: the merchant's text, or a sensible default per action; for a
// product that can't be added straight from a button (variable/out of stock)
// fall back to WooCommerce's own label ("Select options", "Read more", …).
$noorifa_atc_text = isset( $attributes['text'] ) ? trim( $attributes['text'] ) : '';
if ( '' === $noorifa_atc_text ) {
	if ( ! $noorifa_atc_ajax ) {
		$noorifa_atc_text = $noorifa_atc_product->add_to_cart_text();
	} else {
		$noorifa_atc_text = 'buy' === $noorifa_atc_action
			? __( 'Buy now', 'noorifa-core' )
			: __( 'Add to cart', 'noorifa-core' );
	}
}

$noorifa_atc_link_class = 'noorifa-core-add-to-cart-button__link';
$noorifa_atc_extra      = array();

if ( ! $noorifa_atc_ajax ) {
	// Not add-to-cart-able from a button — link to the product page instead.
	$noorifa_atc_href = $noorifa_atc_product->get_permalink();
} elseif ( 'buy' === $noorifa_atc_action ) {
	// Full navigation add: WooCommerce processes the add-to-cart on load and
	// Buy_Now's redirect filter (it reads this field from $_REQUEST) sends the
	// shopper to checkout.
	$noorifa_atc_href = add_query_arg(
		array(
			'quantity'                          => $noorifa_atc_qty,
			\Noorifa\Core\Blocks\Buy_Now::FIELD => 1,
		),
		$noorifa_atc_product->add_to_cart_url()
	);
} else {
	// AJAX add-to-cart — the WooCommerce classes/data attributes its own
	// wc-add-to-cart.js binds to (updates the cart without a reload).
	$noorifa_atc_href        = add_query_arg( 'quantity', $noorifa_atc_qty, $noorifa_atc_product->add_to_cart_url() );
	$noorifa_atc_link_class .= ' button product_type_' . $noorifa_atc_product->get_type() . ' add_to_cart_button ajax_add_to_cart';
	$noorifa_atc_extra       = array(
		'data-product_id' => (string) $noorifa_atc_product->get_id(),
		'data-quantity'   => (string) $noorifa_atc_qty,
		'rel'             => 'nofollow',
	);
}

$noorifa_atc_link_attributes = get_block_wrapper_attributes(
	array_merge(
		array(
			'class' => $noorifa_atc_link_class,
			'href'  => $noorifa_atc_href,
		),
		$noorifa_atc_extra
	)
);

$noorifa_atc_wrap_class = 'wp-block-noorifa-core-add-to-cart-button__wrap';
if ( ! empty( $attributes['textAlign'] ) ) {
	$noorifa_atc_wrap_class .= ' has-text-align-' . sanitize_html_class( $attributes['textAlign'] );
}
if ( ! empty( $attributes['fullWidth'] ) ) {
	$noorifa_atc_wrap_class .= ' is-full-width';
}
?>
<div class="<?php echo esc_attr( $noorifa_atc_wrap_class ); ?>">
	<a <?php echo $noorifa_atc_link_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped attributes. ?>>
		<?php echo wp_kses_post( $noorifa_atc_text ); ?>
	</a>
</div>
