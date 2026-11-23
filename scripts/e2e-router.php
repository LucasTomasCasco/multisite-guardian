<?php
/**
 * Router for PHP's built-in server in CI: serves existing files and directories, sends everything else to WordPress.
 *
 * Copied into the WordPress directory by scripts/ci-network.sh.
 */

$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( is_string( $path ) && ( is_file( __DIR__ . $path ) || is_dir( __DIR__ . $path ) ) ) {
	return false;
}

require __DIR__ . '/index.php';
