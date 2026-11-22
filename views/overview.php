<?php
/**
 * Network overview. Network-level and site-level figures are listed in separate sections.
 *
 * @var array  $data['summary']     Output of Services\Overview::summary().
 * @var int    $data['snapshot_at'] Snapshot timestamp.
 * @var string $data['refresh_url'] Link that rebuilds the snapshot.
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

$summary = $data['summary'];

$rows = array(
	__( 'Network', 'multisite-guardian' ) => array(
		__( 'WordPress version', 'multisite-guardian' )  => $summary['environment']['wordpress_version'],
		__( 'PHP version', 'multisite-guardian' )        => $summary['environment']['php_version'],
		__( 'PHP policy minimum', 'multisite-guardian' ) => $summary['environment']['php_minimum'],
		__( 'MySQL version', 'multisite-guardian' )      => $summary['environment']['mysql_version'],
		__( 'WP_DEBUG', 'multisite-guardian' )           => $summary['environment']['wp_debug'] ? __( 'Enabled', 'multisite-guardian' ) : __( 'Disabled', 'multisite-guardian' ),
	),
	__( 'Sites', 'multisite-guardian' )   => array(
		__( 'Total sites', 'multisite-guardian' )    => $summary['sites']['total'],
		__( 'Enabled', 'multisite-guardian' )        => $summary['sites']['enabled'],
		__( 'Archived', 'multisite-guardian' )       => $summary['sites']['archived'],
		__( 'Marked as spam', 'multisite-guardian' ) => $summary['sites']['spam'],
		__( 'Deleted', 'multisite-guardian' )        => $summary['sites']['deleted'],
	),
	__( 'Plugins', 'multisite-guardian' ) => array(
		__( 'Installed (conventional)', 'multisite-guardian' ) => $summary['plugins']['installed'],
		__( 'Network active', 'multisite-guardian' )   => $summary['plugins']['network_active'],
		__( 'Active on individual sites only', 'multisite-guardian' ) => $summary['plugins']['site_active'],
		__( 'Inactive', 'multisite-guardian' )         => $summary['plugins']['inactive'],
		__( 'Total individual site activations', 'multisite-guardian' ) => $summary['plugins']['site_activations'],
		__( 'Must-use plugins', 'multisite-guardian' ) => $summary['plugins']['must_use'],
		__( 'Drop-ins', 'multisite-guardian' )         => $summary['plugins']['drop_ins'],
		__( 'Updates listed by WordPress', 'multisite-guardian' ) => $summary['plugins']['updates_available'],
	),
	__( 'Themes', 'multisite-guardian' )  => array(
		__( 'Installed', 'multisite-guardian' )          => $summary['themes']['installed'],
		__( 'Network enabled', 'multisite-guardian' )    => $summary['themes']['network_enabled'],
		__( 'Unused by any site', 'multisite-guardian' ) => $summary['themes']['unused'],
		__( 'Updates listed by WordPress', 'multisite-guardian' ) => $summary['themes']['updates_available'],
	),
);
?>
<h1><?php esc_html_e( 'Multisite Guardian', 'multisite-guardian' ); ?></h1>

<p class="description">
	<?php
	printf(
		/* translators: %s: date and time of the network snapshot. */
		esc_html__( 'Snapshot taken %s.', 'multisite-guardian' ),
		esc_html( wp_date( 'Y-m-d H:i', $data['snapshot_at'] ) )
	);
	?>
	<a href="<?php echo esc_url( $data['refresh_url'] ); ?>"><?php esc_html_e( 'Refresh data', 'multisite-guardian' ); ?></a>
	|
	<?php
	if ( $summary['last_audit_at'] ) {
		printf(
			/* translators: %s: date and time of the last saved audit. */
			esc_html__( 'Last saved audit: %s.', 'multisite-guardian' ),
			esc_html( wp_date( 'Y-m-d H:i', $summary['last_audit_at'] ) )
		);
	} else {
		esc_html_e( 'No audit has been saved yet.', 'multisite-guardian' );
	}
	?>
</p>

<?php foreach ( $rows as $section => $items ) : ?>
	<h2><?php echo esc_html( $section ); ?></h2>
	<table class="widefat striped" style="max-width: 720px;">
		<tbody>
			<?php foreach ( $items as $label => $value ) : ?>
				<tr>
					<th scope="row" style="width: 60%;"><?php echo esc_html( $label ); ?></th>
					<td><?php echo esc_html( is_bool( $value ) ? ( $value ? 'true' : 'false' ) : (string) $value ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endforeach; ?>

<p class="description">
	<?php esc_html_e( 'Update status comes from the update data WordPress already stores. This plugin does not query WordPress.org.', 'multisite-guardian' ); ?>
</p>
