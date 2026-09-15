<?php
/**
 * Value object for a single requested file definition.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Domain;

use FileRequestManager\Services\AllowedFileTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object for one requested file definition. `key` is generated once and never changes.
 */
class RequestedFile {

	/**
	 * Stable identifier, generated once.
	 *
	 * @var string
	 */
	public $key;

	/**
	 * Short label shown to the requester, e.g. "Identity document".
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Instructions shown under the title.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Whether the requester must upload this file before the submission counts as complete.
	 *
	 * @var bool
	 */
	public $required;

	/**
	 * Allowed type keys, matching AllowedFileTypes::all() keys.
	 *
	 * @var string[]
	 */
	public $allowed_types;

	/**
	 * Maximum size per file, in megabytes.
	 *
	 * @var int
	 */
	public $max_size_mb;

	/**
	 * Maximum number of files the requester may upload for this slot.
	 *
	 * @var int
	 */
	public $max_files;

	/**
	 * Builds a RequestedFile from raw (REST or stored) input, sanitizing every field.
	 * Unknown allowed-type keys are silently dropped rather than rejected.
	 *
	 * @param array $data Raw associative array.
	 * @return self
	 */
	public static function from_array( array $data ) {
		$file = new self();

		$key               = isset( $data['key'] ) ? sanitize_key( $data['key'] ) : '';
		$file->key         = '' !== $key ? $key : wp_generate_uuid4();
		$file->title       = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$file->description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$file->required    = ! empty( $data['required'] );

		$requested_types     = isset( $data['allowed_types'] ) && is_array( $data['allowed_types'] ) ? $data['allowed_types'] : array();
		$file->allowed_types = array_values(
			array_filter(
				array_map( 'sanitize_key', $requested_types ),
				array( AllowedFileTypes::class, 'is_valid_key' )
			)
		);

		$max_size          = isset( $data['max_size_mb'] ) ? absint( $data['max_size_mb'] ) : 10;
		$file->max_size_mb = max( 1, $max_size );

		$max_files       = isset( $data['max_files'] ) ? absint( $data['max_files'] ) : 1;
		$file->max_files = max( 1, $max_files );

		return $file;
	}

	/**
	 * Converts the object to a plain array for storage or REST output.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'key'           => $this->key,
			'title'         => $this->title,
			'description'   => $this->description,
			'required'      => $this->required,
			'allowed_types' => $this->allowed_types,
			'max_size_mb'   => $this->max_size_mb,
			'max_files'     => $this->max_files,
		);
	}
}
