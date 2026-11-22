<?php
/**
 * Runs every registered rule and sorts the findings by severity.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Rules;

final class Engine {

	const RULES = array(
		Rules\PhpBelowMinimum::class,
		Rules\DebugModeEnabled::class,
		Rules\PluginReferenceMissing::class,
		Rules\PluginUpdateAvailable::class,
		Rules\ThemeUpdateAvailable::class,
		Rules\UpdateDataStale::class,
		Rules\PluginUnused::class,
		Rules\ThemeUnused::class,
		Rules\SiteNotEnabled::class,
	);

	public static function run( array $state ): array {
		$findings = array();
		foreach ( self::RULES as $rule ) {
			$findings = array_merge( $findings, ( new $rule() )->evaluate( $state ) );
		}

		usort(
			$findings,
			static function ( array $a, array $b ): int {
				$by_severity = Finding::SEVERITY_ORDER[ $a['severity'] ] <=> Finding::SEVERITY_ORDER[ $b['severity'] ];
				if ( 0 !== $by_severity ) {
					return $by_severity;
				}

				$by_rule = strcmp( $a['rule_id'], $b['rule_id'] );
				if ( 0 !== $by_rule ) {
					return $by_rule;
				}

				return strcmp( $a['resource'], $b['resource'] );
			}
		);

		return $findings;
	}
}
