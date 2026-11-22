<?php
/**
 * Finding structure and severity levels.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Audit;

defined( 'ABSPATH' ) || exit;

final class Finding {

	/**
	 * Critical is reserved for objective criteria: a missing active plugin file or a PHP version below policy.
	 */
	const CRITICAL    = 'critical';
	const WARNING     = 'warning';
	const INFORMATION = 'information';

	const SEVERITY_ORDER = array(
		self::CRITICAL    => 0,
		self::WARNING     => 1,
		self::INFORMATION => 2,
	);

	public static function make(
		string $rule_id,
		string $severity,
		string $resource_type,
		string $resource_id,
		string $description,
		array $evidence,
		string $recommendation
	): array {
		return array(
			'rule_id'        => $rule_id,
			'severity'       => $severity,
			'resource_type'  => $resource_type,
			'resource'       => $resource_id,
			'description'    => $description,
			'evidence'       => $evidence,
			'recommendation' => $recommendation,
		);
	}
}
