<?php
/**
 * Audit controls: run and save an audit, download reports, and show the last saved run.
 *
 * Findings below are computed from the snapshot on every page view. Saving is an explicit action.
 *
 * @var array|null $data['last_audit']  Last saved audit, or null.
 * @var bool       $data['audit_saved'] True right after a successful save.
 *
 * @package MultisiteGuardian
 */

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Admin\Actions;

// admin-post.php lives in wp-admin/, not in wp-admin/network/, so admin_url() is the correct base here.
$action_url = admin_url( 'admin-post.php' );
$report_url = static fn( string $format ): string => wp_nonce_url(
	add_query_arg(
		array(
			'action' => 'multisite_guardian_report',
			'format' => $format,
		),
		$action_url
	),
	Actions::REPORT_NONCE
);
?>

<?php if ( $data['audit_saved'] ) : ?>
	<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Audit saved.', 'multisite-guardian' ); ?></p></div>
<?php endif; ?>

<p>
	<?php if ( $data['last_audit'] ) : ?>
		<?php
		printf(
			/* translators: %s: date and time of the last saved audit. */
			esc_html__( 'Last saved audit: %s.', 'multisite-guardian' ),
			esc_html( wp_date( 'Y-m-d H:i', $data['last_audit']['generated_at'] ) )
		);
		?>
	<?php else : ?>
		<?php esc_html_e( 'No audit has been saved yet.', 'multisite-guardian' ); ?>
	<?php endif; ?>
</p>

<form method="post" action="<?php echo esc_url( $action_url ); ?>" style="display: inline-block; margin-right: 12px;">
	<input type="hidden" name="action" value="multisite_guardian_run_audit">
	<?php wp_nonce_field( Actions::RUN_AUDIT_NONCE ); ?>
	<?php submit_button( __( 'Run and save audit', 'multisite-guardian' ), 'primary', 'submit', false ); ?>
</form>

<a class="button" href="<?php echo esc_url( $report_url( 'json' ) ); ?>"><?php esc_html_e( 'Download JSON report', 'multisite-guardian' ); ?></a>
<a class="button" href="<?php echo esc_url( $report_url( 'csv' ) ); ?>"><?php esc_html_e( 'Download CSV findings', 'multisite-guardian' ); ?></a>

<p class="description">
	<?php esc_html_e( 'Reports list site URLs, plugin names and versions. Share them only with people who manage the network.', 'multisite-guardian' ); ?>
</p>

<p class="description">
	<?php esc_html_e( 'A finding means a rule matched. Its absence means no rule matched the data available, not that every possible issue was checked.', 'multisite-guardian' ); ?>
</p>
