<?php
/**
 * Flags WP_DEBUG for the whole network. Critical when WP_DEBUG_DISPLAY also shows errors to visitors.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class DebugModeEnabled implements Rule {

	const ID = 'DEBUG_MODE_ENABLED';

	public function evaluate( array $state ): array {
		$environment = $state['environment'];

		if ( ! $environment['wp_debug'] ) {
			return array();
		}

		// Displayed errors reveal file paths and code to anyone who loads a page, which is an objective exposure.
		$displayed = $environment['wp_debug_display'];

		return array(
			Finding::make(
				rule_id: self::ID,
				severity: $displayed ? Finding::CRITICAL : Finding::WARNING,
				resource_type: 'network',
				resource_id: 'wp_debug',
				description: $displayed
					? 'WP_DEBUG and WP_DEBUG_DISPLAY are enabled, so PHP errors are shown to visitors on every site.'
					: 'WP_DEBUG is enabled for every site in the network.',
				evidence: array(
					'WP_DEBUG is true',
					'WP_DEBUG_DISPLAY is ' . ( $displayed ? 'true' : 'false' ),
				),
				recommendation: 'Set WP_DEBUG to false in wp-config.php on production networks. If you need logs, use WP_DEBUG_LOG with WP_DEBUG_DISPLAY set to false.'
			),
		);
	}
}
