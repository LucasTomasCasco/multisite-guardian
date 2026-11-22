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

	const SCOPES = array( 'network_active', 'site_active', 'inactive', 'must_use', 'drop_in' );

	public static function filter( array $state, string $scope = '', string $search = '' ): array {
		$plugins = array_values(
			array_filter(
				$state['plugins'],
				static fn( array $plugin ): bool => '' === $scope || $plugin['scope'] === $scope
			)
		);

		return Query::search( $plugins, $search, array( 'name', 'file' ) );
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
