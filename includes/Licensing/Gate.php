<?php
/**
 * Single source of truth for free/pro feature gating.
 *
 * Modeled directly on NoorQuiz's Core\Licensing (see that plugin's
 * developer-guide.html): the free plugin never contains pro logic that's
 * physically removed — it's a single codebase that only exposes filter
 * points a separately-installed Noorifa Core Pro add-on hooks into to
 * unlock behavior. This keeps the core repo genuinely free-to-inspect and
 * the upgrade path additive rather than crippled.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Licensing;

defined( 'ABSPATH' ) || exit;

/**
 * Plain static utility, not a singleton service — nothing here holds
 * state or needs hooking; every check is a pure filter lookup.
 */
class Gate {

	/**
	 * Whether an active Noorifa Core Pro license is present. Defaults to
	 * `false` — the Noorifa Core Pro add-on plugin hooks
	 * `noorifa_core_is_pro` and returns true once it verifies a real
	 * Freemius license, at which point every gate in this codebase opens.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'noorifa_core_is_pro', false );
	}

	/**
	 * Per-feature gate, e.g. `Gate::has_feature( 'some_feature' )`. Defaults
	 * to the global Pro flag but lets Pro (or a future tiered license)
	 * unlock/restrict individual features independently.
	 *
	 * @param string $feature Feature key, see feature_keys().
	 * @return bool
	 */
	public static function has_feature( $feature ) {
		return (bool) apply_filters( "noorifa_core_feature_{$feature}", self::is_pro() );
	}

	/**
	 * Feature keys the core UI/REST layer checks against. Kept centralized
	 * so admin UI, REST validation, and docs stay in sync.
	 *
	 * @return string[]
	 */
	public static function feature_keys() {
		return array();
	}
}
