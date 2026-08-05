<?php
/**
 * Bundled template source.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates\Sources;

use Noorifa\Core\Templates\Lazy_Source;
use Noorifa\Core\Patterns\Manager as Patterns_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the templates bundled with the plugin in the patterns directory.
 */
class Local_Source implements Lazy_Source {

	/**
	 * Returns the source identifier.
	 *
	 * @return string
	 */
	public function get_id() {
		return 'local';
	}

	/**
	 * Returns the bundled templates, as lightweight metadata.
	 *
	 * @return array[]
	 */
	public function get_templates() {
		return Patterns_Manager::instance()->get_library_templates();
	}

	/**
	 * Returns a single bundled template with its full content.
	 *
	 * @param string $name Template slug.
	 * @return array|null
	 */
	public function get_template( $name ) {
		return Patterns_Manager::instance()->get_template( $name );
	}

	/**
	 * Flushes the bundled pattern cache.
	 *
	 * @return void
	 */
	public function flush_cache() {
		Patterns_Manager::instance()->flush_cache();
	}
}
