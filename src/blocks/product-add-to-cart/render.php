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

// Injected only around this exact call (added, then immediately removed
// after) so it appears once, right after WooCommerce's own Add to Cart
// button, without leaking onto any other add-to-cart form the theme
// might render elsewhere on the site.
$noorifa_core_buy_now_button = function () use ( $product ) {
	/*
	 * WooCommerce's add-to-cart handler (WC_Form_Handler::add_to_cart_action())
	 * bails out immediately unless $_REQUEST['add-to-cart'] is set — for a
	 * simple product that field only exists as the *default* Add to Cart
	 * button's own name/value, so it's only submitted when THAT button is
	 * the one clicked (per normal HTML form semantics: only the activated
	 * submit button's name/value pair is included). Buy Now needs its own
	 * hidden copy so it's submitted regardless of which button was clicked
	 * — matching what WooCommerce's own variable-product template already
	 * does for its cart button, which is why Buy Now happened to work for
	 * variable products without this despite not working for simple ones.
	 */
	?>
	<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
	<button
		type="submit"
		name="<?php echo esc_attr( \Noorifa\Core\Blocks\Buy_Now::FIELD ); ?>"
		value="1"
		class="noorifa-core-buy-now-button"
	>
		<?php esc_html_e( 'Buy Now', 'noorifa-core' ); ?>
	</button>
	<?php
};

add_action( 'woocommerce_after_add_to_cart_button', $noorifa_core_buy_now_button );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php woocommerce_template_single_add_to_cart(); ?>
</div>
<?php
remove_action( 'woocommerce_after_add_to_cart_button', $noorifa_core_buy_now_button );

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
