<?php
/**
 * PHPUnit bootstrap for File Request Manager.
 *
 * Wires up the wp-phpunit/wp-phpunit test library (installed via Composer,
 * see composer.json) together with yoast/phpunit-polyfills, then loads this
 * plugin the same way WordPress would load it in production: on
 * `muplugins_loaded`, before `plugins_loaded` fires. This is the standard
 * pattern documented by wp-phpunit itself.
 *
 * @package FileRequestManager
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName -- $_tests_dir, $_plugin_dir follow the upstream WP core convention this bootstrap is based on.

/**
 * Point wp-phpunit at wp-tests-config.php.
 *
 * The library's own wp-tests-config.php (vendor/wp-phpunit/wp-phpunit/wp-tests-config.php)
 * simply requires whatever file this environment variable points to, so we
 * ship our own alongside this bootstrap rather than editing vendor/.
 */
if ( false === getenv( 'WP_PHPUNIT__TESTS_CONFIG' ) ) {
	putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
}

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php. Did you run `composer install`?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Yoast PHPUnit Polyfills, required by the WP core test library.
require_once dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';

require_once $_tests_dir . '/includes/functions.php';

/**
 * Loads the plugin under test.
 *
 * Hooked to `muplugins_loaded`, which fires while WordPress is still
 * bootstrapping — before `plugins_loaded`, when the plugin itself actually
 * boots (see renevo-file-request-manager.php). This mirrors how a real WP request
 * loads active plugins.
 *
 * @return void
 */
function _renevo_manually_load_plugin() {
	require dirname( __DIR__, 2 ) . '/renevo-file-request-manager.php';
}
tests_add_filter( 'muplugins_loaded', '_renevo_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

// phpcs:enable WordPress.NamingConventions.ValidVariableName
