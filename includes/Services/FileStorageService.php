<?php
/**
 * Filesystem operations for uploaded files.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filesystem operations for submitted files: protected storage, not the Media Library.
 */
class FileStorageService {

	/**
	 * Gets the protected storage root (outside the Media Library), creating it if missing.
	 *
	 * @return string Absolute path, no trailing slash.
	 */
	public function base_dir() {
		$upload_dir = wp_upload_dir();
		$base       = trailingslashit( $upload_dir['basedir'] ) . 'renevo-file-request-manager';

		if ( ! file_exists( $base ) ) {
			wp_mkdir_p( $base );
		}

		return untrailingslashit( $base );
	}

	/**
	 * Gets the temporary-upload directory, creating it if missing.
	 *
	 * @return string Absolute path, no trailing slash.
	 */
	public function temp_dir() {
		$dir = $this->base_dir() . '/tmp';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir;
	}

	/**
	 * Gets the permanent directory for one submission's files, creating it if missing.
	 *
	 * @param int $submission_id Submission ID.
	 * @return string Absolute path, no trailing slash.
	 */
	public function submission_dir( $submission_id ) {
		$dir = $this->base_dir() . '/' . absint( $submission_id );

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir;
	}

	/**
	 * Generates a random storage filename unrelated to the original filename.
	 *
	 * @param string $extension File extension without a leading dot.
	 * @return string
	 */
	private function random_filename( $extension ) {
		return bin2hex( random_bytes( 24 ) ) . '.' . strtolower( $extension );
	}

	/**
	 * Moves a just-uploaded PHP tmp file into the protected tmp/ directory under a random name.
	 *
	 * @param string $tmp_path  Path to the PHP-managed upload tmp file.
	 * @param string $extension Validated, lowercase extension without a dot.
	 * @return string|\WP_Error The stored filename (acts as the upload token), or WP_Error on failure.
	 */
	public function store_temp_upload( $tmp_path, $extension ) {
		if ( ! is_uploaded_file( $tmp_path ) ) {
			return new \WP_Error( 'renevo_upload_failed', __( 'The file could not be saved. Please try again.', 'renevo-file-request-manager' ) );
		}

		$stored_filename = $this->random_filename( $extension );
		$destination     = $this->temp_dir() . '/' . $stored_filename;

		$moved = $this->get_filesystem()->move( $tmp_path, $destination, true );

		if ( ! $moved ) {
			return new \WP_Error( 'renevo_upload_failed', __( 'The file could not be saved. Please try again.', 'renevo-file-request-manager' ) );
		}

		return $stored_filename;
	}

	/**
	 * Moves a temp-uploaded file into its submission's permanent directory.
	 *
	 * @param string $stored_filename Filename returned by store_temp_upload().
	 * @param int    $submission_id   Destination submission ID.
	 * @return bool
	 */
	public function move_to_submission( $stored_filename, $submission_id ) {
		$source      = $this->temp_dir() . '/' . $stored_filename;
		$destination = $this->submission_dir( $submission_id ) . '/' . $stored_filename;

		if ( ! file_exists( $source ) ) {
			return false;
		}

		return $this->get_filesystem()->move( $source, $destination, true );
	}

	/**
	 * Gets an initialized WP_Filesystem instance for direct file operations.
	 *
	 * @return \WP_Filesystem_Base
	 */
	private function get_filesystem() {
		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		return $wp_filesystem;
	}

	/**
	 * Gets the absolute path to a permanently stored submission file.
	 *
	 * @param int    $submission_id   Submission ID.
	 * @param string $stored_filename Random stored filename.
	 * @return string
	 */
	public function get_path( $submission_id, $stored_filename ) {
		return $this->submission_dir( $submission_id ) . '/' . $stored_filename;
	}

	/**
	 * Deletes a temp-uploaded file that never made it into a submission (e.g. the requester
	 * removed it before submitting).
	 *
	 * @param string $stored_filename Filename returned by store_temp_upload().
	 * @return void
	 */
	public function delete_temp_upload( $stored_filename ) {
		$path = $this->temp_dir() . '/' . $stored_filename;

		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Recursively deletes every file for a submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return void
	 */
	public function delete_submission_files( $submission_id ) {
		$dir = $this->base_dir() . '/' . absint( $submission_id );

		if ( ! is_dir( $dir ) ) {
			return;
		}

		$files = glob( $dir . '/*' );

		foreach ( false === $files ? array() : $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}

		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
	}

	/**
	 * Deletes temp uploads older than 2 hours. Hooked to the hourly maintenance
	 * cron, so a script hammering the upload endpoint can't pile up disk usage
	 * for long even between real submissions.
	 *
	 * @return void
	 */
	public function purge_orphaned_temp_files() {
		$dir    = $this->temp_dir();
		$cutoff = time() - 2 * HOUR_IN_SECONDS;
		$files  = glob( $dir . '/*' );

		foreach ( false === $files ? array() : $files as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < $cutoff ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * Streams a stored file to the browser as an attachment and terminates the request.
	 *
	 * @param string $path              Absolute path to the file on disk.
	 * @param string $original_filename Filename to present to the browser.
	 * @param string $mime_type         MIME type recorded at upload time.
	 * @return void
	 */
	public function stream_download( $path, $original_filename, $mime_type ) {
		if ( ! file_exists( $path ) ) {
			wp_die( esc_html__( 'This file is no longer available.', 'renevo-file-request-manager' ), '', array( 'response' => 404 ) );
		}

		nocache_headers();
		header( 'Content-Type: ' . ( $mime_type ? $mime_type : 'application/octet-stream' ) );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $original_filename ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	/**
	 * Builds a temporary ZIP archive from a set of on-disk files.
	 *
	 * @param array<int, array{path: string, archive_name: string}> $entries Files to add, keyed by nothing in particular.
	 * @return string|\WP_Error Absolute path to the temp ZIP file, or WP_Error on failure.
	 */
	public function build_zip( array $entries ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new \WP_Error( 'renevo_zip_unavailable', __( 'ZIP support is not available on this server.', 'renevo-file-request-manager' ) );
		}

		$zip_path = $this->temp_dir() . '/' . $this->random_filename( 'zip' );
		$zip      = new \ZipArchive();

		if ( true !== $zip->open( $zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE ) ) {
			return new \WP_Error( 'renevo_zip_failed', __( 'The ZIP file could not be created.', 'renevo-file-request-manager' ) );
		}

		foreach ( $entries as $entry ) {
			if ( file_exists( $entry['path'] ) ) {
				$zip->addFile( $entry['path'], $entry['archive_name'] );
			}
		}

		$zip->close();

		return $zip_path;
	}

	/**
	 * Streams a temporary ZIP file to the browser, deletes it, and terminates the request.
	 *
	 * @param string $zip_path         Absolute path to the temp ZIP file.
	 * @param string $download_filename Filename to present to the browser.
	 * @return void
	 */
	public function stream_zip_download( $zip_path, $download_filename ) {
		if ( ! file_exists( $zip_path ) ) {
			wp_die( esc_html__( 'This file is no longer available.', 'renevo-file-request-manager' ), '', array( 'response' => 404 ) );
		}

		nocache_headers();
		header( 'Content-Type: application/zip' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $download_filename ) . '"' );
		header( 'Content-Length: ' . filesize( $zip_path ) );
		header( 'X-Content-Type-Options: nosniff' );

		readfile( $zip_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		wp_delete_file( $zip_path );
		exit;
	}
}
