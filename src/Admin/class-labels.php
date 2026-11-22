<?php
/**
 * Translatable labels for enumerated values shown in the admin.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Admin;

defined( 'ABSPATH' ) || exit;

final class Labels {

	public static function get( string $group, string $value ): string {
		$labels = array(
			'site_status'   => array(
				'enabled'  => __( 'Enabled', 'multisite-guardian' ),
				'archived' => __( 'Archived', 'multisite-guardian' ),
				'spam'     => __( 'Spam', 'multisite-guardian' ),
				'deleted'  => __( 'Deleted', 'multisite-guardian' ),
			),
			'plugin_scope'  => array(
				'network_active' => __( 'Network active', 'multisite-guardian' ),
				'site_active'    => __( 'Site active', 'multisite-guardian' ),
				'inactive'       => __( 'Inactive', 'multisite-guardian' ),
				'must_use'       => __( 'Must-use', 'multisite-guardian' ),
				'drop_in'        => __( 'Drop-in', 'multisite-guardian' ),
			),
			'update_status' => array(
				'update_available' => __( 'Update available', 'multisite-guardian' ),
				'up_to_date'       => __( 'Up to date (checked)', 'multisite-guardian' ),
				'stale'            => __( 'Outdated data', 'multisite-guardian' ),
				'unknown'          => __( 'Unknown', 'multisite-guardian' ),
				'not_applicable'   => __( 'Not applicable', 'multisite-guardian' ),
			),
			'theme_usage'   => array(
				'in_use' => __( 'In use', 'multisite-guardian' ),
				'unused' => __( 'Unused', 'multisite-guardian' ),
			),
			'severity'      => array(
				'critical'    => __( 'Critical', 'multisite-guardian' ),
				'warning'     => __( 'Warning', 'multisite-guardian' ),
				'information' => __( 'Information', 'multisite-guardian' ),
			),
		);

		return $labels[ $group ][ $value ] ?? $value;
	}
}
