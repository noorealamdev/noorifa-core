<?php
/**
 * Template repository.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates;

use Noorifa\Core\Traits\Singleton;
use Noorifa\Core\Templates\Sources\Local_Source;
use Noorifa\Core\Templates\Sources\Cloud_Source;

defined( 'ABSPATH' ) || exit;

/**
 * Aggregates every template source into the single collection the editor
 * template library consumes.
 */
class Repository {

	use Singleton;

	/**
	 * Returns all registered template sources.
	 *
	 * @return Source[]
	 */
	public function get_sources() {
		$sources = array(
			new Local_Source(),
			new Cloud_Source(),
		);

		/**
		 * Filters the registered template sources.
		 *
		 * Add-ons can append their own Source implementations.
		 *
		 * @param Source[] $sources Registered sources.
		 */
		$sources = apply_filters( 'noorifa-core/template_sources', $sources );

		return array_filter(
			(array) $sources,
			static function ( $source ) {
				return $source instanceof Source;
			}
		);
	}

	/**
	 * Returns the merged templates of every source, optionally filtered.
	 *
	 * Later sources cannot overwrite a name already provided by an earlier
	 * one, so bundled templates always win over remote ones.
	 *
	 * @param array $args {
	 *     Optional filters.
	 *
	 *     @type string $type     Only 'layout' or 'section'.
	 *     @type string $category Only this category slug.
	 *     @type string $search   Substring match against title/description.
	 * }
	 * @return array[]
	 */
	public function get_templates( array $args = array() ) {
		$templates = array();

		foreach ( $this->get_sources() as $source ) {
			foreach ( $source->get_templates() as $template ) {
				if ( empty( $template['name'] ) || isset( $templates[ $template['name'] ] ) ) {
					continue;
				}

				$template['source']             = $source->get_id();
				$templates[ $template['name'] ] = $template;
			}
		}

		/**
		 * Filters the final template collection served to the editor.
		 *
		 * @param array[] $templates Templates keyed by name.
		 */
		$templates = apply_filters( 'noorifa-core/library_templates', $templates );

		return array_values( $this->filter_templates( $templates, $args ) );
	}

	/**
	 * Returns a single template with its full content, or null when no
	 * source knows about it.
	 *
	 * @param string $name Template slug.
	 * @return array|null
	 */
	public function get_template( $name ) {
		foreach ( $this->get_sources() as $source ) {
			if ( $source instanceof Lazy_Source ) {
				$template = $source->get_template( $name );
			} else {
				$template = null;

				foreach ( $source->get_templates() as $candidate ) {
					if ( isset( $candidate['name'] ) && $name === $candidate['name'] ) {
						$template = $candidate;
						break;
					}
				}
			}

			if ( $template ) {
				$template['source'] = $source->get_id();

				return $template;
			}
		}

		return null;
	}

	/**
	 * Flushes every source cache so the next request refetches.
	 *
	 * @return void
	 */
	public function flush_caches() {
		foreach ( $this->get_sources() as $source ) {
			if ( method_exists( $source, 'flush_cache' ) ) {
				$source->flush_cache();
			}
		}
	}

	/**
	 * Narrows a template collection by type, category and search term.
	 *
	 * @param array[] $templates Templates keyed by name.
	 * @param array   $args      Filters, see get_templates().
	 * @return array[]
	 */
	private function filter_templates( array $templates, array $args ) {
		if ( ! empty( $args['type'] ) ) {
			$templates = array_filter(
				$templates,
				static function ( $template ) use ( $args ) {
					return isset( $template['type'] ) && $template['type'] === $args['type'];
				}
			);
		}

		if ( ! empty( $args['category'] ) ) {
			$templates = array_filter(
				$templates,
				static function ( $template ) use ( $args ) {
					return isset( $template['category'] ) && $template['category'] === $args['category'];
				}
			);
		}

		if ( ! empty( $args['search'] ) ) {
			$needle = strtolower( $args['search'] );

			$templates = array_filter(
				$templates,
				static function ( $template ) use ( $needle ) {
					$haystack = strtolower( ( $template['title'] ?? '' ) . ' ' . ( $template['description'] ?? '' ) );

					return false !== strpos( $haystack, $needle );
				}
			);
		}

		return $templates;
	}
}
