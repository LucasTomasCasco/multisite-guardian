<?php
/**
 * Contract for a deterministic audit rule. Rules are pure: they read a network snapshot and never write.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit;

defined( 'ABSPATH' ) || exit;

interface Rule {

	/**
	 * @return array<int, array> Findings created by Finding::make().
	 */
	public function evaluate( array $state ): array;
}
