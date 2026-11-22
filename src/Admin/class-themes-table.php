<?php
/**
 * Theme inventory table.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class ThemesTable extends InventoryTable {

	public function __construct( array $rows, array $counts, string $base_url, string $filter_key, string $current_filter, string $label_group ) {
		parent::__construct( $rows, $counts, $base_url, $filter_key, $current_filter, $label_group, 'theme', 'themes' );
	}

	public function get_columns(): array {
		return array(
			'name'            => __( 'Name', 'multisite-guardian' ),
			'stylesheet'      => __( 'Directory', 'multisite-guardian' ),
			'version'         => __( 'Installed', 'multisite-guardian' ),
			'parent'          => __( 'Parent theme', 'multisite-guardian' ),
			'update_status'   => __( 'Update status', 'multisite-guardian' ),
			'network_enabled' => __( 'Network enabled', 'multisite-guardian' ),
			'sites'           => __( 'Sites using it', 'multisite-guardian' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No themes match this filter.', 'multisite-guardian' );
	}

	protected function column_name( array $item ): string {
		return esc_html( $item['name'] );
	}

	protected function column_version( array $item ): string {
		return $this->format_nullable( $item['version'] );
	}

	protected function column_parent( array $item ): string {
		return $this->format_nullable( $item['parent'] );
	}

	protected function column_update_status( array $item ): string {
		return esc_html( Labels::get( 'update_status', $item['update_status'] ) );
	}

	protected function column_network_enabled( array $item ): string {
		return $item['network_enabled'] ? esc_html__( 'Yes', 'multisite-guardian' ) : esc_html__( 'No', 'multisite-guardian' );
	}

	protected function column_sites( array $item ): string {
		return (string) count( $item['site_ids'] );
	}
}
