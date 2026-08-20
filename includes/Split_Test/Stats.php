<?php
/**
 * Split test event storage.
 *
 * @package Noorifa Core
 */

namespace Noorifa\Core\Split_Test;

use Noorifa\Core\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and totals per-product, per-layout split test events
 * (impressions, add-to-cart, purchases) in a dedicated table — the first
 * custom `$wpdb`/dbDelta table in this plugin. One row per
 * product/layout/event-type/day, incremented via an atomic upsert, so the
 * table stays small and every read is a simple grouped SUM.
 */
class Stats {

	use Singleton;

	/**
	 * Event type: a variant was shown to a visitor.
	 */
	const EVENT_IMPRESSION = 'impression';

	/**
	 * Event type: a visitor added the product to their cart.
	 */
	const EVENT_ADD_TO_CART = 'add_to_cart';

	/**
	 * Event type: an order containing the product reached a paid status.
	 */
	const EVENT_PURCHASE = 'purchase';

	/**
	 * Returns the fully qualified stats table name.
	 *
	 * @return string
	 */
	private static function table_name() {
		global $wpdb;

		return $wpdb->prefix . 'noorifa_core_split_stats';
	}

	/**
	 * Creates (or upgrades) the stats table. dbDelta is idempotent, so this
	 * is safe to call on every activation and every version migration.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			layout_id BIGINT UNSIGNED NOT NULL,
			event_type VARCHAR(20) NOT NULL,
			stat_date DATE NOT NULL,
			count BIGINT UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY product_layout_event_date (product_id, layout_id, event_type, stat_date),
			KEY product_id (product_id),
			KEY layout_id (layout_id)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Increments today's counter for one product/layout/event combination.
	 *
	 * @param int    $product_id Product ID.
	 * @param int    $layout_id  Layout post ID (the variant shown).
	 * @param string $event_type One of the EVENT_* constants.
	 * @return void
	 */
	public function log_event( $product_id, $layout_id, $event_type ) {
		global $wpdb;

		if ( ! $product_id || ! $layout_id ) {
			return;
		}

		$table_name = self::table_name();

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is built from $wpdb->prefix, not user input.
				"INSERT INTO {$table_name} (product_id, layout_id, event_type, stat_date, count)
				VALUES (%d, %d, %s, %s, 1)
				ON DUPLICATE KEY UPDATE count = count + 1",
				$product_id,
				$layout_id,
				$event_type,
				current_time( 'Y-m-d' )
			)
		);
	}

	/**
	 * Returns total impressions/add-to-cart/purchase counts for one
	 * product/layout combination, summed across every day recorded.
	 *
	 * @param int $product_id Product ID.
	 * @param int $layout_id  Layout post ID.
	 * @return array{impression: int, add_to_cart: int, purchase: int}
	 */
	public function get_totals( $product_id, $layout_id ) {
		global $wpdb;

		$table_name = self::table_name();

		$totals = array(
			self::EVENT_IMPRESSION  => 0,
			self::EVENT_ADD_TO_CART => 0,
			self::EVENT_PURCHASE    => 0,
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is built from $wpdb->prefix, not user input.
				"SELECT event_type, SUM(count) AS total
				FROM {$table_name}
				WHERE product_id = %d AND layout_id = %d
				GROUP BY event_type",
				$product_id,
				$layout_id
			)
		);

		foreach ( (array) $rows as $row ) {
			if ( isset( $totals[ $row->event_type ] ) ) {
				$totals[ $row->event_type ] = (int) $row->total;
			}
		}

		return $totals;
	}
}
