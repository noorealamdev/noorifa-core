<?php
/**
 * Marquee block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

// Drop empty items so a blank gap never scrolls past.
$noorifa_core_items = array();
$noorifa_core_raw   = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : array();

foreach ( $noorifa_core_raw as $noorifa_core_row ) {
	if ( isset( $noorifa_core_row['text'] ) && '' !== trim( (string) $noorifa_core_row['text'] ) ) {
		$noorifa_core_items[] = $noorifa_core_row;
	}
}

if ( empty( $noorifa_core_items ) ) {
	return;
}

$noorifa_core_speed     = ! empty( $attributes['speed'] ) ? (float) $attributes['speed'] : 20;
$noorifa_core_gap       = isset( $attributes['gap'] ) ? absint( $attributes['gap'] ) : 48;
$noorifa_core_direction = ( isset( $attributes['direction'] ) && 'right' === $attributes['direction'] ) ? 'right' : 'left';
$noorifa_core_pause     = ! isset( $attributes['pauseOnHover'] ) || (bool) $attributes['pauseOnHover'];
$noorifa_core_separator = isset( $attributes['separator'] ) ? (string) $attributes['separator'] : '';

$noorifa_core_classes = 'is-direction-' . $noorifa_core_direction;
if ( $noorifa_core_pause ) {
	$noorifa_core_classes .= ' is-pause-hover';
}

$noorifa_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => $noorifa_core_classes,
		// Speed/gap drive CSS variables the animation reads (see style.scss).
		'style' => sprintf(
			'--noorifa-marquee-duration:%ss;--noorifa-marquee-gap:%dpx;',
			$noorifa_core_speed,
			$noorifa_core_gap
		),
	)
);

// One group of items, rendered twice in the track so the loop is seamless
// (the animation shifts the track by exactly one group width — see the
// keyframes in style.scss).
ob_start();
foreach ( $noorifa_core_items as $noorifa_core_item ) {
	printf(
		'<span class="noorifa-core-marquee__item">%s</span>',
		esc_html( $noorifa_core_item['text'] )
	);

	if ( '' !== $noorifa_core_separator ) {
		printf(
			'<span class="noorifa-core-marquee__separator" aria-hidden="true">%s</span>',
			esc_html( $noorifa_core_separator )
		);
	}
}
$noorifa_core_group = ob_get_clean();
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="noorifa-core-marquee__track">
		<div class="noorifa-core-marquee__group">
			<?php echo $noorifa_core_group; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each item/separator is esc_html'd above. ?>
		</div>
		<div class="noorifa-core-marquee__group" aria-hidden="true">
			<?php echo $noorifa_core_group; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- identical, already-escaped duplicate of the group above. ?>
		</div>
	</div>
</div>
