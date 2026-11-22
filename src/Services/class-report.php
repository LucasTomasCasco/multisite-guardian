<?php
/**
 * Builds the consolidated audit report used by the admin and WP-CLI exporters.
 *
 * The report contains no user, email or credential data.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Inventory\NetworkState;

final class Report {

	public static function generate(): array {
		$state = NetworkState::build();
		$audit = Audit::run( $state );
		Audit::record( $audit );

		return array(
			'report'   => array(
				'plugin_version' => \MultisiteGuardian\VERSION,
				'generated_at'   => gmdate( 'c', $state['generated_at'] ),
				'network_id'     => $state['network_id'],
			),
			'summary'  => Overview::summary( $state, $audit ),
			'sites'    => array_map(
				static fn( array $site ): array => array(
					'id'             => $site['id'],
					'name'           => $site['name'],
					'url'            => $site['url'],
					'status'         => $site['status'],
					'registered'     => $site['registered'],
					'last_updated'   => $site['last_updated'],
					'stylesheet'     => $site['stylesheet'],
					'active_plugins' => $site['active_plugins'],
				),
				$state['sites']
			),
			'plugins'  => $state['plugins'],
			'themes'   => $state['themes'],
			'findings' => $audit['findings'],
		);
	}
}
