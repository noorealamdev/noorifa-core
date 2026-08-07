<?php
/**
 * Inline Checkout block server render.
 *
 * Renders a self-contained landing-page order form. The customer picks one
 * package (each linked to a real WooCommerce product), fills in
 * name/address/phone, and view.js posts it to the wc-ajax endpoint handled
 * by \Noorifa\Core\Blocks\Inline_Checkout, which creates a genuine COD
 * order and returns the order-received URL to redirect to.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$noorifa_ic_accent = ! empty( $attributes['accentColor'] )
	? sanitize_hex_color( $attributes['accentColor'] )
	: '';
if ( ! $noorifa_ic_accent ) {
	$noorifa_ic_accent = '#3a9e3a';
}

// Resolve each configured package to a real, purchasable WooCommerce
// product. Packages pointing at a missing/unpurchasable product are
// skipped so a bad id never breaks the whole form.
$noorifa_ic_packages = array();
foreach ( (array) ( $attributes['packages'] ?? array() ) as $noorifa_ic_pkg ) {
	$noorifa_ic_product_id = isset( $noorifa_ic_pkg['productId'] ) ? absint( $noorifa_ic_pkg['productId'] ) : 0;

	if ( ! $noorifa_ic_product_id ) {
		continue;
	}

	$noorifa_ic_product = wc_get_product( $noorifa_ic_product_id );

	if ( ! $noorifa_ic_product || ! $noorifa_ic_product->is_purchasable() ) {
		continue;
	}

	$noorifa_ic_packages[] = array(
		'product'  => $noorifa_ic_product,
		'label'    => ! empty( $noorifa_ic_pkg['label'] ) ? $noorifa_ic_pkg['label'] : $noorifa_ic_product->get_name(),
		'desc'     => $noorifa_ic_pkg['description'] ?? '',
		'badge'    => $noorifa_ic_pkg['badge'] ?? '',
		'show_qty' => ! isset( $noorifa_ic_pkg['showQty'] ) || false !== $noorifa_ic_pkg['showQty'],
		'price'    => (float) wc_get_price_to_display( $noorifa_ic_product ),
	);
}

if ( empty( $noorifa_ic_packages ) ) {
	if ( current_user_can( 'edit_posts' ) ) {
		echo '<div ' . get_block_wrapper_attributes() . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
		echo '<p style="padding:16px;border:1px dashed #ccc;">';
		esc_html_e( 'Inline Checkout: add at least one package linked to a WooCommerce product in the block settings.', 'noorifa-core' );
		echo '</p></div>';
	}
	return;
}

$noorifa_ic_first       = $noorifa_ic_packages[0];
$noorifa_ic_first_price = $noorifa_ic_first['price'];
$noorifa_ic_uid         = wp_unique_id( 'noorifa-ic-' );

// Self-contained max-width so the form can be constrained and centered
// without relying on the theme/template, matching the boxed Layout control
// other Noorifa Core blocks use.
$noorifa_ic_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_ic_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1000;

$noorifa_ic_class = 'noorifa-core-inline-checkout';
$noorifa_ic_style = '--noorifa-ic-accent:' . $noorifa_ic_accent . ';';

if ( $noorifa_ic_boxed ) {
	$noorifa_ic_class .= ' is-boxed';
	$noorifa_ic_style .= 'max-width:' . $noorifa_ic_boxed_width . 'px;';
}

$noorifa_ic_wrapper = get_block_wrapper_attributes(
	array(
		'class'             => $noorifa_ic_class,
		'style'             => $noorifa_ic_style,
		'data-endpoint'     => class_exists( 'WC_AJAX' ) ? \WC_AJAX::get_endpoint( 'noorifa_core_inline_checkout' ) : admin_url( 'admin-ajax.php?action=noorifa_core_inline_checkout' ),
		'data-nonce'        => wp_create_nonce( 'noorifa_core_inline_checkout' ),
		'data-symbol'       => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
		'data-pos'          => get_option( 'woocommerce_currency_pos', 'left' ),
		'data-decimals'     => (string) wc_get_price_decimals(),
		'data-decimal-sep'  => wc_get_price_decimal_separator(),
		'data-thousand-sep' => wc_get_price_thousand_separator(),
	)
);
?>
<div <?php echo $noorifa_ic_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="noorifa-core-inline-checkout__banner">
		<h2 class="noorifa-core-inline-checkout__banner-title"><?php echo esc_html( $attributes['formHeading'] ?? '' ); ?></h2>
	</div>

	<?php if ( ! empty( $attributes['packagesHeading'] ) ) : ?>
		<h3 class="noorifa-core-inline-checkout__packages-heading"><?php echo esc_html( $attributes['packagesHeading'] ); ?></h3>
	<?php endif; ?>

	<div class="noorifa-core-inline-checkout__packages">
		<?php
		foreach ( $noorifa_ic_packages as $noorifa_ic_index => $noorifa_ic_package ) :
			$noorifa_ic_p         = $noorifa_ic_package['product'];
			$noorifa_ic_is_first  = 0 === $noorifa_ic_index;
			$noorifa_ic_image_id  = $noorifa_ic_p->get_image_id();
			$noorifa_ic_image_url = $noorifa_ic_image_id ? wp_get_attachment_image_url( $noorifa_ic_image_id, 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' );
			?>
			<label class="noorifa-core-inline-checkout__package<?php echo $noorifa_ic_is_first ? ' is-selected' : ''; ?>">
				<input
					type="radio"
					class="noorifa-core-inline-checkout__package-radio"
					name="noorifa_ic_package"
					value="<?php echo esc_attr( $noorifa_ic_index ); ?>"
					data-product-id="<?php echo esc_attr( $noorifa_ic_p->get_id() ); ?>"
					data-price="<?php echo esc_attr( $noorifa_ic_package['price'] ); ?>"
					data-name="<?php echo esc_attr( $noorifa_ic_package['label'] ); ?>"
					data-image="<?php echo esc_url( $noorifa_ic_image_url ); ?>"
					<?php checked( $noorifa_ic_is_first ); ?>
				/>
				<span class="noorifa-core-inline-checkout__package-dot" aria-hidden="true"></span>
				<span class="noorifa-core-inline-checkout__package-media">
					<img src="<?php echo esc_url( $noorifa_ic_image_url ); ?>" alt="" loading="lazy" />
				</span>
				<span class="noorifa-core-inline-checkout__package-body">
					<span class="noorifa-core-inline-checkout__package-title"><?php echo esc_html( $noorifa_ic_package['label'] ); ?></span>
					<?php if ( '' !== $noorifa_ic_package['desc'] ) : ?>
						<span class="noorifa-core-inline-checkout__package-desc"><?php echo esc_html( $noorifa_ic_package['desc'] ); ?></span>
					<?php endif; ?>
					<?php if ( $noorifa_ic_package['show_qty'] ) : ?>
						<span class="noorifa-core-inline-checkout__qty">
							<button type="button" class="noorifa-core-inline-checkout__qty-btn" data-step="-1" aria-label="<?php esc_attr_e( 'Decrease quantity', 'noorifa-core' ); ?>">&minus;</button>
							<input type="number" class="noorifa-core-inline-checkout__qty-input" value="1" min="1" step="1" inputmode="numeric" aria-label="<?php esc_attr_e( 'Quantity', 'noorifa-core' ); ?>" />
							<button type="button" class="noorifa-core-inline-checkout__qty-btn" data-step="1" aria-label="<?php esc_attr_e( 'Increase quantity', 'noorifa-core' ); ?>">+</button>
						</span>
					<?php endif; ?>
				</span>
				<span class="noorifa-core-inline-checkout__package-price"><?php echo wp_kses_post( $noorifa_ic_p->get_price_html() ); ?></span>
				<?php if ( '' !== $noorifa_ic_package['badge'] ) : ?>
					<span class="noorifa-core-inline-checkout__package-badge"><?php echo esc_html( $noorifa_ic_package['badge'] ); ?></span>
				<?php endif; ?>
			</label>
		<?php endforeach; ?>
	</div>

	<form class="noorifa-core-inline-checkout__form" novalidate>
		<div class="noorifa-core-inline-checkout__grid">
			<div class="noorifa-core-inline-checkout__billing">
				<h3 class="noorifa-core-inline-checkout__section-title"><?php echo esc_html( $attributes['billingHeading'] ?? '' ); ?></h3>

				<div class="noorifa-core-inline-checkout__field" data-field="name">
					<label class="noorifa-core-inline-checkout__label" for="<?php echo esc_attr( $noorifa_ic_uid . '-name' ); ?>">
						<?php echo esc_html( $attributes['nameLabel'] ?? '' ); ?> <span class="noorifa-core-inline-checkout__req">*</span>
					</label>
					<input type="text" id="<?php echo esc_attr( $noorifa_ic_uid . '-name' ); ?>" class="noorifa-core-inline-checkout__input" name="noorifa_ic_name" autocomplete="name" required />
					<div class="noorifa-core-inline-checkout__error"></div>
				</div>

				<div class="noorifa-core-inline-checkout__field" data-field="address">
					<label class="noorifa-core-inline-checkout__label" for="<?php echo esc_attr( $noorifa_ic_uid . '-address' ); ?>">
						<?php echo esc_html( $attributes['addressLabel'] ?? '' ); ?> <span class="noorifa-core-inline-checkout__req">*</span>
					</label>
					<input type="text" id="<?php echo esc_attr( $noorifa_ic_uid . '-address' ); ?>" class="noorifa-core-inline-checkout__input" name="noorifa_ic_address" autocomplete="street-address" required />
					<div class="noorifa-core-inline-checkout__error"></div>
				</div>

				<div class="noorifa-core-inline-checkout__field" data-field="phone">
					<label class="noorifa-core-inline-checkout__label" for="<?php echo esc_attr( $noorifa_ic_uid . '-phone' ); ?>">
						<?php echo esc_html( $attributes['phoneLabel'] ?? '' ); ?> <span class="noorifa-core-inline-checkout__req">*</span>
					</label>
					<input type="tel" id="<?php echo esc_attr( $noorifa_ic_uid . '-phone' ); ?>" class="noorifa-core-inline-checkout__input" name="noorifa_ic_phone" autocomplete="tel" inputmode="numeric" required />
					<?php if ( ! empty( $attributes['phoneHelp'] ) ) : ?>
						<div class="noorifa-core-inline-checkout__help"><?php echo esc_html( $attributes['phoneHelp'] ); ?></div>
					<?php endif; ?>
					<div class="noorifa-core-inline-checkout__error"></div>
				</div>
			</div>

			<div class="noorifa-core-inline-checkout__order">
				<h3 class="noorifa-core-inline-checkout__section-title"><?php echo esc_html( $attributes['orderHeading'] ?? '' ); ?></h3>

				<div class="noorifa-core-inline-checkout__summary">
					<div class="noorifa-core-inline-checkout__summary-head">
						<span><?php esc_html_e( 'Product', 'noorifa-core' ); ?></span>
						<span><?php esc_html_e( 'Subtotal', 'noorifa-core' ); ?></span>
					</div>
					<div class="noorifa-core-inline-checkout__summary-product">
						<span class="noorifa-core-inline-checkout__summary-thumb">
							<img data-role="thumb" src="<?php echo esc_url( $noorifa_ic_first['product']->get_image_id() ? wp_get_attachment_image_url( $noorifa_ic_first['product']->get_image_id(), 'woocommerce_thumbnail' ) : wc_placeholder_img_src( 'woocommerce_thumbnail' ) ); ?>" alt="" />
						</span>
						<span class="noorifa-core-inline-checkout__summary-name">
							<span data-role="name"><?php echo esc_html( $noorifa_ic_first['label'] ); ?></span>
							<span class="noorifa-core-inline-checkout__summary-qty">&times;&nbsp;<span data-role="qty">1</span></span>
						</span>
						<span class="noorifa-core-inline-checkout__summary-line" data-role="line"><?php echo wp_kses_post( wc_price( $noorifa_ic_first_price ) ); ?></span>
					</div>
					<div class="noorifa-core-inline-checkout__summary-row">
						<span><?php esc_html_e( 'Subtotal', 'noorifa-core' ); ?></span>
						<span data-role="subtotal"><?php echo wp_kses_post( wc_price( $noorifa_ic_first_price ) ); ?></span>
					</div>
					<?php if ( ! empty( $attributes['shippingLabel'] ) ) : ?>
						<div class="noorifa-core-inline-checkout__summary-row">
							<span><?php esc_html_e( 'Shipping', 'noorifa-core' ); ?></span>
							<span><?php echo esc_html( $attributes['shippingLabel'] ); ?></span>
						</div>
					<?php endif; ?>
					<div class="noorifa-core-inline-checkout__summary-row noorifa-core-inline-checkout__summary-total">
						<span><?php esc_html_e( 'Total', 'noorifa-core' ); ?></span>
						<span data-role="total"><?php echo wp_kses_post( wc_price( $noorifa_ic_first_price ) ); ?></span>
					</div>
				</div>

				<div class="noorifa-core-inline-checkout__payment">
					<div class="noorifa-core-inline-checkout__payment-title"><?php echo esc_html( $attributes['paymentTitle'] ?? '' ); ?></div>
					<?php if ( ! empty( $attributes['paymentDescription'] ) ) : ?>
						<div class="noorifa-core-inline-checkout__payment-desc"><?php echo esc_html( $attributes['paymentDescription'] ); ?></div>
					<?php endif; ?>
				</div>

				<?php if ( ! empty( $attributes['privacyText'] ) ) : ?>
					<p class="noorifa-core-inline-checkout__privacy"><?php echo esc_html( $attributes['privacyText'] ); ?></p>
				<?php endif; ?>

				<button type="submit" class="noorifa-core-inline-checkout__submit">
					<svg class="noorifa-core-inline-checkout__lock" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 10V8a6 6 0 1 1 12 0v2h1a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1h1zm2 0h8V8a4 4 0 1 0-8 0v2z" fill="currentColor"/></svg>
					<span class="noorifa-core-inline-checkout__submit-text"><?php echo esc_html( $attributes['buttonText'] ?? '' ); ?></span>
					<span class="noorifa-core-inline-checkout__submit-total" data-role="button-total"><?php echo wp_kses_post( wc_price( $noorifa_ic_first_price ) ); ?></span>
				</button>

				<div class="noorifa-core-inline-checkout__form-error" role="alert"></div>
			</div>
		</div>
	</form>
</div>
