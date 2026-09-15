<?php
/**
 * Server-side authoritative validation for uploaded files.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

use FileRequestManager\Domain\Request;
use FileRequestManager\Domain\RequestedFile;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Server-side authoritative validation for uploaded files.
 */
class UploadValidator {

	/**
	 * Validates one uploaded file against a requested-file definition.
	 *
	 * @param array         $file           A single $_FILES-shaped entry (error, tmp_name, name, size).
	 * @param RequestedFile $requested_file The slot this file is being uploaded for.
	 * @return true|WP_Error
	 */
	public function validate( array $file, RequestedFile $requested_file ) {
		if ( empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return new WP_Error( 'renevo_upload_error', $this->upload_error_message( isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE ) );
		}

		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new WP_Error( 'renevo_invalid_upload', __( 'This upload could not be verified. Please try again.', 'renevo-file-request-manager' ) );
		}

		$original_name = isset( $file['name'] ) ? sanitize_file_name( wp_basename( $file['name'] ) ) : '';
		$extension     = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );

		if ( '' === $extension || in_array( $extension, AllowedFileTypes::hard_denied_extensions(), true ) ) {
			return new WP_Error( 'renevo_type_not_allowed', __( "This file type isn't allowed for this request.", 'renevo-file-request-manager' ) );
		}

		$allowed_extensions = AllowedFileTypes::extensions_for_keys( $requested_file->allowed_types );

		if ( ! in_array( $extension, $allowed_extensions, true ) ) {
			return new WP_Error( 'renevo_type_not_allowed', __( "This file type isn't allowed for this request.", 'renevo-file-request-manager' ) );
		}

		$checked = wp_check_filetype_and_ext( $file['tmp_name'], $original_name );

		if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
			return new WP_Error( 'renevo_type_not_allowed', __( "This file type isn't allowed for this request.", 'renevo-file-request-manager' ) );
		}

		$allowed_mimes = AllowedFileTypes::mimes_for_keys( $requested_file->allowed_types );

		if ( ! in_array( strtolower( $checked['ext'] ), $allowed_extensions, true ) || ! in_array( $checked['type'], $allowed_mimes, true ) ) {
			return new WP_Error( 'renevo_type_not_allowed', __( "This file type isn't allowed for this request.", 'renevo-file-request-manager' ) );
		}

		$max_bytes = $requested_file->max_size_mb * MB_IN_BYTES;

		if ( (int) $file['size'] <= 0 ) {
			return new WP_Error( 'renevo_empty_file', __( 'This file appears to be empty.', 'renevo-file-request-manager' ) );
		}

		if ( (int) $file['size'] > $max_bytes ) {
			return new WP_Error(
				'renevo_too_large',
				sprintf(
					/* translators: %s: maximum file size, e.g. "10 MB". */
					__( 'This file is larger than the allowed maximum of %s.', 'renevo-file-request-manager' ),
					size_format( $max_bytes )
				)
			);
		}

		return true;
	}

	/**
	 * Checks that every required requested-file slot has at least one uploaded file.
	 * Per-slot max file count is enforced separately, in bulk, at submit time.
	 *
	 * @param Request            $request      The request being submitted against.
	 * @param array<string, int> $counts_by_key Number of uploaded files per requested-file key.
	 * @return true|WP_Error WP_Error data contains the list of missing titles under 'missing'.
	 */
	public function validate_required_coverage( Request $request, array $counts_by_key ) {
		$missing = array();

		foreach ( $request->requested_files as $requested_file ) {
			if ( ! $requested_file->required ) {
				continue;
			}

			$count = isset( $counts_by_key[ $requested_file->key ] ) ? $counts_by_key[ $requested_file->key ] : 0;

			if ( $count < 1 ) {
				$missing[] = $requested_file->title;
			}
		}

		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'renevo_missing_required_files',
				__( 'Please upload the following required file(s):', 'renevo-file-request-manager' ),
				array( 'missing' => $missing )
			);
		}

		return true;
	}

	/**
	 * Converts a PHP upload error code into a user-friendly message.
	 *
	 * @param int $code One of the UPLOAD_ERR_* constants.
	 * @return string
	 */
	private function upload_error_message( $code ) {
		switch ( $code ) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return __( 'This file is too large for this server to accept.', 'renevo-file-request-manager' );
			case UPLOAD_ERR_PARTIAL:
				return __( 'The file was only partially uploaded. Please try again.', 'renevo-file-request-manager' );
			case UPLOAD_ERR_NO_FILE:
				return __( 'No file was received.', 'renevo-file-request-manager' );
			default:
				return __( 'This file could not be uploaded. Please try again.', 'renevo-file-request-manager' );
		}
	}
}
