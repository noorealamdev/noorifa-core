<?php
/**
 * Video Testimonials block server render.
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

$noorifa_core_layout  = ! empty( $attributes['layout'] ) && 'carousel' === $attributes['layout'] ? 'carousel' : 'grid';
$noorifa_core_columns = ! empty( $attributes['columns'] ) ? max( 1, min( 4, (int) $attributes['columns'] ) ) : 3;

/**
 * Derives a YouTube thumbnail when the merchant hasn't set a poster image,
 * so a card still shows something instead of a blank placeholder. Vimeo has
 * no public thumbnail URL without an API call, so it falls back to the
 * styled placeholder (edit.js mirrors this logic).
 *
 * @param string $url Video URL.
 * @return string Thumbnail URL, or '' if none can be derived.
 */
$noorifa_core_youtube_thumb = static function ( $url ) {
	if ( ! $url ) {
		return '';
	}
	if ( preg_match( '#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#', $url, $m ) ) {
		return 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg';
	}
	return '';
};

/**
 * Renders one testimonial card. Shared by both the grid and carousel
 * layouts so the card markup stays identical between them.
 *
 * @param array    $item  Single testimonial item.
 * @param callable $thumb YouTube thumbnail deriver.
 * @return string Card HTML.
 */
$noorifa_core_render_card = static function ( $item, $thumb ) {
	$type      = ! empty( $item['videoType'] ) && 'upload' === $item['videoType'] ? 'upload' : 'url';
	$video_url = isset( $item['videoUrl'] ) ? trim( (string) $item['videoUrl'] ) : '';
	$file_url  = isset( $item['videoFileUrl'] ) ? trim( (string) $item['videoFileUrl'] ) : '';
	$poster    = ! empty( $item['posterUrl'] ) ? $item['posterUrl'] : ( 'url' === $type ? $thumb( $video_url ) : '' );
	$quote     = isset( $item['quote'] ) ? $item['quote'] : '';
	$name      = isset( $item['name'] ) ? $item['name'] : '';
	$role      = isset( $item['role'] ) ? $item['role'] : '';

	// Nothing playable — skip so an empty card never renders on the front end.
	$has_video = ( 'url' === $type && '' !== $video_url ) || ( 'upload' === $type && '' !== $file_url );
	if ( ! $has_video ) {
		return '';
	}

	$media_style = $poster ? ' style="background-image:url(' . esc_url( $poster ) . ')"' : '';

	ob_start();
	?>
	<figure class="noorifa-core-video-testimonials__item">
		<button
			type="button"
			class="noorifa-core-video-testimonials__media<?php echo $poster ? '' : ' has-no-poster'; ?>"
			data-video-type="<?php echo esc_attr( $type ); ?>"
			data-video-url="<?php echo esc_attr( 'url' === $type ? esc_url_raw( $video_url ) : '' ); ?>"
			data-video-src="<?php echo esc_attr( 'upload' === $type ? esc_url_raw( $file_url ) : '' ); ?>"
			aria-label="<?php echo esc_attr__( 'Play video testimonial', 'noorifa-core' ); ?>"
			<?php echo $media_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- URL escaped above. ?>
		>
			<span class="noorifa-core-video-testimonials__play" aria-hidden="true"></span>
		</button>
		<?php
		// Only render the caption when there's actually text — an empty,
		// padded figcaption would otherwise add height below every card and
		// inflate the visible gap between grid rows.
		$has_quote = '' !== trim( wp_strip_all_tags( $quote ) );
		$has_name  = '' !== trim( wp_strip_all_tags( $name ) );
		$has_role  = '' !== trim( wp_strip_all_tags( $role ) );
		if ( $has_quote || $has_name || $has_role ) :
			?>
			<figcaption class="noorifa-core-video-testimonials__body">
				<?php if ( $has_quote ) : ?>
					<p class="noorifa-core-video-testimonials__quote"><?php echo wp_kses_post( $quote ); ?></p>
				<?php endif; ?>
				<?php if ( $has_name ) : ?>
					<span class="noorifa-core-video-testimonials__name"><?php echo wp_kses_post( $name ); ?></span>
				<?php endif; ?>
				<?php if ( $has_role ) : ?>
					<span class="noorifa-core-video-testimonials__role"><?php echo wp_kses_post( $role ); ?></span>
				<?php endif; ?>
			</figcaption>
		<?php endif; ?>
	</figure>
	<?php
	return ob_get_clean();
};

// Build the card list once — used by whichever layout is active.
$noorifa_core_cards = '';
foreach ( $noorifa_core_items as $noorifa_core_item ) {
	$noorifa_core_cards .= $noorifa_core_render_card( $noorifa_core_item, $noorifa_core_youtube_thumb );
}

if ( '' === trim( $noorifa_core_cards ) ) {
	return;
}

$noorifa_core_multiple = count( $noorifa_core_items ) > 1;

$noorifa_core_wrapper_style = '--noorifa-video-cols:' . $noorifa_core_columns . ';';
if ( ! empty( $attributes['cardBackground'] ) ) {
	$noorifa_core_wrapper_style .= '--noorifa-video-card-bg:' . $attributes['cardBackground'] . ';';
}

$noorifa_core_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'noorifa-core-video-testimonials is-' . $noorifa_core_layout,
		'style' => $noorifa_core_wrapper_style,
	)
);

$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<div
		class="noorifa-core-video-testimonials__list<?php echo $noorifa_core_boxed ? ' is-boxed' : ''; ?>"
		<?php if ( $noorifa_core_boxed ) : ?>
			style="max-width:<?php echo esc_attr( $noorifa_core_boxed_width ); ?>px"
		<?php endif; ?>
	>
		<?php if ( 'carousel' === $noorifa_core_layout ) : ?>
			<div class="noorifa-core-video-testimonials__stage">
				<div class="noorifa-core-video-testimonials__viewport">
					<div class="noorifa-core-video-testimonials__track">
						<?php echo $noorifa_core_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
					</div>
				</div>
				<?php if ( $noorifa_core_multiple ) : ?>
					<button type="button" class="noorifa-core-video-testimonials__arrow noorifa-core-video-testimonials__arrow--prev" aria-label="<?php echo esc_attr__( 'Previous', 'noorifa-core' ); ?>">
						<span aria-hidden="true">&#10094;</span>
					</button>
					<button type="button" class="noorifa-core-video-testimonials__arrow noorifa-core-video-testimonials__arrow--next" aria-label="<?php echo esc_attr__( 'Next', 'noorifa-core' ); ?>">
						<span aria-hidden="true">&#10095;</span>
					</button>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="noorifa-core-video-testimonials__grid">
				<?php echo $noorifa_core_cards; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts above. ?>
			</div>
		<?php endif; ?>
	</div>
</div>
