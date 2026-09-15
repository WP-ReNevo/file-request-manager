<?php
/**
 * Tests for PublicController's private guard() (nonce + honeypot + rate limit),
 * exercised indirectly through real REST dispatch since the method itself is
 * private by design (public write routes must never be reachable without it).
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Request;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Rest\PublicController;
use FileRequestManager\Rest\RequestsController;

/**
 * Class Test_Public_Controller_Guard
 */
class Test_Public_Controller_Guard extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tearDown();
	}

	private function upload_route( $request_id ) {
		return '/' . RequestsController::NAMESPACE_V1 . "/public/requests/{$request_id}/upload";
	}

	private function submit_route( $request_id ) {
		return '/' . RequestsController::NAMESPACE_V1 . "/public/requests/{$request_id}/submit";
	}

	public function test_missing_nonce_is_rejected_with_expired_session_message() {
		global $wp_rest_server;

		$request  = new WP_REST_Request( 'POST', $this->upload_route( 42 ) );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'renevo_expired', $data['code'] );
		$this->assertStringContainsString( 'session has expired', $data['message'] );
	}

	public function test_invalid_nonce_is_rejected() {
		global $wp_rest_server;

		$request = new WP_REST_Request( 'POST', $this->upload_route( 42 ) );
		$request->set_header( 'X-RENEVO-Nonce', 'not-a-real-nonce' );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'renevo_expired', $response->get_data()['code'] );
	}

	public function test_valid_nonce_for_different_request_id_is_rejected() {
		global $wp_rest_server;

		// A nonce is scoped to a specific request ID (see PublicController::nonce_action());
		// a nonce minted for request 42 must not authorize acting on request 43.
		$nonce_for_42 = wp_create_nonce( PublicController::nonce_action( 42 ) );

		$request = new WP_REST_Request( 'POST', $this->upload_route( 43 ) );
		$request->set_header( 'X-RENEVO-Nonce', $nonce_for_42 );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'renevo_expired', $response->get_data()['code'] );
	}

	public function test_honeypot_filled_is_rejected_even_with_valid_nonce() {
		global $wp_rest_server;

		$request_id = 42;
		$nonce      = wp_create_nonce( PublicController::nonce_action( $request_id ) );

		$request = new WP_REST_Request( 'POST', $this->submit_route( $request_id ) );
		$request->set_header( 'X-RENEVO-Nonce', $nonce );
		$request->set_param( 'website', 'http://spammer.example' );

		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'renevo_rejected', $response->get_data()['code'] );
	}

	public function test_valid_nonce_and_empty_honeypot_passes_the_guard() {
		global $wp_rest_server;

		$request_id = 42; // Nonexistent request — guard should pass, then 404 downstream.
		$nonce      = wp_create_nonce( PublicController::nonce_action( $request_id ) );

		$request = new WP_REST_Request( 'POST', $this->submit_route( $request_id ) );
		$request->set_header( 'X-RENEVO-Nonce', $nonce );
		$request->set_param( 'website', '' );

		$response = $wp_rest_server->dispatch( $request );

		// The guard itself passed; the request fails afterward only because
		// request 42 doesn't exist as a published request (renevo_not_found),
		// proving the guard is not what's rejecting this call.
		$data = $response->get_data();
		$this->assertNotSame( 'renevo_expired', $data['code'] );
		$this->assertNotSame( 'renevo_rejected', $data['code'] );
		$this->assertSame( 'renevo_not_found', $data['code'] );
	}

	/**
	 * Repeated failed upload attempts (here: every attempt fails at
	 * is_uploaded_file(), since a PHPUnit-dispatched request is never a real
	 * HTTP upload) must still eventually trip the rate limit — this is the
	 * abuse-detection behavior the fix must preserve.
	 */
	public function test_repeated_failed_uploads_eventually_trip_the_rate_limit() {
		global $wp_rest_server;

		$requests = new RequestRepository();
		$created  = $requests->create(
			Request::from_array(
				array(
					'title'           => 'Rate limit test',
					'status'          => 'publish',
					'requested_files' => array(
						array(
							'key'           => 'doc',
							'title'         => 'Document',
							'required'      => false,
							'allowed_types' => array( 'pdf' ),
							'max_size_mb'   => 5,
							'max_files'     => 1,
						),
					),
				)
			)
		);

		$nonce = wp_create_nonce( PublicController::nonce_action( $created->id ) );
		$tmp   = wp_tempnam( 'renevo-rate-limit-test' );
		file_put_contents( $tmp, 'not a real http upload' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$dispatch = function () use ( $wp_rest_server, $created, $nonce, $tmp ) {
			$request = new WP_REST_Request( 'POST', $this->upload_route( $created->id ) );
			$request->set_header( 'X-RENEVO-Nonce', $nonce );
			$request->set_param( 'requested_file_key', 'doc' );
			$request->set_file_params(
				array(
					'file' => array(
						'name'     => 'test.pdf',
						'type'     => 'application/pdf',
						'tmp_name' => $tmp,
						'error'    => 0,
						'size'     => 100,
					),
				)
			);

			return $wp_rest_server->dispatch( $request );
		};

		for ( $i = 0; $i < PublicController::RATE_LIMIT_MAX; $i++ ) {
			$response = $dispatch();
			$this->assertNotSame( 429, $response->get_status(), "Attempt {$i} should not be rate-limited yet." );
		}

		$response = $dispatch();

		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'renevo_rate_limited', $response->get_data()['code'] );

		wp_delete_file( $tmp );
	}
}
