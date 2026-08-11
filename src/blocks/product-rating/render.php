<?php
/**
 * Product Rating block server render.
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

$noorifa_core_show_count = ! empty( $attributes['showCount'] );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php
	if ( $noorifa_core_show_count ) {
		// Full WooCommerce rating template — stars plus the
		// "(N customer reviews)" link.
		woocommerce_template_single_rating();
	} else {
		// Stars only: wc_get_rating_html() outputs just the `.star-rating`
		// markup, without the review-count link.
		$noorifa_core_rating = (float) $product->get_average_rating();
		if ( $noorifa_core_rating > 0 || wc_review_ratings_enabled() ) {
			echo wc_get_rating_html( $noorifa_core_rating, $product->get_rating_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
		}
	}
	?>
</div>
