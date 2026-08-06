<?php
/**
 * Feature Cards block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$noorifa_core_items = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();

if ( empty( $noorifa_core_items ) ) {
	return;
}

$noorifa_core_columns = ! empty( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 1;

/*
 * Dark-card reference defaults — same values as edit.js's DEFAULT_CARD_*
 * constants (PHP/JS can't share a module, so these are kept in sync by
 * hand). Applied as conditional inline styles per card, not a CSS class
 * default: once the merchant clears a value via the block's own "Card"
 * panel, there's no inline override left to beat a class rule with, so a
 * class-based default would keep showing regardless of intent (the exact
 * issue already found and fixed for Trust Badges this session).
 */
$noorifa_core_card_background = ! empty( $attributes['cardBackgroundGradient'] )
	? $attributes['cardBackgroundGradient']
	: ( ! empty( $attributes['cardBackground'] ) ? $attributes['cardBackground'] : '#1c1c1c' );
$noorifa_core_card_radius     = ! empty( $attributes['cardRadius'] ) ? (int) $attributes['cardRadius'] : 20;
$noorifa_core_card_padding    = ! empty( $attributes['cardPadding'] ) ? (int) $attributes['cardPadding'] : 28;

// `background` (not `background-color`) since this value can be either a
// plain color or a `linear-gradient(...)`/`radial-gradient(...)` string —
// `background-color` silently drops gradient values.
$noorifa_core_card_style = sprintf(
	'background:%s;border-radius:%dpx;padding:%dpx;',
	$noorifa_core_card_background,
	$noorifa_core_card_radius,
	$noorifa_core_card_padding
);

/*
 * The dark card background needs a paired light text default — plain
 * `color: inherit` on the heading/text would otherwise pick up whatever
 * ambient color happens to be active on the page, which has no guaranteed
 * contrast against a dark card. Only applied when the merchant hasn't set
 * their own Text Color (their choice always wins once set, same
 * conditional-inline-default pattern as the card background above).
 */
$noorifa_core_has_own_text_color = ! empty( $attributes['textColor'] ) || ! empty( $attributes['style']['color']['text'] );
$noorifa_core_text_style         = $noorifa_core_has_own_text_color ? '' : 'color:#fff;';

// Self-contained max-width instead of relying on the theme/template to
// constrain block content — applied to the inner `__list` grid, not this
// block's own wrapper, since the wrapper only carries the columns CSS
// variable and isn't itself the grid container.
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;

$noorifa_core_wrapper = get_block_wrapper_attributes(
	array( 'style' => '--noorifa-core-feature-cards-columns:' . $noorifa_core_columns . ';' )
);
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div
		class="noorifa-core-feature-cards__list<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php foreach ( $noorifa_core_items as $noorifa_core_item ) : ?>
			<div class="noorifa-core-feature-cards__item" style="<?php echo esc_attr( $noorifa_core_card_style ); ?>">
				<?php if ( ! empty( $noorifa_core_item['imageId'] ) ) : ?>
					<div class="noorifa-core-feature-cards__image-wrap">
						<?php
						echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped.
							(int) $noorifa_core_item['imageId'],
							'thumbnail',
							false,
							array( 'class' => 'noorifa-core-feature-cards__image' )
						);
						?>
					</div>
				<?php endif; ?>
				<div class="noorifa-core-feature-cards__heading" style="<?php echo esc_attr( $noorifa_core_text_style ); ?>"><?php echo wp_kses_post( $noorifa_core_item['heading'] ?? '' ); ?></div>
				<div class="noorifa-core-feature-cards__text" style="<?php echo esc_attr( $noorifa_core_text_style ); ?>"><?php echo wp_kses_post( $noorifa_core_item['text'] ?? '' ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
