<?php
/**
 * Related Products block server render.
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
		class="noorifa-core-related-products__inner<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php
		woocommerce_related_products(
			array(
				'posts_per_page' => absint( $attributes['productsToShow'] ),
				'columns'        => absint( $attributes['columns'] ),
			)
		);
		?>
	</div>
</div>
