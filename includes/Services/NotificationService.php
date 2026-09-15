<?php
/**
 * Sends the admin notification and requester confirmation emails.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Services;

use FileRequestManager\Domain\Request;
use FileRequestManager\Domain\Submission;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends the admin notification and requester confirmation emails.
 */
class NotificationService {

	/**
	 * @var PlaceholderResolver
	 */
	private $placeholders;

	/**
	 * Constructor.
	 *
	 * @param PlaceholderResolver $placeholders Placeholder resolver.
	 */
	public function __construct( PlaceholderResolver $placeholders ) {
		$this->placeholders = $placeholders;
	}

	/**
	 * Sends the "you have a new submission" email to the site admin, if enabled.
	 *
	 * @param Request    $request    The request that was submitted against.
	 * @param Submission $submission The newly created submission.
	 * @return void
	 */
	public function send_admin_notification( Request $request, Submission $submission ) {
		$settings = $request->notification_settings;

		if ( empty( $settings['admin_notify_enabled'] ) ) {
			return;
		}

		$to = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );

		if ( ! is_email( $to ) ) {
			return;
		}

		$context = $this->build_context( $request, $submission );

		$this->send(
			$to,
			$this->placeholders->resolve( $settings['admin_subject'], $context ),
			$this->placeholders->resolve( $settings['admin_body'], $context ),
			array(),
			array(
				'url'   => $context['submission_url'],
				'label' => __( 'View submission', 'renevo-file-request-manager' ),
			)
		);
	}

	/**
	 * Sends the "we received your documents" confirmation to the requester, if enabled.
	 *
	 * @param Request    $request    The request that was submitted against.
	 * @param Submission $submission The newly created submission.
	 * @return void
	 */
	public function send_requester_confirmation( Request $request, Submission $submission ) {
		$settings = $request->notification_settings;

		if ( empty( $settings['requester_confirmation_enabled'] ) || ! is_email( $submission->email ) ) {
			return;
		}

		$context = $this->build_context( $request, $submission );

		$this->send(
			$submission->email,
			$this->placeholders->resolve( $settings['requester_subject'], $context ),
			$this->placeholders->resolve( $settings['requester_body'], $context ),
			array(
				'name'  => $settings['requester_from_name'] ?? '',
				'email' => $settings['requester_from_email'] ?? '',
			)
		);
	}

	/**
	 * Builds the placeholder context shared by both email types.
	 *
	 * @param Request    $request    Request.
	 * @param Submission $submission Submission.
	 * @return array<string, string>
	 */
	private function build_context( Request $request, Submission $submission ) {
		$context = array(
			'name'           => $submission->name,
			'email'          => $submission->email,
			'phone'          => $submission->phone,
			'company'        => $submission->company,
			'message'        => $submission->message,
			'request_title'  => $request->title,
			'submission_id'  => $submission->submission_code,
			'submitted_at'   => mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission->submitted_at ),
			'file_count'     => count( $submission->files ),
			'request_url'    => '',
			'submission_url' => admin_url( 'admin.php?page=renevo-submissions&view=submission&id=' . $submission->id ),
		);

		/**
		 * Filters the placeholder context used to render notification emails.
		 *
		 * @param array      $context    Placeholder name => value.
		 * @param Request    $request    The request being notified about.
		 * @param Submission $submission The submission being notified about.
		 */
		return apply_filters( 'renevo_notification_context', $context, $request, $submission );
	}

	/**
	 * Sends a single HTML email using a small shared template.
	 *
	 * @param string               $to      Recipient email address.
	 * @param string               $subject Resolved subject line.
	 * @param string               $body    Resolved plain-text body (line breaks preserved, wrapped in the HTML template).
	 * @param array{name?: string, email?: string} $from   Optional sender override.
	 * @param array{url?: string, label?: string}  $button Optional call-to-action button shown below the body.
	 * @return void
	 */
	private function send( $to, $subject, $body, array $from = array(), array $button = array() ) {
		$set_html_content_type = static function () {
			return 'text/html';
		};
		add_filter( 'wp_mail_content_type', $set_html_content_type );

		$from_email_filter = null;
		$from_name_filter  = null;

		if ( ! empty( $from['email'] ) && is_email( $from['email'] ) ) {
			$from_email_filter = static function () use ( $from ) {
				return $from['email'];
			};
			add_filter( 'wp_mail_from', $from_email_filter );
		}

		if ( ! empty( $from['name'] ) ) {
			$from_name_filter = static function () use ( $from ) {
				return $from['name'];
			};
			add_filter( 'wp_mail_from_name', $from_name_filter );
		}

		$html = $this->render_template( $subject, $body, $button );

		wp_mail( $to, $subject, $html );

		remove_filter( 'wp_mail_content_type', $set_html_content_type );

		if ( $from_email_filter ) {
			remove_filter( 'wp_mail_from', $from_email_filter );
		}

		if ( $from_name_filter ) {
			remove_filter( 'wp_mail_from_name', $from_name_filter );
		}
	}

	/**
	 * Wraps a plain-text body in a minimal, safe HTML email template.
	 *
	 * @param string                               $subject Email subject, used as the on-page heading.
	 * @param string                               $body    Plain-text body.
	 * @param array{url?: string, label?: string}  $button  Optional call-to-action button shown below the body.
	 * @return string
	 */
	private function render_template( $subject, $body, array $button = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		ob_start();
		include RENEVO_PATH . 'includes/templates/emails/notification.php';
		return (string) ob_get_clean();
	}
}
