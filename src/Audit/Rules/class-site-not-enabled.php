<?php
/**
 * Flags sites that are archived or marked as spam.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class SiteNotEnabled implements Rule {

	const ID = 'SITE_NOT_ENABLED';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( $state['sites'] as $site ) {
			if ( ! in_array( $site['status'], array( 'archived', 'spam' ), true ) ) {
				continue;
			}

			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::INFORMATION,
				resource_type: 'site',
				resource_id: (string) $site['id'],
				description: sprintf( 'Site %d is marked as %s.', $site['id'], $site['status'] ),
				evidence: array(
					'URL: ' . $site['url'],
					'Active plugins: ' . count( $site['active_plugins'] ),
				),
				recommendation: 'Confirm whether the site should remain in the network.'
			);
		}

		return $findings;
	}
}
