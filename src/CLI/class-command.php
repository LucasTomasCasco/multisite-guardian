<?php
/**
 * WP-CLI commands under the `guardian` namespace.
 *
 * Authorization: WP-CLI runs with local shell access to the server, so it is trusted in the same way
 * as wp-config.php. It does not check capabilities. Use it only from accounts that already control the server.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\CLI;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Inventory\NetworkState;
use MultisiteGuardian\Reports\Exporter;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\History;
use MultisiteGuardian\Services\Overview;
use MultisiteGuardian\Services\Plugins;
use MultisiteGuardian\Services\Report;
use MultisiteGuardian\Services\Sites;
use MultisiteGuardian\Services\Themes;
use WP_CLI;
use WP_CLI\Utils;

final class Command extends \WP_CLI_Command {

	/**
	 * Shows network-level and site-level totals.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian overview
	 *     wp guardian overview --format=json
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function overview( $args, $assoc_args ): void {
		$summary = Overview::summary( NetworkState::build(), Audit::last_run() );

		Utils\format_items( $this->format( $assoc_args ), $this->flatten( $summary ), array( 'metric', 'value' ) );
	}

	/**
	 * Lists the sites of the network.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Limit results to one administrative status.
	 * ---
	 * options:
	 *   - enabled
	 *   - archived
	 *   - spam
	 *   - deleted
	 * ---
	 *
	 * [--search=<term>]
	 * : Match against the site name or URL.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian sites --status=archived
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function sites( $args, $assoc_args ): void {
		$status = (string) ( $assoc_args['status'] ?? '' );
		$this->validate( $status, Sites::STATUSES, 'status' );

		$rows = array_map(
			static fn( array $site ): array => array(
				'id'             => $site['id'],
				'name'           => $site['name'],
				'url'            => $site['url'],
				'status'         => $site['status'],
				'theme'          => $site['stylesheet'],
				'active_plugins' => count( $site['active_plugins'] ),
				'registered'     => $site['registered'],
				'last_updated'   => $site['last_updated'],
			),
			Sites::filter( NetworkState::build(), $status, (string) ( $assoc_args['search'] ?? '' ) )
		);

		Utils\format_items( $this->format( $assoc_args ), $rows, array( 'id', 'name', 'url', 'status', 'theme', 'active_plugins', 'registered', 'last_updated' ) );
	}

	/**
	 * Lists plugins with their activation scope, or the sites where one plugin is active.
	 *
	 * ## OPTIONS
	 *
	 * [--scope=<scope>]
	 * : Limit results to one activation scope.
	 * ---
	 * options:
	 *   - network_active
	 *   - site_active
	 *   - inactive
	 *   - must_use
	 *   - drop_in
	 * ---
	 *
	 * [--search=<term>]
	 * : Match against the plugin name or file.
	 *
	 * [--plugin=<file>]
	 * : Lists the sites where this plugin is active individually, e.g. akismet/akismet.php.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian plugins --scope=inactive
	 *     wp guardian plugins --plugin=akismet/akismet.php
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function plugins( $args, $assoc_args ): void {
		$state  = NetworkState::build();
		$format = $this->format( $assoc_args );

		if ( isset( $assoc_args['plugin'] ) ) {
			$plugin = Plugins::find( $state, (string) $assoc_args['plugin'] );
			if ( null === $plugin ) {
				WP_CLI::error( sprintf( 'Plugin not found: %s', $assoc_args['plugin'] ) );
			}

			$rows = array_map(
				static fn( array $site ): array => array(
					'id'   => $site['id'],
					'name' => $site['name'],
					'url'  => $site['url'],
				),
				Sites::in( $state, $plugin['site_ids'] )
			);

			Utils\format_items( $format, $rows, array( 'id', 'name', 'url' ) );
			return;
		}

		$scope = (string) ( $assoc_args['scope'] ?? '' );
		$this->validate( $scope, Plugins::SCOPES, 'scope' );

		$rows = array_map(
			static fn( array $plugin ): array => array(
				'file'              => $plugin['file'],
				'name'              => $plugin['name'],
				'version'           => $plugin['version'],
				'available_version' => $plugin['available_version'],
				'update_status'     => $plugin['update_status'],
				'scope'             => $plugin['scope'],
				'sites'             => count( $plugin['site_ids'] ),
				'author'            => $plugin['author'],
			),
			Plugins::filter( $state, $scope, (string) ( $assoc_args['search'] ?? '' ) )
		);

		Utils\format_items( $format, $rows, array( 'file', 'name', 'version', 'available_version', 'update_status', 'scope', 'sites', 'author' ) );
	}

	/**
	 * Lists installed themes and whether any site uses them.
	 *
	 * ## OPTIONS
	 *
	 * [--status=<status>]
	 * : Limit results to themes in use or unused.
	 * ---
	 * options:
	 *   - in_use
	 *   - unused
	 * ---
	 *
	 * [--search=<term>]
	 * : Match against the theme name or directory.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian themes --status=unused --format=csv
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function themes( $args, $assoc_args ): void {
		$status = (string) ( $assoc_args['status'] ?? '' );
		$this->validate( $status, Themes::STATUSES, 'status' );

		$rows = array_map(
			static fn( array $theme ): array => array(
				'stylesheet'      => $theme['stylesheet'],
				'name'            => $theme['name'],
				'version'         => $theme['version'],
				'parent'          => $theme['parent'],
				'network_enabled' => $theme['network_enabled'],
				'update_status'   => $theme['update_status'],
				'sites'           => count( $theme['site_ids'] ),
			),
			Themes::filter( NetworkState::build(), $status, (string) ( $assoc_args['search'] ?? '' ) )
		);

		Utils\format_items( $this->format( $assoc_args ), $rows, array( 'stylesheet', 'name', 'version', 'parent', 'network_enabled', 'update_status', 'sites' ) );
	}

	/**
	 * Runs the audit rules and stores the result as the last audit run.
	 *
	 * Exits with status 1 when a finding reaches the --fail-on severity, so the command can gate a pipeline.
	 *
	 * ## OPTIONS
	 *
	 * [--severity=<severity>]
	 * : Limit output to one severity.
	 * ---
	 * options:
	 *   - critical
	 *   - warning
	 *   - information
	 * ---
	 *
	 * [--fail-on=<severity>]
	 * : Exit with status 1 when a finding has this severity or higher.
	 * ---
	 * options:
	 *   - critical
	 *   - warning
	 *   - information
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian audit --fail-on=critical
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function audit( $args, $assoc_args ): void {
		$severity = (string) ( $assoc_args['severity'] ?? '' );
		$fail_on  = (string) ( $assoc_args['fail-on'] ?? '' );
		$this->validate( $severity, array_keys( Finding::SEVERITY_ORDER ), 'severity' );
		$this->validate( $fail_on, array_keys( Finding::SEVERITY_ORDER ), 'fail-on' );

		$audit = Audit::run( NetworkState::build() );
		Audit::record( $audit );

		$rows = array_map(
			static fn( array $finding ): array => array(
				'rule_id'        => $finding['rule_id'],
				'severity'       => $finding['severity'],
				'resource_type'  => $finding['resource_type'],
				'resource'       => $finding['resource'],
				'description'    => $finding['description'],
				'evidence'       => implode( '; ', $finding['evidence'] ),
				'recommendation' => $finding['recommendation'],
			),
			Audit::filter( $audit['findings'], $severity )
		);

		Utils\format_items( $this->format( $assoc_args ), $rows, array( 'rule_id', 'severity', 'resource_type', 'resource', 'description', 'evidence', 'recommendation' ) );

		if ( '' !== $fail_on && $this->reaches( $audit['findings'], $fail_on ) ) {
			WP_CLI::halt( 1 );
		}
	}

	/**
	 * Lists the saved audit runs, newest first.
	 *
	 * ## OPTIONS
	 *
	 * [--limit=<limit>]
	 * : Number of runs to show, from 1 to 50.
	 * ---
	 * default: 20
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian history --limit=10 --format=csv
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function history( $args, $assoc_args ): void {
		$limit = (int) ( $assoc_args['limit'] ?? 20 );
		if ( $limit < 1 || $limit > History::KEEP ) {
			WP_CLI::error( sprintf( 'Invalid --limit value "%s". Allowed: 1 to %d.', $assoc_args['limit'], History::KEEP ) );
		}

		Utils\format_items(
			$this->format( $assoc_args ),
			History::recent( $limit ),
			array( 'run_at', 'critical', 'warning', 'information', 'sites', 'plugins', 'themes' )
		);
	}

	/**
	 * Generates the consolidated audit report and prints it to stdout.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Report format.
	 * ---
	 * default: json
	 * options:
	 *   - json
	 *   - csv
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp guardian report --format=json > guardian-report.json
	 *     wp guardian report --format=csv > guardian-findings.csv
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function report( $args, $assoc_args ): void {
		$format = (string) ( $assoc_args['format'] ?? 'json' );
		$this->validate( $format, array( 'json', 'csv' ), 'format' );

		$report = Report::generate();

		WP_CLI::line( 'json' === $format ? Exporter::json( $report ) : rtrim( Exporter::csv( $report ) ) );
	}

	private function format( array $assoc_args ): string {
		return (string) ( $assoc_args['format'] ?? 'table' );
	}

	private function validate( string $value, array $allowed, string $name ): void {
		if ( '' !== $value && ! in_array( $value, $allowed, true ) ) {
			WP_CLI::error( sprintf( 'Invalid --%s value "%s". Allowed: %s.', $name, $value, implode( ', ', $allowed ) ) );
		}
	}

	private function reaches( array $findings, string $threshold ): bool {
		foreach ( $findings as $finding ) {
			if ( Finding::SEVERITY_ORDER[ $finding['severity'] ] <= Finding::SEVERITY_ORDER[ $threshold ] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Turns nested summary data into metric/value rows.
	 */
	private function flatten( array $data, string $prefix = '' ): array {
		$rows = array();
		foreach ( $data as $key => $value ) {
			$metric = '' === $prefix ? (string) $key : $prefix . '.' . $key;

			if ( is_array( $value ) ) {
				$rows = array_merge( $rows, $this->flatten( $value, $metric ) );
				continue;
			}

			$rows[] = array(
				'metric' => $metric,
				'value'  => $value,
			);
		}

		return $rows;
	}
}
