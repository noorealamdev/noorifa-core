<?php
/**
 * Admin dashboard page.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Admin;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Blocks\Manager as Blocks_Manager;
use Noorifa\Core\Layouts\Post_Type as Layouts_Post_Type;
use Noorifa\Core\Layouts\Resolver as Layouts_Resolver;

defined( 'ABSPATH' ) || exit;

/**
 * Settings page where individual blocks can be toggled on or off.
 */
class Dashboard {

	use Singleton;

	/**
	 * Admin page slug.
	 */
	const PAGE_SLUG = 'noorifa-core';

	/**
	 * Hooks the admin menu and settings handling.
	 */
	protected function __construct() {
		// Priority 20 so an active Noorifa theme's top-level "Noorifa" menu
		// (registered at priority 10/11) already exists when we attach to it.
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'handle_save' ) );
	}

	/**
	 * Adds the Noorifa Core top-level admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		// Under an active Noorifa theme, attach as a submenu of its shared
		// top-level "Noorifa" menu instead of registering a separate one, so
		// all Noorifa admin lives in one place (Theme Settings, Product
		// Layouts, Core Settings, Migrate URLs).
		if ( defined( 'NOORIFA_ADMIN_MENU_SLUG' ) ) {
			add_submenu_page(
				NOORIFA_ADMIN_MENU_SLUG,
				__( 'Noorifa Core Settings', 'noorifa-core' ),
				__( 'Core Settings', 'noorifa-core' ),
				'manage_options',
				self::PAGE_SLUG,
				array( $this, 'render_page' )
			);
			return;
		}

		// Standalone (a non-Noorifa theme): keep our own top-level menu.
		add_menu_page(
			__( 'Noorifa Core', 'noorifa-core' ),
			__( 'Noorifa Core', 'noorifa-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-layout',
			59
		);

		// add_menu_page() alone doesn't make the top-level menu link open
		// this page — without a submenu explicitly matching the parent
		// slug, WordPress instead links it to whichever submenu registers
		// first under 'noorifa-core' (the Product Layouts CPT). This
		// duplicate submenu is the standard WordPress fix for that.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Noorifa Core', 'noorifa-core' ),
			__( 'Core Settings', 'noorifa-core' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Persists the block toggles when the settings form is submitted.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! isset( $_POST['noorifa_core_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'noorifa_core_settings' );

		$all_blocks = array_keys( Blocks_Manager::instance()->get_all_blocks() );
		$enabled    = isset( $_POST['noorifa_core_enabled'] )
			? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['noorifa_core_enabled'] ) )
			: array();

		// Everything not checked is disabled.
		$disabled = array_values( array_diff( $all_blocks, $enabled ) );

		update_option( Blocks_Manager::DISABLED_OPTION, $disabled );

		$default_layout_id = isset( $_POST['noorifa_core_default_layout_id'] )
			? absint( $_POST['noorifa_core_default_layout_id'] )
			: 0;

		if ( $default_layout_id ) {
			update_option( Layouts_Resolver::DEFAULT_OPTION, $default_layout_id );
		} else {
			delete_option( Layouts_Resolver::DEFAULT_OPTION );
		}

		add_settings_error(
			'noorifa-core',
			'settings_saved',
			__( 'Settings saved.', 'noorifa-core' ),
			'success'
		);
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_page() {
		$blocks         = Blocks_Manager::instance()->get_all_blocks();
		$disabled       = (array) get_option( Blocks_Manager::DISABLED_OPTION, array() );
		$default_layout = (int) get_option( Layouts_Resolver::DEFAULT_OPTION, 0 );
		$layouts        = get_posts(
			array(
				'post_type'     => Layouts_Post_Type::SLUG,
				'post_status'   => 'publish',
				'numberposts'   => -1,
				'orderby'       => 'title',
				'order'         => 'ASC',
				'no_found_rows' => true,
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Noorifa Core', 'noorifa-core' ); ?></h1>
			<p><?php esc_html_e( 'Enable or disable individual blocks. Disabled blocks are removed from the editor but existing content is not deleted.', 'noorifa-core' ); ?></p>

			<?php settings_errors( 'noorifa-core' ); ?>

			<form method="post">
				<?php wp_nonce_field( 'noorifa_core_settings' ); ?>

				<h2><?php esc_html_e( 'Default Product Page Template', 'noorifa-core' ); ?></h2>
				<p><?php esc_html_e( 'Applied to every product that does not have its own template or a category default.', 'noorifa-core' ); ?></p>
				<p>
					<select name="noorifa_core_default_layout_id">
						<option value="0"><?php esc_html_e( '— Default (theme/WooCommerce) —', 'noorifa-core' ); ?></option>
						<?php foreach ( $layouts as $layout ) : ?>
							<option value="<?php echo esc_attr( $layout->ID ); ?>" <?php selected( $default_layout, $layout->ID ); ?>>
								<?php echo esc_html( $layout->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>

				<table class="widefat striped" style="max-width: 720px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Block', 'noorifa-core' ); ?></th>
							<th><?php esc_html_e( 'Description', 'noorifa-core' ); ?></th>
							<th style="width: 100px;"><?php esc_html_e( 'Enabled', 'noorifa-core' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( empty( $blocks ) ) : ?>
							<tr>
								<td colspan="3"><?php esc_html_e( 'No blocks found. Run the build first (npm run build).', 'noorifa-core' ); ?></td>
							</tr>
						<?php endif; ?>
						<?php foreach ( $blocks as $name => $metadata ) : ?>
							<tr>
								<td><strong><?php echo esc_html( isset( $metadata['title'] ) ? $metadata['title'] : $name ); ?></strong></td>
								<td><?php echo esc_html( isset( $metadata['description'] ) ? $metadata['description'] : '' ); ?></td>
								<td>
									<input
										type="checkbox"
										name="noorifa_core_enabled[]"
										value="<?php echo esc_attr( $name ); ?>"
										<?php checked( ! in_array( $name, $disabled, true ) ); ?>
									/>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<button type="submit" name="noorifa_core_save_settings" value="1" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'noorifa-core' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}
}
