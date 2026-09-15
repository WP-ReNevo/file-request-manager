<?php
/**
 * Native WP_List_Table for the Submissions screen.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Domain\Submission;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Native WP_List_Table for the Submissions screen.
 */
class SubmissionsListTable extends \WP_List_Table {

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * Constructor.
	 *
	 * @param SubmissionRepository $submissions Submission repository.
	 * @param RequestRepository    $requests    Request repository.
	 */
	public function __construct( SubmissionRepository $submissions, RequestRepository $requests ) {
		$this->submissions = $submissions;
		$this->requests    = $requests;

		parent::__construct(
			array(
				'singular' => 'submission',
				'plural'   => 'submissions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Defines the columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'submission_code' => __( 'ID', 'renevo-file-request-manager' ),
			'requester'       => __( 'Requester', 'renevo-file-request-manager' ),
			'request'         => __( 'Request', 'renevo-file-request-manager' ),
			'received'        => __( 'Received', 'renevo-file-request-manager' ),
			'progress'        => __( 'Progress', 'renevo-file-request-manager' ),
			'status'          => __( 'Status', 'renevo-file-request-manager' ),
		);
	}

	/**
	 * Fetches and prepares the current page of items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page   = 20;
		$paged      = $this->get_pagenum();
		$search     = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status     = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_id = isset( $_REQUEST['request_id'] ) ? absint( $_REQUEST['request_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = $this->submissions->query(
			array(
				'request_id' => $request_id,
				'status'     => $status,
				'search'     => $search,
				'page'       => $paged,
				'per_page'   => $per_page,
			)
		);

		$this->items           = $result['items'];
		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Renders the submission-code column, linking to the detail view.
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_submission_code( $item ) {
		$url = admin_url( 'admin.php?page=' . AdminMenu::SLUG_SUBMISSIONS . '&view=submission&id=' . $item->id );

		return sprintf( '<a href="%s"><strong>%s</strong></a>', esc_url( $url ), esc_html( $item->submission_code ) );
	}

	/**
	 * Renders the requester column (name + email).
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_requester( $item ) {
		$name = $item->name ? $item->name : __( '(no name)', 'renevo-file-request-manager' );

		return sprintf( '%s<br><span class="description">%s</span>', esc_html( $name ), esc_html( $item->email ) );
	}

	/**
	 * Renders the parent request column.
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_request( $item ) {
		$request = $this->requests->find( $item->request_id );

		if ( ! $request ) {
			return esc_html__( '(deleted request)', 'renevo-file-request-manager' );
		}

		$url = admin_url( 'admin.php?page=' . AdminMenu::SLUG_EDITOR . '&id=' . $request->id );

		return sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html( $request->title ) );
	}

	/**
	 * Renders the received-date column.
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_received( $item ) {
		return esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $item->submitted_at ) );
	}

	/**
	 * Renders the "N / M required files" progress column.
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_progress( $item ) {
		$request = $this->requests->find( $item->request_id );

		if ( ! $request ) {
			return '&#8212;';
		}

		$required = array_filter(
			$request->requested_files,
			static function ( $file ) {
				return $file->required;
			}
		);

		if ( empty( $required ) ) {
			return esc_html( count( $item->files ) . ' ' . __( 'files', 'renevo-file-request-manager' ) );
		}

		$received_keys = wp_list_pluck( $item->files, 'requested_file_key' );
		$received      = 0;

		foreach ( $required as $file ) {
			if ( in_array( $file->key, $received_keys, true ) ) {
				++$received;
			}
		}

		return sprintf(
			/* translators: 1: number of required files received, 2: total number of required files. */
			esc_html__( '%1$d / %2$d required files', 'renevo-file-request-manager' ),
			$received,
			count( $required )
		);
	}

	/**
	 * Renders the status column.
	 *
	 * @param Submission $item Row item.
	 * @return string
	 */
	public function column_status( $item ) {
		$labels = array(
			Submission::STATUS_INCOMPLETE => __( 'Incomplete', 'renevo-file-request-manager' ),
			Submission::STATUS_COMPLETE   => __( 'Complete', 'renevo-file-request-manager' ),
			Submission::STATUS_REVIEWED   => __( 'Reviewed', 'renevo-file-request-manager' ),
			Submission::STATUS_ARCHIVED   => __( 'Archived', 'renevo-file-request-manager' ),
		);

		$label = isset( $labels[ $item->status ] ) ? $labels[ $item->status ] : $item->status;

		return sprintf( '<span class="frm-badge frm-badge--%s">%s</span>', esc_attr( $item->status ), esc_html( $label ) );
	}
}
