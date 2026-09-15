<?php
/**
 * Value object for one uploaded file attached to a submission.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object for one uploaded file attached to a submission.
 */
class SubmissionFile {

	/**
	 * @var int|null
	 */
	public $id;

	/**
	 * @var int
	 */
	public $submission_id;

	/**
	 * Key of the requested-file slot this upload answers.
	 *
	 * @var string
	 */
	public $requested_file_key;

	/**
	 * @var string
	 */
	public $original_filename;

	/**
	 * @var string
	 */
	public $stored_filename;

	/**
	 * @var int
	 */
	public $file_size;

	/**
	 * @var string
	 */
	public $mime_type;

	/**
	 * @var string
	 */
	public $uploaded_at;

	/**
	 * Hydrates a SubmissionFile from a `renevo_submission_files` database row.
	 *
	 * @param object $row Row object from $wpdb.
	 * @return self
	 */
	public static function from_row( $row ) {
		$file                     = new self();
		$file->id                 = (int) $row->id;
		$file->submission_id      = (int) $row->submission_id;
		$file->requested_file_key = $row->requested_file_key;
		$file->original_filename  = $row->original_filename;
		$file->stored_filename    = $row->stored_filename;
		$file->file_size          = (int) $row->file_size;
		$file->mime_type          = $row->mime_type;
		$file->uploaded_at        = $row->uploaded_at;

		return $file;
	}

	/**
	 * Converts the object to a plain array for REST output. Never includes `stored_filename`.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'                 => $this->id,
			'requested_file_key' => $this->requested_file_key,
			'original_filename'  => $this->original_filename,
			'file_size'          => $this->file_size,
			'mime_type'          => $this->mime_type,
			'uploaded_at'        => $this->uploaded_at,
		);
	}
}
