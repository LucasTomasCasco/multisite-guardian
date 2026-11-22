<?php
/**
 * Each rule must fire on its condition and stay silent otherwise.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Unit\Audit\Rules;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rules\PhpBelowMinimum;
use MultisiteGuardian\Audit\Rules\PluginReferenceMissing;
use MultisiteGuardian\Audit\Rules\PluginUnused;
use MultisiteGuardian\Audit\Rules\PluginUpdateAvailable;
use MultisiteGuardian\Audit\Rules\SiteNotEnabled;
use MultisiteGuardian\Audit\Rules\ThemeUnused;
use MultisiteGuardian\Audit\Rules\ThemeUpdateAvailable;
use MultisiteGuardian\Audit\Rules\UpdateDataStale;
use MultisiteGuardian\Audit\Rules\DebugModeEnabled;
use MultisiteGuardian\Inventory\NetworkState;
use MultisiteGuardian\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

final class RulesTest extends TestCase {

	public function test_plugin_update_is_a_warning_and_not_a_vulnerability_claim(): void {
		$state = Fixtures::state(
			array(
				'plugins' => array(
					Fixtures::plugin(
						'a/a.php',
						array(
							'update_status'     => 'update_available',
							'available_version' => '2.0.0',
						)
					),
					Fixtures::plugin( 'b/b.php', array( 'update_status' => 'up_to_date' ) ),
					Fixtures::plugin( 'c/c.php', array( 'update_status' => 'unknown' ) ),
				),
			)
		);

		$findings = ( new PluginUpdateAvailable() )->evaluate( $state );

		$this->assertCount( 1, $findings );
		$this->assertSame( 'PLUGIN_UPDATE_AVAILABLE', $findings[0]['rule_id'] );
		$this->assertSame( Finding::WARNING, $findings[0]['severity'] );
		$this->assertSame( 'a/a.php', $findings[0]['resource'] );
		$this->assertContains( 'Available: 2.0.0', $findings[0]['evidence'] );
		$this->assertStringNotContainsStringIgnoringCase( 'vulnerab', $findings[0]['description'] );
	}

	public function test_theme_update_fires_only_for_available_updates(): void {
		$state = Fixtures::state(
			array(
				'themes' => array(
					Fixtures::theme(
						'twentytwentyfive',
						array(
							'update_status'     => 'update_available',
							'available_version' => '1.4',
						)
					),
					Fixtures::theme( 'guardian-unused', array( 'update_status' => 'stale' ) ),
				),
			)
		);

		$findings = ( new ThemeUpdateAvailable() )->evaluate( $state );

		$this->assertCount( 1, $findings );
		$this->assertSame( 'twentytwentyfive', $findings[0]['resource'] );
	}

	public function test_plugin_unused_is_inactive_everywhere(): void {
		$state = Fixtures::state(
			array(
				'plugins' => array(
					Fixtures::plugin( 'idle/idle.php', array( 'scope' => 'inactive' ) ),
					Fixtures::plugin(
						'busy/busy.php',
						array(
							'scope'    => 'site_active',
							'site_ids' => array( 2 ),
						)
					),
					Fixtures::plugin( 'hello.php', array( 'scope' => 'must_use' ) ),
				),
			)
		);

		$findings = ( new PluginUnused() )->evaluate( $state );

		$this->assertCount( 1, $findings );
		$this->assertSame( 'idle/idle.php', $findings[0]['resource'] );
		$this->assertSame( Finding::INFORMATION, $findings[0]['severity'] );
	}

	public function test_parent_of_a_used_child_theme_is_not_unused(): void {
		$state = Fixtures::state(
			array(
				'themes' => array(
					Fixtures::theme( 'parent-theme' ),
					Fixtures::theme(
						'child-theme',
						array(
							'parent'   => 'parent-theme',
							'site_ids' => array( 3 ),
						)
					),
					Fixtures::theme( 'orphan-theme' ),
				),
			)
		);

		$findings = ( new ThemeUnused() )->evaluate( $state );

		$this->assertSame( array( 'orphan-theme' ), array_column( $findings, 'resource' ) );
	}

	public function test_missing_active_plugin_file_is_critical_and_lists_each_site(): void {
		$state = Fixtures::state(
			array(
				'missing_plugin_references' => array(
					array(
						'file'    => 'gone/gone.php',
						'site_id' => 2,
					),
					array(
						'file'    => 'gone/gone.php',
						'site_id' => 5,
					),
					array(
						'file'    => 'network/gone.php',
						'site_id' => null,
					),
				),
			)
		);

		$findings = ( new PluginReferenceMissing() )->evaluate( $state );

		$this->assertCount( 2, $findings );
		$this->assertSame( Finding::CRITICAL, $findings[0]['severity'] );
		$this->assertSame( array( 'Active on site 2', 'Active on site 5' ), $findings[0]['evidence'] );
		$this->assertSame( 'Network-wide activation', $findings[1]['evidence'][0] );
	}

	public function test_php_below_policy_minimum_is_critical(): void {
		$below                               = Fixtures::state();
		$below['environment']['php_version'] = '8.1.29';

		$findings = ( new PhpBelowMinimum() )->evaluate( $below );

		$this->assertCount( 1, $findings );
		$this->assertSame( Finding::CRITICAL, $findings[0]['severity'] );
	}

	/**
	 * @dataProvider php_versions_at_or_above_policy
	 */
	public function test_php_at_or_above_policy_is_silent( string $version ): void {
		$state                               = Fixtures::state();
		$state['environment']['php_version'] = $version;

		$this->assertSame( array(), ( new PhpBelowMinimum() )->evaluate( $state ) );
	}

	public static function php_versions_at_or_above_policy(): array {
		return array(
			'exact minimum' => array( '8.3.0' ),
			'later minor'   => array( '8.3.10' ),
			'later major'   => array( '9.0.0' ),
		);
	}

	public function test_debug_mode_is_a_warning_when_errors_are_not_displayed(): void {
		$state                                    = Fixtures::state();
		$state['environment']['wp_debug']         = true;
		$state['environment']['wp_debug_display'] = false;

		$findings = ( new DebugModeEnabled() )->evaluate( $state );

		$this->assertCount( 1, $findings );
		$this->assertSame( Finding::WARNING, $findings[0]['severity'] );
		$this->assertSame( array(), ( new DebugModeEnabled() )->evaluate( Fixtures::state() ) );
	}

	public function test_debug_mode_with_displayed_errors_is_critical(): void {
		$state                                    = Fixtures::state();
		$state['environment']['wp_debug']         = true;
		$state['environment']['wp_debug_display'] = true;

		$findings = ( new DebugModeEnabled() )->evaluate( $state );

		$this->assertSame( Finding::CRITICAL, $findings[0]['severity'] );
		$this->assertStringContainsString( 'shown to visitors', $findings[0]['description'] );
	}

	public function test_unknown_update_data_is_reported_instead_of_assuming_no_updates(): void {
		$state = Fixtures::state(
			array(
				'update_checks' => array(
					'plugins' => null,
					'themes'  => Fixtures::NOW - 60,
				),
			)
		);

		$findings = ( new UpdateDataStale() )->evaluate( $state );

		$this->assertCount( 1, $findings );
		$this->assertSame( 'plugins', $findings[0]['resource'] );
		$this->assertStringContainsString( 'unknown', $findings[0]['description'] );
	}

	public function test_outdated_update_data_is_reported_after_the_stale_threshold(): void {
		$checked = Fixtures::NOW - NetworkState::STALE_AFTER - 1;
		$state   = Fixtures::state(
			array(
				'update_checks' => array(
					'plugins' => $checked,
					'themes'  => $checked,
				),
			)
		);

		$findings = ( new UpdateDataStale() )->evaluate( $state );

		$this->assertCount( 2, $findings );
		$this->assertStringContainsString( 'outdated', $findings[0]['description'] );
	}

	public function test_fresh_update_data_produces_no_finding(): void {
		$this->assertSame( array(), ( new UpdateDataStale() )->evaluate( Fixtures::state() ) );
	}

	public function test_archived_and_spam_sites_are_informational_and_deleted_sites_are_not_reported(): void {
		$state = Fixtures::state(
			array(
				'sites' => array(
					Fixtures::site( 1 ),
					Fixtures::site( 2, array( 'status' => 'archived' ) ),
					Fixtures::site( 3, array( 'status' => 'spam' ) ),
					Fixtures::site( 4, array( 'status' => 'deleted' ) ),
				),
			)
		);

		$findings = ( new SiteNotEnabled() )->evaluate( $state );

		$this->assertSame( array( '2', '3' ), array_column( $findings, 'resource' ) );
		$this->assertSame( Finding::INFORMATION, $findings[0]['severity'] );
	}
}
