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
				/* translators: %s: plugin name. */
				description: sprintf( __( 'WordPress lists a newer version of %s.', 'multisite-guardian' ), $plugin['name'] ),
				evidence: array(
					/* translators: %s: installed version. */
					sprintf( __( 'Installed: %s', 'multisite-guardian' ), $plugin['version'] ),
					/* translators: %s: version offered by WordPress. */
					sprintf( __( 'Available: %s', 'multisite-guardian' ), $plugin['available_version'] ),
				),
				recommendation: __( 'Review the release notes and test the update on a staging network before applying it.', 'multisite-guardian' )
			);
		}

		return $findings;
	}
}
