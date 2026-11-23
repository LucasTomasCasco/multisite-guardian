<?php
/**
 * Flags installed themes that no site uses, directly or as a parent theme.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;
use MultisiteGuardian\Services\Themes;

final class ThemeUnused implements Rule {

	const ID = 'THEME_UNUSED';

	public function evaluate( array $state ): array {
		$findings = array();

		foreach ( Themes::unused( $state ) as $theme ) {
			$findings[] = Finding::make(
				rule_id: self::ID,
				severity: Finding::INFORMATION,
				resource_type: 'theme',
				resource_id: $theme['stylesheet'],
				/* translators: %s: theme name. */
				description: sprintf( __( 'The theme %s is installed but not used by any site.', 'multisite-guardian' ), $theme['name'] ),
				evidence: array(
					/* translators: %s: plugin or theme version. */
					sprintf( __( 'Version: %s', 'multisite-guardian' ), $theme['version'] ),
					/* translators: %s: yes or no. */
					sprintf( __( 'Network enabled: %s', 'multisite-guardian' ), $theme['network_enabled'] ? __( 'yes', 'multisite-guardian' ) : __( 'no', 'multisite-guardian' ) ),
				),
				recommendation: __( 'Confirm the theme is not needed, then remove it manually. The guardian does not delete files.', 'multisite-guardian' )
			);
		}

		return $findings;
	}
}
