<?php
/**
 * Unauthenticated REST controller powering the public upload form.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Rest;

use FileRequestManager\Domain\Submission;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;
use FileRequestManager\Services\NotificationService;
use FileRequestManager\Services\UploadValidator;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unauthenticated REST routes for the public upload form. Writes are guarded
 * by a per-request nonce, a honeypot field, a failure-based per-IP rate
 * limit, and a separate per-IP upload-volume cap (so a stream of valid
 * uploads that's never submitted still can't fill the disk).
 */
class PublicController {

	const NAMESPACE_V1 = RequestsController::NAMESPACE_V1;

	/**
	 * Requests allowed per IP+request within the rate-limit window.
	 *
	 * @var int
	 */
	const RATE_LIMIT_MAX = 30;

	/**
	 * Rate-limit window, in seconds.
	 *
	 * @var int
	 */
	const RATE_LIMIT_WINDOW = 600;

	/**
	 * Total bytes one IP may write to temp storage within the volume window —
	 * independent of the failure-based rate limit above, since a stream of
	 * valid uploads that's never submitted is still a disk-usage risk.
	 *
	 * @var int
	 */
	const UPLOAD_VOLUME_LIMIT_BYTES = 500 * MB_IN_BYTES;

	/**
	 * Upload-volume window, in seconds.
	 *
	 * @var int
	 */
	const UPLOAD_VOLUME_WINDOW = HOUR_IN_SECONDS;

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * @var FileStorageService
	 */
	private $file_storage;

	/**
	 * @var UploadValidator
	 */
	private $validator;

	/**
	 * @var NotificationService
	 */
	private $notifications;

