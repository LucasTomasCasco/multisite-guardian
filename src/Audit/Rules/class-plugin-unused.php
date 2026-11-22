<?php
/**
 * Flags conventional plugins that are neither network-active nor active on any site.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;
use MultisiteGuardian\Services\Plugins;

final class PluginUnused implements Rule {

	const ID = 'PLUGIN_UNUSED';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( Plugins::filter( $state, 'inactive' ) as $plugin ) {
			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::INFORMATION,
				resource_type: 'plugin',
				resource_id: $plugin['file'],
				description: sprintf( '%s is installed but not active on any site.', $plugin['name'] ),
				evidence: array( 'Version: ' . $plugin['version'] ),
				recommendation: 'Confirm the plugin is not needed, then remove it manually. The guardian does not delete files.'
			);
		}

		return $findings;
	}
}
