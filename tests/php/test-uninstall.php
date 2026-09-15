<?php
/**
 * Test for uninstall.php.
 *
 * This is the one file in the plugin that is never loaded through the normal
 * bootstrap (it only runs when WordPress deletes the plugin from the Plugins
 * screen), so it's included here directly with WP_UNINSTALL_PLUGIN defined,
 * matching the guard at the top of the file.
 *
 * Because uninstall.php DROPs the plugin's two custom tables, and a MySQL
 * DROP TABLE is DDL that implicitly commits (it cannot be undone by
 * WP_UnitTestCase's transaction rollback), this test recreates the schema
 * afterward so later test classes in the same run are unaffected regardless
 * of test execution order.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Core\Activator;
use FileRequestManager\Core\Migrations;
use FileRequestManager\Domain\Request;
use FileRequestManager\Repositories\RequestRepository;

/**
 * Class Test_Uninstall
 */
class Test_Uninstall extends WP_UnitTestCase {

	public function tearDown(): void {
		// Restore everything the "opted in" test intentionally dropped, so
		// the rest of the suite (which may run after this file) sees a
		// normal install. Real (non-temporary) tables, same reasoning as in
		// the test below: WP_UnitTestCase's per-test `query` filter rewrites
		// CREATE TABLE into CREATE TEMPORARY TABLE, and a temporary table
		// created inside this transaction would itself be undone by the
		// ROLLBACK that immediately follows tearDown() — leaving no table at
		// all for the next test class.
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		Migrations::install();
		if ( false === get_option( 'renevo_settings' ) ) {
			Activator::activate();
		}

		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		parent::tearDown();
	}

	public function test_uninstall_removes_data_when_opted_in() {
		global $wpdb;

		// Arrange: a request post, a settings option opted into deletion, and
		// the daily cron scheduled (as a normal activation would leave it).
		update_option(
			'renevo_settings',
			array(
				'delete_data_on_uninstall' => true,
			)
		);

		$repo    = new RequestRepository();
		$request = $repo->create(
			Request::from_array(
				array(
					'title'  => 'To be deleted',
					'status' => 'publish',
				)
			)
		);
		$this->assertNotNull( get_post( $request->id ) );

		if ( ! wp_next_scheduled( 'renevo_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'renevo_daily_maintenance' );
		}
		if ( ! wp_next_scheduled( 'renevo_hourly_maintenance' ) ) {
			wp_schedule_event( time(), 'hourly', 'renevo_hourly_maintenance' );
		}

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'renevo-file-request-manager/renevo-file-request-manager.php' );
		}

		// WP_UnitTestCase::start_transaction() (called from set_up() before
		// every test) adds a `query` filter that silently rewrites any
		// `CREATE TABLE` / `DROP TABLE` statement into `CREATE|DROP TEMPORARY
		// TABLE` — this is how the harness keeps schema changes from ever
		// touching the real tables during a normal test. Our two custom
		// tables were created as REAL tables before this filter existed (at
		// plugin boot, before the first test's set_up() ran), so with the
		// filter active, uninstall.php's `DROP TABLE IF EXISTS ...` would be
		// silently rewritten to `DROP TEMPORARY TABLE IF EXISTS ...`, find no
		// matching temporary table, and no-op — leaving the real table
		// untouched and making this test unable to observe real DROP TABLE
		// behaviour at all. Removing the filter for the duration of this one
		// `require` lets the statement run as uninstall.php actually wrote
		// it, which is what happens in a real uninstall (no such filter is
		// ever present outside this harness).
		remove_filter( 'query', array( $this, '_create_temporary_tables' ) );
		remove_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// Act.
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		add_filter( 'query', array( $this, '_create_temporary_tables' ) );
		add_filter( 'query', array( $this, '_drop_temporary_tables' ) );

		// Assert: post gone, tables dropped, options gone, cron unscheduled.
		$this->assertNull( get_post( $request->id ) );

		$submissions_table = $wpdb->prefix . 'renevo_submissions';
		$files_table       = $wpdb->prefix . 'renevo_submission_files';

		$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $submissions_table ) ) );
		$this->assertNull( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $files_table ) ) );

		$this->assertFalse( get_option( 'renevo_settings' ) );
		$this->assertFalse( get_option( 'renevo_db_version' ) );
		$this->assertFalse( wp_next_scheduled( 'renevo_daily_maintenance' ) );
		$this->assertFalse( wp_next_scheduled( 'renevo_hourly_maintenance' ) );
	}

	public function test_uninstall_keeps_data_by_default() {
		global $wpdb;

		update_option( 'renevo_settings', array( 'delete_data_on_uninstall' => false ) );

		$repo    = new RequestRepository();
		$request = $repo->create(
			Request::from_array(
				array(
					'title'  => 'Kept on uninstall',
					'status' => 'publish',
				)
			)
		);

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'renevo-file-request-manager/renevo-file-request-manager.php' );
		}

		require dirname( __DIR__, 2 ) . '/uninstall.php';

		// Nothing should have been touched: the post survives and the tables
		// still exist.
		$this->assertNotNull( get_post( $request->id ) );

		$submissions_table = $wpdb->prefix . 'renevo_submissions';
		$this->assertSame( $submissions_table, $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $submissions_table ) ) );

		$this->assertNotFalse( get_option( 'renevo_settings' ) );
	}
}
