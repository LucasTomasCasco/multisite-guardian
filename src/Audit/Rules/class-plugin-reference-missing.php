<?php
/**
 * Flags active plugin references whose files no longer exist. This is an objective, critical condition.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class PluginReferenceMissing implements Rule {

	const ID = 'PLUGIN_REFERENCE_MISSING';

	public function evaluate( array $state ): array {
		$references = array();
		foreach ( $state['missing_plugin_references'] as $reference ) {
			$references[ $reference['file'] ][] = $reference['site_id'];
		}

		$findings = array();
		foreach ( $references as $file => $site_ids ) {
			$evidence = array_map(
				static fn( ?int $site_id ): string => null === $site_id
					? 'Network-wide activation'
					: 'Active on site ' . $site_id,
				$site_ids
			);

			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::CRITICAL,
				resource_type: 'plugin',
				resource_id: $file,
				description: 'An active plugin reference points to a file that no longer exists.',
				evidence: $evidence,
				recommendation: 'Deactivate the reference on each listed site, or restore the plugin files. The guardian does not change the network.'
			);
		}

		return $findings;
	}
}
