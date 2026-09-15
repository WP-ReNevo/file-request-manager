<?php
/**
 * Database schema creation and versioned upgrades.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates/upgrades the plugin's two custom tables via dbDelta().
 */
class Migrations {

	/**
	 * Option name storing the currently installed schema version.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'renevo_db_version';

	/**
	 * Runs the schema installer if the stored version is behind RENEVO_DB_VERSION.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$installed = get_option( self::OPTION_NAME, '0' );

		if ( version_compare( $installed, RENEVO_DB_VERSION, '>=' ) ) {
			return;
		}

		self::install();

		update_option( self::OPTION_NAME, RENEVO_DB_VERSION );
	}

	/**
	 * Creates or updates the plugin tables using dbDelta.
	 *
	 * @return void
	 */
	public static function install() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate  = $wpdb->get_charset_collate();
		$submissions      = $wpdb->prefix . 'renevo_submissions';
		$submission_files = $wpdb->prefix . 'renevo_submission_files';

		$sql_submissions = "CREATE TABLE {$submissions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			request_id BIGINT UNSIGNED NOT NULL,
			submission_code VARCHAR(20) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'incomplete',
			name VARCHAR(191) NOT NULL DEFAULT '',
			email VARCHAR(191) NOT NULL DEFAULT '',
			phone VARCHAR(64) NOT NULL DEFAULT '',
			company VARCHAR(191) NOT NULL DEFAULT '',
			message LONGTEXT NULL,
			ip_hash VARCHAR(64) NULL,
			submitted_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY submission_code (submission_code),
			KEY request_id (request_id),
			KEY status (status),
			KEY email (email)
		) {$charset_collate};";

		$sql_submission_files = "CREATE TABLE {$submission_files} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			submission_id BIGINT UNSIGNED NOT NULL,
			requested_file_key VARCHAR(64) NOT NULL,
			original_filename VARCHAR(255) NOT NULL,
			stored_filename VARCHAR(255) NOT NULL,
			file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
			mime_type VARCHAR(191) NOT NULL DEFAULT '',
			uploaded_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY submission_id (submission_id)
		) {$charset_collate};";

		dbDelta( $sql_submissions );
		dbDelta( $sql_submission_files );
	}
}
