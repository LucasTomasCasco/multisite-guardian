<?php
/**
 * Read by the WordPress PHPUnit library through WP_PHPUNIT__TESTS_CONFIG.
 * Values come from the environment, so the same file serves docker-compose.yml and GitHub Actions.
 *
 * @package MultisiteGuardian
 */

define( 'ABSPATH', rtrim( false !== getenv( 'WP_ABSPATH' ) ? getenv( 'WP_ABSPATH' ) : '/usr/src/wordpress', '/' ) . '/' );
// The WordPress test library reads this variable, so it has to be set here.
$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
define( 'WP_TESTS_MULTISITE', true );
define( 'WP_DEFAULT_THEME', 'twentytwentyfive' );

define( 'DB_NAME', false !== getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', false !== getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'wordpress' );
define( 'DB_PASSWORD', false !== getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : 'wordpress' );
define( 'DB_HOST', false !== getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : 'db' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Multisite Guardian tests' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
