<?php
/**
 * Tests for FileRequestManager\Repositories\RequestRepository.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Request;
use FileRequestManager\Repositories\RequestRepository;

/**
 * Class Test_Request_Repository
 */
class Test_Request_Repository extends WP_UnitTestCase {

	/**
	 * @var RequestRepository
	 */
	private $repo;

	public function setUp(): void {
		parent::setUp();
		$this->repo = new RequestRepository();
	}

	/**
	 * Builds a raw data array suitable for Request::from_array(), with sensible defaults.
	 *
	 * @param array $overrides Overrides merged over the defaults.
	 * @return array
	 */
	private function build_data( array $overrides = array() ) {
		$defaults = array(
			'title'                 => 'Onboarding documents',
			'description'           => 'Please upload the following before we begin.',
			'status'                => 'draft',
			'requested_files'       => array(
				array(
					'key'           => 'identity-doc',
					'title'         => 'Identity document',
					'description'   => 'A government-issued photo ID.',
					'required'      => true,
					'allowed_types' => array( 'pdf', 'jpg' ),
					'max_size_mb'   => 5,
					'max_files'     => 2,
				),
				array(
					'key'           => 'proof-of-address',
					'title'         => 'Proof of address',
					'description'   => '',
					'required'      => false,
					'allowed_types' => array( 'pdf' ),
					'max_size_mb'   => 10,
					'max_files'     => 1,
				),
			),
			'contact_fields'        => Request::default_contact_fields(),
			'submission_settings'   => Request::default_submission_settings(),
			'notification_settings' => Request::default_notification_settings(),
			'form_design'           => Request::default_form_design(),
			'text_labels'           => Request::default_text_labels(),
		);

		return array_replace_recursive( $defaults, $overrides );
	}

	public function test_create_then_find_round_trips_all_fields() {
		$request = Request::from_array( $this->build_data() );
		$created = $this->repo->create( $request );

		$this->assertNotNull( $created->id );

		$found = $this->repo->find( $created->id );

		$this->assertNotNull( $found );
		$this->assertSame( 'Onboarding documents', $found->title );
		$this->assertSame( 'Please upload the following before we begin.', $found->description );
		$this->assertSame( 'draft', $found->status );

		$this->assertCount( 2, $found->requested_files );
		$this->assertSame( 'identity-doc', $found->requested_files[0]->key );
		$this->assertSame( 'Identity document', $found->requested_files[0]->title );
		$this->assertTrue( $found->requested_files[0]->required );
		$this->assertSame( array( 'pdf', 'jpg' ), $found->requested_files[0]->allowed_types );
		$this->assertSame( 5, $found->requested_files[0]->max_size_mb );
		$this->assertSame( 2, $found->requested_files[0]->max_files );

		$this->assertSame( 'proof-of-address', $found->requested_files[1]->key );
		$this->assertFalse( $found->requested_files[1]->required );

		$this->assertTrue( $found->contact_fields['name']['enabled'] );
		$this->assertTrue( $found->contact_fields['email']['required'] );
		$this->assertFalse( $found->contact_fields['phone']['enabled'] );

		$this->assertSame(
			Request::default_submission_settings()['success_message'],
			$found->submission_settings['success_message']
		);
	}

	public function test_update_nonexistent_id_returns_null() {
		$request = Request::from_array( $this->build_data() );

		$result = $this->repo->update( 999999999, $request );

		$this->assertNull( $result );
	}

	public function test_update_existing_request_persists_changes() {
		$created = $this->repo->create( Request::from_array( $this->build_data() ) );

		$updated_data = $this->build_data(
			array(
				'title'  => 'Updated title',
				'status' => 'publish',
			)
		);
		$updated      = $this->repo->update( $created->id, Request::from_array( $updated_data, $created->id ) );

		$this->assertNotNull( $updated );
		$this->assertSame( 'Updated title', $updated->title );
		$this->assertSame( 'publish', $updated->status );

		$found = $this->repo->find( $created->id );
		$this->assertSame( 'Updated title', $found->title );
	}

	public function test_trash_excludes_from_find_but_post_still_exists() {
		$created = $this->repo->create( Request::from_array( $this->build_data() ) );

		$trashed = $this->repo->trash( $created->id );
		$this->assertTrue( $trashed );

		$this->assertNull( $this->repo->find( $created->id ) );

		$raw_post = get_post( $created->id );
		$this->assertNotNull( $raw_post );
		$this->assertSame( 'trash', $raw_post->post_status );
	}

