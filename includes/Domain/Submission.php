<?php
/**
 * Value object for a submission.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object for a submission: contact details plus uploaded files.
 */
class Submission {

	const STATUS_INCOMPLETE = 'incomplete';
	const STATUS_COMPLETE   = 'complete';
	const STATUS_REVIEWED   = 'reviewed';
	const STATUS_ARCHIVED   = 'archived';

	/**
	 * @var int|null
	 */
	public $id;

	/**
	 * @var int
	 */
	public $request_id;

	/**
	 * Human-facing identifier, e.g. "REQ-1042".
	 *
	 * @var string
	 */
	public $submission_code;

	/**
	 * @var string
	 */
	public $status = self::STATUS_INCOMPLETE;

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $email = '';

	/**
	 * @var string
	 */
	public $phone = '';

	/**
	 * @var string
	 */
	public $company = '';

	/**
	 * @var string
	 */
	public $message = '';

	/**
	 * Hashed IP address, only populated when the "store IP" setting is enabled.
	 *
	 * @var string|null
	 */
	public $ip_hash = null;

	/**
	 * @var string
	 */
	public $submitted_at = '';

	/**
	 * @var string
	 */
	public $updated_at = '';

	/**
	 * @var SubmissionFile[]
	 */
	public $files = array();

	/**
	 * Hydrates a Submission from a `renevo_submissions` database row.
	 *
	 * @param object $row Row object from $wpdb.
	 * @return self
	 */
	public static function from_row( $row ) {
		$submission                  = new self();
		$submission->id              = (int) $row->id;
		$submission->request_id      = (int) $row->request_id;
		$submission->submission_code = $row->submission_code;
		$submission->status          = $row->status;
		$submission->name            = $row->name;
		$submission->email           = $row->email;
		$submission->phone           = $row->phone;
		$submission->company         = $row->company;
		$submission->message         = $row->message;
		$submission->ip_hash         = $row->ip_hash;
		$submission->submitted_at    = $row->submitted_at;
		$submission->updated_at      = $row->updated_at;

		return $submission;
	}

	/**
	 * Converts the object to a plain array for REST output.
	 *
	 * @param bool $include_files Whether to include the (already-loaded) files array.
	 * @return array
	 */
	public function to_array( $include_files = true ) {
		$data = array(
			'id'              => $this->id,
			'request_id'      => $this->request_id,
			'submission_code' => $this->submission_code,
			'status'          => $this->status,
			'name'            => $this->name,
			'email'           => $this->email,
			'phone'           => $this->phone,
			'company'         => $this->company,
			'message'         => $this->message,
			'submitted_at'    => $this->submitted_at,
			'updated_at'      => $this->updated_at,
		);

		if ( $include_files ) {
			$data['files'] = array_map(
				static function ( SubmissionFile $file ) {
					return $file->to_array();
				},
				$this->files
			);
		}

		return $data;
	}
}
