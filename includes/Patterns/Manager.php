<?php
/**
 * Bundled JSON template registration manager.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Patterns;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Licensing\Gate;

defined( 'ABSPATH' ) || exit;

/**
 * Registers every premade template shipped in the plugin's
 * `templates/library/` folder, both as native Gutenberg block patterns and
 * as entries in the Noorifa Core library (Templates\Sources\Local_Source).
 *
 * (Not the plugin-root `templates/` folder itself — that also holds
 * `single-product-layout.php`, the unrelated frontend template file used to
 * render a resolved Layout; `library/` keeps demo-template JSON files out
 * of that folder.)
 *
 * Each template is a JSON file, e.g. `templates/library/product-layout-one.json`:
 *
 *     {
 *         "title": "Bold Product Layout",
 *         "description": "…",
 *         "type": "layout",
 *         "category": "bold",
 *         "is_pro": true,
 *         "content": "<!-- wp:noorifa-core/product-title /-->…"
 *     }
 *
 * `type` is required ('layout' or 'section'); `description`, `category` and
 * `is_pro` are optional (`category` falls back to `type`, `is_pro` falls
 * back to `false`). Content always ships with the template — this plugin
 * is a single codebase, nothing is stripped for a "Free" build (see
 * Licensing\Gate's own docblock for why). A `pro`-tier template's metadata
 * is listed for everyone (an upsell), and Rest\Templates_Controller
 * refuses to serve its full content unless Licensing\Gate::is_pro() is
 * true — the exact same shape as NoorQuiz's TemplatesController::use_template().
 * A thumbnail is picked up automatically from
 * `templates/library/thumbnails/{file name}.{png,jpg,webp}` when present —
 * no field needed in the JSON itself.
 *
 * Add-ons (e.g. Noorifa Core Pro) contribute their own template folder via
 * the `noorifa-core/pattern_dirs` filter. Every template found in such a
 * folder is always treated as `is_pro: true`, regardless of its own JSON
 * field — that folder only exists on a site because the add-on is
 * installed, so nothing in it is ever free.
 */
class Manager {

	use Singleton;

	/**
	 * Transient key the parsed template collection is cached under.
	 */
	const CACHE_KEY = 'noorifa_core_patterns';

	/**
	 * Block pattern category every bundled template is registered under.
	 */
	const PATTERN_CATEGORY = 'noorifa-core';

	/**
	 * Per-request memoized templates, keyed by name.
	 *
	 * @var array[]|null
	 */
	private $patterns = null;

	/**
	 * Hooks pattern registration.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_patterns' ) );
	}

	/**
	 * Returns the directory bundled template JSON files are scanned from.
	 *
	 * Filterable so an add-on can ship its own templates folder.
	 *
	 * @return string[] Absolute directory paths.
	 */
	private function get_pattern_dirs() {
		$dirs = array( NOORIFA_CORE_DIR . 'templates/library' );

		/**
		 * Filters the directories bundled template JSON files are scanned
		 * from.
		 *
		 * @param string[] $dirs Absolute directory paths.
		 */
		return (array) apply_filters( 'noorifa-core/pattern_dirs', $dirs );
	}

