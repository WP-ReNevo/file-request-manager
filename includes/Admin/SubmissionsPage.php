<?php
/**
 * The Submissions admin screen (list + detail).
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
 * The Submissions admin screen (list + detail).
 */
class SubmissionsPage {

	/**
	 * Renders the page: the list, or one submission's detail view.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Capabilities::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'renevo-file-request-manager' ) );
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'list'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'submission' === $view && ! empty( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$detail = new SubmissionDetailPage( new SubmissionRepository( new FileStorageService() ), new RequestRepository() );
			$detail->render( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$this->render_list();
	}

	/**
	 * Renders the submissions list.
	 *
	 * @return void
	 */
	private function render_list() {
		$table = new SubmissionsListTable( new SubmissionRepository( new FileStorageService() ), new RequestRepository() );
		$table->prepare_items();
		?>
		<div class="wrap frm-admin-page">
			<?php include RENEVO_PATH . 'includes/templates/admin/brand-header.php'; ?>
			<div class="frm-page-header">
				<div class="frm-page-header__row">
					<div class="frm-page-header__title">
						<h1><?php esc_html_e( 'Submissions', 'renevo-file-request-manager' ); ?></h1>
					</div>
				</div>
			</div>

			<div class="frm-admin-page__body">
				<form method="get">
					<input type="hidden" name="page" value="<?php echo esc_attr( AdminMenu::SLUG_SUBMISSIONS ); ?>">
					<?php $table->search_box( __( 'Search submissions', 'renevo-file-request-manager' ), 'renevo-submission-search' ); ?>
				</form>
				<form method="post">
					<?php $table->display(); ?>
				</form>
			</div>
		</div>
		<?php
	}
}
