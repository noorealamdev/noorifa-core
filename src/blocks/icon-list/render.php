<?php
/**
 * Icon List block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$noorifa_core_rows = isset( $attributes['rows'] ) && is_array( $attributes['rows'] ) ? $attributes['rows'] : array();

if ( empty( $noorifa_core_rows ) ) {
	return;
}

$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 800;
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<ul
		class="noorifa-core-icon-list__list<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php foreach ( $noorifa_core_rows as $noorifa_core_row ) : ?>
			<li class="noorifa-core-icon-list__row">
				<span class="noorifa-core-icon-list__icon">
					<?php echo \Noorifa\Core\Blocks\WP_Icon_Registry::render( $noorifa_core_row['icon'] ?? 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- self-escaping, fixed internal registry. ?>
				</span>
				<span class="noorifa-core-icon-list__text"><?php echo wp_kses_post( $noorifa_core_row['text'] ?? '' ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
