<?php
/**
 * Flags installed themes for which WordPress reports a newer version.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class ThemeUpdateAvailable implements Rule {

	const ID = 'THEME_UPDATE_AVAILABLE';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( $state['themes'] as $theme ) {
			if ( 'update_available' !== $theme['update_status'] ) {
				continue;
			}

			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::WARNING,
				resource_type: 'theme',
				resource_id: $theme['stylesheet'],
				description: sprintf( 'WordPress lists a newer version of the theme %s.', $theme['name'] ),
				evidence: array(
					'Installed: ' . $theme['version'],
					'Available: ' . $theme['available_version'],
				),
				recommendation: 'Review the changes and test the update on a staging network before applying it.'
			);
		}

		return $findings;
	}
}
