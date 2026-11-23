<?php
/**
 * Plugin-by-site matrix for small networks.
 *
 * @var array|null $data['matrix']      Output of Services\Plugins::matrix(), or null when the network is too large.
 * @var int        $data['snapshot_at'] Snapshot timestamp.
 * @var string     $data['refresh_url'] Link that rebuilds the snapshot.
 * @var int        $data['max_sites']   Largest network the matrix is shown for.
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

$matrix = $data['matrix'];
?>
<h1><?php esc_html_e( 'Plugin matrix', 'multisite-guardian' ); ?></h1>

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

<?php if ( null === $matrix ) : ?>
	<div class="notice notice-info inline">
		<p>
			<?php
			printf(
				/* translators: %d: largest network size the matrix is shown for. */
				esc_html__( 'The matrix is shown for networks of up to %d live sites. Use the Plugins page to see the sites where each plugin is active.', 'multisite-guardian' ),
				(int) $data['max_sites']
			);
			?>
		</p>
	</div>
<?php elseif ( empty( $matrix['plugins'] ) ) : ?>
	<p><?php esc_html_e( 'No conventional plugins are installed.', 'multisite-guardian' ); ?></p>
<?php else : ?>
	<p class="description">
		<?php esc_html_e( 'N: network active (runs on every site). S: active on this site only. Empty: not active here.', 'multisite-guardian' ); ?>
	</p>
	<div style="overflow-x: auto;">
		<table class="widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Plugin', 'multisite-guardian' ); ?></th>
					<?php foreach ( $matrix['sites'] as $site ) : ?>
						<th scope="col" style="text-align: center;" title="<?php echo esc_attr( $site['url'] ); ?>">
							<a href="<?php echo esc_url( $site['admin_url'] ); ?>"><?php echo esc_html( '' === $site['name'] ? (string) $site['id'] : $site['name'] ); ?></a>
						</th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $matrix['plugins'] as $row ) : ?>
					<tr>
						<th scope="row"><?php echo esc_html( $row['name'] ); ?></th>
						<?php foreach ( $matrix['sites'] as $site ) : ?>
							<?php
							if ( 'network_active' === $row['scope'] ) {
								$cell = 'N';
							} elseif ( in_array( $site['id'], $row['site_ids'], true ) ) {
								$cell = 'S';
							} else {
								$cell = '';
							}
							?>
							<td style="text-align: center;"><strong><?php echo esc_html( $cell ); ?></strong></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
<?php endif; ?>
