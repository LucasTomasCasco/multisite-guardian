<?php
/**
 * Removes the options and cache entries created by Multisite Guardian.
 *
 * @package MultisiteGuardian
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/src/Services/class-history.php';

MultisiteGuardian\Services\History::drop();
delete_site_option( 'multisite_guardian_last_audit' );
delete_site_transient( 'multisite_guardian_network_state' );
