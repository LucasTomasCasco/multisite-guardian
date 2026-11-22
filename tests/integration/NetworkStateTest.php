<?php
/**
 * Builds the snapshot from a real multisite network.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Integration;

use MultisiteGuardian\Inventory\NetworkState;
use WP_UnitTestCase;

final class NetworkStateTest extends WP_UnitTestCase {

	private int $editorial;
	private int $shop;
	private int $archived;

	public function set_up(): void {
		parent::set_up();

		$this->editorial = self::factory()->blog->create( array( 'path' => '/editorial/' ) );
		$this->shop      = self::factory()->blog->create( array( 'path' => '/shop/' ) );
		$this->archived  = self::factory()->blog->create( array( 'path' => '/archived/' ) );

		update_blog_status( $this->archived, 'archived', 1 );
		update_blog_option( $this->shop, 'active_plugins', array( 'akismet/akismet.php' ) );
		update_site_option( 'active_sitewide_plugins', array( 'hello.php' => time() ) );
	}

	public function test_sites_are_classified_by_status(): void {
		$state = NetworkState::build();

		$statuses = array_column( $state['sites'], 'status', 'id' );
		$this->assertSame( 'archived', $statuses[ $this->archived ] );
		$this->assertSame( 'enabled', $statuses[ $this->shop ] );
	}

	public function test_site_level_activation_is_listed_per_site_and_network_activation_is_separate(): void {
		$state = NetworkState::build();

		$plugins = array_column( $state['plugins'], null, 'file' );
		$this->assertSame( 'network_active', $plugins['hello.php']['scope'] );
		$this->assertSame( 'site_active', $plugins['akismet/akismet.php']['scope'] );
		$this->assertSame( array( $this->shop ), $plugins['akismet/akismet.php']['site_ids'] );

		$shop = array_values( array_filter( $state['sites'], fn( array $site ): bool => $site['id'] === $this->shop ) )[0];
		$this->assertSame( array( 'akismet/akismet.php' ), $shop['active_plugins'] );
	}

	public function test_must_use_plugins_and_drop_ins_have_no_activation_or_update_state(): void {
		$unmanaged = array_filter(
			NetworkState::build()['plugins'],
			static fn( array $plugin ): bool => in_array( $plugin['scope'], array( 'must_use', 'drop_in' ), true )
		);
		if ( empty( $unmanaged ) ) {
			$this->markTestSkipped( 'The test network has no must-use plugins or drop-ins. The demo network does.' );
		}

		foreach ( $unmanaged as $plugin ) {
			$this->assertSame( 'not_applicable', $plugin['update_status'], $plugin['file'] );
			$this->assertSame( array(), $plugin['site_ids'], $plugin['file'] );
		}
	}

	public function test_active_plugin_whose_file_is_missing_is_reported_with_its_site(): void {
		update_blog_option( $this->editorial, 'active_plugins', array( 'ghost/ghost.php' ) );

		$missing = NetworkState::build()['missing_plugin_references'];

		$this->assertSame(
			array(
				array(
					'file'    => 'ghost/ghost.php',
					'site_id' => $this->editorial,
				),
			),
			$missing
		);
	}

	public function test_building_the_snapshot_does_not_change_the_current_blog(): void {
		$blog_before = get_current_blog_id();

		NetworkState::build();

		$this->assertSame( $blog_before, get_current_blog_id() );
		$this->assertFalse( ms_is_switched() );
	}

	public function test_missing_update_transient_is_reported_as_unknown_not_as_up_to_date(): void {
		delete_site_transient( 'update_plugins' );

		$plugins = array_column( NetworkState::build()['plugins'], 'update_status', 'file' );

		$this->assertSame( 'unknown', $plugins['hello.php'] );
		$this->assertNull( NetworkState::build()['update_checks']['plugins'] );
	}

	public function test_cached_snapshot_is_reused_and_refresh_requests_are_rate_limited(): void {
		$cached = NetworkState::get( true );
		update_blog_option( $this->shop, 'active_plugins', array() );

		// A refresh right after a build returns the cached copy, so repeated clicks cannot force a full read.
		$this->assertSame( $cached, NetworkState::get( true ) );
		$this->assertSame( $cached, NetworkState::get() );
	}

	public function test_refresh_rebuilds_once_the_cached_snapshot_is_older_than_the_minimum_interval(): void {
		$old                  = NetworkState::get( true );
		$old['generated_at'] -= NetworkState::MIN_REBUILD_INTERVAL + 1;
		set_site_transient( NetworkState::CACHE_KEY, $old, MINUTE_IN_SECONDS );

		$this->assertNotSame( $old['generated_at'], NetworkState::get( true )['generated_at'] );
	}

	public function test_snapshot_shape_matches_the_documented_contract(): void {
		$state = NetworkState::build();

		$this->assertSame(
			array( 'schema', 'generated_at', 'network_id', 'environment', 'update_checks', 'sites', 'plugins', 'themes', 'missing_plugin_references' ),
			array_keys( $state )
		);
		$this->assertSame(
			array( 'wordpress_version', 'php_version', 'php_minimum', 'mysql_version', 'wp_debug', 'wp_debug_display' ),
			array_keys( $state['environment'] )
		);
		$this->assertSame(
			array( 'id', 'name', 'url', 'admin_url', 'stylesheet', 'template', 'active_plugins', 'status', 'live', 'registered', 'last_updated' ),
			array_keys( $state['sites'][0] )
		);
		$this->assertSame(
			array( 'file', 'name', 'version', 'author', 'scope', 'site_ids', 'update_status', 'available_version' ),
			array_keys( $state['plugins'][0] )
		);
		$this->assertSame(
			array( 'stylesheet', 'name', 'version', 'parent', 'network_enabled', 'site_ids', 'update_status', 'available_version' ),
			array_keys( $state['themes'][0] )
		);
	}

	public function test_cached_snapshot_from_another_schema_is_rebuilt(): void {
		// Simulates a snapshot written by an earlier version of the plugin, before the "live" flag existed.
		$old_shape = NetworkState::build();
		unset( $old_shape['schema'], $old_shape['sites'][0]['live'] );
		set_site_transient( NetworkState::CACHE_KEY, $old_shape, MINUTE_IN_SECONDS );

		$state = NetworkState::get();

		$this->assertSame( NetworkState::SCHEMA, $state['schema'] );
		$this->assertArrayHasKey( 'live', $state['sites'][0] );
	}

	public function test_environment_reports_the_running_php_version(): void {
		$this->assertSame( PHP_VERSION, NetworkState::build()['environment']['php_version'] );
	}
}
