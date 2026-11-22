#!/bin/sh
# Measures the snapshot and audit on a synthetic network. Prints measured values only.
#
#   sh scripts/benchmark-network.sh [site_count]     # default 50
#
# Creates the sites, activates plugins on them, measures, then deletes the synthetic sites.
# Run after scripts/bootstrap-network.sh, because it needs the demo plugins.

set -eu

COUNT="${1:-50}"
URL="${GUARDIAN_URL:-http://localhost:8095}"

wp_cli() {
	docker compose run --rm -T wpcli wp "$@" --url="$URL" 2>/dev/null
}

echo "==> creating $COUNT synthetic sites"
docker compose run --rm -T wpcli sh -c "
	i=1
	while [ \$i -le $COUNT ]; do
		wp site create --slug=bench-\$i --title=\"Bench \$i\" --email=bench-\$i@guardian.test >/dev/null 2>&1 || true
		i=\$((i + 1))
	done
" >/dev/null

echo "==> activating plugins on the synthetic sites"
wp_cli eval '
	$bench_sites = static function (): array {
		$sites = get_sites( array( "number" => 1000, "search" => "bench-", "search_columns" => array( "path" ) ) );
		return array_values( array_filter( $sites, static fn( $site ) => 0 === strpos( $site->path, "/bench-" ) ) );
	};
	$sites = $bench_sites();
	foreach ( $sites as $index => $site ) {
		$plugins = $index % 2 ? array( "akismet/akismet.php" ) : array( "guardian-demo-forms/guardian-demo-forms.php" );
		update_blog_option( $site->blog_id, "active_plugins", $plugins );
	}
	echo count( $sites ), " sites prepared", PHP_EOL;
'

echo "==> measuring (in-process, one request)"
wp_cli eval '
	global $wpdb;

	$measure = static function ( callable $work ): array {
		global $wpdb;
		$queries_before = $wpdb->num_queries;
		$started        = hrtime( true );
		$result         = $work();
		return array(
			"ms"      => round( ( hrtime( true ) - $started ) / 1e6, 1 ),
			"queries" => $wpdb->num_queries - $queries_before,
			"result"  => $result,
		);
	};

	$sites = count( get_sites( array( "number" => 1000 ) ) );
	$build = $measure( static fn() => MultisiteGuardian\Inventory\NetworkState::build() );
	$state = $build["result"];
	$cache  = $measure( static fn() => MultisiteGuardian\Inventory\NetworkState::get( true ) );
	$audit  = $measure( static fn() => MultisiteGuardian\Services\Audit::run( $state ) );

	echo wp_json_encode( array(
		"sites"               => $sites,
		"php"                 => PHP_VERSION,
		"mysql"               => $wpdb->db_version(),
		"build_ms"            => $build["ms"],
		"build_queries"       => $build["queries"],
		"snapshot_and_cache_ms" => $cache["ms"],
		"audit_ms"            => $audit["ms"],
		"audit_queries"       => $audit["queries"],
		"findings"            => count( $audit["result"]["findings"] ),
		"snapshot_kb"          => round( strlen( serialize( $state ) ) / 1024, 1 ),
		"peak_memory_mb"      => round( memory_get_peak_usage( true ) / 1048576, 1 ),
	), JSON_PRETTY_PRINT ), PHP_EOL;
' | sed -n '/^{/,/^}/p'

echo "==> cleaning up synthetic sites"
wp_cli eval '
	$bench_sites = static function (): array {
		$sites = get_sites( array( "number" => 1000, "search" => "bench-", "search_columns" => array( "path" ) ) );
		return array_values( array_filter( $sites, static fn( $site ) => 0 === strpos( $site->path, "/bench-" ) ) );
	};
	foreach ( $bench_sites() as $site ) {
		wpmu_delete_blog( (int) $site->blog_id, true );
	}
	echo "removed", PHP_EOL;
'
