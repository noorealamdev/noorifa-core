<?php
/**
 * Single source of truth for feature gating.
 *
 * Noorifa Core is distributed completely free — every feature is unlocked,
 * so `is_pro()` returns true by default and each gate in the codebase is
 * open. The `noorifa_core_is_pro` / `noorifa_core_feature_*` filters are
 * kept purely as override points (e.g. to restrict a feature on a specific
 * site); the plugin ships with the full feature set available to everyone.
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
	 * Whether Noorifa Core's full feature set is available. The plugin is
	 * free, so this defaults to `true` and every gate in the codebase is
	 * open for all users. The `noorifa_core_is_pro` filter is retained only
	 * as an override hook and is not required for any feature to work.
	 *
	 * @return bool
	 */
	public static function is_pro() {
		return (bool) apply_filters( 'noorifa_core_is_pro', true );
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
