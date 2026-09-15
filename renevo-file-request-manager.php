<?php
/**
 * Plugin Name:       ReNevo File Request Manager
 * Description:       Request specific files from clients, customers and visitors. Tell them exactly what you need, let them upload it, and receive everything in one place.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            ReNevo
 * Author URI:        https://profiles.wordpress.org/renevo/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       renevo-file-request-manager
 * Domain Path:       /languages
 *
 * @package FileRequestManager
 */

namespace FileRequestManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RENEVO_VERSION', '1.0.0' );
define( 'RENEVO_DB_VERSION', '1.0.0' );
define( 'RENEVO_FILE', __FILE__ );
define( 'RENEVO_PATH', plugin_dir_path( __FILE__ ) );
define( 'RENEVO_URL', plugin_dir_url( __FILE__ ) );
define( 'RENEVO_BASENAME', plugin_basename( __FILE__ ) );

$renevo_autoloader = RENEVO_PATH . 'vendor/autoload.php';

if ( file_exists( $renevo_autoloader ) ) {
	require_once $renevo_autoloader;
} else {
	require_once RENEVO_PATH . 'includes/renevo-autoloader.php';
}

register_activation_hook( __FILE__, array( Core\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Core\Activator::class, 'deactivate' ) );

/**
 * Boots the plugin.
 *
 * @return void
 */
function renevo_run() {
	Plugin::instance()->boot();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\renevo_run' );
