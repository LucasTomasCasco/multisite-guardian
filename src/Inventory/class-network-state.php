<?php
/**
 * Builds the network-wide snapshot shared by every interface.
 *
 * Per-site data is read with get_blog_option(), so the current blog never changes
 * during a run and there is no context to restore.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Inventory;

defined( 'ABSPATH' ) || exit;

final class NetworkState {

	const CACHE_KEY = 'multisite_guardian_network_state';
	/**
	 * Bump when the snapshot shape changes. A cached snapshot with another schema is ignored, so an upgrade
	 * never renders against an old structure.
	 */
	const SCHEMA    = 2;
	const CACHE_TTL = 10 * MINUTE_IN_SECONDS;
	/**
	 * A refresh request within this window returns the cached snapshot, so repeated clicks cannot
	 * force a full network read each time.
	 */
	const MIN_REBUILD_INTERVAL = MINUTE_IN_SECONDS;
	const BATCH_SIZE           = 100;
	const STALE_AFTER          = 2 * DAY_IN_SECONDS;

	/**
	 * Returns the cached snapshot. Rebuilds and rewrites the cache when it is empty, or when $fresh is true
	 * and the cached snapshot is older than MIN_REBUILD_INTERVAL.
	 *
	 * @param bool $fresh Ask for a rebuild.
	 */
	public static function get( bool $fresh = false ): array {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) && self::SCHEMA === ( $cached['schema'] ?? null ) ) {
			$too_recent = time() - $cached['generated_at'] < self::MIN_REBUILD_INTERVAL;
			if ( ! $fresh || $too_recent ) {
				return $cached;
			}
		}

		$state = self::build();
		set_site_transient( self::CACHE_KEY, $state, self::CACHE_TTL );

		return $state;
	}

	/**
	 * Reads the network without touching the cache. Used by WP-CLI, reports and audit runs.
	 */
	public static function build(): array {
		// get_plugins() and get_dropins() live in the admin includes, which REST and WP-CLI do not load.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$sites          = self::sites();
		$live_sites     = array_values( array_filter( $sites, static fn( array $site ): bool => $site['live'] ) );
		$plugin_updates = self::update_data( 'update_plugins' );
		$theme_updates  = self::update_data( 'update_themes' );
		$sitewide       = array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) );

		$site_map = array();
		foreach ( $live_sites as $site ) {
			foreach ( $site['active_plugins'] as $file ) {
				$site_map[ $file ][] = $site['id'];
			}
		}

		return array(
			'schema'                    => self::SCHEMA,
			'generated_at'              => time(),
			'network_id'                => get_current_network_id(),
			'environment'               => self::environment(),
			'update_checks'             => array(
				'plugins' => $plugin_updates['last_checked'],
				'themes'  => $theme_updates['last_checked'],
			),
			'sites'                     => $sites,
			'plugins'                   => self::plugins( $sitewide, $site_map, $plugin_updates ),
			'themes'                    => self::themes( $live_sites, $theme_updates ),
			'missing_plugin_references' => self::missing_plugin_references( $sitewide, $live_sites ),
		);
	}

	/**
	 * Reads every site in the network, including archived, spam and deleted ones.
	 */
	private static function sites(): array {
		$sites  = array();
		$offset = 0;

		do {
			$batch = get_sites(
				array(
					'network_id' => get_current_network_id(),
					'number'     => self::BATCH_SIZE,
					'offset'     => $offset,
					'orderby'    => 'id',
					'order'      => 'ASC',
				)
			);

			foreach ( $batch as $site ) {
				$sites[] = self::site( $site );
			}

			$batch_count = count( $batch );
			$offset     += self::BATCH_SIZE;
		} while ( self::BATCH_SIZE === $batch_count );

		return $sites;
	}

	/**
	 * Switches to the site once and reads its options in that context. The original blog is restored
	 * even when a read fails, because the switch is wrapped in try/finally.
	 */
	private static function site( \WP_Site $site ): array {
		$id     = (int) $site->blog_id;
		$status = self::site_status( $site );

		switch_to_blog( $id );
		try {
			$row = array(
				'id'             => $id,
				'name'           => (string) get_option( 'blogname' ),
				'url'            => get_home_url(),
				'admin_url'      => get_admin_url(),
				'stylesheet'     => (string) get_option( 'stylesheet' ),
				'template'       => (string) get_option( 'template' ),
				'active_plugins' => array_values( (array) get_option( 'active_plugins', array() ) ),
			);
		} finally {
			restore_current_blog();
		}

		return array_merge(
			$row,
			array(
				'status'       => $status,
				'live'         => ! in_array( $status, array( 'deleted', 'spam' ), true ),
				'registered'   => $site->registered,
				'last_updated' => $site->last_updated,
			)
		);
	}

	/**
	 * Deleted and spam sites do not count toward plugin or theme usage. Archived sites still count.
	 */
	private static function site_status( \WP_Site $site ): string {
		if ( $site->deleted ) {
			return 'deleted';
		}
		if ( $site->spam ) {
			return 'spam';
		}
		if ( $site->archived ) {
			return 'archived';
		}

		return 'enabled';
	}

	/**
	 * Conventional, must-use and drop-in plugins, one list entry each.
	 */
	private static function plugins( array $sitewide, array $site_map, array $plugin_updates ): array {
		$plugins = array();

		foreach ( get_plugins() as $file => $data ) {
			$scope = 'inactive';
			if ( in_array( $file, $sitewide, true ) ) {
				$scope = 'network_active';
			} elseif ( isset( $site_map[ $file ] ) ) {
				$scope = 'site_active';
			}

			list( $update_status, $available_version ) = self::update_status( $plugin_updates, $file );

			$plugins[] = array(
				'file'              => $file,
				'name'              => $data['Name'],
				'version'           => $data['Version'],
				'author'            => $data['AuthorName'],
				'scope'             => $scope,
				'site_ids'          => $site_map[ $file ] ?? array(),
				'update_status'     => $update_status,
				'available_version' => $available_version,
			);
		}

		foreach ( get_mu_plugins() as $file => $data ) {
			$plugins[] = self::unmanaged_plugin( $file, $data['Name'], $data['Version'], $data['AuthorName'], 'must_use' );
		}

		foreach ( array_keys( get_dropins() ) as $file ) {
			$plugins[] = self::unmanaged_plugin( $file, $file, null, null, 'drop_in' );
		}

		return $plugins;
	}

	private static function unmanaged_plugin( string $file, string $name, ?string $version, ?string $author, string $scope ): array {
		return array(
			'file'              => $file,
			'name'              => $name,
			'version'           => $version,
			'author'            => $author,
			'scope'             => $scope,
			'site_ids'          => array(),
			'update_status'     => 'not_applicable',
			'available_version' => null,
		);
	}

	private static function themes( array $sites, array $theme_updates ): array {
		$allowed = (array) get_site_option( 'allowedthemes', array() );
		$usage   = array();

		foreach ( $sites as $site ) {
			$usage[ $site['stylesheet'] ][] = $site['id'];
			$usage[ $site['template'] ][]   = $site['id'];
		}

		$themes = array();
		foreach ( wp_get_themes() as $stylesheet => $theme ) {
			$parent = $theme->parent();

			list( $update_status, $available_version ) = self::update_status( $theme_updates, $stylesheet );

			$themes[] = array(
				'stylesheet'        => $stylesheet,
				'name'              => $theme->get( 'Name' ),
				'version'           => $theme->get( 'Version' ),
				'parent'            => $parent ? $parent->get_stylesheet() : null,
				'network_enabled'   => isset( $allowed[ $stylesheet ] ),
				'site_ids'          => array_values( array_unique( $usage[ $stylesheet ] ?? array() ) ),
				'update_status'     => $update_status,
				'available_version' => $available_version,
			);
		}

		return $themes;
	}

	/**
	 * Active plugin references whose files no longer exist, network-wide or per site.
	 */
	private static function missing_plugin_references( array $sitewide, array $sites ): array {
		$missing = array();

		foreach ( $sitewide as $file ) {
			if ( ! self::plugin_file_exists( $file ) ) {
				$missing[] = array(
					'file'    => $file,
					'site_id' => null,
				);
			}
		}

		foreach ( $sites as $site ) {
			foreach ( $site['active_plugins'] as $file ) {
				if ( ! self::plugin_file_exists( $file ) ) {
					$missing[] = array(
						'file'    => $file,
						'site_id' => $site['id'],
					);
				}
			}
		}

		return $missing;
	}

	private static function plugin_file_exists( string $file ): bool {
		return file_exists( WP_PLUGIN_DIR . '/' . $file );
	}

	/**
	 * Normalizes the update transient. A missing transient means the data is unknown.
	 *
	 * @return array{last_checked: ?int, available: array<string, string>, checked: array<string, mixed>}
	 */
	private static function update_data( string $transient ): array {
		$data = get_site_transient( $transient );
		if ( ! is_object( $data ) || empty( $data->last_checked ) ) {
			return array(
				'last_checked' => null,
				'available'    => array(),
				'checked'      => array(),
			);
		}

		$available = array();
		foreach ( (array) ( $data->response ?? array() ) as $key => $item ) {
			$available[ $key ] = (string) ( (array) $item )['new_version'];
		}

		return array(
			'last_checked' => (int) $data->last_checked,
			'available'    => $available,
			'checked'      => (array) ( $data->checked ?? array() ),
		);
	}

	/**
	 * @return array{0: string, 1: ?string} Update status and available version.
	 */
	private static function update_status( array $updates, string $key ): array {
		if ( null === $updates['last_checked'] ) {
			return array( 'unknown', null );
		}
		if ( isset( $updates['available'][ $key ] ) ) {
			return array( 'update_available', $updates['available'][ $key ] );
		}
		if ( time() - $updates['last_checked'] > self::STALE_AFTER ) {
			return array( 'stale', null );
		}
		if ( array_key_exists( $key, $updates['checked'] ) ) {
			return array( 'up_to_date', null );
		}

		return array( 'unknown', null );
	}

	private static function environment(): array {
		global $wpdb;

		return array(
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => PHP_VERSION,
			'php_minimum'       => (string) apply_filters( 'multisite_guardian_minimum_php_version', '8.3' ),
			'mysql_version'     => $wpdb->db_version(),
			'wp_debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
			// WordPress displays debug output by default when WP_DEBUG is on, so an unset constant counts as on.
			'wp_debug_display'  => ! defined( 'WP_DEBUG_DISPLAY' ) || (bool) WP_DEBUG_DISPLAY,
		);
	}
}
