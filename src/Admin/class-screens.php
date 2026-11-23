<?php
/**
 * Renders the Network Admin pages. Data comes from the same services used by REST and WP-CLI.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Inventory\NetworkState;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\History;
use MultisiteGuardian\Services\Overview;
use MultisiteGuardian\Services\Plugins;
use MultisiteGuardian\Services\Query;
use MultisiteGuardian\Services\Sites;
use MultisiteGuardian\Services\Themes;

final class Screens {

	const REFRESH_NONCE = 'multisite_guardian_refresh';

	public static function overview(): void {
		$state = self::state();

		self::render(
			'overview',
			array(
				'summary'     => Overview::summary( $state, Audit::last_run() ),
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG ),
			)
		);
	}

	public static function sites(): void {
		self::require_list_table();

		$state  = self::state();
		$status = self::choice( 'status', Sites::STATUSES );
		$table  = new SitesTable(
			Sites::filter( $state, $status, self::query( 's' ) ),
			self::counts( $state['sites'], 'status', Sites::STATUSES ),
			self::page_url( Menu::SLUG . '-sites' ),
			'status',
			$status,
			'site_status'
		);

		self::render(
			'list',
			array(
				'title'       => __( 'Sites', 'multisite-guardian' ),
				'table'       => $table,
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG . '-sites' ),
				'form_page'   => Menu::SLUG . '-sites',
				'filter_key'  => 'status',
				'filter'      => $status,
			)
		);
	}

	public static function plugins(): void {
		self::require_list_table();

		$state = self::state();
		$scope = self::choice( 'scope', Plugins::SCOPES );
		$table = new PluginsTable(
			Plugins::filter( $state, $scope, self::query( 's' ) ),
			self::counts( $state['plugins'], 'scope', Plugins::SCOPES ),
			self::page_url( Menu::SLUG . '-plugins' ),
			'scope',
			$scope,
			'plugin_scope'
		);

		$selected = Plugins::find( $state, self::query( 'plugin' ) );

		self::render(
			'list',
			array(
				'title'       => __( 'Plugins', 'multisite-guardian' ),
				'table'       => $table,
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG . '-plugins' ),
				'form_page'   => Menu::SLUG . '-plugins',
				'filter_key'  => 'scope',
				'filter'      => $scope,
				'selected'    => $selected,
				'sites'       => $selected ? Sites::in( $state, $selected['site_ids'] ) : array(),
			)
		);
	}

	public static function themes(): void {
		self::require_list_table();

		$state  = self::state();
		$status = self::choice( 'status', Themes::STATUSES );
		$unused = count( Themes::unused( $state ) );
		$table  = new ThemesTable(
			Themes::filter( $state, $status, self::query( 's' ) ),
			array(
				''       => count( $state['themes'] ),
				'in_use' => count( $state['themes'] ) - $unused,
				'unused' => $unused,
			),
			self::page_url( Menu::SLUG . '-themes' ),
			'status',
			$status,
			'theme_usage'
		);

		self::render(
			'list',
			array(
				'title'       => __( 'Themes', 'multisite-guardian' ),
				'table'       => $table,
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG . '-themes' ),
				'form_page'   => Menu::SLUG . '-themes',
				'filter_key'  => 'status',
				'filter'      => $status,
			)
		);
	}

	public static function audit(): void {
		self::require_list_table();

		$state    = self::state();
		$findings = Audit::run( $state )['findings'];
		$severity = self::choice( 'severity', array_keys( Finding::SEVERITY_ORDER ) );
		$table    = new FindingsTable(
			Audit::filter( $findings, $severity ),
			self::counts( $findings, 'severity', array_keys( Finding::SEVERITY_ORDER ) ),
			self::page_url( Menu::SLUG . '-audit' ),
			'severity',
			$severity,
			'severity'
		);

		self::render(
			'list',
			array(
				'title'       => __( 'Audit', 'multisite-guardian' ),
				'table'       => $table,
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG . '-audit' ),
				'form_page'   => Menu::SLUG . '-audit',
				'filter_key'  => 'severity',
				'filter'      => $severity,
				'intro'       => dirname( __DIR__, 2 ) . '/views/audit-actions.php',
				'after'       => dirname( __DIR__, 2 ) . '/views/audit-history.php',
				'history'     => History::recent( 20 ),
				'last_audit'  => Audit::last_run(),
				'audit_saved' => '' !== self::query( 'audit_saved' ),
			)
		);
	}

	public static function matrix(): void {
		$state = self::state();

		self::render(
			'matrix',
			array(
				'matrix'      => Plugins::matrix( $state ),
				'snapshot_at' => $state['generated_at'],
				'refresh_url' => self::refresh_url( Menu::SLUG . '-matrix' ),
				'max_sites'   => Plugins::MATRIX_MAX_SITES,
			)
		);
	}

	/**
	 * Uses the cached snapshot unless the refresh link was clicked with a valid nonce.
	 */
	private static function state(): array {
		$refresh = '' !== self::query( 'refresh' ) && wp_verify_nonce( self::query( '_wpnonce' ), self::REFRESH_NONCE );

		return NetworkState::get( $refresh );
	}

	/**
	 * Counts per filter value. The empty key holds the total, which the list view labels "All".
	 */
	private static function counts( array $rows, string $key, array $values ): array {
		$by_value = Query::counts( $rows, $key );
		$counts   = array( '' => count( $rows ) );

		foreach ( $values as $value ) {
			$counts[ $value ] = $by_value[ $value ] ?? 0;
		}

		return $counts;
	}

	private static function choice( string $key, array $allowed ): string {
		$value = self::query( $key );

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	private static function query( string $key ): string {
		// Read-only list filters. Mutating actions verify nonces in Actions.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
	}

	private static function page_url( string $slug ): string {
		return network_admin_url( 'admin.php?page=' . $slug );
	}

	private static function refresh_url( string $slug ): string {
		return wp_nonce_url( add_query_arg( 'refresh', '1', self::page_url( $slug ) ), self::REFRESH_NONCE );
	}

	private static function require_list_table(): void {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
	}

	// The included view reads $data, which PHPCS cannot see.
	private static function render( string $view, array $data ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		echo '<div class="wrap">';
		require __DIR__ . '/../../views/' . $view . '.php';
		echo '</div>';
	}
}
