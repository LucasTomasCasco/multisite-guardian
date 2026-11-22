<?php
/**
 * Theme queries over the network snapshot.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

final class Themes {

	const STATUSES = array( 'in_use', 'unused' );

	/**
	 * A theme is in use when a site uses it, or when it is the parent of a theme a site uses.
	 */
	public static function unused( array $state ): array {
		$in_use = array();
		foreach ( $state['themes'] as $theme ) {
			if ( ! empty( $theme['site_ids'] ) ) {
				$in_use[ $theme['stylesheet'] ] = true;
				if ( $theme['parent'] ) {
					$in_use[ $theme['parent'] ] = true;
				}
			}
		}

		return array_values(
			array_filter(
				$state['themes'],
				static fn( array $theme ): bool => ! isset( $in_use[ $theme['stylesheet'] ] )
			)
		);
	}

	public static function filter( array $state, string $status = '', string $search = '' ): array {
		$unused = array_column( self::unused( $state ), 'stylesheet' );

		$themes = array_values(
			array_filter(
				$state['themes'],
				static function ( array $theme ) use ( $status, $unused ): bool {
					$is_unused = in_array( $theme['stylesheet'], $unused, true );

					return match ( $status ) {
						'unused' => $is_unused,
						'in_use' => ! $is_unused,
						default  => true,
					};
				}
			)
		);

		return Query::search( $themes, $search, array( 'name', 'stylesheet' ) );
	}
}
