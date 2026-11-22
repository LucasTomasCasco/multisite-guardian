<?php
/**
 * REST API under multisite-guardian/v1. Routes only validate, check capabilities and delegate to Services.
 *
 * @package MultisiteGuardian
 */

namespace MultisiteGuardian\REST;

defined( 'ABSPATH' ) || exit;

use MultisiteGuardian\Audit\Finding;
use MultisiteGuardian\Inventory\NetworkState;
use MultisiteGuardian\Services\Audit;
use MultisiteGuardian\Services\Overview;
use MultisiteGuardian\Services\Plugins;
use MultisiteGuardian\Services\Query;
use MultisiteGuardian\Services\Sites;
use MultisiteGuardian\Services\Themes;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class Controller {

	const REST_NAMESPACE = 'multisite-guardian/v1';

	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			'/overview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'overview' ),
				'permission_callback' => array( $this, 'can_manage_network' ),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sites',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'sites' ),
				'permission_callback' => array( $this, 'can_manage_sites' ),
				'args'                => $this->collection_args(
					array(
						'status' => array(
							'type' => 'string',
							'enum' => Sites::STATUSES,
						),
					)
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/sites/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'site' ),
				'permission_callback' => array( $this, 'can_manage_sites' ),
				'args'                => array(
					'id' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/plugins',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'plugins' ),
				'permission_callback' => array( $this, 'can_manage_plugins' ),
				'args'                => $this->collection_args(
					array(
						'scope' => array(
							'type' => 'string',
							'enum' => Plugins::SCOPES,
						),
					)
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/themes',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'themes' ),
				'permission_callback' => array( $this, 'can_manage_themes' ),
				'args'                => $this->collection_args(
					array(
						'status' => array(
							'type' => 'string',
							'enum' => Themes::STATUSES,
						),
					)
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/audit',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'audit' ),
				'permission_callback' => array( $this, 'can_manage_network' ),
				'args'                => $this->collection_args(
					array(
						'severity' => array(
							'type' => 'string',
							'enum' => array_keys( Finding::SEVERITY_ORDER ),
						),
					)
				),
			)
		);
	}

	public function can_manage_network(): bool {
		return current_user_can( 'manage_network' );
	}

	public function can_manage_sites(): bool {
		return current_user_can( 'manage_sites' );
	}

	public function can_manage_plugins(): bool {
		return current_user_can( 'manage_network_plugins' );
	}

	public function can_manage_themes(): bool {
		return current_user_can( 'manage_network_themes' );
	}

	public function overview(): WP_REST_Response {
		return rest_ensure_response( Overview::summary( NetworkState::get(), Audit::last_run() ) );
	}

	public function sites( WP_REST_Request $request ): WP_REST_Response {
		$sites = Sites::filter( NetworkState::get(), (string) $request['status'], (string) $request['search'] );

		return $this->paginated( $sites, $request );
	}

	public function site( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$site = Sites::find( NetworkState::get(), (int) $request['id'] );
		if ( null === $site ) {
			return new WP_Error( 'multisite_guardian_site_not_found', __( 'Site not found.', 'multisite-guardian' ), array( 'status' => 404 ) );
		}

		return rest_ensure_response( $site );
	}

	public function plugins( WP_REST_Request $request ): WP_REST_Response {
		$plugins = Plugins::filter( NetworkState::get(), (string) $request['scope'], (string) $request['search'] );

		return $this->paginated( $plugins, $request );
	}

	public function themes( WP_REST_Request $request ): WP_REST_Response {
		$themes = Themes::filter( NetworkState::get(), (string) $request['status'], (string) $request['search'] );

		return $this->paginated( $themes, $request );
	}

	public function audit( WP_REST_Request $request ): WP_REST_Response {
		$findings = Audit::filter( Audit::run( NetworkState::get() )['findings'], (string) $request['severity'] );

		return $this->paginated( $findings, $request );
	}

	private function paginated( array $rows, WP_REST_Request $request ): WP_REST_Response {
		$per_page = (int) $request['per_page'];
		$response = rest_ensure_response( Query::slice( $rows, (int) $request['page'], $per_page ) );

		$response->header( 'X-WP-Total', (string) count( $rows ) );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( count( $rows ) / $per_page ) );

		return $response;
	}

	private function collection_args( array $extra ): array {
		return array_merge(
			array(
				'page'     => array(
					'type'    => 'integer',
					'default' => 1,
					'minimum' => 1,
				),
				'per_page' => array(
					'type'    => 'integer',
					'default' => 20,
					'minimum' => 1,
					'maximum' => 100,
				),
				'search'   => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
			$extra
		);
	}
}
