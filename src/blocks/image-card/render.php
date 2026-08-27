<?php
/**
 * Image Card block server render.
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

$noorifa_core_columns         = ! empty( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 2;
$noorifa_core_min_height      = ! empty( $attributes['minHeight'] ) ? (int) $attributes['minHeight'] : 480;
$noorifa_core_border_radius   = isset( $attributes['borderRadius'] ) ? max( 0, (int) $attributes['borderRadius'] ) : 0;
$noorifa_core_sub_font_size   = isset( $attributes['subheadingFontSize'] ) ? trim( (string) $attributes['subheadingFontSize'] ) : '';

$noorifa_core_valign = in_array( $attributes['verticalAlign'] ?? 'center', array( 'top', 'center', 'bottom' ), true )
	? $attributes['verticalAlign']
	: 'center';
$noorifa_core_align  = in_array( $attributes['textAlign'] ?? 'left', array( 'left', 'center', 'right' ), true )
	? $attributes['textAlign']
	: 'left';

/**
 * Renders one promo card.
 *
 * @param array  $item   Single card item.
 * @param string $valign Vertical content alignment class suffix.
 * @param string $align  Text alignment class suffix.
 * @return string Card HTML.
 */
$noorifa_core_render_card = static function ( $item, $valign, $align ) {
	$image     = isset( $item['imageUrl'] ) ? trim( (string) $item['imageUrl'] ) : '';
	$heading   = isset( $item['heading'] ) ? $item['heading'] : '';
	$sub       = isset( $item['subheading'] ) ? $item['subheading'] : '';
	$link_text = isset( $item['linkText'] ) ? $item['linkText'] : '';
	$link_url  = isset( $item['linkUrl'] ) ? trim( (string) $item['linkUrl'] ) : '';
	$color         = isset( $item['textColor'] ) ? trim( (string) $item['textColor'] ) : '';
	$overlay       = isset( $item['overlay'] ) ? max( 0, min( 100, (int) $item['overlay'] ) ) : 0;
	$overlay_color = isset( $item['overlayColor'] ) && '' !== trim( (string) $item['overlayColor'] )
		? trim( (string) $item['overlayColor'] )
		: '#000000';

	$has_content = '' !== trim( wp_strip_all_tags( $heading ) )
		|| '' !== trim( wp_strip_all_tags( $sub ) )
		|| '' !== trim( wp_strip_all_tags( $link_text ) );

	// A card with neither an image nor any text is nothing to show.
	if ( '' === $image && ! $has_content ) {
		return '';
	}

	$card_style = '';
	if ( '' !== $image ) {
		$card_style .= 'background-image:url(' . esc_url( $image ) . ');';
	}
	$content_style = '' !== $color ? ' style="color:' . esc_attr( $color ) . '"' : '';

	$classes = 'noorifa-core-image-card__card is-valign-' . $valign . ' is-align-' . $align;
	if ( '' === $image ) {
		$classes .= ' has-no-image';
	}

	ob_start();
	?>
	<div class="<?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $card_style ); ?>">
		<?php if ( $overlay > 0 ) : ?>
			<span class="noorifa-core-image-card__overlay" style="opacity:<?php echo esc_attr( $overlay / 100 ); ?>;background-color:<?php echo esc_attr( $overlay_color ); ?>" aria-hidden="true"></span>
		<?php endif; ?>
		<div class="noorifa-core-image-card__content"<?php echo $content_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>>
			<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
				<h3 class="noorifa-core-image-card__heading"><?php echo wp_kses_post( $heading ); ?></h3>
			<?php endif; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $sub ) ) ) : ?>
				<p class="noorifa-core-image-card__text"><?php echo wp_kses_post( $sub ); ?></p>
			<?php endif; ?>
			<?php if ( '' !== trim( wp_strip_all_tags( $link_text ) ) ) : ?>
				<a class="noorifa-core-image-card__link" href="<?php echo esc_url( $link_url ? $link_url : '#' ); ?>"><?php echo wp_kses_post( $link_text ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};

$noorifa_core_cards = '';
foreach ( $noorifa_core_items as $noorifa_core_item ) {
	$noorifa_core_cards .= $noorifa_core_render_card( $noorifa_core_item, $noorifa_core_valign, $noorifa_core_align );
}

if ( '' === trim( $noorifa_core_cards ) ) {
	return;
}

$noorifa_core_wrapper_style = '--noorifa-image-card-cols:' . $noorifa_core_columns . ';--noorifa-image-card-min-height:' . $noorifa_core_min_height . 'px;--noorifa-image-card-radius:' . $noorifa_core_border_radius . 'px;';
if ( '' !== $noorifa_core_sub_font_size ) {
	$noorifa_core_wrapper_style .= '--noorifa-image-card-sub-size:' . $noorifa_core_sub_font_size . 'em;';
}

$noorifa_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'noorifa-core-image-card',
		'style' => $noorifa_core_wrapper_style,
	)
);

$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div
		class="noorifa-core-image-card__grid<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php echo $noorifa_core_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
	</div>
</div>
