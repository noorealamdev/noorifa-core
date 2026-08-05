<?php
/**
 * Plugin Name:       Noorifa Core
 * Description:       The business-logic layer of the Noorifa Theme — pairs with the Noorifa theme to build custom WooCommerce single product pages from premade templates, using blocks that wrap WooCommerce's own rendering.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Noor E Alam
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       noorifa-core
 *
 * @package Noorifa Core
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'NOORIFA_CORE_VERSION', '1.0.0' );
define( 'NOORIFA_CORE_FILE', __FILE__ );
define( 'NOORIFA_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NOORIFA_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'NOORIFA_CORE_BASENAME', plugin_basename( __FILE__ ) );

define( 'NOORIFA_CORE_CHECKOUT_URL', 'https://checkout.freemius.com/plugin/35066/plan/57625/' );

// Register the PSR-4 autoloader.
require_once NOORIFA_CORE_DIR . 'includes/Autoloader.php';
\Noorifa\Core\Autoloader::register();

// Lifecycle hooks.
register_activation_hook( __FILE__, array( '\Noorifa\Core\Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Noorifa\Core\Install', 'deactivate' ) );

/**
 * Returns the main plugin instance.
 *
 * @return \Noorifa\Core\Plugin
 */
function noorifa_core() {
	return \Noorifa\Core\Plugin::instance();
}

// Boot the plugin once all active plugins (including WooCommerce) have loaded.
add_action( 'plugins_loaded', 'noorifa_core' );
