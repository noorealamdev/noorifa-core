<?php
/**
 * Sticky Add to Cart Bar block server render.
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
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="noorifa-core-sticky-add-to-cart__media">
		<?php echo wp_kses_post( $product->get_image( 'thumbnail' ) ); ?>
	</div>
	<div class="noorifa-core-sticky-add-to-cart__details">
		<span class="noorifa-core-sticky-add-to-cart__name"><?php echo esc_html( $product->get_name() ); ?></span>
		<span class="noorifa-core-sticky-add-to-cart__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
	</div>
	<div class="noorifa-core-sticky-add-to-cart__center">
		<?php if ( ! empty( $attributes['badgeText'] ) ) : ?>
			<span class="noorifa-core-sticky-add-to-cart__badge"><?php echo esc_html( $attributes['badgeText'] ); ?></span>
		<?php endif; ?>
		<?php
		/**
		 * Fires inside the sticky Add to Cart bar, right after the price/details
		 * — hook here to inject custom text/badges (e.g. "Free shipping", a
		 * stock countdown) without editing this block.
		 *
		 * @param \WC_Product $product The current product.
		 */
		do_action( 'noorifa-core/sticky_add_to_cart_details', $product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- intentional plugin-namespaced extensibility hook.
		?>
	</div>
	<?php
	$noorifa_core_button_text = ! empty( $attributes['buttonText'] )
		? $attributes['buttonText']
		: __( 'Buy Now', 'noorifa-core' );

	/*
	 * The button submits this self-contained add-to-cart form, so it works
	 * even when the page has no separate Product Add to Cart block (adds
	 * the product, then Buy_Now's redirect filter sends the shopper to
	 * checkout). When a real add-to-cart form IS present (e.g. a variable
	 * product's variations form), view.js re-points the button at it via
	 * the HTML5 `form` attribute so the shopper's chosen variation/quantity
	 * carry over instead of this quantity-1 fallback.
	 */
	?>
	<?php
	/*
	 * Deliberately NOT given the `cart` class: the theme's AJAX cart script
	 * hijacks `form.cart` submits into a background wc-ajax add (which drops
	 * the Buy Now field and keeps the shopper on the page). A plain form
	 * does a full submit so WooCommerce runs add-to-cart and Buy_Now's
	 * redirect filter lands the shopper on checkout.
	 */
	?>
	<form class="noorifa-core-sticky-add-to-cart__form" method="post" enctype="multipart/form-data">
		<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
		<input type="hidden" name="quantity" value="1" />
		<button
			type="submit"
			name="<?php echo esc_attr( \Noorifa\Core\Blocks\Buy_Now::FIELD ); ?>"
			value="1"
			class="noorifa-core-sticky-add-to-cart__button"
		>
			<?php echo esc_html( $noorifa_core_button_text ); ?>
		</button>
	</form>
</div>
