<?php
/**
 * Mount point for the admin Settings React app.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mount point for the admin Settings React app (GET/PUT /settings, see
 * Rest\SettingsController).
 */
class SettingsPage {

	/**
	 * Renders the page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Capabilities::current_user_can_manage() ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'renevo-file-request-manager' ) );
		}
		?>
		<div class="wrap frm-settings-page">
			<?php include RENEVO_PATH . 'includes/templates/admin/brand-header.php'; ?>
			<div id="frm-settings-app-root"></div>
		</div>
		<?php
	}
}
