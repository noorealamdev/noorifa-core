<?php
/**
 * Split test results dashboard and winner declaration.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Admin;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Layouts\Resolver as Layouts_Resolver;
use Noorifa\Core\Split_Test\Bucketer;
use Noorifa\Core\Split_Test\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Lists every product's active and past A/B split tests with per-variant
 * impressions/add-to-cart/purchase stats, and lets an admin manually end a
 * test by declaring one of its two layouts the winner.
 */
class Split_Test_Dashboard {

	use Singleton;

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'noorifa-core-split-tests';

	/**
	 * Hooks the admin menu and the winner-declaration handler.
	 */
	protected function __construct() {
		// Priority 21: after Dashboard's own priority-20 registration, so
		// its fallback top-level menu already exists when no Noorifa theme
		// is active to provide the shared parent menu.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 21 );
		add_action( 'admin_init', array( $this, 'handle_declare_winner' ) );
	}

	/**
	 * Adds the "Split Tests" submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		$parent_slug = defined( 'NOORIFA_ADMIN_MENU_SLUG' ) ? NOORIFA_ADMIN_MENU_SLUG : Dashboard::PAGE_SLUG;

		add_submenu_page(
			$parent_slug,
			__( 'Split Tests', 'noorifa-core' ),
			__( 'Split Tests', 'noorifa-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Ends a running test by setting one of its two layouts as the
	 * product's normal, single resolved layout.
	 *
	 * @return void
	 */
	public function handle_declare_winner() {
		if ( ! isset( $_POST['noorifa_core_declare_winner'] ) ) {
			return;
		}

		$product_id = isset( $_POST['noorifa_core_split_test_product_id'] ) ? absint( $_POST['noorifa_core_split_test_product_id'] ) : 0;
		$winner_id  = isset( $_POST['noorifa_core_split_test_winner_id'] ) ? absint( $_POST['noorifa_core_split_test_winner_id'] ) : 0;

		if ( ! $product_id || ! $winner_id ) {
			return;
		}

		check_admin_referer( 'noorifa_core_declare_winner_' . $product_id );

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		list( $layout_a, $layout_b ) = Bucketer::instance()->get_variant_ids( $product_id );

		if ( $winner_id !== $layout_a && $winner_id !== $layout_b ) {
			return;
		}

		update_post_meta( $product_id, Layouts_Resolver::PRODUCT_META_KEY, $winner_id );
		update_post_meta( $product_id, Bucketer::STATUS_META_KEY, 'ended' );
		update_post_meta( $product_id, Bucketer::ENDED_META_KEY, time() );
		update_post_meta( $product_id, Bucketer::WINNER_META_KEY, $winner_id );

		wp_safe_redirect(
			add_query_arg(
				'noorifa_core_winner_declared',
				'1',
				admin_url( 'admin.php?page=' . self::PAGE_SLUG )
			)
		);
		exit;
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_page() {
		$active = $this->get_products_by_status( 'running' );
		$past   = $this->get_products_by_status( 'ended' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Split Tests', 'noorifa-core' ); ?></h1>

			<?php if ( isset( $_GET['noorifa_core_winner_declared'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Winner declared. The product now uses the winning layout for every visitor.', 'noorifa-core' ); ?></p>
				</div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'If you use a page-caching plugin, exclude products under an active split test from the cache (or configure it to vary by cookie) — otherwise every visitor may be served whichever layout happened to be cached, instead of their own assigned variant.', 'noorifa-core' ); ?>
			</p>

			<h2><?php esc_html_e( 'Active tests', 'noorifa-core' ); ?></h2>
			<?php if ( empty( $active ) ) : ?>
				<p><?php esc_html_e( 'No split tests are currently running.', 'noorifa-core' ); ?></p>
			<?php else : ?>
				<?php foreach ( $active as $product ) : ?>
					<?php $this->render_test_card( $product, true ); ?>
				<?php endforeach; ?>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Past tests', 'noorifa-core' ); ?></h2>
			<?php if ( empty( $past ) ) : ?>
				<p><?php esc_html_e( 'No split tests have been completed yet.', 'noorifa-core' ); ?></p>
			<?php else : ?>
				<?php foreach ( $past as $product ) : ?>
					<?php $this->render_test_card( $product, false ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Returns every product whose split test is in the given status.
	 *
	 * @param string $status 'running' or 'ended'.
	 * @return \WP_Post[]
	 */
	private function get_products_by_status( $status ) {
		return get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small, admin-only, infrequent query.
					array(
						'key'   => Bucketer::STATUS_META_KEY,
						'value' => $status,
					),
				),
			)
		);
	}

	/**
	 * Renders one product's test card: a variant comparison table plus,
	 * for an active test, a "Declare winner" button per variant.
	 *
	 * @param \WP_Post $product   Product post.
	 * @param bool     $is_active Whether the test is still running.
	 * @return void
	 */
	private function render_test_card( $product, $is_active ) {
		$product_id = $product->ID;

		list( $layout_a_id, $layout_b_id ) = Bucketer::instance()->get_variant_ids( $product_id );

		$winner_id = (int) get_post_meta( $product_id, Bucketer::WINNER_META_KEY, true );

		$variants = array(
			'A' => array( get_post( $layout_a_id ), $layout_a_id, Stats::instance()->get_totals( $product_id, $layout_a_id ) ),
			'B' => array( get_post( $layout_b_id ), $layout_b_id, Stats::instance()->get_totals( $product_id, $layout_b_id ) ),
		);

		$cart_confidence = $this->compute_confidence(
			$variants['A'][2][ Stats::EVENT_ADD_TO_CART ],
			$variants['A'][2][ Stats::EVENT_IMPRESSION ],
			$variants['B'][2][ Stats::EVENT_ADD_TO_CART ],
			$variants['B'][2][ Stats::EVENT_IMPRESSION ]
		);

		$purchase_confidence = $this->compute_confidence(
			$variants['A'][2][ Stats::EVENT_PURCHASE ],
			$variants['A'][2][ Stats::EVENT_IMPRESSION ],
			$variants['B'][2][ Stats::EVENT_PURCHASE ],
			$variants['B'][2][ Stats::EVENT_IMPRESSION ]
		);
		?>
		<div class="card" style="max-width:900px; padding:16px; margin-bottom:20px;">
			<h3>
				<a href="<?php echo esc_url( get_edit_post_link( $product_id ) ); ?>"><?php echo esc_html( $product->post_title ); ?></a>
			</h3>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Variant', 'noorifa-core' ); ?></th>
						<th><?php esc_html_e( 'Impressions', 'noorifa-core' ); ?></th>
						<th><?php esc_html_e( 'Add to cart', 'noorifa-core' ); ?></th>
						<th><?php esc_html_e( 'Purchases', 'noorifa-core' ); ?></th>
						<?php if ( $is_active ) : ?>
							<th></th>
						<?php endif; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $variants as $label => $variant ) : ?>
						<?php list( $layout, $layout_id, $totals ) = $variant; ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $label ); ?></strong>
								&mdash; <?php echo esc_html( $layout ? $layout->post_title : __( '(deleted)', 'noorifa-core' ) ); ?>
								<?php if ( $winner_id && $winner_id === $layout_id ) : ?>
									<span class="dashicons dashicons-awards" title="<?php esc_attr_e( 'Winner', 'noorifa-core' ); ?>"></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( number_format_i18n( $totals[ Stats::EVENT_IMPRESSION ] ) ); ?></td>
							<td><?php echo esc_html( $this->format_rate( $totals[ Stats::EVENT_ADD_TO_CART ], $totals[ Stats::EVENT_IMPRESSION ] ) ); ?></td>
							<td><?php echo esc_html( $this->format_rate( $totals[ Stats::EVENT_PURCHASE ], $totals[ Stats::EVENT_IMPRESSION ] ) ); ?></td>
							<?php if ( $is_active ) : ?>
								<td>
									<form method="post">
										<?php wp_nonce_field( 'noorifa_core_declare_winner_' . $product_id ); ?>
										<input type="hidden" name="noorifa_core_split_test_product_id" value="<?php echo esc_attr( $product_id ); ?>" />
										<input type="hidden" name="noorifa_core_split_test_winner_id" value="<?php echo esc_attr( $layout_id ); ?>" />
										<button
											type="submit"
											name="noorifa_core_declare_winner"
											value="1"
											class="button"
											onclick="return confirm('<?php echo esc_js( __( 'Declare this layout the winner? The test will end and every visitor will see it from now on.', 'noorifa-core' ) ); ?>');"
										>
											<?php esc_html_e( 'Declare winner', 'noorifa-core' ); ?>
										</button>
									</form>
								</td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ( $cart_confidence && $cart_confidence['leader'] ) || ( $purchase_confidence && $purchase_confidence['leader'] ) ) : ?>
				<p class="description">
					<?php if ( $cart_confidence && $cart_confidence['leader'] ) : ?>
						<?php
						printf(
							/* translators: 1: variant letter, 2: confidence percentage. */
							esc_html__( 'Add-to-cart: Layout %1$s is ahead with %2$s%% confidence.', 'noorifa-core' ),
							esc_html( 'a' === $cart_confidence['leader'] ? 'A' : 'B' ),
							esc_html( number_format_i18n( $cart_confidence['confidence'] * 100, 1 ) )
						);
						?>
						<br />
					<?php endif; ?>
					<?php if ( $purchase_confidence && $purchase_confidence['leader'] ) : ?>
						<?php
						printf(
							/* translators: 1: variant letter, 2: confidence percentage. */
							esc_html__( 'Purchases: Layout %1$s is ahead with %2$s%% confidence.', 'noorifa-core' ),
							esc_html( 'a' === $purchase_confidence['leader'] ? 'A' : 'B' ),
							esc_html( number_format_i18n( $purchase_confidence['confidence'] * 100, 1 ) )
						);
						?>
					<?php endif; ?>
				</p>
			<?php elseif ( $is_active ) : ?>
				<p class="description"><?php esc_html_e( 'Not enough data yet to estimate confidence. Let the test run longer — checking too often and declaring a winner the moment a number looks good raises the chance of a false call.', 'noorifa-core' ); ?></p>
			<?php endif; ?>

			<?php if ( ! $is_active ) : ?>
				<?php $ended = (int) get_post_meta( $product_id, Bucketer::ENDED_META_KEY, true ); ?>
				<?php if ( $ended ) : ?>
					<p class="description">
						<?php
						printf(
							/* translators: %s: formatted date. */
							esc_html__( 'Ended %s.', 'noorifa-core' ),
							esc_html( date_i18n( get_option( 'date_format' ), $ended ) )
						);
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Formats a "count (rate%)" cell, or just the count when there are no
	 * impressions yet to compute a rate against.
	 *
	 * @param int $conversions Conversion count.
	 * @param int $impressions Impression count.
	 * @return string
	 */
	private function format_rate( $conversions, $impressions ) {
		if ( ! $impressions ) {
			return (string) $conversions;
		}

		return sprintf( '%d (%s%%)', $conversions, number_format_i18n( ( $conversions / $impressions ) * 100, 1 ) );
	}

	/**
	 * Runs a two-proportion z-test between the two variants' conversion
	 * rates for one event type, returning null when there isn't enough
	 * data yet to say anything meaningful.
	 *
	 * @param int $conversions_a Variant A conversions.
	 * @param int $impressions_a Variant A impressions.
	 * @param int $conversions_b Variant B conversions.
	 * @param int $impressions_b Variant B impressions.
	 * @return array{confidence: float, leader: string|null}|null
	 */
	private function compute_confidence( $conversions_a, $impressions_a, $conversions_b, $impressions_b ) {
		if ( $impressions_a < 30 || $impressions_b < 30 ) {
			return null;
		}

		$rate_a = $conversions_a / $impressions_a;
		$rate_b = $conversions_b / $impressions_b;
		$pooled = ( $conversions_a + $conversions_b ) / ( $impressions_a + $impressions_b );

		$standard_error = sqrt( $pooled * ( 1 - $pooled ) * ( 1 / $impressions_a + 1 / $impressions_b ) );

		if ( ! $standard_error ) {
			return null;
		}

		$z_score = ( $rate_a - $rate_b ) / $standard_error;
		$p_value = 2 * ( 1 - $this->normal_cdf( abs( $z_score ) ) );

		return array(
			'confidence' => 1 - $p_value,
			'leader'     => $rate_a === $rate_b ? null : ( $rate_a > $rate_b ? 'a' : 'b' ),
		);
	}

	/**
	 * Standard normal cumulative distribution function, via the
	 * Abramowitz-Stegun approximation of the error function — no external
	 * stats library needed for this single use.
	 *
	 * @param float $z Z-score.
	 * @return float
	 */
	private function normal_cdf( $z ) {
		return 0.5 * ( 1 + $this->erf( $z / sqrt( 2 ) ) );
	}

	/**
	 * Error function approximation (Abramowitz-Stegun 7.1.26).
	 *
	 * @param float $x Input.
	 * @return float
	 */
	private function erf( $x ) {
		$sign = $x < 0 ? -1 : 1;
		$x    = abs( $x );

		$a1 = 0.254829592;
		$a2 = -0.284496736;
		$a3 = 1.421413741;
		$a4 = -1.453152027;
		$a5 = 1.061405429;
		$p  = 0.3275911;

		$t = 1.0 / ( 1.0 + $p * $x );
		$y = 1.0 - ( ( ( ( ( $a5 * $t + $a4 ) * $t ) + $a3 ) * $t + $a2 ) * $t + $a1 ) * $t * exp( -$x * $x );

		return $sign * $y;
	}
}
