<?php
/**
 * Registers the `file_request` custom post type.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the `file_request` custom post type (storage only, never exposed as a post UI).
 */
class PostType {

	/**
	 * Post type slug.
	 *
	 * @var string
	 */
	const SLUG = 'file_request';

	/**
	 * Registers the post type.
	 *
	 * @return void
	 */
	public function register() {
		$labels = array(
			'name'          => __( 'File Requests', 'renevo-file-request-manager' ),
			'singular_name' => __( 'File Request', 'renevo-file-request-manager' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
		);

		/**
		 * Filters the arguments used to register the `file_request` post type.
		 *
		 * @param array $args Post type registration arguments.
		 */
		$args = apply_filters( 'renevo_post_type_args', $args );

		register_post_type( self::SLUG, $args );
	}
}
