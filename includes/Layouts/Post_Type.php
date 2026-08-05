<?php
/**
 * Product Layout custom post type.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Admin\Dashboard;

defined( 'ABSPATH' ) || exit;

/**
 * Each Product Layout post holds the block markup for one alternate
 * single-product page arrangement. Layouts are edited in the standard
 * block editor (works on any theme) and applied to products via the
 * Resolver.
 */
class Post_Type {

	use Singleton;

	/**
	 * Post type slug.
	 */
	const SLUG = 'noorifa_core_layout';

	/**
	 * Hooks post type registration.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	/**
	 * Registers the Product Layout post type.
	 *
	 * @return void
	 */
	public function register() {
		register_post_type(
			self::SLUG,
			array(
				'labels'          => array(
					'name'          => __( 'Product Layouts', 'noorifa-core' ),
					'singular_name' => __( 'Product Layout', 'noorifa-core' ),
					'add_new_item'  => __( 'Add New Product Layout', 'noorifa-core' ),
					'edit_item'     => __( 'Edit Product Layout', 'noorifa-core' ),
					'all_items'     => __( 'Product Layouts', 'noorifa-core' ),
					'search_items'  => __( 'Search Product Layouts', 'noorifa-core' ),
					'not_found'     => __( 'No product layouts found.', 'noorifa-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => Dashboard::PAGE_SLUG,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'editor' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
			)
		);
	}
}
