<?php
/**
 * Flags conventional plugins for which WordPress reports a newer version.
 *
 * This is an update, not a vulnerability: no external vulnerability source is consulted.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class PluginUpdateAvailable implements Rule {

	const ID = 'PLUGIN_UPDATE_AVAILABLE';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( $state['plugins'] as $plugin ) {
			if ( 'update_available' !== $plugin['update_status'] ) {
				continue;
			}

			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::WARNING,
				resource_type: 'plugin',
				resource_id: $plugin['file'],
				description: sprintf( 'WordPress lists a newer version of %s.', $plugin['name'] ),
				evidence: array(
					'Installed: ' . $plugin['version'],
					'Available: ' . $plugin['available_version'],
				),
				recommendation: 'Review the release notes and test the update on a staging network before applying it.'
			);
		}

		return $findings;
	}
}
