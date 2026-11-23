<?php
/**
 * Saved audit runs in a network table. Each row summarizes one run: counts by severity and the size of the network.
 *
 * The table lives on the network's base prefix, so it is never assumed to be wp_. Schema changes bump DB_VERSION.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

// This is a plugin-owned table with small, admin-only reads and writes. Object caching would only serve stale history.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

use MultisiteGuardian\Audit\Finding;

final class History {

	const DB_VERSION = 1;
	const OPTION     = 'multisite_guardian_db_version';
	const KEEP       = 50;

	public static function table(): string {
		global $wpdb;

		return $wpdb->base_prefix . 'multisite_guardian_runs';
	}

	/**
	 * Creates or upgrades the table when the stored schema version is behind. Cheap on every request.
	 */
	public static function maybe_install(): void {
		if ( (int) get_site_option( self::OPTION ) === self::DB_VERSION ) {
			return;
		}

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		// dbDelta() takes its SQL unprepared by design. The table name comes from base_prefix, never from input.
		dbDelta( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				run_at datetime NOT NULL,
				sites int(10) unsigned NOT NULL,
				plugins int(10) unsigned NOT NULL,
				themes int(10) unsigned NOT NULL,
				critical int(10) unsigned NOT NULL,
				warning int(10) unsigned NOT NULL,
				information int(10) unsigned NOT NULL,
				PRIMARY KEY  (id)
			) {$charset};"
		);

		update_site_option( self::OPTION, self::DB_VERSION );
	}

	public static function insert( array $audit ): void {
		global $wpdb;

		$wpdb->insert(
			self::table(),
			array(
				'run_at'      => gmdate( 'Y-m-d H:i:s', $audit['generated_at'] ),
				'sites'       => $audit['sites'],
				'plugins'     => $audit['plugins'],
				'themes'      => $audit['themes'],
				'critical'    => $audit['counts'][ Finding::CRITICAL ],
				'warning'     => $audit['counts'][ Finding::WARNING ],
				'information' => $audit['counts'][ Finding::INFORMATION ],
			),
			array( '%s', '%d', '%d', '%d', '%d', '%d', '%d' )
		);

		self::prune();
	}

	/**
	 * @return array<int, array<string, string>> Newest first.
	 */
	public static function recent( int $limit = 20 ): array {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', self::table(), $limit ),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Keeps the newest KEEP runs and deletes older ones.
	 */
	private static function prune(): void {
		global $wpdb;

		$table       = self::table();
		$oldest_kept = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM %i ORDER BY id DESC LIMIT 1 OFFSET %d', $table, self::KEEP - 1 )
		);

		if ( null !== $oldest_kept ) {
			$wpdb->query(
				$wpdb->prepare( 'DELETE FROM %i WHERE id < %d', $table, (int) $oldest_kept )
			);
		}
	}

	public static function drop(): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', self::table() ) );
		delete_site_option( self::OPTION );
	}
}