	/**
	 * Whether the current request can actually make use of registered
	 * block patterns.
	 *
	 * Registering patterns requires reading and decoding every template
	 * file, so this is skipped on front-end requests (e.g. WooCommerce
	 * product pages) where the pattern registry is never consulted.
	 *
	 * @return bool
	 */
	private function patterns_are_needed() {
		if ( is_admin() || wp_doing_ajax() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		/**
		 * Filters whether block patterns should be registered on requests
		 * that are neither admin, ajax, nor REST.
		 *
		 * @param bool $needed Whether patterns are needed.
		 */
		return (bool) apply_filters( 'noorifa-core/patterns_are_needed', false );
	}

	/**
	 * Registers the pattern category and every bundled template as a
	 * native block pattern.
	 *
	 * @return void
	 */
	public function register_patterns() {
		if ( ! $this->patterns_are_needed() ) {
			return;
		}

		register_block_pattern_category(
			self::PATTERN_CATEGORY,
			array( 'label' => __( 'Noorifa Core', 'noorifa-core' ) )
		);

		foreach ( $this->get_patterns() as $name => $pattern ) {
			// Native block patterns are inserted directly from the editor's
			// Patterns tab with no REST round-trip, so Rest\Templates_Controller's
			// gate never runs for this path — a Pro-tier template's real
			// content must never be registered here unless actually licensed.
			if ( ! empty( $pattern['is_pro'] ) && ! Gate::is_pro() ) {
				continue;
			}

			register_block_pattern( 'noorifa-core/' . $name, $pattern );
		}
	}

	/**
	 * Returns every template for the editor template library, as
	 * lightweight metadata (no `content`).
	 *
	 * @return array[] Each item: name, title, description, type
	 *                 (layout|section), category, is_pro (bool), thumbnail.
	 */
	public function get_library_templates() {
		$templates = array();

		foreach ( $this->get_patterns() as $name => $pattern ) {
			if ( empty( $pattern['type'] ) ) {
				continue;
			}

			$templates[] = array(
				'name'        => $name,
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'type'        => $pattern['type'],
				'category'    => $pattern['category'],
				'is_pro'      => $pattern['is_pro'],
				'thumbnail'   => $pattern['thumbnail'],
			);
		}

		return $templates;
	}

	/**
	 * Returns a single bundled template with its full content, or null.
	 *
	 * @param string $name Template file name without extension.
	 * @return array|null
	 */
	public function get_template( $name ) {
		$patterns = $this->get_patterns();

		if ( ! isset( $patterns[ $name ] ) || empty( $patterns[ $name ]['type'] ) ) {
			return null;
		}

		$pattern = $patterns[ $name ];

		return array(
			'name'        => $name,
			'title'       => $pattern['title'],
			'description' => $pattern['description'],
			'type'        => $pattern['type'],
			'category'    => $pattern['category'],
			'is_pro'      => $pattern['is_pro'],
			'thumbnail'   => $pattern['thumbnail'],
			'content'     => $pattern['content'],
		);
	}

	/**
	 * Returns the raw block markup of a template.
	 *
	 * Lets page templates be composed from section templates without
	 * duplicating markup.
	 *
	 * @param string $name Template file name without extension.
	 * @return string
	 */
	public static function get_section_content( $name ) {
		$patterns = self::instance()->get_patterns();

		return isset( $patterns[ $name ]['content'] ) ? $patterns[ $name ]['content'] : '';
	}

	/**
	 * Empties the cached template collection so the next request rescans
	 * every template file.
	 *
	 * @return void
	 */
	public function flush_cache() {
		$this->patterns = null;
		delete_transient( self::CACHE_KEY );
	}

	/**
	 * Returns every template, keyed by name, normalized with fallbacks for
	 * `description`, `category` and `thumbnail` and ready for
	 * register_block_pattern().
	 *
	 * Cached per-request in $this->patterns and across requests in a
	 * transient, invalidated whenever any template file's mtime changes.
	 *
	 * @return array[]
	 */
	private function get_patterns() {
		if ( null !== $this->patterns ) {
			return $this->patterns;
		}

		$fingerprint = $this->get_fingerprint();
		$cached      = get_transient( self::CACHE_KEY );

		if ( is_array( $cached ) && isset( $cached['fingerprint'], $cached['patterns'] ) && $cached['fingerprint'] === $fingerprint ) {
			$this->patterns = $cached['patterns'];
			return $this->patterns;
		}

		$patterns = $this->scan_patterns();

		set_transient(
			self::CACHE_KEY,
			array(
				'fingerprint' => $fingerprint,
				'patterns'    => $patterns,
			),
			DAY_IN_SECONDS
		);

		$this->patterns = $patterns;
		return $this->patterns;
	}

	/**
	 * Builds a fingerprint from every template file's and thumbnail's path
	 * and mtime, so editing a template, or just adding/replacing its
	 * thumbnail image, invalidates the cache immediately (not just
	 * adding/removing/renaming a `.json` file, which is all a directory
	 * mtime would reliably reflect).
	 *
	 * @return string
	 */
	private function get_fingerprint() {
		$stamp = '';

		foreach ( $this->get_pattern_dirs() as $dir ) {
			foreach ( $this->glob_pattern_files( $dir ) as $file ) {
				$stamp .= $file . filemtime( $file );
			}

			foreach ( $this->glob_thumbnail_files( $dir ) as $file ) {
				$stamp .= $file . filemtime( $file );
			}
		}

		return md5( $stamp );
	}

	/**
	 * Globs the *.json files directly inside a template directory.
	 *
	 * @param string $dir Absolute directory path.
	 * @return string[]
	 */
	private function glob_pattern_files( $dir ) {
		$files = glob( $dir . '/*.json' );

		return is_array( $files ) ? $files : array();
	}

	/**
	 * Globs every thumbnail image inside a template directory's
	 * `thumbnails/` subfolder.
	 *
	 * @param string $dir Absolute template directory.
	 * @return string[]
	 */
	private function glob_thumbnail_files( $dir ) {
		$files = array();

		foreach ( array( 'png', 'jpg', 'webp' ) as $ext ) {
			$matches = glob( $dir . '/thumbnails/*.' . $ext );

			if ( is_array( $matches ) ) {
				$files = array_merge( $files, $matches );
			}
		}

		return $files;
	}

	/**
	 * Reads and normalizes every template file.
	 *
	 * @return array[]
	 */
	private function scan_patterns() {
		$patterns = array();
		$own_dir  = NOORIFA_CORE_DIR . 'templates/library';

		foreach ( $this->get_pattern_dirs() as $dir ) {
			// Every directory contributed by an add-on (i.e. not this
			// plugin's own) only exists on a site because that add-on is
			// installed, so its templates are always Pro — regardless of
			// the JSON's own `is_pro` field, which an add-on author might
			// forget to set.
			$dir_is_pro = ( $dir !== $own_dir );

			foreach ( $this->glob_pattern_files( $dir ) as $file ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading this plugin's own bundled local template files, not a remote URL.
				$pattern = json_decode( (string) file_get_contents( $file ), true );

				if ( ! is_array( $pattern ) || empty( $pattern['content'] ) || empty( $pattern['type'] ) ) {
					continue;
				}

				$raw_name = basename( $file, '.json' );
				// The REST route matching a single template only accepts
				// `[a-z0-9_-]+` — a filename with spaces or uppercase (e.g.
				// "Supplement Product Layout.json") would otherwise produce
				// a name that route can never match, 404ing every time that
				// specific template is fetched.
				$name = sanitize_title( $raw_name );

				$pattern['title']       = isset( $pattern['title'] ) ? $pattern['title'] : $raw_name;
				$pattern['description'] = isset( $pattern['description'] ) ? $pattern['description'] : '';
				$pattern['category']    = isset( $pattern['category'] ) ? $pattern['category'] : $pattern['type'];
				$pattern['is_pro']      = $dir_is_pro || ! empty( $pattern['is_pro'] );
				$pattern['thumbnail']   = isset( $pattern['thumbnail'] ) ? $pattern['thumbnail'] : $this->guess_thumbnail( $dir, $raw_name );
				$pattern['categories']  = array( self::PATTERN_CATEGORY );

				$patterns[ $name ] = $pattern;
			}
		}

		return $patterns;
	}

	/**
	 * Auto-detects a thumbnail image co-located with a template file, in
	 * `{dir}/thumbnails/{name}.{png,jpg,webp}`.
	 *
	 * @param string $dir  Absolute template directory.
	 * @param string $name Template file name without extension.
	 * @return string Absolute URL, or '' when no thumbnail exists.
	 */
	private function guess_thumbnail( $dir, $name ) {
		foreach ( array( 'png', 'jpg', 'webp' ) as $ext ) {
			$path = $dir . '/thumbnails/' . $name . '.' . $ext;

			if ( is_readable( $path ) ) {
				return str_replace( wp_normalize_path( WP_CONTENT_DIR ), content_url(), wp_normalize_path( $path ) );
			}
		}

		return '';
	}
}
