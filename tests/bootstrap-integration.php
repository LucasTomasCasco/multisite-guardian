<?php
/**
 * Loads WordPress in Multisite mode through the WordPress PHPUnit library, then loads the plugin.
 *
 * @package MultisiteGuardian
 */

$wp_phpunit = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

define( 'WP_TESTS_CONFIG_FILE_PATH', __DIR__ . '/wp-tests-config.php' );
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__ ) . '/vendor/yoast/phpunit-polyfills' );

require_once $wp_phpunit . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require dirname( __DIR__ ) . '/multisite-guardian.php';
	}
);

require $wp_phpunit . '/includes/bootstrap.php';