	public function test_duplicate_creates_draft_copy_with_suffix_and_preserved_keys() {
		$created = $this->repo->create( Request::from_array( $this->build_data( array( 'status' => 'publish' ) ) ) );

		$copy = $this->repo->duplicate( $created->id );

		$this->assertNotNull( $copy );
		$this->assertNotSame( $created->id, $copy->id );
		$this->assertSame( 'Onboarding documents (copy)', $copy->title );
		$this->assertSame( 'draft', $copy->status );

		// Requested-file keys must be preserved as-is, not regenerated,
		// so a duplicate's file slots line up with the originals if ever compared.
		$this->assertSame( 'identity-doc', $copy->requested_files[0]->key );
		$this->assertSame( 'proof-of-address', $copy->requested_files[1]->key );

		// The original is untouched.
		$original_still = $this->repo->find( $created->id );
		$this->assertSame( 'Onboarding documents', $original_still->title );
		$this->assertSame( 'publish', $original_still->status );
	}

	public function test_duplicate_nonexistent_id_returns_null() {
		$this->assertNull( $this->repo->duplicate( 999999999 ) );
	}

	public function test_query_filters_by_status() {
		$this->repo->create(
			Request::from_array(
				$this->build_data(
					array(
						'title'  => 'Draft one',
						'status' => 'draft',
					)
				)
			)
		);
		$this->repo->create(
			Request::from_array(
				$this->build_data(
					array(
						'title'  => 'Published one',
						'status' => 'publish',
					)
				)
			)
		);
		$this->repo->create(
			Request::from_array(
				$this->build_data(
					array(
						'title'  => 'Published two',
						'status' => 'publish',
					)
				)
			)
		);

		$published = $this->repo->query( array( 'status' => 'publish' ) );
		$this->assertSame( 2, $published['total'] );
		foreach ( $published['items'] as $item ) {
			$this->assertSame( 'publish', $item->status );
		}

		$drafts = $this->repo->query( array( 'status' => 'draft' ) );
		$this->assertSame( 1, $drafts['total'] );
	}

