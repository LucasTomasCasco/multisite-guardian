<?php
/**
 * CSV output must stay parseable and must not run as spreadsheet formulas.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Unit\Reports;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Reports\Exporter;
use PHPUnit\Framework\TestCase;

final class ExporterTest extends TestCase {

	public function test_csv_header_and_one_row_per_finding(): void {
		$csv = Exporter::csv( array( 'findings' => array( $this->finding( 'Plain text' ) ) ) );

		$lines = explode( "\r\n", rtrim( $csv, "\r\n" ) );
		$this->assertSame( '"rule_id","severity","resource_type","resource","description","evidence","recommendation"', $lines[0] );
		$this->assertCount( 2, $lines );
		$this->assertStringStartsWith( '"PLUGIN_UNUSED","information","plugin"', $lines[1] );
	}

	/**
	 * @dataProvider formula_values
	 */
	public function test_values_that_start_like_formulas_are_neutralized( string $value ): void {
		$csv = Exporter::csv( array( 'findings' => array( $this->finding( $value ) ) ) );

		$this->assertStringContainsString( '"' . str_replace( '"', '""', "'" . $value ) . '"', $csv );
	}

	public static function formula_values(): array {
		return array(
			'equals'  => array( '=HYPERLINK("http://x","x")' ),
			'plus'    => array( '+1+1' ),
			'minus'   => array( '-2' ),
			'at sign' => array( '@SUM(A1)' ),
			'tab'     => array( "\tcmd" ),
		);
	}

	public function test_quotes_inside_values_are_doubled(): void {
		$csv = Exporter::csv( array( 'findings' => array( $this->finding( 'Say "hi"' ) ) ) );

		$this->assertStringContainsString( '"Say ""hi"""', $csv );
	}

	private function finding( string $description ): array {
		return Finding::make(
			rule_id: 'PLUGIN_UNUSED',
			severity: Finding::INFORMATION,
			resource_type: 'plugin',
			resource_id: 'a/a.php',
			description: $description,
			evidence: array( 'Version: 1.0' ),
			recommendation: 'Remove it.'
		);
	}
}
