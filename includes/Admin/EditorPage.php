<?php
/**
 * Mount point for the "Create/Edit File Request" React app.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Mount point for the "Create/Edit File Request" React app.
 */
class EditorPage {

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
		<div class="wrap frm-editor-page">
			<?php include RENEVO_PATH . 'includes/templates/admin/brand-header.php'; ?>
			<div id="frm-editor-app-root"></div>
		</div>
		<?php
	}
}
