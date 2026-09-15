<?php
/**
 * Tests for FileRequestManager\Services\FormLayoutRegistry.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Request;
use FileRequestManager\Services\FormLayoutRegistry;

/**
 * Class Test_Form_Layout_Registry
 */
class Test_Form_Layout_Registry extends WP_UnitTestCase {

	public function test_all_includes_the_built_in_layouts() {
		$keys = array_keys( FormLayoutRegistry::all() );

		$this->assertSame(
			array( 'stacked', 'grid', 'editorial' ),
			$keys
		);
	}

	public function test_is_valid_key() {
		$this->assertTrue( FormLayoutRegistry::is_valid_key( 'editorial' ) );
		$this->assertFalse( FormLayoutRegistry::is_valid_key( 'does-not-exist' ) );
	}

	public function test_renevo_form_layouts_filter_can_add_a_layout() {
		add_filter( 'renevo_form_layouts', array( $this, 'add_test_layout' ) );

		$layouts = FormLayoutRegistry::all();
		$valid   = FormLayoutRegistry::is_valid_key( 'pro-test' );

		remove_filter( 'renevo_form_layouts', array( $this, 'add_test_layout' ) );

		$this->assertArrayHasKey( 'pro-test', $layouts );
		$this->assertTrue( $valid );
		$this->assertFalse( FormLayoutRegistry::is_valid_key( 'pro-test' ) );
	}

	/**
	 * Filter callback: adds a fake Pro-style layout.
	 *
	 * @param array $layouts Layouts keyed by layout key.
	 * @return array
	 */
	public function add_test_layout( array $layouts ) {
		$layouts['pro-test'] = array(
			'label'       => 'Pro Test',
			'description' => 'A layout added externally via the filter.',
		);

		return $layouts;
	}

	public function test_request_sanitizes_invalid_layout_to_default() {
		$request = Request::from_array(
			array(
				'title'       => 'Test',
				'form_design' => array_merge(
					Request::default_form_design(),
					array( 'layout' => 'does-not-exist' )
				),
			)
		);

		$this->assertSame( 'stacked', $request->to_array()['form_design']['layout'] );
	}

	public function test_request_keeps_a_valid_layout() {
		$request = Request::from_array(
			array(
				'title'       => 'Test',
				'form_design' => array_merge(
					Request::default_form_design(),
					array( 'layout' => 'editorial' )
				),
			)
		);

		$this->assertSame( 'editorial', $request->to_array()['form_design']['layout'] );
	}
}
