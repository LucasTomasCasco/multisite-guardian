<?php
/**
 * Composition root: registers hooks for each interface.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Admin\Actions;
use MultisiteGuardian\Admin\Menu;
use MultisiteGuardian\CLI\Command;
use MultisiteGuardian\REST\Controller;
use MultisiteGuardian\Services\History;

final class Plugin {

	/**
	 * Plugins outside wordpress.org load their translations explicitly.
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'multisite-guardian', false, plugin_basename( dirname( __DIR__ ) ) . '/languages' );
	}

	public static function boot(): void {
		if ( ! is_multisite() ) {
			return;
		}

		History::maybe_install();
		add_action( 'init', array( self::class, 'load_textdomain' ) );
		add_action( 'network_admin_menu', array( Menu::class, 'register' ) );
		add_action( 'admin_post_multisite_guardian_run_audit', array( Actions::class, 'run_audit' ) );
		add_action( 'admin_post_multisite_guardian_report', array( Actions::class, 'download_report' ) );
		add_action( 'rest_api_init', array( new Controller(), 'register_routes' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'guardian', Command::class );
		}
	}
}
