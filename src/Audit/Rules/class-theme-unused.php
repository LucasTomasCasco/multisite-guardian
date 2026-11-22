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
				description: sprintf( 'The theme %s is installed but not used by any site.', $theme['name'] ),
				evidence: array(
					'Version: ' . $theme['version'],
					'Network enabled: ' . ( $theme['network_enabled'] ? 'yes' : 'no' ),
				),
				recommendation: 'Confirm the theme is not needed, then remove it manually. The guardian does not delete files.'
			);
		}

		return $findings;
	}
}
