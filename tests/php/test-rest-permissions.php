<?php
/**
 * Tests for the admin REST controllers' permission gates.
 *
 * Covers both the permission_callback methods directly and one full
 * rest_do_request() round trip, to prove the gate is actually wired into
 * the registered route and not just correct in isolation.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Rest\RequestsController;
use FileRequestManager\Rest\SettingsController;
use FileRequestManager\Rest\SubmissionsController;

/**
 * Class Test_Rest_Permissions
 */
class Test_Rest_Permissions extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tearDown(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function test_requests_controller_check_permission_direct() {
		$controller = \FileRequestManager\Plugin::instance()->get( RequestsController::class );

		wp_set_current_user( 0 );
		$this->assertFalse( $controller->check_permission() );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( $controller->check_permission() );
	}

	public function test_submissions_controller_check_permission_direct() {
		$controller = \FileRequestManager\Plugin::instance()->get( SubmissionsController::class );

		wp_set_current_user( 0 );
		$this->assertFalse( $controller->check_permission() );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( $controller->check_permission() );
	}

	public function test_settings_controller_check_permission_direct() {
		$controller = \FileRequestManager\Plugin::instance()->get( SettingsController::class );

		wp_set_current_user( 0 );
		$this->assertFalse( $controller->check_permission() );

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		$this->assertTrue( $controller->check_permission() );
	}

	/**
	 * A subscriber (logged in, but without manage_options) must also be
	 * rejected — this is not merely an "is logged in" check.
	 */
	public function test_subscriber_is_rejected_by_check_permission() {
		$controller = \FileRequestManager\Plugin::instance()->get( RequestsController::class );

		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$this->assertFalse( $controller->check_permission() );
	}

	public function test_anonymous_rest_request_to_admin_route_is_rejected() {
		global $wp_rest_server;

		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/' . RequestsController::NAMESPACE_V1 . '/requests' );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );

		$data = $response->get_data();
		$this->assertSame( 'rest_forbidden', $data['code'] );
	}

	public function test_logged_in_non_admin_rest_request_is_forbidden_403() {
		global $wp_rest_server;

		$subscriber_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$request  = new WP_REST_Request( 'GET', '/' . RequestsController::NAMESPACE_V1 . '/requests' );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
	}

	public function test_admin_rest_request_to_admin_route_succeeds() {
		global $wp_rest_server;

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request  = new WP_REST_Request( 'GET', '/' . RequestsController::NAMESPACE_V1 . '/requests' );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_admin_can_reach_submissions_route() {
		global $wp_rest_server;

		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$request  = new WP_REST_Request( 'GET', '/' . RequestsController::NAMESPACE_V1 . '/submissions' );
		$response = $wp_rest_server->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
	}
}
