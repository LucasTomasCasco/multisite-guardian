<?php
/**
 * Flags a PHP version below the documented policy minimum. This is an objective, critical condition.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit\Rules;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Audit\Rule;

final class PhpBelowMinimum implements Rule {

	const ID = 'PHP_BELOW_MINIMUM';

	public function evaluate( array $state ): array {
		$environment = $state['environment'];

		if ( version_compare( $environment['php_version'], $environment['php_minimum'], '>=' ) ) {
			return array();
		}

		return array(
			Finding::make(
				rule_id: self::ID,
				severity: Finding::CRITICAL,
				resource_type: 'network',
				resource_id: 'php',
				description: sprintf(
					/* translators: 1: installed PHP version, 2: policy minimum PHP version. */
					__( 'PHP %1$s is below the policy minimum of %2$s.', 'multisite-guardian' ),
					$environment['php_version'],
					$environment['php_minimum']
				),
				evidence: array(
					/* translators: %s: installed PHP version. */
					sprintf( __( 'PHP version: %s', 'multisite-guardian' ), $environment['php_version'] ),
					/* translators: %s: minimum PHP version set by the policy. */
					sprintf( __( 'Policy minimum: %s', 'multisite-guardian' ), $environment['php_minimum'] ),
				),
				recommendation: __( 'Upgrade PHP to the policy minimum or a later supported version.', 'multisite-guardian' )
			),
		);
	}
}
