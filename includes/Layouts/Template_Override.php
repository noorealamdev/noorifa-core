<?php
/**
 * Frontend single-product template override.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Swaps in the plugin's single-product template when the current product
 * resolves to a Product Layout, leaving every other request (and every
 * product without a resolved layout) completely untouched.
 */
class Template_Override {

	use Singleton;

	/**
	 * Hooks the template selection filter.
	 */
	protected function __construct() {
		// Priority 100: must run after WooCommerce's own `template_loader()`
		// (class-wc-template-loader.php), which unconditionally recomputes
		// `$template` for product pages and would otherwise discard our
		// override regardless of plugin load order.
		add_filter( 'template_include', array( $this, 'maybe_override_template' ), 100 );
	}

	/**
	 * Returns the plugin's layout template when applicable.
	 *
	 * @param string $template The template WordPress resolved.
	 * @return string
	 */
	public function maybe_override_template( $template ) {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return $template;
		}

		$layout_id = Resolver::instance()->get_layout_id( get_queried_object_id() );

		if ( ! $layout_id ) {
			return $template;
		}

		return NOORIFA_CORE_DIR . 'templates/single-product-layout.php';
	}
}
