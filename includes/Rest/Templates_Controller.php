<?php
/**
 * Templates REST controller.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Rest;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Templates\Repository;
use Noorifa\Core\Templates\Media_Sideloader;
use Noorifa\Core\Licensing\Gate;

defined( 'ABSPATH' ) || exit;

/**
 * Serves the template library to the editor.
 */
class Templates_Controller {

	use Singleton;

	/**
	 * REST namespace.
	 */
	const REST_NAMESPACE = 'noorifa-core/v1';

	/**
	 * Hooks route registration.
	 */
	protected function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the template library routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/templates',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'can_use_library' ),
					'args'                => array(
						'type'     => array(
							'type' => 'string',
							'enum' => array( 'layout', 'section' ),
						),
						'category' => array(
							'type' => 'string',
						),
						'search'   => array(
							'type' => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/templates/sync',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'sync_templates' ),
					'permission_callback' => array( $this, 'can_manage' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/templates/(?P<name>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_template' ),
					'permission_callback' => array( $this, 'can_use_library' ),
				),
			)
		);
	}

	/**
	 * Returns library templates as lightweight metadata, optionally
	 * filtered by type, category and/or search term.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response
	 */
	public function get_templates( $request ) {
		$args = array_intersect_key(
			$request->get_params(),
			array_flip( array( 'type', 'category', 'search' ) )
		);

		return rest_ensure_response( Repository::instance()->get_templates( $args ) );
	}

	/**
	 * Returns a single template with its full content.
	 *
	 * @param \WP_REST_Request $request Full data about the request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_template( $request ) {
		$template = Repository::instance()->get_template( $request['name'] );

		if ( ! $template ) {
			return new \WP_Error(
				'noorifa_core_template_not_found',
				__( 'Template not found.', 'noorifa-core' ),
				array( 'status' => 404 )
			);
		}

		// Mirrors NoorQuiz's TemplatesController::use_template() exactly:
		// the list stays unfiltered (an upsell — everyone sees Pro
		// templates exist), full content is refused without a license.
		if ( ! empty( $template['is_pro'] ) && ! Gate::is_pro() ) {
			return new \WP_Error(
				'noorifa_core_pro_required',
				__( 'This template requires Noorifa Core Pro.', 'noorifa-core' ),
				array( 'status' => 403 )
			);
		}

		// Any image the content references (this plugin's own bundled
		// samples, or a Cloud-source template's remote images) gets copied
		// into this site's real Media Library before the content is handed
		// to the editor, so imported content never depends on this plugin's
		// files or a third-party server still being there later.
		if ( ! empty( $template['content'] ) ) {
			$template['content'] = Media_Sideloader::rewrite_content( $template['content'] );
		}

		return rest_ensure_response( $template );
	}

	/**
	 * Flushes source caches and returns the refetched templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function sync_templates() {
		$repository = Repository::instance();
		$repository->flush_caches();

		return rest_ensure_response( $repository->get_templates() );
	}

	/**
	 * Whether the current user may read the template library.
	 *
	 * @return bool
	 */
	public function can_use_library() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Whether the current user may flush template caches.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_options' );
	}
}
