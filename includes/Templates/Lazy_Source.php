<?php
/**
 * Lazy-content template source contract.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * A source that can fetch a single template's full content on demand,
 * instead of always returning it in the lightweight get_templates() list.
 *
 * This is a separate, optional interface (rather than a new required
 * method on Source) so existing Source implementations are unaffected.
 */
interface Lazy_Source extends Source {

	/**
	 * Returns the full template, including its `content`, or null when the
	 * name is unknown to this source.
	 *
	 * @param string $name Template slug.
	 * @return array|null
	 */
	public function get_template( $name );
}
