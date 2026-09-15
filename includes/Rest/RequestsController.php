<?php
/**
 * Admin REST controller for creating, editing, and listing File Requests.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Rest;

use FileRequestManager\Core\Capabilities;
use FileRequestManager\Domain\Request;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Services\AllowedFileTypes;
use FileRequestManager\Services\FormDesignRegistry;
use FileRequestManager\Services\FormLayoutRegistry;
use FileRequestManager\Services\TemplateRegistry;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin REST controller backing the "Create/Edit File Request" app.
 */
class RequestsController {

	const NAMESPACE_V1 = 'renevo/v1';

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * @var TemplateRegistry
	 */
	private $templates;

	/**
	 * Constructor.
	 *
	 * @param RequestRepository $requests  Request repository.
	 * @param TemplateRegistry  $templates Template registry.
	 */
	public function __construct( RequestRepository $requests, TemplateRegistry $templates ) {
		$this->requests  = $requests;
		$this->templates = $templates;
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/requests',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'index' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'status'   => array( 'type' => 'string' ),
						'search'   => array( 'type' => 'string' ),
						'page'     => array( 'type' => 'integer' ),
						'per_page' => array( 'type' => 'integer' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/requests/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'trash' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/requests/(?P<id>\d+)/duplicate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'duplicate' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/templates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'templates' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/file-types',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'file_types' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/form-design-presets',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'form_design_presets' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/form-layouts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'form_layouts' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Permission callback shared by every route in this controller.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return Capabilities::current_user_can_manage();
	}

	/**
	 * GET /requests
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function index( WP_REST_Request $req ) {
		$result = $this->requests->query(
			array(
				'status'   => $req->get_param( 'status' ) ? $req->get_param( 'status' ) : 'any',
				'search'   => (string) $req->get_param( 'search' ),
				'page'     => $req->get_param( 'page' ) ? (int) $req->get_param( 'page' ) : 1,
				'per_page' => $req->get_param( 'per_page' ) ? (int) $req->get_param( 'per_page' ) : 20,
			)
		);

		$response = rest_ensure_response(
			array_map(
				static function ( Request $request ) {
					return $request->to_array();
				},
				$result['items']
			)
		);
		$response->header( 'X-WP-Total', (string) $result['total'] );

		return $response;
	}

	/**
	 * GET /requests/{id}
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function show( WP_REST_Request $req ) {
		$request = $this->requests->find( (int) $req->get_param( 'id' ) );

		if ( ! $request ) {
			return $this->not_found();
		}

		return rest_ensure_response( $request->to_array() );
	}

	/**
	 * POST /requests
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function create( WP_REST_Request $req ) {
		$request = Request::from_array( (array) $req->get_json_params() );
		$request = $this->requests->create( $request );

		return rest_ensure_response( $request->to_array() );
	}

	/**
	 * PUT /requests/{id}
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update( WP_REST_Request $req ) {
		$id       = (int) $req->get_param( 'id' );
		$existing = $this->requests->find( $id );

		if ( ! $existing ) {
			return $this->not_found();
		}

		$request = Request::from_array( (array) $req->get_json_params(), $id, $existing );
		$updated = $this->requests->update( $id, $request );

		return rest_ensure_response( $updated->to_array() );
	}

	/**
	 * DELETE /requests/{id} — moves to trash, matching native post behaviour.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function trash( WP_REST_Request $req ) {
		$id = (int) $req->get_param( 'id' );

		if ( ! $this->requests->find( $id ) ) {
			return $this->not_found();
		}

		$this->requests->trash( $id );

		return rest_ensure_response( array( 'deleted' => true ) );
	}

	/**
	 * POST /requests/{id}/duplicate
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function duplicate( WP_REST_Request $req ) {
		$copy = $this->requests->duplicate( (int) $req->get_param( 'id' ) );

		if ( ! $copy ) {
			return $this->not_found();
		}

		return rest_ensure_response( $copy->to_array() );
	}

	/**
	 * GET /templates
	 *
	 * @return WP_REST_Response
	 */
	public function templates() {
		return rest_ensure_response( $this->templates->all() );
	}

	/**
	 * GET /file-types — the registry backing the "allowed file types" picker.
	 *
	 * @return WP_REST_Response
	 */
	public function file_types() {
		return rest_ensure_response( AllowedFileTypes::all() );
	}

	/**
	 * GET /form-design-presets — the registry backing the "Form Design" tab.
	 *
	 * @return WP_REST_Response
	 */
	public function form_design_presets() {
		return rest_ensure_response( FormDesignRegistry::all() );
	}

	/**
	 * GET /form-layouts — the registry backing the layout picker.
	 *
	 * @return WP_REST_Response
	 */
	public function form_layouts() {
		return rest_ensure_response( FormLayoutRegistry::all() );
	}

	/**
	 * Builds the standard "request not found" error.
	 *
	 * @return WP_Error
	 */
	private function not_found() {
		return new WP_Error( 'renevo_not_found', __( "We couldn't find this request.", 'renevo-file-request-manager' ), array( 'status' => 404 ) );
	}
}
