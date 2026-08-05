<?php
/**
 * Cloud template source.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates\Sources;

use Noorifa\Core\Templates\Lazy_Source;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches predesigned templates as JSON from a remote cloud API.
 *
 * The API must return a JSON array of template objects:
 *
 *     [
 *         {
 *             "name": "bold-product-page",
 *             "title": "Bold",
 *             "description": "…",
 *             "type": "layout",
 *             "category": "bold",
 *             "thumbnail": "https://example.com/thumbnails/bold.png",
 *             "content": "<!-- wp:noorifa-core/product-title /-->…"
 *         }
 *     ]
 *
 * `category` and `thumbnail` are optional; `category` falls back to `type`
 * and `thumbnail` falls back to an empty string (the editor renders a
 * placeholder icon instead).
 *
 * The endpoint URL is configured with the NOORIFA_CORE_CLOUD_URL constant or
 * the `noorifa-core/cloud_api_url` filter; the source is inactive until one
 * of them provides a URL. Responses are cached in a transient.
 */
class Cloud_Source implements Lazy_Source {

	/**
	 * Transient name for the cached cloud response.
	 */
	const CACHE_KEY = 'noorifa_core_cloud_templates';

	/**
	 * How long successful responses are cached.
	 */
	const CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * How long failures are cached, so an unreachable API cannot slow
	 * down every editor load.
	 */
	const FAILURE_CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Valid template types.
	 *
	 * @var string[]
	 */
	private static $types = array( 'layout', 'section' );

	/**
	 * Returns the source identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'cloud';
	}

	/**
	 * Returns the cloud templates as lightweight metadata (no `content`),
	 * from cache when available.
	 *
	 * @return array[]
	 */
	public function get_templates() {
		return array_map(
			static function ( $template ) {
				unset( $template['content'] );

				return $template;
			},
			$this->get_cached_or_fetch()
		);
	}

	/**
	 * Returns a single cloud template with its full content.
	 *
	 * @param string $name Template slug.
	 * @return array|null
	 */
	public function get_template( $name ) {
		foreach ( $this->get_cached_or_fetch() as $template ) {
			if ( isset( $template['name'] ) && $name === $template['name'] ) {
				return $template;
			}
		}

		return null;
	}

	/**
	 * Returns the full cloud templates (with `content`), from cache when
	 * available.
	 *
	 * @return array[]
	 */
	private function get_cached_or_fetch() {
		$url = $this->get_api_url();

		if ( ! $url ) {
			return array();
		}

		$cached = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$templates = $this->fetch( $url );

		set_transient(
			self::CACHE_KEY,
			$templates,
			empty( $templates ) ? self::FAILURE_CACHE_TTL : self::CACHE_TTL
		);

		return $templates;
	}

	/**
	 * Empties the cached cloud response.
	 *
	 * @return void
	 */
	public function flush_cache() {
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Returns the configured cloud API URL, or an empty string when the
	 * cloud source is disabled.
	 *
	 * @return string
	 */
	private function get_api_url() {
		$url = defined( 'NOORIFA_CORE_CLOUD_URL' ) ? NOORIFA_CORE_CLOUD_URL : '';

		/**
		 * Filters the cloud template API URL.
		 *
		 * @param string $url The API URL; empty string disables the source.
		 */
		return (string) apply_filters( 'noorifa-core/cloud_api_url', $url );
	}

	/**
	 * Requests and sanitizes the templates from the API.
	 *
	 * @param string $url API endpoint.
	 * @return array[]
	 */
	private function fetch( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		return $this->sanitize( $data );
	}

	/**
	 * Keeps only well-formed template items and normalizes their fields.
	 *
	 * @param array $data Decoded API response.
	 * @return array[]
	 */
	private function sanitize( $data ) {
		$templates = array();

		foreach ( $data as $item ) {
			if (
				! is_array( $item )
				|| empty( $item['name'] ) || ! is_string( $item['name'] )
				|| empty( $item['title'] ) || ! is_string( $item['title'] )
				|| empty( $item['content'] ) || ! is_string( $item['content'] )
				|| empty( $item['type'] ) || ! in_array( $item['type'], self::$types, true )
			) {
				continue;
			}

			$templates[] = array(
				'name'        => sanitize_key( $item['name'] ),
				'title'       => sanitize_text_field( $item['title'] ),
				'description' => isset( $item['description'] ) && is_string( $item['description'] )
					? sanitize_text_field( $item['description'] )
					: '',
				'type'        => $item['type'],
				'category'    => isset( $item['category'] ) && is_string( $item['category'] )
					? sanitize_key( $item['category'] )
					: $item['type'],
				'thumbnail'   => isset( $item['thumbnail'] ) && is_string( $item['thumbnail'] )
					? esc_url_raw( $item['thumbnail'] )
					: '',
				'content'     => $item['content'],
			);
		}

		return $templates;
	}
}
