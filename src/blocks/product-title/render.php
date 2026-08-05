<?php
/**
 * Product Title block server render.
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
 * WooCommerce's own default callbacks on this hook — the sale-flash badge
 * and (critically) the entire product gallery — are temporarily removed
 * before firing it. This block only wants third-party extensions hooked
 * here (badges, "recently viewed" trackers, waitlist notices) to run;
 * without removing these two, WooCommerce's own gallery renders a second
 * time on top of the Product Gallery Carousel block already on the page.
 */
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

do_action( 'woocommerce_before_single_product_summary' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
add_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php woocommerce_template_single_title(); ?>
</div>
