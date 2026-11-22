<?php
/**
 * Consolidated report: structure, privacy and the saved audit timestamp.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Integration;

use MultisiteGuardian\Reports\Exporter;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\Report;
use WP_UnitTestCase;

final class ReportTest extends WP_UnitTestCase {

	public function test_report_contains_the_expected_sections_and_no_account_data(): void {
		self::factory()->user->create(
			array(
				'role'       => 'administrator',
				'user_email' => 'private@guardian.test',
			)
		);

		$report = Report::generate();
		$json   = Exporter::json( $report );

		$this->assertSame( array( 'report', 'summary', 'sites', 'plugins', 'themes', 'findings' ), array_keys( $report ) );
		$this->assertArrayHasKey( 'plugin_version', $report['report'] );
		$this->assertStringNotContainsString( 'private@guardian.test', $json );
		$this->assertStringNotContainsString( 'user_email', $json );
	}

	public function test_generating_a_report_saves_the_audit_timestamp(): void {
		$this->assertNull( Audit::last_run() );

		Report::generate();

		$this->assertIsInt( Audit::last_run()['generated_at'] );
	}

	public function test_csv_export_has_a_header_and_no_formula_cells(): void {
		$csv = Exporter::csv( Report::generate() );

		$this->assertStringStartsWith( '"rule_id","severity"', $csv );
		$this->assertDoesNotMatchRegularExpression( '/^"[=+@-]/m', $csv );
	}
}
