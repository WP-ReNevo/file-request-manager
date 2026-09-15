<?php
/**
 * Persistence for submissions and their files (custom tables).
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Repositories;

use FileRequestManager\Domain\Submission;
use FileRequestManager\Domain\SubmissionFile;
use FileRequestManager\Services\FileStorageService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence for submissions and their files.
 */
class SubmissionRepository {

	/**
	 * Object cache group for single-submission and per-submission-files lookups.
	 */
	const CACHE_GROUP = 'renevo_submissions';

	/**
	 * Object cache group for a submission's file list, keyed by submission id.
	 */
	const CACHE_GROUP_FILES = 'renevo_submission_files';

	/**
	 * Object cache group for submission_code => id lookups.
	 */
	const CACHE_GROUP_CODES = 'renevo_submission_codes';

	/**
	 * Object cache group for per-request submission counts (Requests list table).
	 */
	const CACHE_GROUP_REQUEST_COUNTS = 'renevo_request_submission_counts';

	/**
	 * @var FileStorageService
	 */
	private $file_storage;

	/**
	 * Constructor.
	 *
	 * @param FileStorageService $file_storage File storage service.
	 */
	public function __construct( FileStorageService $file_storage ) {
		$this->file_storage = $file_storage;
	}

	/**
	 * Gets the submissions table name.
	 *
	 * @return string
	 */
	private function table() {
		global $wpdb;
		return $wpdb->prefix . 'renevo_submissions';
	}

	/**
	 * Gets the submission files table name.
	 *
	 * @return string
	 */
	private function files_table() {
		global $wpdb;
		return $wpdb->prefix . 'renevo_submission_files';
	}

