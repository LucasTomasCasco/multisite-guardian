<?php
/**
 * Shared WP_List_Table behavior: pagination and filter views. Subclasses define the columns.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Services\Query;
use WP_List_Table;

abstract class InventoryTable extends WP_List_Table {

	const PER_PAGE = 20;

	/**
	 * @param array  $rows           Rows already matching the filter and search.
	 * @param array  $counts         Totals per filter value. The empty key is the unfiltered total.
	 * @param string $base_url       Page URL used by the filter views.
	 * @param string $filter_key     Query argument that holds the filter value.
	 * @param string $current_filter Active filter value, or an empty string for all rows.
	 * @param string $label_group    Labels group used for the view names.
	 */
	public function __construct(
		protected array $rows,
		protected array $counts,
		protected string $base_url,
		protected string $filter_key,
		protected string $current_filter,
		protected string $label_group,
		string $singular,
		string $plural
	) {
		parent::__construct(
			array(
				'singular' => $singular,
				'plural'   => $plural,
				'ajax'     => false,
			)
		);
	}

	public function prepare_items(): void {
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$this->set_pagination_args(
			array(
				'total_items' => count( $this->rows ),
				'per_page'    => self::PER_PAGE,
			)
		);

		$this->items = Query::slice( $this->rows, $this->get_pagenum(), self::PER_PAGE );
	}

	protected function get_views(): array {
		$views = array();

		foreach ( $this->counts as $value => $count ) {
			$url   = '' === $value ? $this->base_url : add_query_arg( $this->filter_key, $value, $this->base_url );
			$class = $value === $this->current_filter ? ' class="current" aria-current="page"' : '';
			$label = '' === $value ? __( 'All', 'multisite-guardian' ) : Labels::get( $this->label_group, $value );

			$views[ '' === $value ? 'all' : $value ] = sprintf(
				'<a href="%1$s"%2$s>%3$s <span class="count">(%4$d)</span></a>',
				esc_url( $url ),
				$class,
				esc_html( $label ),
				$count
			);
		}

		return $views;
	}

	protected function column_default( $item, $column_name ): string {
		return esc_html( (string) ( $item[ $column_name ] ?? '' ) );
	}

	/**
	 * WordPress stores the zero date for the main site's last update, which has no meaningful date.
	 */
	protected function format_date( string $value ): string {
		if ( str_starts_with( $value, '0000-00-00' ) ) {
			return '&mdash;';
		}

		return esc_html( mysql2date( 'Y-m-d H:i', $value ) );
	}

	protected function format_nullable( ?string $value ): string {
		return null === $value || '' === $value ? '&mdash;' : esc_html( $value );
	}
}
