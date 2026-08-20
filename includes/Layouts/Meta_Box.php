<?php
/**
 * Product and product category Layout assignment UI.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Admin\Split_Test_Dashboard;
use Noorifa\Core\Split_Test\Bucketer;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Product Page Template" picker to the Product edit screen and a
 * default-template field to the product category edit/add screens.
 */
class Meta_Box {

	use Singleton;

	/**
	 * Hooks the meta box and taxonomy field registration.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_product', array( $this, 'save_meta_box' ) );

		add_action( 'product_cat_edit_form_fields', array( $this, 'render_term_edit_field' ) );
		add_action( 'product_cat_add_form_fields', array( $this, 'render_term_add_field' ) );
		add_action( 'edited_product_cat', array( $this, 'save_term_field' ) );
		add_action( 'created_product_cat', array( $this, 'save_term_field' ) );
	}

	/**
	 * Registers the product edit screen meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'noorifa-core-layout',
			__( 'Product Page Template', 'noorifa-core' ),
			array( $this, 'render_meta_box' ),
			'product',
			'side',
			'default'
		);
	}

	/**
	 * Returns every published Product Layout, for use in the select fields.
	 *
	 * @return \WP_Post[]
	 */
	private function get_layouts() {
		return get_posts(
			array(
				'post_type'     => Post_Type::SLUG,
				'post_status'   => 'publish',
				'numberposts'   => -1,
				'orderby'       => 'title',
				'order'         => 'ASC',
				'no_found_rows' => true,
			)
		);
	}

