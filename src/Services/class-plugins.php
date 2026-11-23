<?php
/**
 * Plugin queries over the network snapshot.
 *
 * Conventional plugins are shared files; their activation is tracked per scope.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

final class Plugins {

	const SCOPES           = array( 'network_active', 'site_active', 'inactive', 'must_use', 'drop_in' );
	const MATRIX_MAX_SITES = 25;

	public static function filter( array $state, string $scope = '', string $search = '' ): array {
		$plugins = array_values(
			array_filter(
				$state['plugins'],
				static fn( array $plugin ): bool => '' === $scope || $plugin['scope'] === $scope
			)
		);

		return Query::search( $plugins, $search, array( 'name', 'file' ) );
	}

	/**
	 * Plugin-by-site matrix. Only for small networks: beyond MATRIX_MAX_SITES the grid stops being readable,
	 * so the caller shows the drill-down instead. Must-use plugins and drop-ins are left out.
	 *
	 * @return array{sites: array, plugins: array}|null
	 */
	public static function matrix( array $state ): ?array {
		$sites = array_values( array_filter( $state['sites'], static fn( array $site ): bool => $site['live'] ) );
		if ( count( $sites ) > self::MATRIX_MAX_SITES ) {
			return null;
		}

		return array(
			'sites'   => $sites,
			'plugins' => array_values(
				array_filter(
					$state['plugins'],
					static fn( array $plugin ): bool => ! in_array( $plugin['scope'], array( 'must_use', 'drop_in' ), true )
				)
			),
		);
	}

	public static function find( array $state, string $file ): ?array {
		foreach ( $state['plugins'] as $plugin ) {
			if ( $plugin['file'] === $file ) {
				return $plugin;
			}
		}

		return null;
	}
}
