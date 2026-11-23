<?php
/**
 * Saved audit runs: schema, summary rows, ordering, retention and upgrades.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Integration;

// Asserts read the plugin's own table directly, so the query cache is not involved.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\History;
use WP_UnitTestCase;

final class HistoryTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		History::maybe_install();
		$this->clear();
	}

	public function tear_down(): void {
		$this->clear();
		parent::tear_down();
	}

	public function test_table_lives_on_the_base_prefix_and_is_installed(): void {
		global $wpdb;

		$this->assertStringStartsWith( $wpdb->base_prefix, History::table() );
		$this->assertSame( History::table(), $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', History::table() ) ) );
		$this->assertSame( History::DB_VERSION, (int) get_site_option( History::OPTION ) );
	}

	public function test_recording_an_audit_writes_a_summary_row(): void {
		Audit::record(
			$this->audit(
				time(),
				3,
				2,
				1,
				array(
					Finding::CRITICAL    => 1,
					Finding::WARNING     => 2,
					Finding::INFORMATION => 4,
				)
			)
		);

		$row = History::recent( 1 )[0];

		$this->assertSame( '1', $row['critical'] );
		$this->assertSame( '2', $row['warning'] );
		$this->assertSame( '4', $row['information'] );
		$this->assertSame( '3', $row['sites'] );
		$this->assertSame( '2', $row['plugins'] );
		$this->assertSame( '1', $row['themes'] );
	}

	public function test_history_is_returned_newest_first(): void {
		History::insert( $this->audit( 1700000000, 1, 1, 1, $this->zero() ) );
		History::insert( $this->audit( 1800000000, 2, 1, 1, $this->zero() ) );

		$rows = History::recent( 2 );

		$this->assertSame( '2', $rows[0]['sites'] );
		$this->assertSame( '1', $rows[1]['sites'] );
	}

	public function test_retention_keeps_only_the_newest_runs(): void {
		for ( $sites = 1; $sites <= History::KEEP + 5; $sites++ ) {
			History::insert( $this->audit( time() - ( History::KEEP + 5 - $sites ), $sites, 1, 1, $this->zero() ) );
		}

		$rows = History::recent( History::KEEP + 10 );

		$this->assertCount( History::KEEP, $rows );
		$this->assertSame( (string) ( History::KEEP + 5 ), $rows[0]['sites'] );
		$this->assertSame( '6', end( $rows )['sites'] );
	}

	public function test_install_runs_again_when_the_stored_version_is_behind(): void {
		delete_site_option( History::OPTION );

		History::maybe_install();

		$this->assertSame( History::DB_VERSION, (int) get_site_option( History::OPTION ) );
	}

	public function test_uninstall_forgets_the_schema_version(): void {
		// The WordPress test suite does not commit DDL, so the DROP itself is verified in the clean-install check.
		History::drop();

		$this->assertFalse( get_site_option( History::OPTION ) );
	}

	private function audit( int $generated_at, int $sites, int $plugins, int $themes, array $counts ): array {
		return array(
			'generated_at' => $generated_at,
			'sites'        => $sites,
			'plugins'      => $plugins,
			'themes'       => $themes,
			'counts'       => $counts,
			'findings'     => array(),
		);
	}

	private function zero(): array {
		return array(
			Finding::CRITICAL    => 0,
			Finding::WARNING     => 0,
			Finding::INFORMATION => 0,
		);
	}

	private function clear(): void {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'DELETE FROM %i', History::table() ) );
	}
}
