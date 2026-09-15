<?php
/**
 * Tests for FileRequestManager\Repositories\SubmissionRepository.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Submission;
use FileRequestManager\Domain\SubmissionFile;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;

/**
 * Class Test_Submission_Repository
 *
 * Submissions live in custom tables (not wp_posts), so — unlike CPT-backed
 * data — rows inserted here are not guaranteed to be rolled back by
 * WP_UnitTestCase's per-test transaction the same way core tables are.
 * Every row created is tracked and deleted in tearDown() as a safety net.
 */
class Test_Submission_Repository extends WP_UnitTestCase {

	/**
	 * @var SubmissionRepository
	 */
	private $repo;

	/**
	 * @var int[]
	 */
	private $created_ids = array();

	public function setUp(): void {
		parent::setUp();
		$this->repo        = new SubmissionRepository( new FileStorageService() );
		$this->created_ids = array();
	}

	public function tearDown(): void {
		foreach ( $this->created_ids as $id ) {
			$this->repo->delete( $id );
		}
		parent::tearDown();
	}

	/**
	 * Creates and tracks a submission for a given request/email.
	 *
	 * @param int    $request_id Request ID.
	 * @param string $email      Email address.
	 * @return Submission
	 */
	private function make_submission( $request_id, $email = 'client@example.com', $status = Submission::STATUS_INCOMPLETE ) {
		$submission             = new Submission();
		$submission->request_id = $request_id;
		$submission->name       = 'Jane Client';
		$submission->email      = $email;

		$created             = $this->repo->create( $submission );
		$this->created_ids[] = $created->id;

		if ( Submission::STATUS_INCOMPLETE !== $status ) {
			$this->repo->update_status( $created->id, $status );
			$created = $this->repo->find( $created->id );
		}

		return $created;
	}

	public function test_create_assigns_code_derived_from_own_id() {
		$submission = $this->make_submission( 1 );

		$this->assertSame( 'REQ-' . ( 1000 + $submission->id ), $submission->submission_code );
	}

	public function test_find_includes_attached_files() {
		$submission = $this->make_submission( 1 );

		$file                     = new SubmissionFile();
		$file->requested_file_key = 'identity-doc';
		$file->original_filename  = 'passport.pdf';
		$file->stored_filename    = 'abc123.pdf';
		$file->file_size          = 1024;
		$file->mime_type          = 'application/pdf';

		$this->repo->add_file( $submission->id, $file );

		$found = $this->repo->find( $submission->id );

		$this->assertCount( 1, $found->files );
		$this->assertSame( 'passport.pdf', $found->files[0]->original_filename );
		$this->assertSame( 'identity-doc', $found->files[0]->requested_file_key );
	}

	public function test_get_file_returns_null_for_wrong_submission() {
		$submission_a = $this->make_submission( 1, 'a@example.com' );
		$submission_b = $this->make_submission( 1, 'b@example.com' );

		$file                     = new SubmissionFile();
		$file->requested_file_key = 'identity-doc';
		$file->original_filename  = 'a-file.pdf';
		$file->stored_filename    = 'stored-a.pdf';
		$file->file_size          = 100;
		$file->mime_type          = 'application/pdf';

		$file_a = $this->repo->add_file( $submission_a->id, $file );

		// The file belongs to submission A. Requesting it scoped to submission B
		// must return null even though the file row ID is otherwise correct —
		// this is the boundary that keeps one requester from downloading
		// another requester's file by guessing/incrementing IDs.
		$this->assertNull( $this->repo->get_file( $submission_b->id, $file_a->id ) );

		$found_correctly = $this->repo->get_file( $submission_a->id, $file_a->id );
		$this->assertNotNull( $found_correctly );
		$this->assertSame( 'a-file.pdf', $found_correctly->original_filename );
	}

	public function test_get_file_returns_null_for_nonexistent_file() {
		$submission = $this->make_submission( 1 );

		$this->assertNull( $this->repo->get_file( $submission->id, 999999 ) );
	}

	public function test_query_filters_by_request_id() {
		$this->make_submission( 10 );
		$this->make_submission( 10 );
		$this->make_submission( 20 );

		$result = $this->repo->query( array( 'request_id' => 10 ) );

		$this->assertSame( 2, $result['total'] );
		foreach ( $result['items'] as $item ) {
			$this->assertSame( 10, $item->request_id );
		}
	}

	public function test_query_filters_by_status() {
		$this->make_submission( 30, 'x@example.com', Submission::STATUS_COMPLETE );
		$this->make_submission( 30, 'y@example.com', Submission::STATUS_INCOMPLETE );

		$result = $this->repo->query(
			array(
				'request_id' => 30,
				'status'     => Submission::STATUS_COMPLETE,
			)
		);

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( Submission::STATUS_COMPLETE, $result['items'][0]->status );
	}

	public function test_query_search_matches_name_email_or_code() {
		$submission = $this->make_submission( 40, 'findme@example.com' );
		$this->make_submission( 40, 'other@example.com' );

		$by_email = $this->repo->query(
			array(
				'request_id' => 40,
				'search'     => 'findme',
			)
		);
		$this->assertSame( 1, $by_email['total'] );

		$by_code = $this->repo->query(
			array(
				'request_id' => 40,
				'search'     => $submission->submission_code,
			)
		);
		$this->assertSame( 1, $by_code['total'] );
		$this->assertSame( $submission->id, $by_code['items'][0]->id );
	}

	public function test_update_status_rejects_invalid_status() {
		$submission = $this->make_submission( 1 );

		$result = $this->repo->update_status( $submission->id, 'not-a-real-status' );

		$this->assertFalse( $result );

		$unchanged = $this->repo->find( $submission->id );
		$this->assertSame( Submission::STATUS_INCOMPLETE, $unchanged->status );
	}

	public function test_delete_removes_submission_and_its_files() {
		$submission = $this->make_submission( 1 );

		$file                     = new SubmissionFile();
		$file->requested_file_key = 'identity-doc';
		$file->original_filename  = 'x.pdf';
		$file->stored_filename    = 'stored-x.pdf';
		$file->file_size          = 10;
		$file->mime_type          = 'application/pdf';
		$this->repo->add_file( $submission->id, $file );

		$this->repo->delete( $submission->id );

		$this->assertNull( $this->repo->find( $submission->id ) );

		// Already deleted — drop it from the tearDown cleanup list.
		$this->created_ids = array_diff( $this->created_ids, array( $submission->id ) );
	}
}
