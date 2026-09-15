<?php
/**
 * Detail view for a single submission.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Domain\Submission;
use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Rest\RequestsController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detail view for a single submission.
 */
class SubmissionDetailPage {

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
	}

	/**
	 * Renders the detail view for one submission.
	 *
	 * @param int $id Submission ID.
	 * @return void
	 */
	public function render( $id ) {
		$this->maybe_handle_status_update( $id );

		$submission = $this->submissions->find( $id );
		$back_url   = admin_url( 'admin.php?page=' . AdminMenu::SLUG_SUBMISSIONS );

		if ( ! $submission ) {
			?>
			<div class="wrap"><p><?php esc_html_e( "We couldn't find this submission.", 'renevo-file-request-manager' ); ?></p></div>
			<?php
			return;
		}

		$request = $this->requests->find( $submission->request_id );
		?>
		<div class="wrap frm-admin-page">
			<?php include RENEVO_PATH . 'includes/templates/admin/brand-header.php'; ?>
			<div class="frm-page-header">
				<div class="frm-page-header__row">
					<div class="frm-page-header__title">
						<a href="<?php echo esc_url( $back_url ); ?>" class="frm-page-header__back"><?php esc_html_e( '← Back to submissions', 'renevo-file-request-manager' ); ?></a>
						<h1><?php echo esc_html( $submission->submission_code ); ?></h1>
					</div>
					<div class="frm-page-header__actions">
						<?php $this->render_status_form( $submission ); ?>
					</div>
				</div>
			</div>

			<div class="frm-admin-page__body">
				<div class="postbox frm-submission-overview">
					<div class="inside">
						<div class="frm-submission-overview__grid">
							<div>
								<h3><?php esc_html_e( 'Requested files', 'renevo-file-request-manager' ); ?></h3>
								<?php $this->render_requested_files( $submission, $request ); ?>
							</div>
							<div>
								<h3><?php esc_html_e( 'Submitter', 'renevo-file-request-manager' ); ?></h3>
								<p><strong><?php esc_html_e( 'Name', 'renevo-file-request-manager' ); ?>:</strong> <?php echo esc_html( $submission->name ); ?></p>
								<p><strong><?php esc_html_e( 'Email', 'renevo-file-request-manager' ); ?>:</strong> <?php echo esc_html( $submission->email ); ?></p>
								<?php if ( $submission->phone ) : ?>
									<p><strong><?php esc_html_e( 'Phone', 'renevo-file-request-manager' ); ?>:</strong> <?php echo esc_html( $submission->phone ); ?></p>
								<?php endif; ?>
								<?php if ( $submission->company ) : ?>
									<p><strong><?php esc_html_e( 'Company', 'renevo-file-request-manager' ); ?>:</strong> <?php echo esc_html( $submission->company ); ?></p>
								<?php endif; ?>
								<?php if ( $submission->message ) : ?>
									<p><strong><?php esc_html_e( 'Message', 'renevo-file-request-manager' ); ?>:</strong><br><?php echo esc_html( $submission->message ); ?></p>
								<?php endif; ?>
								<p><strong><?php esc_html_e( 'Request', 'renevo-file-request-manager' ); ?>:</strong> <?php echo $request ? esc_html( $request->title ) : esc_html__( '(deleted)', 'renevo-file-request-manager' ); ?></p>
								<p><strong><?php esc_html_e( 'Submitted at', 'renevo-file-request-manager' ); ?>:</strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission->submitted_at ) ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<h2 class="frm-postbox-header frm-files-received-heading">
					<span><?php esc_html_e( 'Files received', 'renevo-file-request-manager' ); ?></span>
					<?php $this->render_download_all_button( $submission ); ?>
				</h2>

				<div class="frm-files-grid">
					<?php $this->render_files_by_field( $submission, $request ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the inline Status select + Update button shown in the page header.
	 *
	 * @param Submission $submission Submission.
	 * @return void
	 */
	private function render_status_form( Submission $submission ) {
		?>
		<form method="post" class="frm-page-header__status-form">
			<?php wp_nonce_field( 'renevo_update_status_' . $submission->id ); ?>
			<select name="status" id="frm-status">
				<?php foreach ( $this->status_labels() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $submission->status, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php submit_button( __( 'Update status', 'renevo-file-request-manager' ), 'secondary', 'renevo_update_status', false ); ?>
		</form>
		<?php
	}

	/**
	 * Renders the requested-files checklist (received/missing).
	 *
	 * @param Submission                            $submission Submission.
	 * @param \FileRequestManager\Domain\Request|null $request    Parent request.
	 * @return void
	 */
	private function render_requested_files( Submission $submission, $request ) {
		if ( ! $request || empty( $request->requested_files ) ) {
			echo '<p>' . esc_html__( 'No specific files were requested.', 'renevo-file-request-manager' ) . '</p>';
			return;
		}

		$received_keys = wp_list_pluck( $submission->files, 'requested_file_key' );
		echo '<ul class="frm-requested-files-checklist">';
		foreach ( $request->requested_files as $file ) {
			$received = in_array( $file->key, $received_keys, true );
			printf(
				'<li>%1$s %2$s%3$s</li>',
				$received ? '&#9989;' : '&#10060;',
				esc_html( $file->title ),
				$received ? '' : ' <em>(' . esc_html__( 'missing', 'renevo-file-request-manager' ) . ')</em>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
		echo '</ul>';
	}

	/**
	 * Renders one postbox per requested-file field, each with its own table
	 * of received files and its own "Download all" button scoped to
	 * just that field.
	 *
	 * @param Submission                             $submission Submission.
	 * @param \FileRequestManager\Domain\Request|null $request    Parent request.
	 * @return void
	 */
	private function render_files_by_field( Submission $submission, $request ) {
		if ( empty( $submission->files ) ) {
			echo '<p>' . esc_html__( 'No files have been received yet.', 'renevo-file-request-manager' ) . '</p>';
			return;
		}

		$groups = $this->group_files_by_field( $submission->files, $request );

		foreach ( $groups as $group ) :
			?>
			<div class="postbox">
				<h2 class="frm-postbox-header">
					<span><?php echo esc_html( $group['label'] ); ?></span>
					<?php $this->render_download_all_button( $submission, $group['key'] ); ?>
				</h2>
				<div class="inside">
					<table class="widefat striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'File name', 'renevo-file-request-manager' ); ?></th>
								<th><?php esc_html_e( 'Type', 'renevo-file-request-manager' ); ?></th>
								<th><?php esc_html_e( 'Size', 'renevo-file-request-manager' ); ?></th>
								<th><?php esc_html_e( 'Uploaded', 'renevo-file-request-manager' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $group['files'] as $file ) : ?>
								<tr>
									<td><?php echo esc_html( $file->original_filename ); ?></td>
									<td><?php echo esc_html( $file->mime_type ); ?></td>
									<td><?php echo esc_html( size_format( $file->file_size ) ); ?></td>
									<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $file->uploaded_at ) ); ?></td>
									<td><a class="button button-small" href="<?php echo esc_url( $this->download_url( $submission->id, $file->id ) ); ?>"><?php esc_html_e( 'Download', 'renevo-file-request-manager' ); ?></a></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
			<?php
		endforeach;
	}

	/**
	 * Groups a submission's files by which requested-file slot they answer,
	 * in the request's own field order, with any orphaned files (the field
	 * no longer exists on the request) trailing under "Other files".
	 *
	 * @param \FileRequestManager\Domain\SubmissionFile[] $files   Submission files.
	 * @param \FileRequestManager\Domain\Request|null      $request Parent request.
	 * @return array<int, array{label: string, files: array}>
	 */
	private function group_files_by_field( array $files, $request ) {
		$titles_by_key = array();
		if ( $request ) {
			foreach ( $request->requested_files as $requested_file ) {
				$titles_by_key[ $requested_file->key ] = $requested_file->title;
			}
		}

		$groups = array();
		foreach ( $titles_by_key as $key => $title ) {
			$groups[ $key ] = array(
				'key'   => $key,
				'label' => $title,
				'files' => array(),
			);
		}

		$other = array();
		foreach ( $files as $file ) {
			if ( isset( $groups[ $file->requested_file_key ] ) ) {
				$groups[ $file->requested_file_key ]['files'][] = $file;
			} else {
				$other[] = $file;
			}
		}

		$groups = array_filter(
			$groups,
			static function ( $group ) {
				return ! empty( $group['files'] );
			}
		);

		if ( ! empty( $other ) ) {
			$groups[] = array(
				'key'   => '',
				'label' => __( 'Other files', 'renevo-file-request-manager' ),
				'files' => $other,
			);
		}

		return $groups;
	}

	/**
	 * Renders a "Download all" button, optionally scoped to one field.
	 *
	 * @param Submission $submission Submission.
	 * @param string     $field      Requested-file key to scope the ZIP to, or '' for everything.
	 * @return void
	 */
	private function render_download_all_button( Submission $submission, $field = '' ) {
		if ( empty( $submission->files ) ) {
			return;
		}
		?>
		<a class="button button-small" href="<?php echo esc_url( $this->download_all_url( $submission->id, $field ) ); ?>">
			<?php esc_html_e( 'Download all', 'renevo-file-request-manager' ); ?>
		</a>
		<?php
	}

	/**
	 * Builds the protected REST download URL for one file.
	 *
	 * @param int $submission_id Submission ID.
	 * @param int $file_id       File ID.
	 * @return string
	 */
	private function download_url( $submission_id, $file_id ) {
		$url = rest_url( RequestsController::NAMESPACE_V1 . "/submissions/{$submission_id}/files/{$file_id}/download" );

		return add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), $url );
	}

	/**
	 * Builds the protected REST download URL for a submission's files as one
	 * ZIP, optionally scoped to a single requested-file field.
	 *
	 * @param int    $submission_id Submission ID.
	 * @param string $field         Requested-file key to scope the ZIP to, or '' for everything.
	 * @return string
	 */
	private function download_all_url( $submission_id, $field = '' ) {
		$url = rest_url( RequestsController::NAMESPACE_V1 . "/submissions/{$submission_id}/download-all" );
		$url = add_query_arg( '_wpnonce', wp_create_nonce( 'wp_rest' ), $url );

		return $field ? add_query_arg( 'field', $field, $url ) : $url;
	}

	/**
	 * Handles the status-update form submission.
	 *
	 * @param int $id Submission ID.
	 * @return void
	 */
	private function maybe_handle_status_update( $id ) {
		if ( empty( $_POST['renevo_update_status'] ) ) {
			return;
		}

		check_admin_referer( 'renevo_update_status_' . $id );

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$this->submissions->update_status( $id, $status );
	}

	/**
	 * Gets the status dropdown labels.
	 *
	 * @return array<string, string>
	 */
	private function status_labels() {
		return array(
			Submission::STATUS_INCOMPLETE => __( 'Incomplete', 'renevo-file-request-manager' ),
			Submission::STATUS_COMPLETE   => __( 'Complete', 'renevo-file-request-manager' ),
			Submission::STATUS_REVIEWED   => __( 'Reviewed', 'renevo-file-request-manager' ),
			Submission::STATUS_ARCHIVED   => __( 'Archived', 'renevo-file-request-manager' ),
		);
	}
}
