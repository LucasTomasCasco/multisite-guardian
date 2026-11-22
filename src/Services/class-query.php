<?php
/**
 * Shared search, counting and pagination helpers for inventory collections.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Services;

defined( 'ABSPATH' ) || exit;

final class Query {

	/**
	 * Keeps the rows where any of the given keys contains the term, case-insensitively.
	 */
	public static function search( array $rows, string $term, array $keys ): array {
		if ( '' === $term ) {
			return $rows;
		}

		return array_values(
			array_filter(
				$rows,
				static function ( array $row ) use ( $term, $keys ): bool {
					foreach ( $keys as $key ) {
						if ( false !== stripos( (string) $row[ $key ], $term ) ) {
							return true;
						}
					}

					return false;
				}
			)
		);
	}

	/**
	 * Counts rows by the value of one key.
	 *
	 * @return array<string, int>
	 */
	public static function counts( array $rows, string $key ): array {
		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ $row[ $key ] ] = ( $counts[ $row[ $key ] ] ?? 0 ) + 1;
		}

		return $counts;
	}

	public static function slice( array $rows, int $page, int $per_page ): array {
		return array_slice( $rows, ( $page - 1 ) * $per_page, $per_page );
	}
}
