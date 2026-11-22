<?php
/**
 * Site queries over the network snapshot.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

final class Sites {

	const STATUSES = array( 'enabled', 'archived', 'spam', 'deleted' );

	public static function filter( array $state, string $status = '', string $search = '' ): array {
		$sites = array_values(
			array_filter(
				$state['sites'],
				static fn( array $site ): bool => '' === $status || $site['status'] === $status
			)
		);

		return Query::search( $sites, $search, array( 'name', 'url' ) );
	}

	public static function find( array $state, int $id ): ?array {
		foreach ( $state['sites'] as $site ) {
			if ( $site['id'] === $id ) {
				return $site;
			}
		}

		return null;
	}

	public static function in( array $state, array $ids ): array {
		return array_values(
			array_filter(
				$state['sites'],
				static fn( array $site ): bool => in_array( $site['id'], $ids, true )
			)
		);
	}
}
