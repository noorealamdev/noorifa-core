<?php
/**
 * Template source contract.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Templates;

defined( 'ABSPATH' ) || exit;

/**
 * A provider of library templates.
 *
 * Implement this to add templates from any origin (bundled files, a remote
 * cloud API, a Pro add-on, …) and register the instance via the
 * `noorifa-core/template_sources` filter.
 */
interface Source {

	/**
	 * Returns a unique identifier for this source.
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Returns the templates provided by this source, as lightweight
	 * metadata for browsing the library.
	 *
	 * Each template is an associative array:
	 * - name        (string) Unique slug.
	 * - title       (string) Human-readable title.
	 * - description (string) Short description.
	 * - type        (string) Either 'layout' or 'section'.
	 * - category    (string) Filter category slug.
	 * - thumbnail   (string) Absolute URL to a preview image.
	 *
	 * Implementations do not need to include `content` here; sources that
	 * can provide full content on demand should implement Lazy_Source.
	 *
	 * @return array[]
	 */
	public function get_templates();
}
