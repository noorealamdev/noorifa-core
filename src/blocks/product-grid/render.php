<?php
/**
 * Product Grid block server render.
 *
 * @package Noorifa Core
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (unused, dynamic block).
 * @var WP_Block $block      Block instance.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$noorifa_core_relation = in_array( $attributes['relation'], array( 'latest', 'related', 'upsells', 'cross-sells' ), true )
	? $attributes['relation']
	: 'latest';
$noorifa_core_current  = wc_get_product( get_the_ID() );
$noorifa_core_to_show  = absint( $attributes['productsToShow'] );

if ( 'related' === $noorifa_core_relation && $noorifa_core_current ) {
	$noorifa_core_ids = wc_get_related_products( $noorifa_core_current->get_id(), $noorifa_core_to_show );
} elseif ( 'upsells' === $noorifa_core_relation && $noorifa_core_current ) {
	$noorifa_core_ids = array_slice( $noorifa_core_current->get_upsell_ids(), 0, $noorifa_core_to_show );
} elseif ( 'cross-sells' === $noorifa_core_relation && $noorifa_core_current ) {
	$noorifa_core_ids = array_slice( $noorifa_core_current->get_cross_sell_ids(), 0, $noorifa_core_to_show );
} else {
	$noorifa_core_ids = array();
}

if ( 'latest' !== $noorifa_core_relation && empty( $noorifa_core_ids ) ) {
	// Related/upsell products requested but none exist for the current product.
	$noorifa_core_query = new WP_Query();
} elseif ( 'latest' !== $noorifa_core_relation ) {
	$noorifa_core_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'post__in'            => $noorifa_core_ids,
			'orderby'             => 'post__in',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
} else {
	$noorifa_core_query = new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $noorifa_core_to_show,
			'order'               => 'asc' === strtolower( $attributes['order'] ) ? 'ASC' : 'DESC',
			'orderby'             => in_array( $attributes['orderBy'], array( 'date', 'title', 'rand' ), true ) ? $attributes['orderBy'] : 'date',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
}

// Self-contained max-width instead of relying on the theme/template to
// constrain block content — applied to this block's own wrapper (rather
// than an inner div, like other blocks in this plugin) because that
// wrapper is also the CSS grid container itself; an extra nested div would
// separate `display: grid` from the item children it needs to apply to.
$noorifa_core_boxed       = ! isset( $attributes['boxed'] ) || (bool) $attributes['boxed'];
$noorifa_core_boxed_width = isset( $attributes['boxedWidth'] ) ? absint( $attributes['boxedWidth'] ) : 1200;

$noorifa_core_wrapper_args = array( 'class' => 'columns-' . absint( $attributes['columns'] ) );

if ( $noorifa_core_boxed ) {
	$noorifa_core_wrapper_args['class'] .= ' is-boxed';
	$noorifa_core_wrapper_args['style']  = 'max-width:' . $noorifa_core_boxed_width . 'px';
}

$noorifa_core_wrapper = get_block_wrapper_attributes( $noorifa_core_wrapper_args );
?>
<div <?php echo $noorifa_core_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-escaped. ?>>
	<?php if ( ! $noorifa_core_query->have_posts() ) : ?>
		<p class="noorifa-core-product-grid__empty"><?php esc_html_e( 'No products found.', 'noorifa-core' ); ?></p>
	<?php endif; ?>

	<?php
	while ( $noorifa_core_query->have_posts() ) :
		$noorifa_core_query->the_post();

		$noorifa_core_product = wc_get_product( get_the_ID() );

		if ( ! $noorifa_core_product ) {
			continue;
		}
		?>
		<div class="noorifa-core-product-grid__item">
			<?php if ( $attributes['showImage'] ) : ?>
				<a class="noorifa-core-product-grid__image" href="<?php the_permalink(); ?>">
					<?php echo wp_kses_post( $noorifa_core_product->get_image( 'medium_large' ) ); ?>
				</a>
			<?php endif; ?>

			<h3 class="noorifa-core-product-grid__title">
				<a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
			</h3>

			<?php if ( $attributes['showPrice'] ) : ?>
				<div class="noorifa-core-product-grid__price">
					<?php echo wp_kses_post( $noorifa_core_product->get_price_html() ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showAddToCart'] ) : ?>
				<div class="noorifa-core-product-grid__add-to-cart">
					<?php
					echo wp_kses_post(
						apply_filters(
							'woocommerce_loop_add_to_cart_link', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own filter.
							sprintf(
								'<a href="%s" data-quantity="1" class="button product_type_%s add_to_cart_button ajax_add_to_cart" data-product_id="%s" aria-label="%s" rel="nofollow">%s</a>',
								esc_url( $noorifa_core_product->add_to_cart_url() ),
								esc_attr( $noorifa_core_product->get_type() ),
								esc_attr( $noorifa_core_product->get_id() ),
								esc_attr( $noorifa_core_product->add_to_cart_description() ),
								esc_html( $noorifa_core_product->add_to_cart_text() )
							),
							$noorifa_core_product,
							array()
						)
					);
					?>
				</div>
			<?php endif; ?>
		</div>
	<?php endwhile; ?>
</div>
<?php
wp_reset_postdata();
