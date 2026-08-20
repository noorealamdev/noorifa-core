<?php
/**
 * Product Tabs block server render.
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

// Self-contained max-width instead of relying on the theme/template to
// constrain block content — this block can land in templates that render
// blocks without any width-limiting ancestor.
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div
		class="noorifa-core-product-tabs__inner<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php
		/*
		 * WooCommerce's own description panel prints a second real
		 * "Description" <h2> above the content — redundant with the tab
		 * nav button right above it (also labelled "Description"), and
		 * unstyled/oversized here since nothing in this block scopes a
		 * heading size down for it. `__return_false` (not `''`) because
		 * the description template's own check is a truthy test, and
		 * `''` is falsy too but `__return_false` is WordPress's real,
		 * canonical no-op-filter callback for this.
		 */
		add_filter( 'woocommerce_product_description_heading', '__return_false' );
		woocommerce_output_product_data_tabs();
		remove_filter( 'woocommerce_product_description_heading', '__return_false' );
		?>
	</div>
</div>
