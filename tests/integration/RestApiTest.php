<?php
/**
 * REST routes: authentication, capability checks, validation and pagination against a real network.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\Tests\Integration;

use WP_REST_Request;
use WP_UnitTestCase;

final class RestApiTest extends WP_UnitTestCase {

	const ROUTE = '/multisite-guardian/v1';

	public function set_up(): void {
		parent::set_up();

		rest_get_server();
		self::factory()->blog->create_many( 3, array() );
	}

	public function test_anonymous_requests_are_rejected(): void {
		$response = $this->get( '/overview' );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_site_administrator_cannot_read_network_data(): void {
		$site_admin = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $site_admin );

		foreach ( array( '/overview', '/sites', '/plugins', '/themes', '/audit' ) as $path ) {
			$this->assertSame( 403, $this->get( $path )->get_status(), $path );
		}
	}

	public function test_network_administrator_reads_every_collection(): void {
		$this->act_as_super_admin();

		$this->assertSame( 200, $this->get( '/overview' )->get_status() );
		$this->assertSame( 200, $this->get( '/sites' )->get_status() );
		$this->assertSame( 200, $this->get( '/plugins' )->get_status() );
		$this->assertSame( 200, $this->get( '/themes' )->get_status() );
		$this->assertSame( 200, $this->get( '/audit' )->get_status() );
	}

	public function test_sites_are_paginated_with_wordpress_headers(): void {
		$this->act_as_super_admin();

		$response = $this->get(
			'/sites',
			array(
				'per_page' => 2,
				'page'     => 2,
			)
		);

		$this->assertCount( 2, $response->get_data() );
		$total = (int) $response->get_headers()['X-WP-Total'];
		$this->assertSame( 4, $total );
		$this->assertSame( 2, (int) $response->get_headers()['X-WP-TotalPages'] );
	}

	public function test_invalid_parameters_are_rejected_before_reaching_the_service(): void {
		$this->act_as_super_admin();

		$this->assertSame( 400, $this->get( '/sites', array( 'per_page' => 500 ) )->get_status() );
		$this->assertSame( 400, $this->get( '/sites', array( 'page' => 0 ) )->get_status() );
		$this->assertSame( 400, $this->get( '/sites', array( 'status' => 'bogus' ) )->get_status() );
		$this->assertSame( 400, $this->get( '/audit', array( 'severity' => 'fatal' ) )->get_status() );
	}

	public function test_unknown_site_returns_not_found(): void {
		$this->act_as_super_admin();

		$response = $this->get( '/sites/999999' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'multisite_guardian_site_not_found', $response->get_data()['code'] );
	}

	public function test_known_site_is_returned_with_its_status(): void {
		$this->act_as_super_admin();
		$site_id = self::factory()->blog->create( array( 'path' => '/detail/' ) );

		$response = $this->get( '/sites/' . $site_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'enabled', $response->get_data()['status'] );
	}

	private function act_as_super_admin(): void {
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $user );
		wp_set_current_user( $user );
	}

	private function get( string $path, array $params = array() ) {
		$request = new WP_REST_Request( 'GET', self::ROUTE . $path );
		$request->set_query_params( $params );

		return rest_do_request( $request );
	}
}
