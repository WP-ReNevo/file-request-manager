<?php
/**
 * Native WP_List_Table for the Requests screen.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Domain\Request;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Native WP_List_Table for the Requests screen.
 */
class RequestsListTable extends \WP_List_Table {

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * Constructor.
	 *
	 * @param RequestRepository    $requests    Request repository.
	 * @param SubmissionRepository $submissions Submission repository.
	 */
	public function __construct( RequestRepository $requests, SubmissionRepository $submissions ) {
		$this->requests    = $requests;
		$this->submissions = $submissions;

		parent::__construct(
			array(
				'singular' => 'file_request',
				'plural'   => 'file_requests',
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
			'title'       => __( 'Title', 'renevo-file-request-manager' ),
			'status'      => __( 'Status', 'renevo-file-request-manager' ),
			'shortcode'   => __( 'Shortcode', 'renevo-file-request-manager' ),
			'submissions' => __( 'Submissions', 'renevo-file-request-manager' ),
			'date'        => __( 'Date', 'renevo-file-request-manager' ),
		);
	}

	/**
	 * Fetches and prepares the current page of items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = 20;
		$paged    = $this->get_pagenum();
		$search   = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status   = isset( $_REQUEST['status'] ) ? sanitize_key( wp_unslash( $_REQUEST['status'] ) ) : 'any'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = $this->requests->query(
			array(
				'status'   => $status,
				'search'   => $search,
				'page'     => $paged,
				'per_page' => $per_page,
			)
		);

		$this->items = $result['items'];

		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Renders the checkbox/title column with row actions.
	 *
	 * @param Request $item Row item.
	 * @return string
	 */
	public function column_title( $item ) {
		$edit_url        = admin_url( 'admin.php?page=' . AdminMenu::SLUG_EDITOR . '&id=' . $item->id );
		$duplicate_url   = wp_nonce_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG_REQUESTS . '&action=duplicate&id=' . $item->id ), 'renevo_duplicate_request_' . $item->id );
		$trash_url       = wp_nonce_url( admin_url( 'admin.php?page=' . AdminMenu::SLUG_REQUESTS . '&action=trash&id=' . $item->id ), 'renevo_trash_request_' . $item->id );
		$submissions_url = admin_url( 'admin.php?page=' . AdminMenu::SLUG_SUBMISSIONS . '&request_id=' . $item->id );

		$title = $item->title ? $item->title : __( '(no title)', 'renevo-file-request-manager' );

		$actions = array(
			'edit'        => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'renevo-file-request-manager' ) ),
			'submissions' => sprintf( '<a href="%s">%s</a>', esc_url( $submissions_url ), esc_html__( 'View submissions', 'renevo-file-request-manager' ) ),
			'duplicate'   => sprintf( '<a href="%s">%s</a>', esc_url( $duplicate_url ), esc_html__( 'Duplicate', 'renevo-file-request-manager' ) ),
			'trash'       => sprintf( '<a href="%s" onclick="return confirm(\'%s\');">%s</a>', esc_url( $trash_url ), esc_js( __( 'Move this request to trash?', 'renevo-file-request-manager' ) ), esc_html__( 'Trash', 'renevo-file-request-manager' ) ),
		);

		return sprintf(
			'<strong><a class="row-title" href="%1$s">%2$s</a></strong>%3$s',
			esc_url( $edit_url ),
			esc_html( $title ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Renders the status column.
	 *
	 * @param Request $item Row item.
	 * @return string
	 */
	public function column_status( $item ) {
		$label = 'publish' === $item->status ? __( 'Published', 'renevo-file-request-manager' ) : __( 'Draft', 'renevo-file-request-manager' );
		$class = 'publish' === $item->status ? 'renevo-badge frm-badge--published' : 'renevo-badge frm-badge--draft';

		return sprintf( '<span class="%s">%s</span>', esc_attr( $class ), esc_html( $label ) );
	}

	/**
	 * Renders the shortcode column with a copy button.
	 *
	 * @param Request $item Row item.
	 * @return string
	 */
	public function column_shortcode( $item ) {
		$shortcode = '[file_request id="' . $item->id . '"]';

		return sprintf(
			'<code class="frm-shortcode" title="%s">%s</code>',
			esc_attr__( 'Click to copy', 'renevo-file-request-manager' ),
			esc_html( $shortcode )
		);
	}

	/**
	 * Renders the submissions-count column.
	 *
	 * @param Request $item Row item.
	 * @return string
	 */
	public function column_submissions( $item ) {
		$count = $this->submissions->count_for_request( $item->id );

		$url = admin_url( 'admin.php?page=' . AdminMenu::SLUG_SUBMISSIONS . '&request_id=' . $item->id );

		return sprintf( '<a href="%s">%d</a>', esc_url( $url ), $count );
	}

	/**
	 * Renders the date column.
	 *
	 * @param Request $item Row item.
	 * @return string
	 */
	public function column_date( $item ) {
		return esc_html( mysql2date( get_option( 'date_format' ), $item->created_at ) );
	}
}
