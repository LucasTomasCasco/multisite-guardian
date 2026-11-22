<?php
/**
 * Pure service logic: filtering, pagination, unused-theme detection and overview counts.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Unit\Services;

use MultisiteGuardian\Services\Overview;
use MultisiteGuardian\Services\Plugins;
use MultisiteGuardian\Services\Query;
use MultisiteGuardian\Services\Sites;
use MultisiteGuardian\Services\Themes;
use MultisiteGuardian\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

final class ServicesTest extends TestCase {

	public function test_slice_returns_the_requested_page_only(): void {
		$rows = range( 1, 5 );

		$this->assertSame( array( 3, 4 ), Query::slice( $rows, 2, 2 ) );
		$this->assertSame( array( 5 ), Query::slice( $rows, 3, 2 ) );
		$this->assertSame( array(), Query::slice( $rows, 4, 2 ) );
	}

	public function test_search_is_case_insensitive_and_checks_every_key(): void {
		$rows = array(
			array(
				'name' => 'Editorial',
				'url'  => 'https://example.test/e/',
			),
			array(
				'name' => 'Shop',
				'url'  => 'https://example.test/editorial-shop/',
			),
		);

		$this->assertCount( 2, Query::search( $rows, 'EDIT', array( 'name', 'url' ) ) );
		$this->assertCount( 1, Query::search( $rows, 'shop', array( 'name' ) ) );
		$this->assertCount( 2, Query::search( $rows, '', array( 'name' ) ) );
	}

	public function test_sites_filter_by_status_and_search(): void {
		$state = Fixtures::state(
			array(
				'sites' => array(
					Fixtures::site( 1, array( 'name' => 'Main' ) ),
					Fixtures::site(
						2,
						array(
							'name'   => 'Old',
							'status' => 'archived',
						)
					),
					Fixtures::site(
						3,
						array(
							'name'   => 'Old shop',
							'status' => 'archived',
						)
					),
				),
			)
		);

		$this->assertSame( array( 2, 3 ), array_column( Sites::filter( $state, 'archived' ), 'id' ) );
		$this->assertSame( array( 3 ), array_column( Sites::filter( $state, 'archived', 'shop' ), 'id' ) );
		$this->assertNull( Sites::find( $state, 99 ) );
	}

	public function test_plugins_filter_by_scope(): void {
		$state = Fixtures::state(
			array(
				'plugins' => array(
					Fixtures::plugin( 'a/a.php', array( 'scope' => 'network_active' ) ),
					Fixtures::plugin( 'b/b.php', array( 'scope' => 'must_use' ) ),
				),
			)
		);

		$this->assertSame( array( 'b/b.php' ), array_column( Plugins::filter( $state, 'must_use' ), 'file' ) );
	}

	public function test_parent_of_a_used_child_is_in_use_for_the_status_filter(): void {
		$state = Fixtures::state(
			array(
				'themes' => array(
					Fixtures::theme( 'parent-theme' ),
					Fixtures::theme(
						'child-theme',
						array(
							'parent'   => 'parent-theme',
							'site_ids' => array( 2 ),
						)
					),
					Fixtures::theme( 'orphan-theme' ),
				),
			)
		);

		$this->assertSame( array( 'orphan-theme' ), array_column( Themes::filter( $state, 'unused' ), 'stylesheet' ) );
		$this->assertSame( array( 'parent-theme', 'child-theme' ), array_column( Themes::filter( $state, 'in_use' ), 'stylesheet' ) );
	}

	public function test_overview_separates_network_scope_from_site_scope(): void {
		$state = Fixtures::state(
			array(
				'sites'   => array(
					Fixtures::site( 1, array( 'active_plugins' => array( 'b/b.php' ) ) ),
					Fixtures::site(
						2,
						array(
							'status'         => 'spam',
							'live'           => false,
							'active_plugins' => array( 'b/b.php', 'c/c.php' ),
						)
					),
				),
				'plugins' => array(
					Fixtures::plugin(
						'a/a.php',
						array(
							'scope'             => 'network_active',
							'update_status'     => 'update_available',
							'available_version' => '2',
						)
					),
					Fixtures::plugin(
						'b/b.php',
						array(
							'scope'    => 'site_active',
							'site_ids' => array( 1, 2 ),
						)
					),
					Fixtures::plugin( 'c/c.php', array( 'scope' => 'inactive' ) ),
					Fixtures::plugin( 'd.php', array( 'scope' => 'must_use' ) ),
				),
			)
		);

		$summary = Overview::summary( $state, array( 'generated_at' => 1700000000 ) );

		$this->assertSame( 2, $summary['sites']['total'] );
		$this->assertSame( 1, $summary['sites']['spam'] );
		$this->assertSame( 1, $summary['sites']['enabled'] );
		$this->assertSame( 3, $summary['plugins']['installed'] );
		$this->assertSame( 1, $summary['plugins']['network_active'] );
		$this->assertSame( 1, $summary['plugins']['site_active'] );
		$this->assertSame( 1, $summary['plugins']['must_use'] );
		$this->assertSame( 1, $summary['plugins']['updates_available'] );
		// Activations on the spam site do not count.
		$this->assertSame( 1, $summary['plugins']['site_activations'] );
		$this->assertSame( 1700000000, $summary['last_audit_at'] );
	}
}
