<?php
/**
 * Urgency & Countdown block server render.
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

$noorifa_core_type        = ! empty( $attributes['type'] ) ? $attributes['type'] : 'stock';
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 800;

/*
 * Background/text/border/padding all come from the block's own supports
 * (Color, Border, Dimensions panels) via get_block_wrapper_attributes() —
 * nothing here hardcodes a look. `is-boxed`/max-width is merged into that
 * same single wrapper (not a separately-styled inner div) so the merchant's
 * Inspector choices always land on the one visible surface.
 */
$noorifa_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $noorifa_core_boxed ? 'is-boxed' : '',
		'style' => $noorifa_core_boxed ? 'max-width:' . $noorifa_core_boxed_width . 'px;' : '',
	)
);

if ( 'stock' === $noorifa_core_type ) {
	// Honest scarcity only: relies entirely on the product's own real
	// stock-management data, never a fabricated/estimated number.
	if ( ! $product->managing_stock() || ! $product->is_in_stock() ) {
		return;
	}

	$noorifa_core_stock     = $product->get_stock_quantity();
	$noorifa_core_threshold = isset( $attributes['stockThreshold'] ) ? absint( $attributes['stockThreshold'] ) : 10;

	if ( null === $noorifa_core_stock || $noorifa_core_stock > $noorifa_core_threshold ) {
		return;
	}

	$noorifa_core_template  = ! empty( $attributes['stockMessage'] )
		? $attributes['stockMessage']
		: __( 'Only {stock} left in stock — order soon!', 'noorifa-core' );
	$noorifa_core_message   = str_replace( '{stock}', $noorifa_core_stock, $noorifa_core_template );
	$noorifa_core_show_icon = ! isset( $attributes['showIcon'] ) || (bool) $attributes['showIcon'];
	?>
	<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
		<?php if ( $noorifa_core_show_icon ) : ?>
			<svg class="noorifa-core-urgency__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2 1 21h22L12 2zm0 5.5 7.53 12.5H4.47L12 7.5zM11 10v5h2v-5h-2zm0 6.5v2h2v-2h-2z"/></svg>
		<?php endif; ?>
		<span class="noorifa-core-urgency__message"><?php echo esc_html( $noorifa_core_message ); ?></span>
	</div>
	<?php
	return;
}

// Countdown type: only ever counts down to a real, merchant-controlled
// deadline (the product's own scheduled sale end date, or a date the
// merchant explicitly sets) — never a fake/rolling "limited time" timer.
$noorifa_core_source   = ! empty( $attributes['countdownSource'] ) ? $attributes['countdownSource'] : 'sale';
$noorifa_core_deadline = null;

if ( 'sale' === $noorifa_core_source ) {
	$noorifa_core_sale_to = $product->get_date_on_sale_to();

	if ( $noorifa_core_sale_to && $product->is_on_sale() ) {
		$noorifa_core_deadline = $noorifa_core_sale_to->getTimestamp();
	}
} elseif ( ! empty( $attributes['customDate'] ) ) {
	try {
		$noorifa_core_custom   = new DateTime( $attributes['customDate'], wp_timezone() );
		$noorifa_core_deadline = $noorifa_core_custom->getTimestamp();
	} catch ( Exception $e ) {
		$noorifa_core_deadline = null;
	}
}

if ( null === $noorifa_core_deadline ) {
	return;
}

$noorifa_core_hide_when_expired = ! isset( $attributes['hideWhenExpired'] ) || (bool) $attributes['hideWhenExpired'];
$noorifa_core_is_expired        = $noorifa_core_deadline <= time();

if ( $noorifa_core_is_expired && $noorifa_core_hide_when_expired ) {
	return;
}

$noorifa_core_label           = ! empty( $attributes['countdownLabel'] ) ? $attributes['countdownLabel'] : __( 'Sale ends in:', 'noorifa-core' );
$noorifa_core_expired_message = ! empty( $attributes['expiredMessage'] ) ? $attributes['expiredMessage'] : __( 'This offer has ended.', 'noorifa-core' );
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php if ( $noorifa_core_is_expired ) : ?>
		<span class="noorifa-core-urgency__message"><?php echo esc_html( $noorifa_core_expired_message ); ?></span>
	<?php else : ?>
		<span class="noorifa-core-urgency__label"><?php echo esc_html( $noorifa_core_label ); ?></span>
		<div
			class="noorifa-core-urgency__timer"
			data-deadline="<?php echo esc_attr( $noorifa_core_deadline * 1000 ); ?>"
			data-expired-message="<?php echo esc_attr( $noorifa_core_expired_message ); ?>"
			data-hide-when-expired="<?php echo $noorifa_core_hide_when_expired ? '1' : '0'; ?>"
		>
			<div class="noorifa-core-urgency__unit">
				<span class="noorifa-core-urgency__value" data-unit="days">00</span>
				<span class="noorifa-core-urgency__unit-label"><?php esc_html_e( 'Days', 'noorifa-core' ); ?></span>
			</div>
			<div class="noorifa-core-urgency__unit">
				<span class="noorifa-core-urgency__value" data-unit="hours">00</span>
				<span class="noorifa-core-urgency__unit-label"><?php esc_html_e( 'Hrs', 'noorifa-core' ); ?></span>
			</div>
			<div class="noorifa-core-urgency__unit">
				<span class="noorifa-core-urgency__value" data-unit="minutes">00</span>
				<span class="noorifa-core-urgency__unit-label"><?php esc_html_e( 'Min', 'noorifa-core' ); ?></span>
			</div>
			<div class="noorifa-core-urgency__unit">
				<span class="noorifa-core-urgency__value" data-unit="seconds">00</span>
				<span class="noorifa-core-urgency__unit-label"><?php esc_html_e( 'Sec', 'noorifa-core' ); ?></span>
			</div>
		</div>
	<?php endif; ?>
</div>
