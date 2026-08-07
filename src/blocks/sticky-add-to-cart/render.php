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
	/*
	 * This button lives outside form.cart (it's fixed to the viewport, not
	 * part of the product summary markup), but still submits that exact
	 * form via the HTML5 `form` attribute — an externally-associated
	 * button still contributes its own name/value to that form's
	 * submission, same as if it were nested inside it. view.js sets the
	 * attribute to the real form's id once it locates it; left empty here
	 * so the button is inert (matches no form) rather than erroring if JS
	 * fails to load, instead of accidentally submitting nothing useful.
	 */
	?>
	<?php
	$noorifa_core_button_text = ! empty( $attributes['buttonText'] )
		? $attributes['buttonText']
		: __( 'Buy Now', 'noorifa-core' );
	?>
	<button
		type="submit"
		form=""
		name="<?php echo esc_attr( \Noorifa\Core\Blocks\Buy_Now::FIELD ); ?>"
		value="1"
		class="noorifa-core-sticky-add-to-cart__button"
	>
		<?php echo esc_html( $noorifa_core_button_text ); ?>
	</button>
</div>
