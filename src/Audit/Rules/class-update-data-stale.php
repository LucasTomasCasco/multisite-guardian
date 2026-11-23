<?php
/**
 * Reports when update data is unknown or outdated, so "no update" is never assumed.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;
use MultisiteGuardian\Inventory\NetworkState;

final class UpdateDataStale implements Rule {

	const ID = 'UPDATE_DATA_STALE';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( array( 'plugins', 'themes' ) as $kind ) {
			$last_checked = $state['update_checks'][ $kind ];

			if ( null === $last_checked ) {
				$status   = 'unknown';
				$evidence = array( __( 'WordPress has no update check record.', 'multisite-guardian' ) );
			} elseif ( $state['generated_at'] - $last_checked > NetworkState::STALE_AFTER ) {
				$status = 'outdated';
				/* translators: %s: date and time of the last update check. */
				$evidence = array( sprintf( __( 'Last update check: %s UTC', 'multisite-guardian' ), gmdate( 'Y-m-d H:i', $last_checked ) ) );
			} else {
				continue;
			}

			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::INFORMATION,
				resource_type: 'network',
				resource_id: $kind,
				/* translators: 1: data kind (plugins or themes), 2: status (unknown or outdated). */
				description: sprintf( __( 'Update status of %1$s is %2$s. Absence of updates is not confirmed.', 'multisite-guardian' ), $kind, $status ),
				evidence: $evidence,
				recommendation: __( 'Make sure WP-Cron runs, or check for updates from the WordPress admin before relying on update status.', 'multisite-guardian' )
			);
		}

		return $findings;
	}
}
