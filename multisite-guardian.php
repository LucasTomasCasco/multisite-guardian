<?php
/**
 * Plugin Name:       Multisite Guardian
 * Description:       Network-wide inventory, audit and diagnostics for WordPress Multisite.
 * Version:           0.3.0
 * Requires at least: 6.5
 * Requires PHP:      8.3
 * Network:           true
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       multisite-guardian
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian;

defined( 'ABSPATH' ) || exit;

const VERSION = '0.3.0';

// Maps MultisiteGuardian\Admin\SitesTable to src/Admin/class-sites-table.php (WordPress file naming).
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$segments = explode( '\\', substr( $class_name, strlen( $prefix ) ) );
		$name     = strtolower( preg_replace( '/(?<!^)[A-Z]/', '-$0', array_pop( $segments ) ) );
		$dir      = __DIR__ . '/src/' . implode( '/', $segments );

		foreach ( array( 'class-', 'interface-' ) as $type ) {
			$file = $dir . '/' . $type . $name . '.php';
			if ( is_readable( $file ) ) {
				require $file;
				return;
			}
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		Plugin::boot();
	}
);
