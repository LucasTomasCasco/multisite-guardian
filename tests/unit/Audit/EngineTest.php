<?php
/**
 * The engine runs every rule and orders findings by severity, then rule, then resource.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Unit\Audit;

use MultisiteGuardian\Audit\Engine;
use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Tests\Support\Fixtures;
use PHPUnit\Framework\TestCase;

final class EngineTest extends TestCase {

	public function test_clean_state_produces_no_findings(): void {
		$this->assertSame( array(), Engine::run( Fixtures::state() ) );
	}

	public function test_findings_are_ordered_by_severity_first(): void {
		$state                               = Fixtures::state(
			array(
				'plugins'                   => array(
					Fixtures::plugin( 'b/b.php', array( 'scope' => 'inactive' ) ),
					Fixtures::plugin(
						'a/a.php',
						array(
							'scope'             => 'network_active',
							'update_status'     => 'update_available',
							'available_version' => '2.0.0',
						)
					),
				),
				'missing_plugin_references' => array(
					array(
						'file'    => 'gone/gone.php',
						'site_id' => 1,
					),
				),
			)
		);
		$state['environment']['php_version'] = '7.4.33';

		$findings = Engine::run( $state );

		$this->assertSame(
			array( Finding::CRITICAL, Finding::CRITICAL, Finding::WARNING, Finding::INFORMATION ),
			array_column( $findings, 'severity' )
		);
	}

	public function test_findings_with_the_same_severity_are_ordered_by_rule_then_resource(): void {
		$state = Fixtures::state(
			array(
				'plugins' => array(
					Fixtures::plugin( 'z/z.php', array( 'scope' => 'inactive' ) ),
					Fixtures::plugin( 'a/a.php', array( 'scope' => 'inactive' ) ),
				),
				'sites'   => array( Fixtures::site( 7, array( 'status' => 'spam' ) ) ),
			)
		);

		$findings = Engine::run( $state );

		$this->assertSame(
			array( 'PLUGIN_UNUSED', 'PLUGIN_UNUSED', 'SITE_NOT_ENABLED' ),
			array_column( $findings, 'rule_id' )
		);
		$this->assertSame( array( 'a/a.php', 'z/z.php' ), array_column( array_slice( $findings, 0, 2 ), 'resource' ) );
	}
}
