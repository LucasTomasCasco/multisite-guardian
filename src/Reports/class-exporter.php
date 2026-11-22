<?php
/**
 * Serializes a consolidated report as JSON, or its findings as CSV.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Reports;

defined( 'ABSPATH' ) || exit;

final class Exporter {

	const CSV_COLUMNS = array( 'rule_id', 'severity', 'resource_type', 'resource', 'description', 'evidence', 'recommendation' );

	public static function json( array $report ): string {
		return (string) wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Exports findings only. Summary and inventory are available in JSON.
	 */
	public static function csv( array $report ): string {
		$lines = array( self::line( self::CSV_COLUMNS ) );

		foreach ( $report['findings'] as $finding ) {
			$lines[] = self::line(
				array(
					$finding['rule_id'],
					$finding['severity'],
					$finding['resource_type'],
					$finding['resource'],
					$finding['description'],
					implode( '; ', $finding['evidence'] ),
					$finding['recommendation'],
				)
			);
		}

		return implode( "\r\n", $lines ) . "\r\n";
	}

	private static function line( array $cells ): string {
		return implode( ',', array_map( array( self::class, 'cell' ), $cells ) );
	}

	/**
	 * Quotes a CSV cell and neutralizes spreadsheet formulas, so values cannot run as formulas.
	 */
	private static function cell( string $value ): string {
		if ( preg_match( '/^[=+\-@\t\r]/', $value ) ) {
			$value = "'" . $value;
		}

		return '"' . str_replace( '"', '""', $value ) . '"';
	}
}
