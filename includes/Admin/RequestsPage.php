<?php
/**
 * The Requests list admin screen.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Core\Capabilities;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Requests list admin screen: the plugin's landing page, combining the
 * former Dashboard's summary cards with the requests table so the page
 * reads as one substantial screen instead of two sparse ones.
 */
class RequestsPage {

	const CACHE_KEY = 'renevo_dashboard_counts';

	/**
	 * Renders the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Capabilities::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'renevo-file-request-manager' ) );
		}

		$requests    = new RequestRepository();
		$submissions = new SubmissionRepository( new FileStorageService() );
		$this->maybe_handle_action( $requests );

		$table = new RequestsListTable( $requests, $submissions );
		$table->prepare_items();

		$counts  = $this->get_counts( $requests, $submissions );
		$new_url = admin_url( 'admin.php?page=' . AdminMenu::SLUG_EDITOR );
		?>
		<div class="wrap frm-admin-page">
			<?php include RENEVO_PATH . 'includes/templates/admin/brand-header.php'; ?>
			<div class="frm-page-header">
				<div class="frm-page-header__row">
					<div class="frm-page-header__title">
						<h1><?php esc_html_e( 'File Requests', 'renevo-file-request-manager' ); ?></h1>
					</div>
					<div class="frm-page-header__actions">
						<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary"><?php esc_html_e( 'Add New', 'renevo-file-request-manager' ); ?></a>
					</div>
				</div>
			</div>

			<div class="frm-admin-page__body">
				<p class="frm-dashboard-intro"><?php esc_html_e( 'Create a request, tell people exactly what you need, and collect everything in one place.', 'renevo-file-request-manager' ); ?></p>

				<div class="frm-dashboard-cards">
					<div class="frm-dashboard-card">
						<span class="frm-dashboard-card__value"><?php echo esc_html( $counts['active_requests'] ); ?></span>
						<span class="frm-dashboard-card__label"><?php esc_html_e( 'Active requests', 'renevo-file-request-manager' ); ?></span>
					</div>
					<div class="frm-dashboard-card">
						<span class="frm-dashboard-card__value"><?php echo esc_html( $counts['submissions_this_week'] ); ?></span>
						<span class="frm-dashboard-card__label"><?php esc_html_e( 'Submissions this week', 'renevo-file-request-manager' ); ?></span>
					</div>
					<div class="frm-dashboard-card">
						<span class="frm-dashboard-card__value"><?php echo esc_html( $counts['awaiting_review'] ); ?></span>
						<span class="frm-dashboard-card__label"><?php esc_html_e( 'Awaiting review', 'renevo-file-request-manager' ); ?></span>
					</div>
					<div class="frm-dashboard-card">
						<span class="frm-dashboard-card__value"><?php echo esc_html( $counts['files_received'] ); ?></span>
						<span class="frm-dashboard-card__label"><?php esc_html_e( 'Files received', 'renevo-file-request-manager' ); ?></span>
					</div>
				</div>

				<form method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG_REQUESTS ); ?>">
					<?php $table->search_box( __( 'Search requests', 'renevo-file-request-manager' ), 'renevo-request-search' ); ?>
				</form>
				<form method="post">
					<?php $table->display(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Gets summary counts, cached briefly to avoid recomputing on every page load.
	 *
	 * @param RequestRepository    $requests    Request repository.
	 * @param SubmissionRepository $submissions Submission repository.
	 * @return array<string, int>
	 */
	private function get_counts( RequestRepository $requests, SubmissionRepository $submissions ) {
		$cached = get_transient( self::CACHE_KEY );

		if ( false !== $cached ) {
			return $cached;
		}

		$request_counts = $requests->counts_by_status();

		$counts = array(
			'active_requests'       => $request_counts['publish'],
			'submissions_this_week' => $submissions->count_since( 7 ),
			'awaiting_review'       => $submissions->count_awaiting_review(),
			'files_received'        => $submissions->count_files_received(),
		);

		set_transient( self::CACHE_KEY, $counts, 5 * MINUTE_IN_SECONDS );

		return $counts;
	}

	/**
	 * Handles the duplicate/trash row actions.
	 *
	 * @param RequestRepository $requests Request repository.
	 * @return void
	 */
	private function maybe_handle_action( RequestRepository $requests ) {
		if ( empty( $_GET['action'] ) || empty( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$action = sanitize_key( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = absint( $_GET['id'] );

		if ( 'duplicate' === $action && check_admin_referer( 'renevo_duplicate_request_' . $id ) ) {
			$requests->duplicate( $id );
		} elseif ( 'trash' === $action && check_admin_referer( 'renevo_trash_request_' . $id ) ) {
			$requests->trash( $id );
		} else {
			return;
		}

		wp_safe_redirect( remove_query_arg( array( 'action', 'id', '_wpnonce' ) ) );
		exit;
	}
}
