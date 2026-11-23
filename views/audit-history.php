<?php
/**
 * Saved audit runs, newest first. Each row is a summary, not the findings themselves.
 *
 * @var array $data['history'] Rows from Services\History::recent().
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

$history = $data['history'];
?>
<h2><?php esc_html_e( 'Audit history', 'multisite-guardian' ); ?></h2>

<?php if ( empty( $history ) ) : ?>
	<p><?php esc_html_e( 'No runs saved yet. Run and save an audit to start the history.', 'multisite-guardian' ); ?></p>
<?php else : ?>
	<table class="widefat striped" style="max-width: 900px;">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Run', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Critical', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Warning', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Information', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Sites', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Plugins', 'multisite-guardian' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Themes', 'multisite-guardian' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $history as $run ) : ?>
				<tr>
					<td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $run['run_at'] ) ); ?></td>
					<td><?php echo esc_html( $run['critical'] ); ?></td>
					<td><?php echo esc_html( $run['warning'] ); ?></td>
					<td><?php echo esc_html( $run['information'] ); ?></td>
					<td><?php echo esc_html( $run['sites'] ); ?></td>
					<td><?php echo esc_html( $run['plugins'] ); ?></td>
					<td><?php echo esc_html( $run['themes'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