	/**
	 * Renders the layout <select> markup shared by both screens.
	 *
	 * @param string $name     Field name/id.
	 * @param int    $selected Currently selected layout ID.
	 * @return void
	 */
	private function render_select( $name, $selected ) {
		?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" style="width:100%;">
			<option value="0"><?php esc_html_e( '— Default (theme/WooCommerce) —', 'noorifa-core' ); ?></option>
			<?php foreach ( $this->get_layouts() as $layout ) : ?>
				<option value="<?php echo esc_attr( $layout->ID ); ?>" <?php selected( (int) $selected, $layout->ID ); ?>>
					<?php echo esc_html( $layout->post_title ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Renders the product edit screen meta box contents.
	 *
	 * @param \WP_Post $post Product post.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'noorifa_core_layout_meta', 'noorifa_core_layout_nonce' );

		if ( 'running' === get_post_meta( $post->ID, Bucketer::STATUS_META_KEY, true ) ) {
			$this->render_split_test_summary( $post->ID );
			return;
		}

		$selected = get_post_meta( $post->ID, Resolver::PRODUCT_META_KEY, true );
		?>
		<div id="noorifa-core-single-layout-fields">
			<?php $this->render_select( 'noorifa_core_layout_id', (int) $selected ); ?>
			<p class="description">
				<?php esc_html_e( 'Replace this product\'s page with a Noorifa Core layout.', 'noorifa-core' ); ?>
			</p>
		</div>

		<p>
			<label>
				<input type="checkbox" name="noorifa_core_split_test_enabled" id="noorifa-core-split-test-enabled" value="1" />
				<?php esc_html_e( 'Run this as an A/B split test instead', 'noorifa-core' ); ?>
			</label>
		</p>

		<div id="noorifa-core-split-test-fields" style="display:none;">
			<p>
				<label for="noorifa_core_split_test_layout_a"><?php esc_html_e( 'Layout A', 'noorifa-core' ); ?></label>
				<?php $this->render_select( 'noorifa_core_split_test_layout_a', 0 ); ?>
			</p>
			<p>
				<label for="noorifa_core_split_test_layout_b"><?php esc_html_e( 'Layout B', 'noorifa-core' ); ?></label>
				<?php $this->render_select( 'noorifa_core_split_test_layout_b', 0 ); ?>
			</p>
			<p class="description">
				<?php esc_html_e( 'Visitors will be split 50/50 between the two layouts. Track results and declare a winner from Split Tests.', 'noorifa-core' ); ?>
			</p>
		</div>

		<script>
		( function() {
			var checkbox = document.getElementById( 'noorifa-core-split-test-enabled' );
			var splitFields = document.getElementById( 'noorifa-core-split-test-fields' );
			var singleFields = document.getElementById( 'noorifa-core-single-layout-fields' );

			if ( ! checkbox || ! splitFields || ! singleFields ) {
				return;
			}

			checkbox.addEventListener( 'change', function() {
				splitFields.style.display = checkbox.checked ? '' : 'none';
				singleFields.style.display = checkbox.checked ? 'none' : '';
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Renders the read-only summary shown while a split test is running —
	 * lifecycle changes (ending the test) only happen from the Split Tests
	 * dashboard, so this meta box doesn't offer editable fields for one.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private function render_split_test_summary( $product_id ) {
		$layout_a_id = (int) get_post_meta( $product_id, Bucketer::LAYOUT_A_META_KEY, true );
		$layout_b_id = (int) get_post_meta( $product_id, Bucketer::LAYOUT_B_META_KEY, true );
		$layout_a    = $layout_a_id ? get_post( $layout_a_id ) : null;
		$layout_b    = $layout_b_id ? get_post( $layout_b_id ) : null;
		?>
		<p><strong><?php esc_html_e( 'A/B split test running', 'noorifa-core' ); ?></strong></p>
		<p>
			A &mdash; <?php echo esc_html( $layout_a ? $layout_a->post_title : __( '(deleted)', 'noorifa-core' ) ); ?><br />
			B &mdash; <?php echo esc_html( $layout_b ? $layout_b->post_title : __( '(deleted)', 'noorifa-core' ) ); ?>
		</p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . Split_Test_Dashboard::PAGE_SLUG ) ); ?>">
				<?php esc_html_e( 'View results & declare a winner', 'noorifa-core' ); ?> &rarr;
			</a>
		</p>
		<?php
	}

	/**
	 * Persists the product's chosen layout.
	 *
	 * @param int $post_id Product post ID.
	 * @return void
	 */
	public function save_meta_box( $post_id ) {
		if (
			! isset( $_POST['noorifa_core_layout_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noorifa_core_layout_nonce'] ) ), 'noorifa_core_layout_meta' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// A running test's own assignments only ever change from the Split
		// Tests dashboard (Declare Winner) — this meta box doesn't render
		// editable fields for one, so never touch them here.
		if ( 'running' === get_post_meta( $post_id, Bucketer::STATUS_META_KEY, true ) ) {
			return;
		}

		if ( ! empty( $_POST['noorifa_core_split_test_enabled'] ) ) {
			$layout_a = isset( $_POST['noorifa_core_split_test_layout_a'] ) ? absint( $_POST['noorifa_core_split_test_layout_a'] ) : 0;
			$layout_b = isset( $_POST['noorifa_core_split_test_layout_b'] ) ? absint( $_POST['noorifa_core_split_test_layout_b'] ) : 0;

			if (
				$layout_a && $layout_b && $layout_a !== $layout_b
				&& $this->is_published_layout( $layout_a ) && $this->is_published_layout( $layout_b )
			) {
				update_post_meta( $post_id, Bucketer::LAYOUT_A_META_KEY, $layout_a );
				update_post_meta( $post_id, Bucketer::LAYOUT_B_META_KEY, $layout_b );
				update_post_meta( $post_id, Bucketer::STATUS_META_KEY, 'running' );
				update_post_meta( $post_id, Bucketer::STARTED_META_KEY, time() );
				delete_post_meta( $post_id, Bucketer::ENDED_META_KEY );
				delete_post_meta( $post_id, Bucketer::WINNER_META_KEY );

				// A running split test resolves via its own two layouts,
				// not this single-layout field — clear it so Resolver's
				// normal fallback chain doesn't hold stale data underneath.
				delete_post_meta( $post_id, Resolver::PRODUCT_META_KEY );

				return;
			}
		}

		$layout_id = isset( $_POST['noorifa_core_layout_id'] ) ? absint( $_POST['noorifa_core_layout_id'] ) : 0;

		if ( $layout_id ) {
			update_post_meta( $post_id, Resolver::PRODUCT_META_KEY, $layout_id );
		} else {
			delete_post_meta( $post_id, Resolver::PRODUCT_META_KEY );
		}
	}

	/**
	 * Whether a layout ID points to a published Product Layout post.
	 *
	 * @param int $layout_id Layout post ID.
	 * @return bool
	 */
	private function is_published_layout( $layout_id ) {
		return $layout_id
			&& Post_Type::SLUG === get_post_type( $layout_id )
			&& 'publish' === get_post_status( $layout_id );
	}

	/**
	 * Renders the default-layout field on the category edit screen.
	 *
	 * @param \WP_Term $term Product category term.
	 * @return void
	 */
	public function render_term_edit_field( $term ) {
		$selected = get_term_meta( $term->term_id, Resolver::CATEGORY_META_KEY, true );
		wp_nonce_field( 'noorifa_core_term_layout_meta', 'noorifa_core_term_layout_nonce' );
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="noorifa_core_layout_id"><?php esc_html_e( 'Default Product Page Template', 'noorifa-core' ); ?></label>
			</th>
			<td>
				<?php $this->render_select( 'noorifa_core_layout_id', (int) $selected ); ?>
				<p class="description">
					<?php esc_html_e( 'Used for products in this category that do not have their own template selected.', 'noorifa-core' ); ?>
				</p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Renders the default-layout field on the add-category screen.
	 *
	 * @return void
	 */
	public function render_term_add_field() {
		wp_nonce_field( 'noorifa_core_term_layout_meta', 'noorifa_core_term_layout_nonce' );
		?>
		<div class="form-field">
			<label for="noorifa_core_layout_id"><?php esc_html_e( 'Default Product Page Template', 'noorifa-core' ); ?></label>
			<?php $this->render_select( 'noorifa_core_layout_id', 0 ); ?>
		</div>
		<?php
	}

	/**
	 * Persists the category's default layout.
	 *
	 * @param int $term_id Product category term ID.
	 * @return void
	 */
	public function save_term_field( $term_id ) {
		if (
			! isset( $_POST['noorifa_core_term_layout_nonce'], $_POST['noorifa_core_layout_id'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noorifa_core_term_layout_nonce'] ) ), 'noorifa_core_term_layout_meta' )
		) {
			return;
		}

		if ( ! current_user_can( 'manage_product_terms' ) ) {
			return;
		}

		$layout_id = absint( $_POST['noorifa_core_layout_id'] );

		if ( $layout_id ) {
			update_term_meta( $term_id, Resolver::CATEGORY_META_KEY, $layout_id );
		} else {
			delete_term_meta( $term_id, Resolver::CATEGORY_META_KEY );
		}
	}
}
