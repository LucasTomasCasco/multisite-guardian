<?php
/**
 * Shared list layout: heading, optional intro block, filter views, search and table.
 *
 * @var string                          $data['title']       Page heading.
 * @var \WP_List_Table                  $data['table']       Table to display.
 * @var int                             $data['snapshot_at'] Snapshot timestamp.
 * @var string                          $data['refresh_url'] Link that rebuilds the snapshot.
 * @var string                          $data['form_page']   Page slug for the search form.
 * @var string                          $data['filter_key']  Query argument that holds the filter.
 * @var string                          $data['filter']      Active filter value.
 * @var string                          $data['intro']       Optional path to an intro view.
 * @var array|null                      $data['selected']    Plugin selected for the site drill-down.
 * @var array                           $data['sites']       Sites where the selected plugin is active.
 * @var string                          $data['after']       Optional path to a view printed after the table.
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

$table = $data['table'];
?>
<h1><?php echo esc_html( $data['title'] ); ?></h1>

<p class="description">
	<?php
	printf(
		/* translators: %s: date and time of the network snapshot. */
		esc_html__( 'Snapshot taken %s.', 'multisite-guardian' ),
		esc_html( wp_date( 'Y-m-d H:i', $data['snapshot_at'] ) )
	);
	?>
	<a href="<?php echo esc_url( $data['refresh_url'] ); ?>"><?php esc_html_e( 'Refresh data', 'multisite-guardian' ); ?></a>
</p>

<?php
if ( isset( $data['intro'] ) ) {
	require $data['intro'];
}
?>

<?php $table->views(); ?>

<form method="get">
	<input type="hidden" name="page" value="<?php echo esc_attr( $data['form_page'] ); ?>">
	<?php if ( '' !== $data['filter'] ) : ?>
		<input type="hidden" name="<?php echo esc_attr( $data['filter_key'] ); ?>" value="<?php echo esc_attr( $data['filter'] ); ?>">
	<?php endif; ?>
	<?php
	$table->search_box( __( 'Search', 'multisite-guardian' ), 'multisite-guardian-search' );
	$table->prepare_items();
	$table->display();
	?>
</form>

<?php
if ( isset( $data['after'] ) ) {
	require $data['after'];
}

if ( ! empty( $data['selected'] ) ) {
	require dirname( __DIR__ ) . '/views/plugin-sites.php';
}
?>
