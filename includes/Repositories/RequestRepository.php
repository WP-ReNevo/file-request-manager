<?php
/**
 * Persistence for File Requests (CPT + postmeta).
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Repositories;

use FileRequestManager\Core\PostType;
use FileRequestManager\Domain\Request;
use FileRequestManager\Domain\RequestedFile;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistence for File Requests (CPT + postmeta).
 */
class RequestRepository {

	const META_REQUESTED_FILES       = '_renevo_requested_files';
	const META_CONTACT_FIELDS        = '_renevo_contact_fields';
	const META_SUBMISSION_SETTINGS   = '_renevo_submission_settings';
	const META_NOTIFICATION_SETTINGS = '_renevo_notification_settings';
	const META_FORM_DESIGN           = '_renevo_form_design';
	const META_TEXT_LABELS           = '_renevo_text_labels';
	const META_SCHEMA_VERSION        = '_renevo_schema_version';

	/**
	 * Finds a request by ID.
	 *
	 * @param int $id Post ID.
	 * @return Request|null
	 */
	public function find( $id ) {
		$post = get_post( $id );

		if ( ! $post || PostType::SLUG !== $post->post_type || 'trash' === $post->post_status ) {
			return null;
		}

		return $this->hydrate( $post );
	}

	/**
	 * Finds a published request by ID (used by the public-facing REST routes).
	 *
	 * @param int $id Post ID.
	 * @return Request|null
	 */
	public function find_published( $id ) {
		$request = $this->find( $id );

		if ( ! $request || 'publish' !== $request->status ) {
			return null;
		}

		return $request;
	}

	/**
	 * Creates a new request.
	 *
	 * @param Request $request Sanitized request object (id is ignored).
	 * @return Request
	 */
	public function create( Request $request ) {
		$post_id = wp_insert_post(
			array(
				'post_type'    => PostType::SLUG,
				'post_title'   => $request->title,
				'post_content' => $request->description,
				'post_status'  => $request->status,
			),
			true
		);

		$request->id = (int) $post_id;

		$this->save_meta( $request );

		return $this->find( $request->id );
	}

	/**
	 * Updates an existing request.
	 *
	 * @param int     $id      Post ID to update.
	 * @param Request $request Sanitized request object.
	 * @return Request|null
	 */
	public function update( $id, Request $request ) {
		$id = absint( $id );

		if ( ! $this->find( $id ) ) {
			return null;
		}

		wp_update_post(
			array(
				'ID'           => $id,
				'post_title'   => $request->title,
				'post_content' => $request->description,
				'post_status'  => $request->status,
			)
		);

		$request->id = $id;
		$this->save_meta( $request );

		return $this->find( $id );
	}

	/**
	 * Moves a request to trash.
	 *
	 * @param int $id Post ID.
	 * @return bool
	 */
	public function trash( $id ) {
		return (bool) wp_trash_post( $id );
	}

	/**
	 * Permanently deletes a request and its meta.
	 *
	 * @param int $id Post ID.
	 * @return bool
	 */
	public function delete( $id ) {
		return (bool) wp_delete_post( $id, true );
	}

	/**
	 * Duplicates a request (title suffixed, always created as a draft).
	 *
	 * @param int $id Post ID to duplicate.
	 * @return Request|null
	 */
	public function duplicate( $id ) {
		$source = $this->find( $id );

		if ( ! $source ) {
			return null;
		}

		$copy     = clone $source;
		$copy->id = null;
		/* translators: %s: original request title. */
		$copy->title  = sprintf( __( '%s (copy)', 'renevo-file-request-manager' ), $source->title );
		$copy->status = 'draft';

		return $this->create( $copy );
	}

