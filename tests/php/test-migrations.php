<?php
/**
 * Tests for FileRequestManager\Core\Migrations.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Core\Migrations;

/**
 * Class Test_Migrations
 */
class Test_Migrations extends WP_UnitTestCase {

	public function test_install_is_idempotent_and_tables_have_expected_columns() {
		global $wpdb;

		// The plugin already installs its schema on boot (Migrations::maybe_upgrade(),
		// called from Plugin::boot() on every load). Calling install() again here
		// directly must not error and must leave the schema intact.
		Migrations::install();
		Migrations::install();

		$submissions_table      = $wpdb->prefix . 'renevo_submissions';
		$submission_files_table = $wpdb->prefix . 'renevo_submission_files';

		$submissions_columns      = $wpdb->get_col( "DESCRIBE {$submissions_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$submission_files_columns = $wpdb->get_col( "DESCRIBE {$submission_files_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$expected_submissions_columns = array(
			'id',
			'request_id',
			'submission_code',
			'status',
			'name',
			'email',
			'phone',
			'company',
			'message',
			'ip_hash',
			'submitted_at',
			'updated_at',
		);

		foreach ( $expected_submissions_columns as $column ) {
			$this->assertContains( $column, $submissions_columns, "Missing column '{$column}' on {$submissions_table}" );
		}

		$expected_files_columns = array(
			'id',
			'submission_id',
			'requested_file_key',
			'original_filename',
			'stored_filename',
			'file_size',
			'mime_type',
			'uploaded_at',
		);

		foreach ( $expected_files_columns as $column ) {
			$this->assertContains( $column, $submission_files_columns, "Missing column '{$column}' on {$submission_files_table}" );
		}
	}

	public function test_maybe_upgrade_is_a_noop_once_version_matches() {
		update_option( Migrations::OPTION_NAME, RENEVO_DB_VERSION );

		// Should simply return without error; nothing meaningful to assert
		// beyond "did not throw" since install() isn't re-invoked internally
		// in a way we can directly observe without mocking dbDelta.
		Migrations::maybe_upgrade();

		$this->assertSame( RENEVO_DB_VERSION, get_option( Migrations::OPTION_NAME ) );
	}

	public function test_maybe_upgrade_installs_and_bumps_version_when_behind() {
		update_option( Migrations::OPTION_NAME, '0.0.1' );

		Migrations::maybe_upgrade();

		$this->assertSame( RENEVO_DB_VERSION, get_option( Migrations::OPTION_NAME ) );

		global $wpdb;
		$submissions_table = $wpdb->prefix . 'renevo_submissions';
		$exists            = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $submissions_table ) );
		$this->assertSame( $submissions_table, $exists );
	}
}
