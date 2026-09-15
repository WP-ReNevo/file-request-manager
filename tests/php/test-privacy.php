<?php
/**
 * Tests for FileRequestManager\Privacy\Privacy (WP Privacy exporter/eraser).
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Submission;
use FileRequestManager\Privacy\Privacy;
use FileRequestManager\Repositories\SubmissionRepository;
use FileRequestManager\Services\FileStorageService;

/**
 * Class Test_Privacy
 */
class Test_Privacy extends WP_UnitTestCase {

	/**
	 * @var SubmissionRepository
	 */
	private $submissions;

	/**
	 * @var Privacy
	 */
	private $privacy;

	/**
	 * @var int[]
	 */
	private $created_ids = array();

	public function setUp(): void {
		parent::setUp();
		$storage           = new FileStorageService();
		$this->submissions = new SubmissionRepository( $storage );
		$this->privacy     = new Privacy( $this->submissions, $storage );
		$this->created_ids = array();
	}

	public function tearDown(): void {
		foreach ( $this->created_ids as $id ) {
			$this->submissions->delete( $id );
		}
		parent::tearDown();
	}

	private function make_submission( $email ) {
		$submission             = new Submission();
		$submission->request_id = 1;
		$submission->name       = 'Jane Client';
		$submission->email      = $email;
		$submission->phone      = '555-0100';

		$created             = $this->submissions->create( $submission );
		$this->created_ids[] = $created->id;

		return $created;
	}

	public function test_export_returns_data_for_matching_email() {
		$submission = $this->make_submission( 'match@example.com' );

		$result = $this->privacy->export( 'match@example.com' );

		$this->assertTrue( $result['done'] );
		$this->assertCount( 1, $result['data'] );
		$this->assertSame( 'renevo-submission-' . $submission->id, $result['data'][0]['item_id'] );

		$fields = array();
		foreach ( $result['data'][0]['data'] as $field ) {
			$fields[ $field['name'] ] = $field['value'];
		}
		$this->assertSame( 'match@example.com', $fields['Email'] );
	}

	public function test_export_returns_nothing_for_non_matching_email() {
		$this->make_submission( 'someone@example.com' );

		$result = $this->privacy->export( 'nobody-else@example.com' );

		$this->assertTrue( $result['done'] );
		$this->assertSame( array(), $result['data'] );
	}

	public function test_erase_removes_matching_submissions() {
		$submission = $this->make_submission( 'erase-me@example.com' );

		$result = $this->privacy->erase( 'erase-me@example.com' );

		$this->assertTrue( $result['items_removed'] );
		$this->assertFalse( $result['items_retained'] );
		$this->assertNull( $this->submissions->find( $submission->id ) );

		// Already erased — remove from the tearDown cleanup list.
		$this->created_ids = array_diff( $this->created_ids, array( $submission->id ) );
	}

	public function test_erase_reports_nothing_removed_for_non_matching_email() {
		$this->make_submission( 'keep-me@example.com' );

		$result = $this->privacy->erase( 'unrelated@example.com' );

		$this->assertFalse( $result['items_removed'] );
	}
}
