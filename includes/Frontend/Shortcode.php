<?php
/**
 * The [file_request] shortcode.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The [file_request] shortcode.
 */
class Shortcode {

	/**
	 * @var RequestRenderer
	 */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param RequestRenderer $renderer Shared renderer.
	 */
	public function __construct( RequestRenderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Registers the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'file_request', array( $this, 'render' ) );
	}

	/**
	 * Renders [file_request id="123"].
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), (array) $atts, 'file_request' );
		$id   = absint( $atts['id'] );

		if ( ! $id ) {
			return '';
		}

		return $this->renderer->render( $id );
	}
}
