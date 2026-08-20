<?php
/**
 * Main plugin class.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Blocks\Manager as Blocks_Manager;
use Noorifa\Core\Blocks\Interactivity;
use Noorifa\Core\Blocks\Extensions as Blocks_Extensions;
use Noorifa\Core\Blocks\Buy_Now;
use Noorifa\Core\Blocks\Inline_Checkout;
use Noorifa\Core\Patterns\Manager as Patterns_Manager;
use Noorifa\Core\Assets\Manager as Assets_Manager;
use Noorifa\Core\Rest\Templates_Controller;
use Noorifa\Core\Rest\Product_Reviews_Controller;
use Noorifa\Core\Admin\Dashboard;
use Noorifa\Core\Layouts\Post_Type as Layouts_Post_Type;
use Noorifa\Core\Layouts\Meta_Box as Layouts_Meta_Box;
use Noorifa\Core\Layouts\Template_Override as Layouts_Template_Override;
use Noorifa\Core\Layouts\Duplicate as Layouts_Duplicate;
use Noorifa\Core\Layouts\Preview as Layouts_Preview;
use Noorifa\Core\Layouts\Split_Test_Router;
use Noorifa\Core\Split_Test\Conversion_Tracker as Split_Test_Conversion_Tracker;
use Noorifa\Core\Admin\Split_Test_Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Boots every plugin service.
 */
class Plugin {

	use Singleton;

	/**
	 * Wires up all services.
	 */
	protected function __construct() {
		if ( ! $this->has_woocommerce() ) {
			add_action( 'admin_notices', array( $this, 'render_missing_woocommerce_notice' ) );
			return;
		}

		$this->init_services();
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	private function has_woocommerce() {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Warns the site admin that WooCommerce is required.
	 *
	 * @return void
	 */
	public function render_missing_woocommerce_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php
				esc_html_e(
					'Noorifa Core requires WooCommerce to be installed and active.',
					'noorifa-core'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Instantiates the service managers.
	 *
	 * @return void
	 */
	private function init_services() {
		Install::init();

		Blocks_Manager::instance();
		Interactivity::instance();
		Blocks_Extensions::instance();
		Buy_Now::instance();
		Inline_Checkout::instance();
		Patterns_Manager::instance();
		Assets_Manager::instance();
		Templates_Controller::instance();
		Product_Reviews_Controller::instance();

		Layouts_Post_Type::instance();
		Layouts_Meta_Box::instance();
		Layouts_Template_Override::instance();
		Layouts_Duplicate::instance();
		Layouts_Preview::instance();
		Split_Test_Router::instance();
		Split_Test_Conversion_Tracker::instance();

		if ( is_admin() ) {
			Dashboard::instance();
			Split_Test_Dashboard::instance();
		}

		/**
		 * Fires after every core service booted.
		 *
		 * Add-ons can hook here to register their own services.
		 *
		 * @param Plugin $plugin The plugin instance.
		 */
		do_action( 'noorifa-core/loaded', $this );
	}
}
