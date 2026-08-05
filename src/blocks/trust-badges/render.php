<?php
/**
 * Trust Badges block server render.
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

/*
 * No forced default background/text-color/radius here — same approach as
 * Icon List. WordPress can't distinguish "cleared" from "never set" for a
 * color attribute, so any baked-in default can never be fully suppressed
 * by the user; plain color/border supports with nothing hardcoded avoids
 * the problem entirely. The soft rounded card look ships as starter
 * *content* instead (see the `items` default in block.json) — pick this
 * block from the inserter and its own default color/border/spacing
 * values (set once via Inspector, no code needed) give the same look.
 */
$noorifa_core_wrapper = get_block_wrapper_attributes();

$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<ul
		class="noorifa-core-trust-badges__list<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php foreach ( $noorifa_core_items as $noorifa_core_item ) : ?>
			<li class="noorifa-core-trust-badges__item">
				<?php if ( ! empty( $noorifa_core_item['icon'] ) ) : ?>
					<span class="noorifa-core-trust-badges__icon">
						<?php echo \Noorifa\Core\Blocks\WP_Icon_Registry::render( $noorifa_core_item['icon'], 'nt-shieldCheck' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self-escaping, fixed internal registry. ?>
					</span>
				<?php endif; ?>
				<span class="noorifa-core-trust-badges__label"><?php echo wp_kses_post( $noorifa_core_item['label'] ?? '' ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
