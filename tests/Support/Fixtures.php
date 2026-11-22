<?php
/**
 * Builds network snapshots for unit tests. Every value is explicit, so each test states the case it covers.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Support;

final class Fixtures {

	const NOW = 1800000000;

	public static function state( array $overrides = array() ): array {
		return array_replace(
			array(
				'generated_at'              => self::NOW,
				'network_id'                => 1,
				'environment'               => array(
					'wordpress_version' => '6.8.3',
					'php_version'       => '8.3.10',
					'php_minimum'       => '8.3',
					'mysql_version'     => '8.0.36',
					'wp_debug'          => false,
					'wp_debug_display'  => true,
				),
				'update_checks'             => array(
					'plugins' => self::NOW - HOUR_IN_SECONDS,
					'themes'  => self::NOW - HOUR_IN_SECONDS,
				),
				'sites'                     => array(),
				'plugins'                   => array(),
				'themes'                    => array(),
				'missing_plugin_references' => array(),
			),
			$overrides
		);
	}

	public static function site( int $id, array $overrides = array() ): array {
		return array_replace(
			array(
				'id'             => $id,
				'name'           => 'Site ' . $id,
				'url'            => 'https://example.test/site-' . $id . '/',
				'admin_url'      => 'https://example.test/site-' . $id . '/wp-admin/',
				'status'         => 'enabled',
				'live'           => true,
				'registered'     => '2024-01-01 00:00:00',
				'last_updated'   => '2024-06-01 00:00:00',
				'stylesheet'     => 'twentytwentyfive',
				'template'       => 'twentytwentyfive',
				'active_plugins' => array(),
			),
			$overrides
		);
	}

	public static function plugin( string $file, array $overrides = array() ): array {
		return array_replace(
			array(
				'file'              => $file,
				'name'              => ucfirst( basename( $file, '.php' ) ),
				'version'           => '1.0.0',
				'author'            => 'Author',
				'scope'             => 'inactive',
				'site_ids'          => array(),
				'update_status'     => 'up_to_date',
				'available_version' => null,
			),
			$overrides
		);
	}

	public static function theme( string $stylesheet, array $overrides = array() ): array {
		return array_replace(
			array(
				'stylesheet'        => $stylesheet,
				'name'              => ucfirst( $stylesheet ),
				'version'           => '1.0.0',
				'parent'            => null,
				'network_enabled'   => true,
				'site_ids'          => array(),
				'update_status'     => 'up_to_date',
				'available_version' => null,
			),
			$overrides
		);
	}
}
