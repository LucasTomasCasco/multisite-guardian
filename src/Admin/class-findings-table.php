<?php
/**
 * Audit findings table.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class FindingsTable extends InventoryTable {

	public function __construct( array $rows, array $counts, string $base_url, string $filter_key, string $current_filter, string $label_group ) {
		parent::__construct( $rows, $counts, $base_url, $filter_key, $current_filter, $label_group, 'finding', 'findings' );
	}

	public function get_columns(): array {
		return array(
			'severity'       => __( 'Severity', 'multisite-guardian' ),
			'rule_id'        => __( 'Rule', 'multisite-guardian' ),
			'resource'       => __( 'Resource', 'multisite-guardian' ),
			'description'    => __( 'Finding', 'multisite-guardian' ),
			'evidence'       => __( 'Evidence', 'multisite-guardian' ),
			'recommendation' => __( 'Recommendation', 'multisite-guardian' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No findings. This means no rule matched the current data, not that the network was checked for every possible issue.', 'multisite-guardian' );
	}

	protected function column_severity( array $item ): string {
		return esc_html( Labels::get( 'severity', $item['severity'] ) );
	}

	protected function column_rule_id( array $item ): string {
		return sprintf( '<code style="font-size: 12px;">%s</code>', esc_html( $item['rule_id'] ) );
	}

	protected function column_resource( array $item ): string {
		return sprintf( '<code>%s</code><br><small>%s</small>', esc_html( $item['resource'] ), esc_html( $item['resource_type'] ) );
	}

	protected function column_evidence( array $item ): string {
		return esc_html( implode( '; ', $item['evidence'] ) );
	}
}
