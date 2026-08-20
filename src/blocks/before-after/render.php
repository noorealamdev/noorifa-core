<?php
/**
 * Before / After block server render.
 *
 * @package Noorifa Core
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$noorifa_core_before_url = isset( $attributes['beforeImageUrl'] ) ? trim( (string) $attributes['beforeImageUrl'] ) : '';
$noorifa_core_after_url  = isset( $attributes['afterImageUrl'] ) ? trim( (string) $attributes['afterImageUrl'] ) : '';

// Nothing to compare without both images.
if ( '' === $noorifa_core_before_url || '' === $noorifa_core_after_url ) {
	return;
}

$noorifa_core_before_label = isset( $attributes['beforeLabel'] ) ? trim( wp_strip_all_tags( $attributes['beforeLabel'] ) ) : '';
$noorifa_core_after_label  = isset( $attributes['afterLabel'] ) ? trim( wp_strip_all_tags( $attributes['afterLabel'] ) ) : '';

$noorifa_core_position = isset( $attributes['initialPosition'] ) ? max( 0, min( 100, (int) $attributes['initialPosition'] ) ) : 50;
$noorifa_core_height   = ! empty( $attributes['height'] ) ? (int) $attributes['height'] : 480;

$noorifa_core_badge_bg   = isset( $attributes['badgeBackground'] ) ? trim( (string) $attributes['badgeBackground'] ) : '';
$noorifa_core_badge_text = isset( $attributes['badgeTextColor'] ) ? trim( (string) $attributes['badgeTextColor'] ) : '';
$noorifa_core_handle     = isset( $attributes['handleColor'] ) ? trim( (string) $attributes['handleColor'] ) : '';

$noorifa_core_style  = '--noorifa-before-after-position:' . $noorifa_core_position . '%;';
$noorifa_core_style .= '--noorifa-before-after-height:' . $noorifa_core_height . 'px;';
if ( '' !== $noorifa_core_badge_bg ) {
	$noorifa_core_style .= '--noorifa-before-after-badge-bg:' . esc_attr( $noorifa_core_badge_bg ) . ';';
}
if ( '' !== $noorifa_core_badge_text ) {
	$noorifa_core_style .= '--noorifa-before-after-badge-text:' . esc_attr( $noorifa_core_badge_text ) . ';';
}
if ( '' !== $noorifa_core_handle ) {
	$noorifa_core_style .= '--noorifa-before-after-handle:' . esc_attr( $noorifa_core_handle ) . ';';
}

$noorifa_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'noorifa-core-before-after',
		'style' => $noorifa_core_style,
	)
);
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div class="noorifa-core-before-after__image noorifa-core-before-after__image--before">
		<img src="<?php echo esc_url( $noorifa_core_before_url ); ?>" alt="<?php echo esc_attr( $noorifa_core_before_label ); ?>" />
	</div>
	<div class="noorifa-core-before-after__image noorifa-core-before-after__image--after">
		<img src="<?php echo esc_url( $noorifa_core_after_url ); ?>" alt="<?php echo esc_attr( $noorifa_core_after_label ); ?>" />
	</div>

	<?php if ( '' !== $noorifa_core_before_label ) : ?>
		<span class="noorifa-core-before-after__badge noorifa-core-before-after__badge--before"><?php echo esc_html( $noorifa_core_before_label ); ?></span>
	<?php endif; ?>
	<?php if ( '' !== $noorifa_core_after_label ) : ?>
		<span class="noorifa-core-before-after__badge noorifa-core-before-after__badge--after"><?php echo esc_html( $noorifa_core_after_label ); ?></span>
	<?php endif; ?>

	<div
		class="noorifa-core-before-after__handle-wrap"
		role="slider"
		tabindex="0"
		aria-label="<?php esc_attr_e( 'Before and after image comparison', 'noorifa-core' ); ?>"
		aria-valuemin="0"
		aria-valuemax="100"
		aria-valuenow="<?php echo esc_attr( $noorifa_core_position ); ?>"
	>
		<span class="noorifa-core-before-after__line" aria-hidden="true"></span>
		<span class="noorifa-core-before-after__handle" aria-hidden="true">
			<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M6 1 2 5l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
			<svg width="10" height="10" viewBox="0 0 10 10" fill="none"><path d="M4 1l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
		</span>
	</div>
</div>
