<?php
/**
 * Runs audit rules and stores the time of the last explicit audit run.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Engine;
use MultisiteGuardian\Audit\Finding;

final class Audit {

	const OPTION = 'multisite_guardian_last_audit';

	public static function run( array $state ): array {
		$findings = Engine::run( $state );

		return array(
			'generated_at'       => time(),
			'state_generated_at' => $state['generated_at'],
			'counts'             => array(
				Finding::CRITICAL    => 0,
				Finding::WARNING     => 0,
				Finding::INFORMATION => 0,
			) + Query::counts( $findings, 'severity' ),
			'sites'              => count( $state['sites'] ),
			'plugins'            => count(
				array_filter(
					$state['plugins'],
					static fn( array $plugin ): bool => ! in_array( $plugin['scope'], array( 'must_use', 'drop_in' ), true )
				)
			),
			'themes'             => count( $state['themes'] ),
			'findings'           => $findings,
		);
	}

	public static function filter( array $findings, string $severity = '' ): array {
		return array_values(
			array_filter(
				$findings,
				static fn( array $finding ): bool => '' === $severity || $finding['severity'] === $severity
			)
		);
	}

	/**
	 * Keeps the last run for the dashboard and appends a summary row to the history.
	 */
	public static function record( array $audit ): void {
		update_site_option( self::OPTION, $audit );
		History::insert( $audit );
	}

	public static function last_run(): ?array {
		$last = get_site_option( self::OPTION );

		return is_array( $last ) ? $last : null;
	}
}
