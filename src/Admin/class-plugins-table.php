<?php
/**
 * Plugin inventory table. Must-use plugins and drop-ins are shown with a scope label and no activation controls.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class PluginsTable extends InventoryTable {

	public function __construct( array $rows, array $counts, string $base_url, string $filter_key, string $current_filter, string $label_group ) {
		parent::__construct( $rows, $counts, $base_url, $filter_key, $current_filter, $label_group, 'plugin', 'plugins' );
	}

	public function get_columns(): array {
		return array(
			'name'          => __( 'Name', 'multisite-guardian' ),
			'version'       => __( 'Installed', 'multisite-guardian' ),
			'available'     => __( 'Available', 'multisite-guardian' ),
			'update_status' => __( 'Update status', 'multisite-guardian' ),
			'scope'         => __( 'Activation', 'multisite-guardian' ),
			'sites'         => __( 'Sites where active', 'multisite-guardian' ),
			'author'        => __( 'Author', 'multisite-guardian' ),
		);
	}

	public function no_items(): void {
		esc_html_e( 'No plugins match this filter.', 'multisite-guardian' );
	}

	protected function column_name( array $item ): string {
		return sprintf( '<strong>%s</strong><br><code>%s</code>', esc_html( $item['name'] ), esc_html( $item['file'] ) );
	}

	protected function column_version( array $item ): string {
		return $this->format_nullable( $item['version'] );
	}

	protected function column_available( array $item ): string {
		return $this->format_nullable( $item['available_version'] );
	}

	protected function column_update_status( array $item ): string {
		return esc_html( Labels::get( 'update_status', $item['update_status'] ) );
	}

	protected function column_scope( array $item ): string {
		return esc_html( Labels::get( 'plugin_scope', $item['scope'] ) );
	}

	protected function column_sites( array $item ): string {
		$count = count( $item['site_ids'] );
		if ( 0 === $count ) {
			return '0';
		}

		$url = add_query_arg(
			array(
				'page'   => Menu::SLUG . '-plugins',
				'plugin' => $item['file'],
			),
			network_admin_url( 'admin.php' )
		);

		return sprintf(
			'%1$d &middot; <a href="%2$s">%3$s</a>',
			$count,
			esc_url( $url ),
			esc_html__( 'View sites', 'multisite-guardian' )
		);
	}

	protected function column_author( array $item ): string {
		return $this->format_nullable( $item['author'] );
	}
}
