<?php
/**
 * Tests for FileRequestManager\Core\Activator's cron scheduling.
 *
 * @package FileRequestManager
 */

use FileRequestManager\Core\Activator;

/**
 * Class Test_Activator
 */
class Test_Activator extends WP_UnitTestCase {

	public function tearDown(): void {
		Activator::deactivate();
		parent::tearDown();
	}

	public function test_activate_schedules_both_maintenance_events() {
		Activator::activate();

		$this->assertNotFalse( wp_next_scheduled( 'renevo_daily_maintenance' ) );
		$this->assertNotFalse( wp_next_scheduled( 'renevo_hourly_maintenance' ) );
	}

	public function test_activate_is_idempotent() {
		Activator::activate();
		$first_daily  = wp_next_scheduled( 'renevo_daily_maintenance' );
		$first_hourly = wp_next_scheduled( 'renevo_hourly_maintenance' );

		Activator::activate();

		$this->assertSame( $first_daily, wp_next_scheduled( 'renevo_daily_maintenance' ) );
		$this->assertSame( $first_hourly, wp_next_scheduled( 'renevo_hourly_maintenance' ) );
	}

	public function test_deactivate_unschedules_both_maintenance_events() {
		Activator::activate();

		Activator::deactivate();

		$this->assertFalse( wp_next_scheduled( 'renevo_daily_maintenance' ) );
		$this->assertFalse( wp_next_scheduled( 'renevo_hourly_maintenance' ) );
	}
}
