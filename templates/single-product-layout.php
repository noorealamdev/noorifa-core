<?php
/**
 * Fallback single-product template that renders a Noorifa Core Product
 * Layout in place of the theme's default, mirroring the structure of
 * WooCommerce's own templates/single-product.php so theme header, footer
 * and sidebar wrappers stay intact.
 *
 * @package Noorifa Core
 */

use Noorifa\Core\Layouts\Resolver;

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook, kept so the theme's structural CSS still applies.

while ( have_posts() ) :
	the_post();

	global $product;
	$product = wc_get_product( get_the_ID() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- $product is WooCommerce's own global.

	// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- explanatory prose, not commented-out code; it just mentions a filename and hook names.
	// Mirrors WooCommerce's own wrapping hooks around content-single-product
	// (empty by default) so plugins that hook onto them — structured data,
	// "recently viewed" trackers, page-builder compatibility layers — still
	// fire around a Noorifa Core layout exactly as they would around the
	// theme's default template.
	do_action( 'woocommerce_before_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

	$noorifa_core_layout = Resolver::instance()->get_layout( get_the_ID() );

	if ( $noorifa_core_layout ) {
		echo '<div class="noorifa-core-layout">';
		echo do_blocks( $noorifa_core_layout->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block content is sanitized like any other post content.
		echo '</div>';
	}

	do_action( 'woocommerce_after_single_product' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

endwhile;

do_action( 'woocommerce_after_main_content' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

do_action( 'woocommerce_sidebar' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own hook.

get_footer( 'shop' );
