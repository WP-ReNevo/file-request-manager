<?php
/**
 * Admin REST controller for listing submissions and downloading their files.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Rest;

use FileRequestManager\Core\Capabilities;
use FileRequestManager\Domain\Submission;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin REST controller for listing submissions and downloading their files.
 */
class SubmissionsController {

	const NAMESPACE_V1 = RequestsController::NAMESPACE_V1;

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * @var FileStorageService
	 */
	private $file_storage;

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * Constructor.
	 *
	 * @param SubmissionRepository $submissions  Submission repository.
	 * @param FileStorageService   $file_storage File storage service.
	 * @param RequestRepository    $requests     Request repository.
	 */
	public function __construct( SubmissionRepository $submissions, FileStorageService $file_storage, RequestRepository $requests ) {
		$this->submissions  = $submissions;
		$this->file_storage = $file_storage;
		$this->requests     = $requests;
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/submissions',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'index' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/submissions/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/submissions/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/submissions/(?P<id>\d+)/files/(?P<file_id>\d+)/download',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/submissions/(?P<id>\d+)/download-all',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'download_all' ),
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
	 * GET /submissions
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function index( WP_REST_Request $req ) {
		$result = $this->submissions->query(
			array(
				'request_id' => $req->get_param( 'request_id' ) ? (int) $req->get_param( 'request_id' ) : 0,
				'status'     => (string) $req->get_param( 'status' ),
				'search'     => (string) $req->get_param( 'search' ),
				'page'       => $req->get_param( 'page' ) ? (int) $req->get_param( 'page' ) : 1,
				'per_page'   => $req->get_param( 'per_page' ) ? (int) $req->get_param( 'per_page' ) : 20,
			)
		);

		$response = rest_ensure_response(
			array_map(
				static function ( Submission $submission ) {
					return $submission->to_array();
				},
				$result['items']
			)
		);
		$response->header( 'X-WP-Total', (string) $result['total'] );

		return $response;
	}

	/**
	 * GET /submissions/{id}
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function show( WP_REST_Request $req ) {
		$submission = $this->submissions->find( (int) $req->get_param( 'id' ) );

		if ( ! $submission ) {
			return $this->not_found();
		}

		return rest_ensure_response( $submission->to_array() );
	}

	/**
	 * PUT /submissions/{id}/status
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_status( WP_REST_Request $req ) {
		$id     = (int) $req->get_param( 'id' );
		$status = sanitize_key( (string) $req->get_param( 'status' ) );

		if ( ! $this->submissions->find( $id ) ) {
			return $this->not_found();
		}

		if ( ! $this->submissions->update_status( $id, $status ) ) {
			return new WP_Error( 'renevo_invalid_status', __( 'That status is not valid.', 'renevo-file-request-manager' ), array( 'status' => 400 ) );
		}

		return rest_ensure_response( $this->submissions->find( $id )->to_array() );
	}

	/**
	 * GET /submissions/{id}/files/{file_id}/download
	 *
	 * Streams the file directly and terminates the request; this route
	 * never returns a normal REST response.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_Error|void
	 */
	public function download( WP_REST_Request $req ) {
		$submission_id = (int) $req->get_param( 'id' );
		$file_id       = (int) $req->get_param( 'file_id' );

		$file = $this->submissions->get_file( $submission_id, $file_id );

		if ( ! $file ) {
			return $this->not_found();
		}

		$path = $this->file_storage->get_path( $submission_id, $file->stored_filename );

		$this->file_storage->stream_download( $path, $file->original_filename, $file->mime_type );
	}

	/**
	 * GET /submissions/{id}/download-all
	 *
	 * Streams a submission's files as one ZIP, in folders named after the
	 * requested-file each answers, and terminates the request. Pass a
	 * `field` query param (a requested-file key) to scope the ZIP to just
	 * that field instead of the whole submission.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_Error|void
	 */
	public function download_all( WP_REST_Request $req ) {
		$submission_id = (int) $req->get_param( 'id' );
		$submission    = $this->submissions->find( $submission_id );

		if ( ! $submission ) {
			return $this->not_found();
		}

		$field = sanitize_key( (string) $req->get_param( 'field' ) );
		$files = $submission->files;

		if ( '' !== $field ) {
			$files = array_values(
				array_filter(
					$files,
					static function ( $file ) use ( $field ) {
						return $field === $file->requested_file_key;
					}
				)
			);
		}

		if ( empty( $files ) ) {
			return new WP_Error( 'renevo_no_files', __( 'There are no files to download.', 'renevo-file-request-manager' ), array( 'status' => 404 ) );
		}

		$request        = $this->requests->find( $submission->request_id );
		$folders_by_key = array();
		$field_slug     = '';

		if ( $request ) {
			foreach ( $request->requested_files as $requested_file ) {
				$folders_by_key[ $requested_file->key ] = sanitize_title( $requested_file->title );

				if ( $field === $requested_file->key ) {
					$field_slug = $folders_by_key[ $requested_file->key ];
				}
			}
		}

		$entries = array();
		foreach ( $files as $file ) {
			$folder    = isset( $folders_by_key[ $file->requested_file_key ] ) ? $folders_by_key[ $file->requested_file_key ] : 'other';
			$entries[] = array(
				'path'         => $this->file_storage->get_path( $submission_id, $file->stored_filename ),
				'archive_name' => $submission->submission_code . '/' . $folder . '/' . $file->original_filename,
			);
		}

		$zip_path = $this->file_storage->build_zip( $entries );

		if ( is_wp_error( $zip_path ) ) {
			return $zip_path;
		}

		$download_name = $field_slug ? $submission->submission_code . '-' . $field_slug . '.zip' : $submission->submission_code . '.zip';

		$this->file_storage->stream_zip_download( $zip_path, $download_name );
	}

	/**
	 * Builds the standard "submission not found" error.
	 *
	 * @return WP_Error
	 */
	private function not_found() {
		return new WP_Error( 'renevo_not_found', __( "We couldn't find this submission.", 'renevo-file-request-manager' ), array( 'status' => 404 ) );
	}
}
