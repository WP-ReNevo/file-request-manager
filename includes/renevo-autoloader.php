<?php
/**
 * Fallback PSR-4 autoloader, used only when vendor/autoload.php hasn't been generated yet.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

spl_autoload_register(
	static function ( $class_name ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = RENEVO_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $path ) ) {
			require $path;
		}
	}
);