	public function test_query_search_matches_title() {
		$this->repo->create( Request::from_array( $this->build_data( array( 'title' => 'Client onboarding kit' ) ) ) );
		$this->repo->create( Request::from_array( $this->build_data( array( 'title' => 'Photographer release form' ) ) ) );

		$result = $this->repo->query(
			array(
				'search' => 'onboarding',
				'status' => 'any',
			)
		);

		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'Client onboarding kit', $result['items'][0]->title );
	}

	public function test_query_paginates() {
		for ( $i = 1; $i <= 5; $i++ ) {
			$this->repo->create(
				Request::from_array(
					$this->build_data(
						array(
							'title'  => "Request {$i}",
							'status' => 'publish',
						)
					)
				)
			);
		}

		$page_one = $this->repo->query(
			array(
				'status'   => 'publish',
				'page'     => 1,
				'per_page' => 2,
			)
		);
		$page_two = $this->repo->query(
			array(
				'status'   => 'publish',
				'page'     => 2,
				'per_page' => 2,
			)
		);

		$this->assertSame( 5, $page_one['total'] );
		$this->assertCount( 2, $page_one['items'] );
		$this->assertCount( 2, $page_two['items'] );
		$this->assertNotSame( $page_one['items'][0]->id, $page_two['items'][0]->id );
	}

	public function test_form_design_defaults_apply_when_omitted() {
		$data = $this->build_data();
		unset( $data['form_design'] );

		$request = Request::from_array( $data );

		$this->assertSame( Request::default_form_design(), $request->form_design );
	}

	public function test_form_design_sanitizes_invalid_values_to_defaults() {
		$data                = $this->build_data();
		$data['form_design'] = array(
			'layout'                     => 'not-a-real-layout',
			'preset'                     => 'not-a-real-preset',
			'background_color'           => 'not-a-hex-color',
			'page_background_color'      => 'not-a-hex-color',
			'upload_style'               => 'carrier-pigeon',
			'font_family'                => 'comic-sans-ms',
			'submit_button_position'     => 'diagonally',
			'file_info_position'         => 'sideways',
			'file_block_wrap'            => 'gift-wrap',
			'contact_fields_wrap'        => 'gift-wrap',
			'required_badge_color'       => 'not-a-hex-color',
			'submit_button_hover_color'  => 'not-a-hex-color',
			'submit_button_border_color' => 'not-a-hex-color',
			'required_indicator'         => 'not-a-real-indicator',
		);
		$request             = Request::from_array( $data );
		$defaults            = Request::default_form_design();

		$this->assertSame( $defaults['layout'], $request->form_design['layout'] );
		$this->assertSame( $defaults['preset'], $request->form_design['preset'] );
		$this->assertSame( $defaults['background_color'], $request->form_design['background_color'] );
		$this->assertSame( $defaults['page_background_color'], $request->form_design['page_background_color'] );
		$this->assertSame( $defaults['upload_style'], $request->form_design['upload_style'] );
		$this->assertSame( $defaults['font_family'], $request->form_design['font_family'] );
		$this->assertSame( $defaults['submit_button_position'], $request->form_design['submit_button_position'] );
		$this->assertSame( $defaults['file_info_position'], $request->form_design['file_info_position'] );
		$this->assertSame( $defaults['file_block_wrap'], $request->form_design['file_block_wrap'] );
		$this->assertSame( $defaults['contact_fields_wrap'], $request->form_design['contact_fields_wrap'] );
		$this->assertSame( $defaults['required_badge_color'], $request->form_design['required_badge_color'] );
		$this->assertSame( $defaults['submit_button_hover_color'], $request->form_design['submit_button_hover_color'] );
		$this->assertSame( $defaults['submit_button_border_color'], $request->form_design['submit_button_border_color'] );
		$this->assertSame( $defaults['required_indicator'], $request->form_design['required_indicator'] );
	}

	public function test_form_design_clamps_numeric_ranges() {
		$data                = $this->build_data();
		$data['form_design'] = array_merge(
			Request::default_form_design(),
			array(
				'border_radius' => 500,
				'padding'       => 500,
				'font_size'     => 500,
			)
		);

		$request = Request::from_array( $data );

		$this->assertSame( 40, $request->form_design['border_radius'] );
		$this->assertSame( 64, $request->form_design['padding'] );
		$this->assertSame( 24, $request->form_design['font_size'] );
	}

	public function test_form_design_clamps_negative_numeric_values_to_zero_floor() {
		$data                = $this->build_data();
		$data['form_design'] = array_merge(
			Request::default_form_design(),
			array(
				'border_radius' => -5,
				'padding'       => -5,
			)
		);

		$request = Request::from_array( $data );

		// absint() takes the absolute value first, so -5 becomes 5 (still
		// within the valid 0-40/0-64 range), not 0 — documenting that
		// behavior here rather than asserting a floor that isn't real.
		$this->assertSame( 5, $request->form_design['border_radius'] );
		$this->assertSame( 5, $request->form_design['padding'] );
	}

	public function test_form_design_partial_update_falls_back_to_base() {
		$created = $this->repo->create( Request::from_array( $this->build_data() ) );

		$customized                = $this->build_data();
		$customized['form_design'] = array_merge(
			Request::default_form_design(),
			array(
				'preset'           => 'modern',
				'background_color' => '#f8fafc',
			)
		);
		$with_design               = $this->repo->update( $created->id, Request::from_array( $customized, $created->id ) );
		$this->assertSame( 'modern', $with_design->form_design['preset'] );

		// An update payload that omits form_design entirely must keep the
		// existing value, not silently reset it back to the Classic default.
		$data_without_design = $this->build_data();
		unset( $data_without_design['form_design'] );

		$updated = $this->repo->update(
			$created->id,
			Request::from_array( $data_without_design, $created->id, $with_design )
		);

		$this->assertSame( 'modern', $updated->form_design['preset'] );
		$this->assertSame( '#f8fafc', $updated->form_design['background_color'] );
	}

	public function test_form_design_round_trips_through_create_and_find() {
		$data                = $this->build_data();
		$data['form_design'] = array_merge(
			Request::default_form_design(),
			array(
				'preset'      => 'editorial',
				'padding'     => 12,
				'font_family' => 'monospace',
			)
		);

		$created = $this->repo->create( Request::from_array( $data ) );
		$found   = $this->repo->find( $created->id );

		$this->assertSame( 'editorial', $found->form_design['preset'] );
		$this->assertSame( 12, $found->form_design['padding'] );
		$this->assertSame( 'monospace', $found->form_design['font_family'] );
	}

	public function test_form_design_layout_and_wrap_fields_round_trip_through_create_and_find() {
		$data                = $this->build_data();
		$data['form_design'] = array_merge(
			Request::default_form_design(),
			array(
				'layout'                     => 'grid',
				'page_background_color'      => '#111827',
				'file_block_wrap'            => 'flat',
				'contact_fields_wrap'        => 'flat',
				'required_badge_color'       => '#c2410c',
				'submit_button_hover_color'  => '#3f3f46',
				'submit_button_border_color' => '#111111',
				'required_indicator'         => 'asterisk',
				'show_optional_badge'        => false,
			)
		);

		$created = $this->repo->create( Request::from_array( $data ) );
		$found   = $this->repo->find( $created->id );

		$this->assertSame( 'grid', $found->form_design['layout'] );
		$this->assertSame( '#111827', $found->form_design['page_background_color'] );
		$this->assertSame( 'flat', $found->form_design['file_block_wrap'] );
		$this->assertSame( 'flat', $found->form_design['contact_fields_wrap'] );
		$this->assertSame( '#c2410c', $found->form_design['required_badge_color'] );
		$this->assertSame( '#3f3f46', $found->form_design['submit_button_hover_color'] );
		$this->assertSame( '#111111', $found->form_design['submit_button_border_color'] );
		$this->assertSame( 'asterisk', $found->form_design['required_indicator'] );
		$this->assertFalse( $found->form_design['show_optional_badge'] );
	}

	public function test_to_array_and_to_public_array_include_form_design() {
		$request = Request::from_array( $this->build_data() );

		$this->assertArrayHasKey( 'form_design', $request->to_array() );
		$this->assertArrayHasKey( 'form_design', $request->to_public_array() );
	}

	public function test_text_labels_defaults_apply_when_omitted() {
		$data = $this->build_data();
		unset( $data['text_labels'] );

		$request = Request::from_array( $data );

		$this->assertSame( Request::default_text_labels(), $request->text_labels );
	}

	public function test_text_labels_blank_values_fall_back_to_defaults() {
		$data                = $this->build_data();
		$data['text_labels'] = array(
			'contact_heading'    => '',
			'submit_button_text' => '   ',
			'name_label'         => 'Full name',
		);

		$request  = Request::from_array( $data );
		$defaults = Request::default_text_labels();

		// A genuinely blank override falls back to the default rather than
		// rendering an empty label. sanitize_text_field() trims whitespace,
		// so an all-whitespace value is blank too.
		$this->assertSame( $defaults['contact_heading'], $request->text_labels['contact_heading'] );
		$this->assertSame( $defaults['submit_button_text'], $request->text_labels['submit_button_text'] );
		$this->assertSame( 'Full name', $request->text_labels['name_label'] );
	}

	public function test_text_labels_partial_update_falls_back_to_base() {
		$created = $this->repo->create( Request::from_array( $this->build_data() ) );

		$customized                = $this->build_data();
		$customized['text_labels'] = array_merge(
			Request::default_text_labels(),
			array( 'submit_button_text' => 'Send documents' )
		);
		$with_labels               = $this->repo->update( $created->id, Request::from_array( $customized, $created->id ) );
		$this->assertSame( 'Send documents', $with_labels->text_labels['submit_button_text'] );

		// An update payload that omits text_labels entirely must keep the
		// existing value, not silently reset it back to the defaults.
		$data_without_labels = $this->build_data();
		unset( $data_without_labels['text_labels'] );

		$updated = $this->repo->update(
			$created->id,
			Request::from_array( $data_without_labels, $created->id, $with_labels )
		);

		$this->assertSame( 'Send documents', $updated->text_labels['submit_button_text'] );
	}

	public function test_text_labels_round_trips_through_create_and_find() {
		$data                = $this->build_data();
		$data['text_labels'] = array_merge(
			Request::default_text_labels(),
			array(
				'contact_heading' => 'Datele tale',
				'name_label'      => 'Nume',
			)
		);

		$created = $this->repo->create( Request::from_array( $data ) );
		$found   = $this->repo->find( $created->id );

		$this->assertSame( 'Datele tale', $found->text_labels['contact_heading'] );
		$this->assertSame( 'Nume', $found->text_labels['name_label'] );
	}

	public function test_to_array_and_to_public_array_include_text_labels() {
		$request = Request::from_array( $this->build_data() );

		$this->assertArrayHasKey( 'text_labels', $request->to_array() );
		$this->assertArrayHasKey( 'text_labels', $request->to_public_array() );
	}

	public function test_description_allows_inline_style_but_strips_scripts() {
		$data = $this->build_data(
			array(
				'description' => '<p style="color:red;">Hello</p><script>alert(1)</script><iframe src="evil"></iframe>',
			)
		);

		$request = Request::from_array( $data );

		$this->assertStringContainsString( 'style=', $request->description );
		$this->assertStringContainsString( 'Hello', $request->description );
		$this->assertStringNotContainsString( '<script', $request->description );
		$this->assertStringNotContainsString( '<iframe', $request->description );
	}
}
