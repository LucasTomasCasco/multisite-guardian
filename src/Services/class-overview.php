<?php
/**
 * Network-level summary. Site-level figures are kept apart from network-level ones.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

final class Overview {

	public static function summary( array $state, ?array $last_audit ): array {
		$sites   = Query::counts( $state['sites'], 'status' );
		$plugins = Query::counts( $state['plugins'], 'scope' );
		$updates = Query::counts( $state['plugins'], 'update_status' );
		$themes  = Query::counts( $state['themes'], 'update_status' );

		return array(
			'generated_at'  => $state['generated_at'],
			'last_audit_at' => $last_audit['generated_at'] ?? null,
			'environment'   => $state['environment'],
			'sites'         => array(
				'total'    => count( $state['sites'] ),
				'enabled'  => $sites['enabled'] ?? 0,
				'archived' => $sites['archived'] ?? 0,
				'spam'     => $sites['spam'] ?? 0,
				'deleted'  => $sites['deleted'] ?? 0,
			),
			'plugins'       => array(
				'installed'         => ( $plugins['network_active'] ?? 0 ) + ( $plugins['site_active'] ?? 0 ) + ( $plugins['inactive'] ?? 0 ),
				'network_active'    => $plugins['network_active'] ?? 0,
				'site_active'       => $plugins['site_active'] ?? 0,
				'inactive'          => $plugins['inactive'] ?? 0,
				'must_use'          => $plugins['must_use'] ?? 0,
				'drop_ins'          => $plugins['drop_in'] ?? 0,
				'updates_available' => $updates['update_available'] ?? 0,
				'site_activations'  => array_sum( array_map( static fn( array $site ): int => count( $site['active_plugins'] ), array_filter( $state['sites'], static fn( array $site ): bool => $site['live'] ) ) ),
			),
			'themes'        => array(
				'installed'         => count( $state['themes'] ),
				'network_enabled'   => count( array_filter( $state['themes'], static fn( array $theme ): bool => $theme['network_enabled'] ) ),
				'unused'            => count( Themes::unused( $state ) ),
				'updates_available' => $themes['update_available'] ?? 0,
			),
		);
	}
}
