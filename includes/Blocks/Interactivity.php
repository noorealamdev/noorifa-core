<?php
/**
 * Server-side Interactivity API state.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Blocks;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the PHP side of the derived state used by interactive blocks.
 *
 * Each derived state getter defined in a block's view.js must have a PHP
 * twin here so WordPress can evaluate the data-wp-bind directives during
 * server rendering — otherwise the initial HTML would not reflect the
 * correct closed/active states until the module hydrates.
 */
class Interactivity {

	use Singleton;

	/**
	 * Hooks state registration.
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'register_state' ) );
	}

	/**
	 * Registers derived state for every interactive block.
	 *
	 * @return void
	 */
	public function register_state() {
		if ( ! function_exists( 'wp_interactivity_state' ) ) {
			return;
		}

		wp_interactivity_state(
			'noorifa-core/accordion',
			array(
				'isOpen' => static function () {
					$context = wp_interactivity_get_context();

					$uid = isset( $context['uid'] ) ? $context['uid'] : '';
					$ids = isset( $context['open']['ids'] ) ? (array) $context['open']['ids'] : array();

					return in_array( $uid, $ids, true );
				},
			)
		);

		wp_interactivity_state(
			'noorifa-core/tabs',
			array(
				'isSelected' => static function () {
					$context = wp_interactivity_get_context();

					return isset( $context['tabId'], $context['active']['id'] )
						&& $context['tabId'] === $context['active']['id'];
				},
				'tabIndex'   => static function () {
					$context = wp_interactivity_get_context();

					$selected = isset( $context['tabId'], $context['active']['id'] )
						&& $context['tabId'] === $context['active']['id'];

					return $selected ? 0 : -1;
				},
			)
		);
	}
}
