<?php
/**
 * Registers the `renevo/file-request` Gutenberg block.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Blocks;

use FileRequestManager\Frontend\RequestRenderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `renevo/file-request` Gutenberg block.
 * Dynamic block; render_callback delegates to the same RequestRenderer as the shortcode.
 */
class FileRequestBlock {

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
	 * Registers the block type.
	 *
	 * @return void
	 */
	public function register() {
		$build_dir = RENEVO_PATH . 'build/blocks/file-request';

		if ( ! file_exists( $build_dir . '/block.json' ) ) {
			return;
		}

		register_block_type(
			$build_dir,
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * Renders the block on the frontend.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$id = isset( $attributes['requestId'] ) ? absint( $attributes['requestId'] ) : 0;

		if ( ! $id ) {
			return '';
		}

		return $this->renderer->render( $id );
	}
}
