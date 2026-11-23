<?php
/**
 * Network Admin menu. Each page is registered with the capability it needs.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class Menu {

	const SLUG = 'multisite-guardian';

	public static function register(): void {
		add_menu_page(
			__( 'Multisite Guardian', 'multisite-guardian' ),
			__( 'Multisite Guardian', 'multisite-guardian' ),
			'manage_network',
			self::SLUG,
			array( Screens::class, 'overview' ),
			'dashicons-shield-alt',
			25
		);

		add_submenu_page( self::SLUG, __( 'Overview', 'multisite-guardian' ), __( 'Overview', 'multisite-guardian' ), 'manage_network', self::SLUG, array( Screens::class, 'overview' ) );
		add_submenu_page( self::SLUG, __( 'Sites', 'multisite-guardian' ), __( 'Sites', 'multisite-guardian' ), 'manage_sites', self::SLUG . '-sites', array( Screens::class, 'sites' ) );
		add_submenu_page( self::SLUG, __( 'Plugins', 'multisite-guardian' ), __( 'Plugins', 'multisite-guardian' ), 'manage_network_plugins', self::SLUG . '-plugins', array( Screens::class, 'plugins' ) );
		add_submenu_page( self::SLUG, __( 'Matrix', 'multisite-guardian' ), __( 'Matrix', 'multisite-guardian' ), 'manage_network_plugins', self::SLUG . '-matrix', array( Screens::class, 'matrix' ) );
		add_submenu_page( self::SLUG, __( 'Themes', 'multisite-guardian' ), __( 'Themes', 'multisite-guardian' ), 'manage_network_themes', self::SLUG . '-themes', array( Screens::class, 'themes' ) );
		add_submenu_page( self::SLUG, __( 'Audit', 'multisite-guardian' ), __( 'Audit', 'multisite-guardian' ), 'manage_network', self::SLUG . '-audit', array( Screens::class, 'audit' ) );
	}
}
