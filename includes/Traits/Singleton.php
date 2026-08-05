<?php
/**
 * Singleton trait.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Provides a shared instance() accessor for service classes.
 */
trait Singleton {

	/**
	 * Shared instance.
	 *
	 * @var static|null
	 */
	private static $instance = null;

	/**
	 * Returns the shared instance, creating it on first call.
	 *
	 * @return static
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Constructor is protected; use instance().
	 */
	protected function __construct() {
	}

	/**
	 * Prevent cloning.
	 */
	private function __clone() {
	}
}
