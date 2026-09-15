<?php
/**
 * WordPress Privacy API integration: personal data exporter and eraser.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Privacy;

use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress Privacy API integration: personal data exporter and eraser.
 */
class Privacy {

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * @var FileStorageService
	 */
	private $file_storage;

	/**
	 * Constructor.
	 *
	 * @param SubmissionRepository $submissions  Submission repository.
	 * @param FileStorageService   $file_storage File storage service.
	 */
	public function __construct( SubmissionRepository $submissions, FileStorageService $file_storage ) {
		$this->submissions  = $submissions;
		$this->file_storage = $file_storage;
	}

	/**
	 * Registers the exporter and eraser with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Adds the exporter to the registry.
	 *
	 * @param array $exporters Registered exporters.
	 * @return array
	 */
	public function register_exporter( $exporters ) {
		$exporters['renevo-file-request-manager'] = array(
			'exporter_friendly_name' => __( 'ReNevo', 'renevo-file-request-manager' ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Adds the eraser to the registry.
	 *
	 * @param array $erasers Registered erasers.
	 * @return array
	 */
	public function register_eraser( $erasers ) {
		$erasers['renevo-file-request-manager'] = array(
			'eraser_friendly_name' => __( 'ReNevo', 'renevo-file-request-manager' ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Exports every submission made under a given email address.
	 *
	 * @param string $email_address Requested email address.
	 * @return array{data: array, done: bool}
	 */
	public function export( $email_address ) {
		$items = array();

		foreach ( $this->submissions->find_by_email( $email_address ) as $submission ) {
			$filenames = wp_list_pluck( $submission->files, 'original_filename' );

			$items[] = array(
				'group_id'    => 'renevo-submissions',
				'group_label' => __( 'File Request Submissions', 'renevo-file-request-manager' ),
				'item_id'     => 'renevo-submission-' . $submission->id,
				'data'        => array(
					array(
						'name'  => __( 'Submission ID', 'renevo-file-request-manager' ),
						'value' => $submission->submission_code,
					),
					array(
						'name'  => __( 'Name', 'renevo-file-request-manager' ),
						'value' => $submission->name,
					),
					array(
						'name'  => __( 'Email', 'renevo-file-request-manager' ),
						'value' => $submission->email,
					),
					array(
						'name'  => __( 'Phone', 'renevo-file-request-manager' ),
						'value' => $submission->phone,
					),
					array(
						'name'  => __( 'Company', 'renevo-file-request-manager' ),
						'value' => $submission->company,
					),
					array(
						'name'  => __( 'Message', 'renevo-file-request-manager' ),
						'value' => $submission->message,
					),
					array(
						'name'  => __( 'Submitted at', 'renevo-file-request-manager' ),
						'value' => $submission->submitted_at,
					),
					array(
						'name'  => __( 'Files submitted', 'renevo-file-request-manager' ),
						'value' => implode( ', ', $filenames ),
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => true,
		);
	}

	/**
	 * Erases every submission made under a given email address.
	 *
	 * @param string $email_address Requested email address.
	 * @return array{items_removed: bool, items_retained: bool, messages: array, done: bool}
	 */
	public function erase( $email_address ) {
		$removed = false;

		foreach ( $this->submissions->find_by_email( $email_address ) as $submission ) {
			$this->file_storage->delete_submission_files( $submission->id );
			$this->submissions->delete( $submission->id );
			$removed = true;
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
