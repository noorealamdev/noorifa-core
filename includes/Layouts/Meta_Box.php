<?php
/**
 * Product and product category Layout assignment UI.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;

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

		$selected = get_post_meta( $post->ID, Resolver::PRODUCT_META_KEY, true );

		$this->render_select( 'noorifa_core_layout_id', (int) $selected );
		?>
		<p class="description">
			<?php esc_html_e( 'Replace this product\'s page with a Noorifa Core layout.', 'noorifa-core' ); ?>
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

		$layout_id = isset( $_POST['noorifa_core_layout_id'] ) ? absint( $_POST['noorifa_core_layout_id'] ) : 0;

		if ( $layout_id ) {
			update_post_meta( $post_id, Resolver::PRODUCT_META_KEY, $layout_id );
		} else {
			delete_post_meta( $post_id, Resolver::PRODUCT_META_KEY );
		}
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