	/**
	 * Creates a new submission and assigns it a human-friendly submission code.
	 *
	 * @param Submission $submission Submission to persist (id is ignored).
	 * @return Submission
	 */
	public function create( Submission $submission ) {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$wpdb->insert(
			$this->table(),
			array(
				'request_id'      => $submission->request_id,
				'submission_code' => '',
				'status'          => Submission::STATUS_INCOMPLETE,
				'name'            => $submission->name,
				'email'           => $submission->email,
				'phone'           => $submission->phone,
				'company'         => $submission->company,
				'message'         => $submission->message,
				'ip_hash'         => $submission->ip_hash,
				'submitted_at'    => $now,
				'updated_at'      => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$id   = (int) $wpdb->insert_id;
		$code = 'REQ-' . ( 1000 + $id );

		$wpdb->update( $this->table(), array( 'submission_code' => $code ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );

		wp_cache_delete( absint( $submission->request_id ), self::CACHE_GROUP_REQUEST_COUNTS );

		return $this->find( $id );
	}

	/**
	 * Finds a submission by ID, with its files loaded.
	 *
	 * @param int $id Submission ID.
	 * @return Submission|null
	 */
	public function find( $id ) {
		global $wpdb;

		$id = absint( $id );

		$cached = wp_cache_get( $id, self::CACHE_GROUP );
		if ( false !== $cached ) {
			return $cached;
		}

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table()} WHERE id = %d", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name (wpdb prefix + static suffix), not user input; the %d value is passed through prepare().

		if ( ! $row ) {
			return null;
		}

		$submission        = Submission::from_row( $row );
		$submission->files = $this->get_files( $id );

		wp_cache_set( $id, $submission, self::CACHE_GROUP );

		return $submission;
	}

	/**
	 * Finds a submission by its human-facing code.
	 *
	 * @param string $code Submission code, e.g. "REQ-1042".
	 * @return Submission|null
	 */
	public function find_by_code( $code ) {
		global $wpdb;

		$code = sanitize_text_field( $code );

		$id = wp_cache_get( $code, self::CACHE_GROUP_CODES );
		if ( false === $id ) {
			$row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE submission_code = %s", $code ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %s value is passed through prepare().
			$id  = $row ? (int) $row->id : 0;

			// A submission's code never changes after creation, so this cache never needs invalidating.
			wp_cache_set( $code, $id, self::CACHE_GROUP_CODES );
		}

		return $id ? $this->find( $id ) : null;
	}

	/**
	 * Updates a submission's status.
	 *
	 * @param int    $id     Submission ID.
	 * @param string $status One of Submission::STATUS_*.
	 * @return bool
	 */
	public function update_status( $id, $status ) {
		global $wpdb;

		$allowed = array( Submission::STATUS_INCOMPLETE, Submission::STATUS_COMPLETE, Submission::STATUS_REVIEWED, Submission::STATUS_ARCHIVED );

		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$updated = false !== $wpdb->update(
			$this->table(),
			array(
				'status'     => $status,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $id ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		wp_cache_delete( absint( $id ), self::CACHE_GROUP );

		return $updated;
	}

	/**
	 * Attaches an uploaded file record to a submission.
	 *
	 * @param int             $submission_id Submission ID.
	 * @param SubmissionFile  $file          File to persist (id is ignored).
	 * @return SubmissionFile
	 */
	public function add_file( $submission_id, SubmissionFile $file ) {
		global $wpdb;

		$now = current_time( 'mysql', true );

		$wpdb->insert(
			$this->files_table(),
			array(
				'submission_id'      => absint( $submission_id ),
				'requested_file_key' => $file->requested_file_key,
				'original_filename'  => $file->original_filename,
				'stored_filename'    => $file->stored_filename,
				'file_size'          => $file->file_size,
				'mime_type'          => $file->mime_type,
				'uploaded_at'        => $now,
			),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		$wpdb->update( $this->table(), array( 'updated_at' => $now ), array( 'id' => absint( $submission_id ) ), array( '%s' ), array( '%d' ) );

		$file->id            = (int) $wpdb->insert_id;
		$file->submission_id = absint( $submission_id );

		wp_cache_delete( absint( $submission_id ), self::CACHE_GROUP );
		wp_cache_delete( absint( $submission_id ), self::CACHE_GROUP_FILES );

		return $file;
	}

	/**
	 * Gets every file attached to a submission.
	 *
	 * @param int $submission_id Submission ID.
	 * @return SubmissionFile[]
	 */
	public function get_files( $submission_id ) {
		global $wpdb;

		$submission_id = absint( $submission_id );

		$cached = wp_cache_get( $submission_id, self::CACHE_GROUP_FILES );
		if ( false !== $cached ) {
			return $cached;
		}

		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->files_table()} WHERE submission_id = %d ORDER BY id ASC", $submission_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->files_table()} is a computed table name, not user input; the %d value is passed through prepare().

		$files = array_map( array( SubmissionFile::class, 'from_row' ), $rows );

		wp_cache_set( $submission_id, $files, self::CACHE_GROUP_FILES );

		return $files;
	}

	/**
	 * Gets a single file, scoped to the submission it must belong to.
	 *
	 * @param int $submission_id Submission ID.
	 * @param int $file_id       File row ID.
	 * @return SubmissionFile|null
	 */
	public function get_file( $submission_id, $file_id ) {
		foreach ( $this->get_files( $submission_id ) as $file ) {
			if ( absint( $file_id ) === $file->id ) {
				return $file;
			}
		}

		return null;
	}

	/**
	 * Queries submissions for the admin list screen.
	 *
	 * @param array $args {
	 *     @type int    $request_id Filter by request. 0 = all.
	 *     @type string $status     Filter by status. '' = all.
	 *     @type string $search     Matches name, email, or submission code.
	 *     @type int    $page       1-indexed page number.
	 *     @type int    $per_page   Results per page.
	 * }
	 * @return array{items: Submission[], total: int}
	 */
	public function query( array $args ) {
		global $wpdb;

		$defaults = array(
			'request_id' => 0,
			'status'     => '',
			'search'     => '',
			'page'       => 1,
			'per_page'   => 20,
		);
		$args     = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['request_id'] ) ) {
			$where[]  = 'request_id = %d';
			$params[] = absint( $args['request_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(name LIKE %s OR email LIKE %s OR submission_code LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$per_page  = max( 1, (int) $args['per_page'] );
		$offset    = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$table = $this->table();

		// Not cached: filter/search/page combinations form an effectively unbounded
		// key space, and this list must always reflect the latest submissions.
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table/$where_sql are built from a computed table name and column identifiers, not raw user input; values are bound via $wpdb->prepare().
		$total     = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$list_params = array_merge( $params, array( $per_page, $offset ) );
		$list_sql    = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY submitted_at DESC LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $table/$where_sql are built from a computed table name and column identifiers, not raw user input; values are bound via $wpdb->prepare().
		$rows        = $wpdb->get_results( $wpdb->prepare( $list_sql, $list_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$items = array_map(
			function ( $row ) {
				$submission        = Submission::from_row( $row );
				$submission->files = $this->get_files( $submission->id );
				return $submission;
			},
			$rows
		);

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Counts submissions received since a given number of days ago.
	 *
	 * @param int $days Lookback window in days.
	 * @return int
	 */
	public function count_since( $days ) {
		global $wpdb;

		$since = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );

		// Not cached here: the only caller (RequestsPage) already wraps this whole
		// summary in a 5-minute transient, so a second cache layer would be redundant.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE submitted_at >= %s", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %s value is passed through prepare().
	}

	/**
	 * Counts submissions awaiting review (complete but not yet reviewed).
	 *
	 * @return int
	 */
	public function count_awaiting_review() {
		global $wpdb;

		// Not cached here: the only caller (RequestsPage) already wraps this whole
		// summary in a 5-minute transient, so a second cache layer would be redundant.
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE status = %s", Submission::STATUS_COMPLETE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %s value is passed through prepare().
	}

	/**
	 * Counts every file ever received, across all submissions.
	 *
	 * @return int
	 */
	public function count_files_received() {
		global $wpdb;

		// Not cached here: the only caller (RequestsPage) already wraps this whole
		// summary in a 5-minute transient, so a second cache layer would be redundant.
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->files_table()}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->files_table()} is a computed table name, not user input.
	}

	/**
	 * Counts submissions received for a given request (Requests list table column).
	 *
	 * @param int $request_id Request ID.
	 * @return int
	 */
	public function count_for_request( $request_id ) {
		global $wpdb;

		$request_id = absint( $request_id );

		$cached = wp_cache_get( $request_id, self::CACHE_GROUP_REQUEST_COUNTS );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->table()} WHERE request_id = %d", $request_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %d value is passed through prepare().

		wp_cache_set( $request_id, $count, self::CACHE_GROUP_REQUEST_COUNTS );

		return $count;
	}

	/**
	 * Permanently deletes a submission's database rows (files table + submission row).
	 * Does not touch the filesystem.
	 *
	 * @param int $id Submission ID.
	 * @return void
	 */
	public function delete( $id ) {
		global $wpdb;

		$id         = absint( $id );
		$submission = $this->find( $id );

		$wpdb->delete( $this->files_table(), array( 'submission_id' => $id ), array( '%d' ) );
		$wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );

		wp_cache_delete( $id, self::CACHE_GROUP );
		wp_cache_delete( $id, self::CACHE_GROUP_FILES );

		if ( $submission ) {
			wp_cache_delete( absint( $submission->request_id ), self::CACHE_GROUP_REQUEST_COUNTS );
		}
	}

	/**
	 * Finds every submission belonging to a given email address (privacy exporter/eraser).
	 *
	 * @param string $email Email address.
	 * @return Submission[]
	 */
	public function find_by_email( $email ) {
		global $wpdb;

		// Not cached: privacy exporter/eraser tooling must always see the current,
		// authoritative state — a stale cache here would be a compliance bug.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE email = %s", sanitize_email( $email ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %s value is passed through prepare().

		return array_values( array_filter( array_map( array( $this, 'find' ), wp_list_pluck( $rows, 'id' ) ) ) );
	}

	/**
	 * Deletes every submission (rows + files) past the configured retention window.
	 *
	 * Hooked to the daily maintenance cron. A retention of 0 days means "keep
	 * forever" and this becomes a no-op.
	 *
	 * @return void
	 */
	public function purge_expired_submissions() {
		global $wpdb;

		$settings       = get_option( 'renevo_settings', array() );
		$retention_days = isset( $settings['retention_days'] ) ? absint( $settings['retention_days'] ) : 0;

		if ( $retention_days <= 0 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( $retention_days * DAY_IN_SECONDS ) );

		// Not cached: this runs once a day from cron and must see the current state.
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$this->table()} WHERE submitted_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- {$this->table()} is a computed table name, not user input; the %s value is passed through prepare().

		foreach ( $ids as $id ) {
			$this->file_storage->delete_submission_files( (int) $id );
			$this->delete( (int) $id );
		}
	}
}
