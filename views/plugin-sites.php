<?php
/**
 * Sites where one plugin is active individually. Network-wide activation is shown separately.
 *
 * @var array $data['selected'] Plugin row from NetworkState.
 * @var array $data['sites']    Site rows where the plugin is active individually.
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

$selected = $data['selected'];
?>
<h2>
	<?php
	printf(
		/* translators: %s: plugin name. */
		esc_html__( 'Sites where %s is active', 'multisite-guardian' ),
		esc_html( $selected['name'] )
	);
	?>
</h2>

<?php if ( 'network_active' === $selected['scope'] ) : ?>
	<p class="description">
		<?php esc_html_e( 'This plugin is network active, so it runs on every site in addition to the individual activations below.', 'multisite-guardian' ); ?>
	</p>
<?php endif; ?>

<?php if ( empty( $data['sites'] ) ) : ?>
	<p><?php esc_html_e( 'No site activates this plugin individually.', 'multisite-guardian' ); ?></p>
<?php else : ?>
	<table class="widefat striped" style="max-width: 900px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Site ID', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Name', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'URL', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Admin', 'multisite-guardian' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $data['sites'] as $site ) : ?>
				<tr>
					<td><?php echo esc_html( (string) $site['id'] ); ?></td>
					<td><?php echo esc_html( $site['name'] ); ?></td>
					<td><a href="<?php echo esc_url( $site['url'] ); ?>"><?php echo esc_html( $site['url'] ); ?></a></td>
					<td><a href="<?php echo esc_url( $site['admin_url'] ); ?>"><?php esc_html_e( 'Open admin', 'multisite-guardian' ); ?></a></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
