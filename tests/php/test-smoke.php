<?php
/**
 * Smoke test: confirms the bootstrap loaded WordPress and the plugin.
 *
 * @package FileRequestManager
 */

class Test_Smoke extends WP_UnitTestCase {

	public function test_plugin_is_loaded() {
		$this->assertTrue( class_exists( \FileRequestManager\Plugin::class ) );
		$this->assertTrue( did_action( 'renevo_loaded' ) > 0 );
	}
}
