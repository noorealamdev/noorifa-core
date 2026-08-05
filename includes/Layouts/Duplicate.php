<?php
/**
 * "Duplicate" row action for Product Layout posts.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Layouts;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a "Duplicate" link to each Product Layout's row actions, cloning
 * its title/content into a new draft so a merchant can safely experiment
 * on a copy instead of editing the original directly.
 */
class Duplicate {

	use Singleton;

	/**
	 * Hooks the row action and its handler.
	 */
	protected function __construct() {
		add_filter( 'post_row_actions', array( $this, 'add_row_action' ), 10, 2 );
		add_action( 'admin_action_noorifa_core_duplicate_layout', array( $this, 'handle_duplicate' ) );
	}

	/**
	 * Adds the "Duplicate" link to a Product Layout row.
	 *
	 * @param array    $actions Existing row actions.
	 * @param \WP_Post $post    The row's post.
	 * @return array
	 */
	public function add_row_action( $actions, $post ) {
		if ( Post_Type::SLUG !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$url = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'noorifa_core_duplicate_layout',
					'post'   => $post->ID,
				),
				admin_url( 'admin.php' )
			),
			'noorifa_core_duplicate_layout_' . $post->ID
		);

		$actions['noorifa_core_duplicate'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'Duplicate', 'noorifa-core' )
		);

		return $actions;
	}

	/**
	 * Clones a Product Layout into a new draft, then redirects to edit it.
	 *
	 * @return void
	 */
	public function handle_duplicate() {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

		if ( ! $post_id ) {
			wp_die( esc_html__( 'No layout specified.', 'noorifa-core' ) );
		}

		check_admin_referer( 'noorifa_core_duplicate_layout_' . $post_id );

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'You are not allowed to duplicate this layout.', 'noorifa-core' ) );
		}

		$source = get_post( $post_id );

		if ( ! $source || Post_Type::SLUG !== $source->post_type ) {
			wp_die( esc_html__( 'Layout not found.', 'noorifa-core' ) );
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => Post_Type::SLUG,
				/* translators: %s: original layout title. */
				'post_title'   => sprintf( __( '%s (Copy)', 'noorifa-core' ), $source->post_title ),
				'post_content' => $source->post_content,
				// Draft, not published/matching the original's status —
				// a duplicate is for safely experimenting on, and should
				// never silently start applying itself to any product.
				'post_status'  => 'draft',
				'post_author'  => get_current_user_id(),
			),
			true
		);

		if ( is_wp_error( $new_id ) ) {
			wp_die( esc_html( $new_id->get_error_message() ) );
		}

		wp_safe_redirect( get_edit_post_link( $new_id, 'raw' ) );
		exit;
	}
}
