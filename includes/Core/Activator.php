<?php
/**
 * Plugin activation and deactivation routines.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin activation and deactivation routines. Never deletes stored data — see uninstall.php.
 */
class Activator {

	/**
	 * Handles plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		Migrations::install();
		update_option( Migrations::OPTION_NAME, RENEVO_DB_VERSION );

		self::add_default_options();
		self::create_storage_directory();

		if ( ! wp_next_scheduled( 'renevo_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'renevo_daily_maintenance' );
		}

		if ( ! wp_next_scheduled( 'renevo_hourly_maintenance' ) ) {
			wp_schedule_event( time(), 'hourly', 'renevo_hourly_maintenance' );
		}
	}

	/**
	 * Handles plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		foreach ( array( 'renevo_daily_maintenance', 'renevo_hourly_maintenance' ) as $hook ) {
			$timestamp = wp_next_scheduled( $hook );

			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $hook );
			}
		}
	}

	/**
	 * Seeds default plugin options if they do not already exist.
	 *
	 * @return void
	 */
	private static function add_default_options() {
		add_option(
			'renevo_settings',
			array(
				'default_max_file_size_mb' => 10,
				'default_allowed_types'    => array( 'pdf', 'jpg', 'jpeg', 'png' ),
				'notification_email'       => get_option( 'admin_email' ),
				'store_ip_hash'            => false,
				'retention_days'           => 0,
				'delete_data_on_uninstall' => false,
			)
		);
	}

	/**
	 * Creates the protected uploads subdirectory and writes protection files into it.
	 *
	 * @return void
	 */
	private static function create_storage_directory() {
		$upload_dir = wp_upload_dir();
		$base       = trailingslashit( $upload_dir['basedir'] ) . 'renevo-file-request-manager';

		foreach ( array( $base, $base . '/tmp' ) as $dir ) {
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}

		$index_file = $base . '/index.php';
		if ( ! file_exists( $index_file ) ) {
			file_put_contents( $index_file, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		$htaccess_file = $base . '/.htaccess';
		if ( ! file_exists( $htaccess_file ) ) {
			$htaccess = "Deny from all\n<IfModule mod_php.c>\nphp_flag engine off\n</IfModule>\n<IfModule mod_php7.c>\nphp_flag engine off\n</IfModule>\n<FilesMatch \"\\.(php|phtml|php\\d)$\">\nRequire all denied\n</FilesMatch>\n";
			file_put_contents( $htaccess_file, $htaccess ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}
	}
}
