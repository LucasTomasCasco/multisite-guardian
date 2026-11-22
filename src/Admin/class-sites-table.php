<?php
/**
 * Sites inventory table.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class SitesTable extends InventoryTable {

	public function __construct( array $rows, array $counts, string $base_url, string $filter_key, string $current_filter, string $label_group ) {
		parent::__construct( $rows, $counts, $base_url, $filter_key, $current_filter, $label_group, 'site', 'sites' );
	}

	public function get_columns(): array {
		return array(
			'id'           => __( 'Site ID', 'multisite-guardian' ),
			'name'         => __( 'Name', 'multisite-guardian' ),
			'url'          => __( 'URL', 'multisite-guardian' ),
			'status'       => __( 'Status', 'multisite-guardian' ),
			'registered'   => __( 'Registered', 'multisite-guardian' ),
			'last_updated' => __( 'Last modified', 'multisite-guardian' ),
			'theme'        => __( 'Active theme', 'multisite-guardian' ),
			'plugins'      => __( 'Active plugins', 'multisite-guardian' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No sites match this filter.', 'multisite-guardian' );
	}

	protected function column_name( array $item ): string {
		$name = '' === $item['name'] ? __( '(no name)', 'multisite-guardian' ) : $item['name'];

		return sprintf( '<a href="%s">%s</a>', esc_url( $item['admin_url'] ), esc_html( $name ) );
	}

	protected function column_url( array $item ): string {
		return sprintf( '<a href="%s">%s</a>', esc_url( $item['url'] ), esc_html( $item['url'] ) );
	}

	protected function column_status( array $item ): string {
		return esc_html( Labels::get( 'site_status', $item['status'] ) );
	}

	protected function column_registered( array $item ): string {
		return $this->format_date( $item['registered'] );
	}

	protected function column_last_updated( array $item ): string {
		return $this->format_date( $item['last_updated'] );
	}

	protected function column_theme( array $item ): string {
		return esc_html( $item['stylesheet'] );
	}

	protected function column_plugins( array $item ): string {
		return (string) count( $item['active_plugins'] );
	}
}
