<?php
/**
 * Local test-database configuration for the WP core PHPUnit test suite.
 *
 * This file is picked up via the WP_PHPUNIT__TESTS_CONFIG environment
 * variable (see tests/php/bootstrap.php) and is only ever used when running
 * `vendor/bin/phpunit`. It intentionally never touches the real site's
 * wp-config.php / database.
 *
 * On a CI machine these values are typically supplied instead via env vars
 * (WP_TESTS_DB_NAME, WP_TESTS_DB_USER, ...); this file provides sane local
 * defaults matching a Local by Flywheel install so `vendor/bin/phpunit` works
 * out of the box on a dev machine too.
 *
 * @package FileRequestManager
 */

// Path to a real WordPress core checkout (wp-settings.php, wp-admin/, wp-includes/).
// wp-phpunit only ships the *test* library, not WordPress core itself, so this
// must point at an actual WP install. Override with WP_TESTS_ABSPATH if needed.
define( 'ABSPATH', getenv( 'WP_TESTS_ABSPATH' ) ? getenv( 'WP_TESTS_ABSPATH' ) : 'C:/Users/teoal/Local Sites/mywp/app/public/' );

define( 'DB_NAME', getenv( 'WP_TESTS_DB_NAME' ) ? getenv( 'WP_TESTS_DB_NAME' ) : 'wordpress_test' );
define( 'DB_USER', getenv( 'WP_TESTS_DB_USER' ) ? getenv( 'WP_TESTS_DB_USER' ) : 'root' );
define( 'DB_PASSWORD', getenv( 'WP_TESTS_DB_PASSWORD' ) ? getenv( 'WP_TESTS_DB_PASSWORD' ) : 'root' );
// Local by Flywheel reassigns each site's MySQL port on every restart of the
// app (Site → Database tab shows the current one), so this default will
// occasionally go stale. Override with WP_TESTS_DB_HOST when it does.
define( 'DB_HOST', getenv( 'WP_TESTS_DB_HOST' ) ? getenv( 'WP_TESTS_DB_HOST' ) : '127.0.0.1:10361' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'File Request Manager Test Suite' );

define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );

define( 'WP_DEBUG', true );
