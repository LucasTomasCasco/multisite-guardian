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
				/* translators: %s: theme name. */
				description: sprintf( __( 'WordPress lists a newer version of the theme %s.', 'multisite-guardian' ), $theme['name'] ),
				evidence: array(
					/* translators: %s: installed version. */
					sprintf( __( 'Installed: %s', 'multisite-guardian' ), $theme['version'] ),
					/* translators: %s: version offered by WordPress. */
					sprintf( __( 'Available: %s', 'multisite-guardian' ), $theme['available_version'] ),
				),
				recommendation: __( 'Review the changes and test the update on a staging network before applying it.', 'multisite-guardian' )
			);
		}

		return $findings;
	}
}
