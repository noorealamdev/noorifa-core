<?php
/**
 * Product Category block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$noorifa_core_product_id = get_the_ID();
$noorifa_core_terms      = get_the_terms( $noorifa_core_product_id, 'product_cat' );

if ( empty( $noorifa_core_terms ) || is_wp_error( $noorifa_core_terms ) ) {
	return;
}

$noorifa_core_term = $noorifa_core_terms[0];

/*
 * This block ships with an opinionated "kicker label" default look (small,
 * uppercase, letter-spaced, muted) — applied as a conditional inline style
 * rather than a plain CSS class, since a class-based default can never be
 * fully "cleared": once the user changes a value via the Typography/Color
 * panel, that panel's own inline style naturally wins over ours; skipping
 * each default the moment the user has set their own keeps every one of
 * these controls genuinely editable instead of silently dead.
 */
$noorifa_core_has_own_font_size      = ! empty( $attributes['fontSize'] ) || ! empty( $attributes['style']['typography']['fontSize'] );
$noorifa_core_has_own_letter_spacing = ! empty( $attributes['style']['typography']['letterSpacing'] );
$noorifa_core_has_own_text_transform = ! empty( $attributes['style']['typography']['textTransform'] );
$noorifa_core_has_own_font_weight    = ! empty( $attributes['style']['typography']['fontWeight'] );
$noorifa_core_has_own_text_color     = ! empty( $attributes['textColor'] ) || ! empty( $attributes['style']['color']['text'] );

$noorifa_core_default_style = '';

if ( ! $noorifa_core_has_own_font_size ) {
	$noorifa_core_default_style .= 'font-size:0.8rem;';
}

if ( ! $noorifa_core_has_own_letter_spacing ) {
	$noorifa_core_default_style .= 'letter-spacing:0.06em;';
}

if ( ! $noorifa_core_has_own_text_transform ) {
	$noorifa_core_default_style .= 'text-transform:uppercase;';
}

if ( ! $noorifa_core_has_own_font_weight ) {
	$noorifa_core_default_style .= 'font-weight:600;';
}

if ( ! $noorifa_core_has_own_text_color ) {
	$noorifa_core_default_style .= 'color:#6b7280;';
}

$noorifa_core_wrapper = get_block_wrapper_attributes(
	$noorifa_core_default_style ? array( 'style' => $noorifa_core_default_style ) : array()
);
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<a href="<?php echo esc_url( get_term_link( $noorifa_core_term ) ); ?>">
		<?php echo esc_html( $noorifa_core_term->name ); ?>
	</a>
</div>
