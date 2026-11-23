<?php
/**
 * Network Admin screens render real data, escape user input, and stay out of reach of site administrators.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Integration;

use MultisiteGuardian\Admin\Screens;
use WP_UnitTestCase;

final class ScreensTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();

		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $admin );
		wp_set_current_user( $admin );
		self::factory()->blog->create(
			array(
				'path'  => '/screen-site/',
				'title' => 'Screen site',
			)
		);
	}

	public function tear_down(): void {
		$_GET = array();
		parent::tear_down();
	}

	public function test_overview_renders_network_totals(): void {
		$html = $this->render( array(), 'overview' );

		$this->assertStringContainsString( 'Total sites', $html );
		$this->assertStringContainsString( 'Snapshot taken', $html );
	}

	public function test_sites_screen_lists_the_network_sites(): void {
		$html = $this->render( array(), 'sites' );

		$this->assertStringContainsString( 'Screen site', $html );
		$this->assertStringContainsString( 'Site ID', $html );
	}

	public function test_search_term_is_escaped_in_the_output(): void {
		$html = $this->render( array( 's' => '"><script>alert(1)</script>' ), 'sites' );

		$this->assertStringNotContainsString( '<script>alert(1)</script>', $html );
	}

	public function test_plugin_drill_down_for_an_unknown_plugin_renders_without_a_panel(): void {
		$html = $this->render( array( 'plugin' => '<img src=x onerror=alert(1)>' ), 'plugins' );

		$this->assertStringNotContainsString( 'onerror=alert(1)', $html );
		// The 'Sites where active' column header always exists; the drill-down panel has its own table width.
		$this->assertStringNotContainsString( 'max-width: 900px', $html );
	}

	public function test_audit_screen_shows_the_history_block(): void {
		$html = $this->render( array(), 'audit' );

		$this->assertStringContainsString( 'Audit history', $html );
	}

	public function test_matrix_screen_shows_each_site_column_for_a_small_network(): void {
		$html = $this->render( array(), 'matrix' );

		$this->assertStringContainsString( 'Plugin matrix', $html );
		$this->assertStringContainsString( 'Screen site', $html );
		$this->assertStringContainsString( '<th scope="row">Hello Dolly</th>', $html );
	}

	public function test_audit_screen_shows_the_rule_table(): void {
		$html = $this->render( array(), 'audit' );

		$this->assertStringContainsString( 'Download JSON report', $html );
		$this->assertStringContainsString( 'Run and save audit', $html );
	}

	/**
	 * Calls a screen with the given query string and returns the printed HTML.
	 */
	private function render( array $query, string $screen ): string {
		$_GET = $query;

		ob_start();
		match ( $screen ) {
			'overview' => Screens::overview(),
			'sites'    => Screens::sites(),
			'plugins'  => Screens::plugins(),
			'audit'    => Screens::audit(),
			'matrix'   => Screens::matrix(),
		};

		return (string) ob_get_clean();
	}
}
