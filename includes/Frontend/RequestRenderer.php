<?php
/**
 * Shared rendering used by both the shortcode and the Gutenberg block.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Frontend;

use FileRequestManager\Repositories\RequestRepository;
use FileRequestManager\Rest\PublicController;
use FileRequestManager\Rest\RequestsController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a request ID into frontend HTML. Shared by the shortcode and the block.
 */
class RequestRenderer {

	/**
	 * @var RequestRepository
	 */
	private $requests;

	/**
	 * Constructor.
	 *
	 * @param RequestRepository $requests Request repository.
	 */
	public function __construct( RequestRepository $requests ) {
		$this->requests = $requests;
	}

	/**
	 * Renders a published request as a mount point for the frontend React app.
	 *
	 * @param int $id Request post ID.
	 * @return string HTML.
	 */
	public function render( $id ) {
		$id      = absint( $id );
		$request = $this->requests->find_published( $id );

		if ( ! $request ) {
			return '<p class="frm-frontend-unavailable">' . esc_html__( 'This file request is not available.', 'renevo-file-request-manager' ) . '</p>';
		}

		wp_enqueue_script( 'renevo-frontend' );
		wp_enqueue_style( 'renevo-frontend' );

		$data            = $request->to_public_array();
		$data['nonce']   = wp_create_nonce( PublicController::nonce_action( $id ) );
		$data['restUrl'] = esc_url_raw( trailingslashit( rest_url( RequestsController::NAMESPACE_V1 ) ) );

		/**
		 * Filters the data passed to the frontend app for one rendered request.
		 *
		 * @param array $data    Public request data plus nonce/restUrl.
		 * @param int   $id      Request ID.
		 */
		$data = apply_filters( 'renevo_frontend_render_data', $data, $id );

		$container_id = 'renevo-request-' . $id . '-' . wp_unique_id();

		return sprintf(
			'<div id="%1$s" class="frm-frontend-root" data-frm-request="%2$s"></div>',
			esc_attr( $container_id ),
			esc_attr( wp_json_encode( $data ) )
		);
	}
}
