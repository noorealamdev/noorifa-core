<?php
/**
 * Product Add to Cart block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

global $product;
$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $product is WooCommerce's own global.

if ( ! $product ) {
	return;
}

/*
 * NOT injecting a separate "Buy Now" button here: the theme's own
 * `woocommerce/single-product/add-to-cart/simple.php` (and the variable
 * equivalent), rendered below via `woocommerce_template_single_add_to_cart()`,
 * already includes a real, fully-styled "Buy It Now" button wired to the
 * theme's own working checkout-redirect (`Noorifa\WooCommerce\BuyItNow`).
 * A second, separately-styled button here (the plugin's own
 * `Noorifa\Core\Blocks\Buy_Now`) just duplicated it.
 *
 * The wrapper below nests `product-info-wrap` > `product-variant` — the
 * SAME two ancestor classes `template-parts/product/summary.php` wraps
 * `woocommerce_template_single_add_to_cart()` in on the theme's own
 * default product page. Every real style for this markup lives in
 * main.css scoped under one or the other of those two classes, not
 * written generically (the quantity pill's `display:flex`/border under
 * `.product-variant`, the Add to Cart/Buy It Now row's `display:grid`
 * under `.product-info-wrap`) — without both, this block rendered with
 * none of that styling at all.
 */
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'product-info-wrap' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="product-variant">
		<?php woocommerce_template_single_add_to_cart(); ?>
	</div>
</div>
<?php
/*
 * WooCommerce's own default callbacks on this hook — the product data
 * tabs, upsells, and related products — are temporarily removed before
 * firing it. This block only wants third-party extensions hooked here
 * (bundles, "frequently bought together") to run; the tabs/related
 * products already have their own dedicated blocks elsewhere in this
 * layout, so leaving these in would render everything a second time.
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

do_action( 'woocommerce_after_single_product_summary' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