	/**
	 * Constructor.
	 *
	 * @param RequestRepository     $requests      Request repository.
	 * @param SubmissionRepository  $submissions   Submission repository.
	 * @param FileStorageService    $file_storage  File storage service.
	 * @param UploadValidator       $validator     Upload validator.
	 * @param NotificationService   $notifications Notification service.
	 */
	public function __construct(
		RequestRepository $requests,
		SubmissionRepository $submissions,
		FileStorageService $file_storage,
		UploadValidator $validator,
		NotificationService $notifications
	) {
		$this->requests      = $requests;
		$this->submissions   = $submissions;
		$this->file_storage  = $file_storage;
		$this->validator     = $validator;
		$this->notifications = $notifications;
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/public/requests/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'show' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/public/requests/(?P<id>\d+)/upload',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upload' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE_V1,
			'/public/requests/(?P<id>\d+)/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * The nonce action name scoped to one request, so a nonce for request A
	 * can never be replayed against request B.
	 *
	 * @param int $request_id Request ID.
	 * @return string
	 */
	public static function nonce_action( $request_id ) {
		return 'renevo_public_request_' . absint( $request_id );
	}

	/**
	 * GET /public/requests/{id} — the reduced, public-safe shape of a published request.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function show( WP_REST_Request $req ) {
		$id      = (int) $req->get_param( 'id' );
		$request = $this->requests->find_published( $id );

		if ( ! $request ) {
			return $this->not_found();
		}

		$data          = $request->to_public_array();
		$data['nonce'] = wp_create_nonce( self::nonce_action( $id ) );

		return rest_ensure_response( $data );
	}

	/**
	 * POST /public/requests/{id}/upload — validates and stores a single file, returns a token.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload( WP_REST_Request $req ) {
		$id = (int) $req->get_param( 'id' );

		$guard = $this->guard( $req, $id );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$request = $this->requests->find_published( $id );
		if ( ! $request ) {
			return $this->not_found();
		}

		$key            = sanitize_key( (string) $req->get_param( 'requested_file_key' ) );
		$requested_file = $request->get_requested_file( $key );

		if ( ! $requested_file ) {
			$this->record_failed_attempt( $id );
			return new WP_Error( 'renevo_invalid_field', __( "This item isn't part of this request.", 'renevo-file-request-manager' ), array( 'status' => 400 ) );
		}

		$files = $req->get_file_params();
		if ( empty( $files['file'] ) ) {
			return new WP_Error( 'renevo_no_file', __( 'No file was received.', 'renevo-file-request-manager' ), array( 'status' => 400 ) );
		}

		$validation = $this->validator->validate( $files['file'], $requested_file );
		if ( is_wp_error( $validation ) ) {
			$this->record_failed_attempt( $id );
			$validation->add_data( array( 'status' => 400 ) );
			return $validation;
		}

		$size = (int) $files['file']['size'];

		if ( ! $this->within_upload_volume_limit( $size ) ) {
			return new WP_Error( 'renevo_upload_volume_limit', __( 'You have uploaded too much data recently. Please try again later.', 'renevo-file-request-manager' ), array( 'status' => 429 ) );
		}

		$extension = strtolower( pathinfo( $files['file']['name'], PATHINFO_EXTENSION ) );
		$stored    = $this->file_storage->store_temp_upload( $files['file']['tmp_name'], $extension );

		if ( is_wp_error( $stored ) ) {
			$stored->add_data( array( 'status' => 500 ) );
			return $stored;
		}

		$this->record_upload_volume( $size );

		$checked = wp_check_filetype_and_ext( $files['file']['tmp_name'], $files['file']['name'] );

		$token = $stored;

		set_transient(
			'renevo_upload_' . $token,
			array(
				'request_id'         => $id,
				'requested_file_key' => $key,
				'original_filename'  => sanitize_file_name( wp_basename( $files['file']['name'] ) ),
				'mime_type'          => $checked['type'] ? $checked['type'] : 'application/octet-stream',
				'size'               => $size,
				'stored_filename'    => $stored,
			),
			DAY_IN_SECONDS
		);

		return rest_ensure_response(
			array(
				'token'             => $token,
				'original_filename' => sanitize_file_name( wp_basename( $files['file']['name'] ) ),
				'size'              => $size,
			)
		);
	}

	/**
	 * POST /public/requests/{id}/submit — finalizes the submission.
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function submit( WP_REST_Request $req ) {
		$id = (int) $req->get_param( 'id' );

		$guard = $this->guard( $req, $id );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$request = $this->requests->find_published( $id );
		if ( ! $request ) {
			return $this->not_found();
		}

		$body = (array) $req->get_json_params();

		$contact_error = $this->validate_contact_fields( $request, $body );
		if ( is_wp_error( $contact_error ) ) {
			return $contact_error;
		}

		if ( empty( $request->submission_settings['allow_multiple_submissions'] ) && ! empty( $body['email'] ) ) {
			global $wpdb;
			$table = $wpdb->prefix . 'renevo_submissions';

			// Not cached: this is a duplicate-submission guard, so it must always see
			// the very latest row — a stale cache here would let a duplicate through.
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE request_id = %d AND email = %s", $id, sanitize_email( $body['email'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table is a computed table name, not user input; the %d/%s values are passed through prepare().

			if ( $existing ) {
				return new WP_Error( 'renevo_already_submitted', __( 'You have already submitted your documents for this request.', 'renevo-file-request-manager' ), array( 'status' => 409 ) );
			}
		}

		$tokens         = isset( $body['tokens'] ) && is_array( $body['tokens'] ) ? array_map( 'sanitize_text_field', $body['tokens'] ) : array();
		$resolved_files = array();
		$counts_by_key  = array();

		foreach ( $tokens as $token ) {
			$meta = get_transient( 'renevo_upload_' . $token );

			if ( ! $meta || (int) $meta['request_id'] !== $id ) {
				continue;
			}

			$resolved_files[]                             = $meta;
			$counts_by_key[ $meta['requested_file_key'] ] = ( $counts_by_key[ $meta['requested_file_key'] ] ?? 0 ) + 1;
		}

		foreach ( $request->requested_files as $requested_file ) {
			$count = $counts_by_key[ $requested_file->key ] ?? 0;

			if ( $count > $requested_file->max_files ) {
				return new WP_Error(
					'renevo_too_many_files',
					sprintf(
						/* translators: %d: maximum number of files. */
						_n( 'Only %d file may be uploaded for this item.', 'Only %d files may be uploaded for this item.', $requested_file->max_files, 'renevo-file-request-manager' ),
						$requested_file->max_files
					),
					array( 'status' => 400 )
				);
			}
		}

		$submission             = new Submission();
		$submission->request_id = $id;
		$submission->name       = ! empty( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
		$submission->email      = ! empty( $body['email'] ) ? sanitize_email( $body['email'] ) : '';
		$submission->phone      = ! empty( $body['phone'] ) ? sanitize_text_field( $body['phone'] ) : '';
		$submission->company    = ! empty( $body['company'] ) ? sanitize_text_field( $body['company'] ) : '';
		$submission->message    = ! empty( $body['message'] ) ? sanitize_textarea_field( $body['message'] ) : '';
		$submission->ip_hash    = $this->maybe_hash_ip();

		$submission = $this->submissions->create( $submission );

		foreach ( $resolved_files as $meta ) {
			if ( ! $this->file_storage->move_to_submission( $meta['stored_filename'], $submission->id ) ) {
				continue;
			}

			$file                     = new \FileRequestManager\Domain\SubmissionFile();
			$file->requested_file_key = $meta['requested_file_key'];
			$file->original_filename  = $meta['original_filename'];
			$file->stored_filename    = $meta['stored_filename'];
			$file->file_size          = $meta['size'];
			$file->mime_type          = $meta['mime_type'];

			$this->submissions->add_file( $submission->id, $file );
			delete_transient( 'renevo_upload_' . $meta['stored_filename'] );
		}

		$submission = $this->submissions->find( $submission->id );

		$required_check = $this->validator->validate_required_coverage( $request, $counts_by_key );
		$missing        = is_wp_error( $required_check ) ? $required_check->get_error_data()['missing'] : array();

		$this->submissions->update_status( $submission->id, empty( $missing ) ? Submission::STATUS_COMPLETE : Submission::STATUS_INCOMPLETE );
		$submission = $this->submissions->find( $submission->id );

		$this->notifications->send_admin_notification( $request, $submission );
		$this->notifications->send_requester_confirmation( $request, $submission );

		return rest_ensure_response(
			array(
				'submission_code' => $submission->submission_code,
				'status'          => $submission->status,
				'missing_files'   => $missing,
				'success_message' => $request->submission_settings['success_message'],
				'redirect_url'    => $request->submission_settings['redirect_url'],
			)
		);
	}

	/**
	 * Validates the requester's contact-field submission against the request's enabled/required configuration.
	 *
	 * @param \FileRequestManager\Domain\Request $request Request being submitted against.
	 * @param array                              $body    Raw JSON body.
	 * @return true|WP_Error
	 */
	private function validate_contact_fields( $request, array $body ) {
		foreach ( $request->contact_fields as $key => $config ) {
			if ( empty( $config['enabled'] ) || empty( $config['required'] ) ) {
				continue;
			}

			if ( empty( $body[ $key ] ) || ! is_string( $body[ $key ] ) || '' === trim( $body[ $key ] ) ) {
				return new WP_Error(
					'renevo_missing_field',
					__( 'Please fill in all required fields.', 'renevo-file-request-manager' ),
					array( 'status' => 400 )
				);
			}
		}

		if ( ! empty( $body['email'] ) && ! is_email( $body['email'] ) ) {
			return new WP_Error( 'renevo_invalid_email', __( 'Please enter a valid email address.', 'renevo-file-request-manager' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Shared guard for the two write routes: nonce, honeypot, and rate limit.
	 *
	 * Only counts against the rate limit when something actually goes wrong
	 * (honeypot trip here; validation/storage failures recorded by the
	 * caller via record_failed_attempt()) — a requester successfully
	 * uploading many valid files never burns through the budget.
	 *
	 * @param WP_REST_Request $req        Request.
	 * @param int              $request_id Request ID, used to scope the nonce.
	 * @return true|WP_Error
	 */
	private function guard( WP_REST_Request $req, $request_id ) {
		$nonce = (string) $req->get_header( 'X-RENEVO-Nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $req->get_param( 'nonce' );
		}

		if ( ! wp_verify_nonce( $nonce, self::nonce_action( $request_id ) ) ) {
			return new WP_Error( 'renevo_expired', __( 'Your session has expired. Please refresh the page and try again.', 'renevo-file-request-manager' ), array( 'status' => 403 ) );
		}

		if ( $this->is_rate_limited( $request_id ) ) {
			return new WP_Error( 'renevo_rate_limited', __( 'Too many attempts. Please try again in a few minutes.', 'renevo-file-request-manager' ), array( 'status' => 429 ) );
		}

		$honeypot = $req->get_param( 'website' );
		if ( ! empty( $honeypot ) ) {
			$this->record_failed_attempt( $request_id );
			return new WP_Error( 'renevo_rejected', __( "We couldn't process your submission. Please try again.", 'renevo-file-request-manager' ), array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Read-only check of a simple per-IP, per-request rate limit.
	 *
	 * @param int $request_id Request ID.
	 * @return bool Whether this IP+request has already hit the limit.
	 */
	private function is_rate_limited( $request_id ) {
		return (int) get_transient( $this->rate_limit_key( $request_id ) ) >= self::RATE_LIMIT_MAX;
	}

	/**
	 * Records one failed attempt (invalid file, failed submission, honeypot
	 * trip, ...) toward the rate limit. Successful uploads/submissions never
	 * call this.
	 *
	 * @param int $request_id Request ID.
	 * @return void
	 */
	private function record_failed_attempt( $request_id ) {
		$key   = $this->rate_limit_key( $request_id );
		$count = (int) get_transient( $key );

		set_transient( $key, $count + 1, self::RATE_LIMIT_WINDOW );
	}

	/**
	 * Builds the rate-limit transient key for one IP+request pair.
	 *
	 * @param int $request_id Request ID.
	 * @return string
	 */
	private function rate_limit_key( $request_id ) {
		return 'renevo_rl_' . md5( $this->client_ip() . ':' . $request_id );
	}

	/**
	 * Whether one more upload of $additional_bytes would keep this IP under
	 * its rolling upload-volume budget — global per IP (not per-request),
	 * since the risk being bounded is total disk usage, not any one request.
	 *
	 * @param int $additional_bytes Size of the file about to be stored.
	 * @return bool
	 */
	private function within_upload_volume_limit( $additional_bytes ) {
		$used  = (int) get_transient( $this->upload_volume_key() );
		$limit = (int) apply_filters( 'renevo_upload_volume_limit_bytes', self::UPLOAD_VOLUME_LIMIT_BYTES );

		return ( $used + $additional_bytes ) <= $limit;
	}

	/**
	 * Records bytes actually written to temp storage toward this IP's
	 * rolling upload-volume budget.
	 *
	 * @param int $bytes Size of the file just stored.
	 * @return void
	 */
	private function record_upload_volume( $bytes ) {
		$key  = $this->upload_volume_key();
		$used = (int) get_transient( $key );

		set_transient( $key, $used + $bytes, self::UPLOAD_VOLUME_WINDOW );
	}

	/**
	 * Builds the upload-volume transient key for one IP.
	 *
	 * @return string
	 */
	private function upload_volume_key() {
		return 'renevo_uv_' . md5( $this->client_ip() );
	}

	/**
	 * Gets the visitor's IP address from REMOTE_ADDR only (not X-Forwarded-For, which is spoofable).
	 *
	 * @return string
	 */
	private function client_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	/**
	 * Hashes the visitor's IP for storage, only when the admin has opted in.
	 *
	 * @return string|null
	 */
	private function maybe_hash_ip() {
		$settings = \FileRequestManager\Rest\SettingsController::get_settings();

		if ( empty( $settings['store_ip_hash'] ) ) {
			return null;
		}

		return hash_hmac( 'sha256', $this->client_ip(), wp_salt( 'auth' ) );
	}

	/**
	 * Builds the standard "request not found" error.
	 *
	 * @return WP_Error
	 */
	private function not_found() {
		return new WP_Error( 'renevo_not_found', __( "We couldn't load this request. Please refresh the page.", 'renevo-file-request-manager' ), array( 'status' => 404 ) );
	}
}