	/**
	 * Queries requests for the admin list screen.
	 *
	 * @param array $args {
	 *     @type string $status   Post status filter, or 'any'.
	 *     @type string $search   Search term.
	 *     @type int    $page     1-indexed page number.
	 *     @type int    $per_page Results per page.
	 *     @type string $orderby  WP_Query orderby.
	 *     @type string $order    ASC|DESC.
	 * }
	 * @return array{items: Request[], total: int}
	 */
	public function query( array $args ) {
		$defaults = array(
			'status'   => 'any',
			'search'   => '',
			'page'     => 1,
			'per_page' => 20,
			'orderby'  => 'date',
			'order'    => 'DESC',
		);
		$args     = wp_parse_args( $args, $defaults );

		$query_args = array(
			'post_type'      => PostType::SLUG,
			'post_status'    => 'any' === $args['status'] ? array( 'draft', 'publish' ) : $args['status'],
			's'              => $args['search'],
			'paged'          => max( 1, (int) $args['page'] ),
			'posts_per_page' => max( 1, (int) $args['per_page'] ),
			'orderby'        => $args['orderby'],
			'order'          => $args['order'],
			'no_found_rows'  => false,
		);

		$query = new WP_Query( $query_args );

		return array(
			'items' => array_map( array( $this, 'hydrate' ), $query->posts ),
			'total' => (int) $query->found_posts,
		);
	}

	/**
	 * Counts requests per post status, for the dashboard.
	 *
	 * @return array<string, int>
	 */
	public function counts_by_status() {
		$counts = wp_count_posts( PostType::SLUG );

		return array(
			'draft'   => isset( $counts->draft ) ? (int) $counts->draft : 0,
			'publish' => isset( $counts->publish ) ? (int) $counts->publish : 0,
		);
	}

	/**
	 * Hydrates a Request domain object from a WP_Post.
	 *
	 * @param \WP_Post $post Post object.
	 * @return Request
	 */
	private function hydrate( $post ) {
		$data = array(
			'title'                 => $post->post_title,
			'description'           => $post->post_content,
			'status'                => $post->post_status,
			'requested_files'       => $this->get_meta_array( $post->ID, self::META_REQUESTED_FILES ),
			'contact_fields'        => $this->get_meta_array( $post->ID, self::META_CONTACT_FIELDS, Request::default_contact_fields() ),
			'submission_settings'   => $this->get_meta_array( $post->ID, self::META_SUBMISSION_SETTINGS, Request::default_submission_settings() ),
			'notification_settings' => $this->get_meta_array( $post->ID, self::META_NOTIFICATION_SETTINGS, Request::default_notification_settings() ),
			'form_design'           => $this->get_meta_array( $post->ID, self::META_FORM_DESIGN, Request::default_form_design() ),
			'text_labels'           => $this->get_meta_array( $post->ID, self::META_TEXT_LABELS, Request::default_text_labels() ),
		);

		$request             = Request::from_array( $data, $post->ID );
		$request->created_at = $post->post_date_gmt;
		$request->updated_at = $post->post_modified_gmt;

		return $request;
	}

	/**
	 * Reads and unserializes a postmeta array, falling back to a default when absent.
	 *
	 * @param int    $post_id       Post ID.
	 * @param string $meta_key      Meta key.
	 * @param array  $default_value Fallback value.
	 * @return array
	 */
	private function get_meta_array( $post_id, $meta_key, array $default_value = array() ) {
		$value = get_post_meta( $post_id, $meta_key, true );

		return is_array( $value ) ? $value : $default_value;
	}

	/**
	 * Writes every structured meta blob for a request.
	 *
	 * @param Request $request Request to persist.
	 * @return void
	 */
	private function save_meta( Request $request ) {
		update_post_meta(
			$request->id,
			self::META_REQUESTED_FILES,
			array_map(
				static function ( RequestedFile $file ) {
					return $file->to_array();
				},
				$request->requested_files
			)
		);
		update_post_meta( $request->id, self::META_CONTACT_FIELDS, $request->contact_fields );
		update_post_meta( $request->id, self::META_SUBMISSION_SETTINGS, $request->submission_settings );
		update_post_meta( $request->id, self::META_NOTIFICATION_SETTINGS, $request->notification_settings );
		update_post_meta( $request->id, self::META_FORM_DESIGN, $request->form_design );
		update_post_meta( $request->id, self::META_TEXT_LABELS, $request->text_labels );
		update_post_meta( $request->id, self::META_SCHEMA_VERSION, $request->schema_version );
	}
}
