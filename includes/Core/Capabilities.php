<?php
/**
 * Central capability check used by every admin-facing REST route and screen.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central, filterable capability gate for every admin-facing route and screen.
 */
class Capabilities {

	/**
	 * Gets the capability required to manage requests and submissions.
	 *
	 * @return string
	 */
	public static function capability() {
		/**
		 * Filters the capability required to manage ReNevo.
		 *
		 * @param string $capability Capability slug. Default 'manage_options'.
		 */
		return (string) apply_filters( 'renevo_manage_capability', 'manage_options' );
	}

	/**
	 * Whether the current user may manage requests and submissions.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::capability() );
	}
}
