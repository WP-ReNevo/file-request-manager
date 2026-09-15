<?php
/**
 * The fixed registry of file types an admin can pick for a requested file.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registry of file types selectable for a requested file.
 */
class AllowedFileTypes {

	/**
	 * Gets the full type registry, keyed by type identifier.
	 *
	 * @return array<string, array{label: string, extensions: string[], mimes: string[]}>
	 */
	public static function all() {
		$types = array(
			'pdf'  => array(
				'label'      => __( 'PDF', 'renevo-file-request-manager' ),
				'extensions' => array( 'pdf' ),
				'mimes'      => array( 'application/pdf' ),
			),
			'jpg'  => array(
				'label'      => __( 'JPG / JPEG', 'renevo-file-request-manager' ),
				'extensions' => array( 'jpg', 'jpeg' ),
				'mimes'      => array( 'image/jpeg' ),
			),
			'png'  => array(
				'label'      => __( 'PNG', 'renevo-file-request-manager' ),
				'extensions' => array( 'png' ),
				'mimes'      => array( 'image/png' ),
			),
			'gif'  => array(
				'label'      => __( 'GIF', 'renevo-file-request-manager' ),
				'extensions' => array( 'gif' ),
				'mimes'      => array( 'image/gif' ),
			),
			'webp' => array(
				'label'      => __( 'WEBP', 'renevo-file-request-manager' ),
				'extensions' => array( 'webp' ),
				'mimes'      => array( 'image/webp' ),
			),
			'doc'  => array(
				'label'      => __( 'DOC', 'renevo-file-request-manager' ),
				'extensions' => array( 'doc' ),
				'mimes'      => array( 'application/msword' ),
			),
			'docx' => array(
				'label'      => __( 'DOCX', 'renevo-file-request-manager' ),
				'extensions' => array( 'docx' ),
				'mimes'      => array( 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ),
			),
			'xls'  => array(
				'label'      => __( 'XLS', 'renevo-file-request-manager' ),
				'extensions' => array( 'xls' ),
				'mimes'      => array( 'application/vnd.ms-excel' ),
			),
			'xlsx' => array(
				'label'      => __( 'XLSX', 'renevo-file-request-manager' ),
				'extensions' => array( 'xlsx' ),
				'mimes'      => array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ),
			),
			'zip'  => array(
				'label'      => __( 'ZIP', 'renevo-file-request-manager' ),
				'extensions' => array( 'zip' ),
				'mimes'      => array( 'application/zip', 'application/x-zip-compressed' ),
			),
		);

		/**
		 * Filters the registry of file types selectable for a requested file.
		 *
		 * @param array $types Type registry, keyed by type identifier.
		 */
		return apply_filters( 'renevo_requested_file_types', $types );
	}

	/**
	 * Whether a type key exists in the registry.
	 *
	 * @param string $key Type identifier, e.g. "pdf".
	 * @return bool
	 */
	public static function is_valid_key( $key ) {
		return array_key_exists( $key, self::all() );
	}

	/**
	 * Gets every allowed file extension for a set of type keys.
	 *
	 * @param string[] $keys Type identifiers.
	 * @return string[] Lowercase extensions without a leading dot.
	 */
	public static function extensions_for_keys( array $keys ) {
		$types      = self::all();
		$extensions = array();

		foreach ( $keys as $key ) {
			if ( isset( $types[ $key ] ) ) {
				$extensions = array_merge( $extensions, $types[ $key ]['extensions'] );
			}
		}

		return array_values( array_unique( $extensions ) );
	}

	/**
	 * Gets every allowed MIME type for a set of type keys.
	 *
	 * @param string[] $keys Type identifiers.
	 * @return string[]
	 */
	public static function mimes_for_keys( array $keys ) {
		$types = self::all();
		$mimes = array();

		foreach ( $keys as $key ) {
			if ( isset( $types[ $key ] ) ) {
				$mimes = array_merge( $mimes, $types[ $key ]['mimes'] );
			}
		}

		return array_values( array_unique( $mimes ) );
	}

	/**
	 * File extensions that are never allowed, regardless of admin configuration.
	 *
	 * @return string[]
	 */
	public static function hard_denied_extensions() {
		return array( 'php', 'php3', 'php4', 'php5', 'phtml', 'pht', 'phar', 'exe', 'sh', 'js', 'jsp', 'asp', 'aspx', 'cgi', 'py', 'rb', 'htaccess', 'htm', 'html', 'svg' );
	}
}
