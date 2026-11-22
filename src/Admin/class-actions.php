<?php
/**
 * Handlers for state-changing or download actions. Each one checks a nonce and a capability.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Inventory\NetworkState;
use MultisiteGuardian\Reports\Exporter;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\Report;

final class Actions {

	const RUN_AUDIT_NONCE = 'multisite_guardian_run_audit';
	const REPORT_NONCE    = 'multisite_guardian_report';

	public static function run_audit(): void {
		check_admin_referer( self::RUN_AUDIT_NONCE );
		self::require_capability();

		$audit = Audit::run( NetworkState::build() );
		Audit::record( $audit );

		wp_safe_redirect(
			add_query_arg(
				'audit_saved',
				'1',
				network_admin_url( 'admin.php?page=' . Menu::SLUG . '-audit' )
			)
		);
		exit;
	}

	public static function download_report(): void {
		check_admin_referer( self::REPORT_NONCE );
		self::require_capability();

		$format = isset( $_GET['format'] ) ? sanitize_key( wp_unslash( $_GET['format'] ) ) : 'json';
		if ( ! in_array( $format, array( 'json', 'csv' ), true ) ) {
			wp_die( esc_html__( 'Unsupported report format.', 'multisite-guardian' ), 400 );
		}

		$report = Report::generate();
		$body   = 'json' === $format ? Exporter::json( $report ) : Exporter::csv( $report );

		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: ' . ( 'json' === $format ? 'application/json' : 'text/csv' ) . '; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="multisite-guardian-' . gmdate( 'Ymd-His' ) . '.' . $format . '"' );

		// Raw JSON or CSV download. The body is machine-readable, not HTML.
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	private static function require_capability(): void {
		if ( ! current_user_can( 'manage_network' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'multisite-guardian' ), 403 );
		}
	}
}
