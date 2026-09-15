<?php
/**
 * Tests for FileRequestManager\Services\FormDesignRegistry.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Domain\Request;
use FileRequestManager\Services\FormDesignRegistry;
use FileRequestManager\Services\FormLayoutRegistry;

/**
 * Class Test_Form_Design_Registry
 */
class Test_Form_Design_Registry extends WP_UnitTestCase {

	public function test_all_includes_the_built_in_templates() {
		$keys = array_keys( FormDesignRegistry::all() );

		$this->assertSame(
			array( 'classic', 'modern', 'editorial' ),
			$keys
		);
	}

	public function test_every_template_names_a_valid_layout() {
		foreach ( FormDesignRegistry::all() as $key => $template ) {
			$this->assertTrue(
				FormLayoutRegistry::is_valid_key( $template['layout'] ),
				"Template '{$key}' names an unknown layout."
			);
		}
	}

	public function test_every_template_defaults_round_trip_through_sanitization() {
		$defaults = Request::default_form_design();

		foreach ( FormDesignRegistry::all() as $key => $template ) {
			$request = Request::from_array(
				array(
					'title'       => 'Test',
					'form_design' => array_merge( $defaults, $template['defaults'], array( 'preset' => $key ) ),
				)
			);

			foreach ( $template['defaults'] as $field => $value ) {
				$this->assertSame(
					$value,
					$request->form_design[ $field ],
					"Template '{$key}' field '{$field}' did not round-trip."
				);
			}
		}
	}

	public function test_classic_pairs_with_the_stacked_layout() {
		$this->assertSame( 'stacked', FormDesignRegistry::all()['classic']['layout'] );
	}

	public function test_is_valid_key() {
		$this->assertTrue( FormDesignRegistry::is_valid_key( 'modern' ) );
		$this->assertFalse( FormDesignRegistry::is_valid_key( 'does-not-exist' ) );
	}

	public function test_renevo_form_design_presets_filter_can_add_a_template() {
		add_filter( 'renevo_form_design_presets', array( $this, 'add_test_template' ) );

		$templates = FormDesignRegistry::all();
		$valid     = FormDesignRegistry::is_valid_key( 'pro-test' );

		remove_filter( 'renevo_form_design_presets', array( $this, 'add_test_template' ) );

		$this->assertArrayHasKey( 'pro-test', $templates );
		$this->assertTrue( $valid );
		$this->assertFalse( FormDesignRegistry::is_valid_key( 'pro-test' ) );
	}

	/**
	 * Filter callback: adds a fake Pro-style template.
	 *
	 * @param array $templates Templates keyed by template key.
	 * @return array
	 */
	public function add_test_template( array $templates ) {
		$templates['pro-test'] = array(
			'label'       => 'Pro Test',
			'description' => 'A template added externally via the filter.',
			'layout'      => 'stacked',
			'defaults'    => array(),
		);

		return $templates;
	}
}
