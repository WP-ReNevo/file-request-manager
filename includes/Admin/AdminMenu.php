<?php
/**
 * Registers the "File Requests" admin menu.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

use FileRequestManager\Core\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "File Requests" admin menu: Requests (a merged
 * dashboard-plus-list landing page), Submissions, Settings.
 */
class AdminMenu {

	const SLUG_DASHBOARD   = 'renevo';
	const SLUG_REQUESTS    = self::SLUG_DASHBOARD; // The Requests list is the merged landing page.
	const SLUG_EDITOR      = 'renevo-editor';
	const SLUG_SUBMISSIONS = 'renevo-submissions';
	const SLUG_SETTINGS    = 'renevo-settings';

	/**
	 * Registers the menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		$capability = Capabilities::capability();

		add_menu_page(
			__( 'File Requests', 'renevo-file-request-manager' ),
			__( 'File Requests', 'renevo-file-request-manager' ),
			$capability,
			self::SLUG_DASHBOARD,
			array( new RequestsPage(), 'render' ),
			'dashicons-media-document',
			26
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'All Requests', 'renevo-file-request-manager' ),
			__( 'All Requests', 'renevo-file-request-manager' ),
			$capability,
			self::SLUG_DASHBOARD,
			'' // Same slug as parent; add_menu_page() already hooked the callback, avoid double render.
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Submissions', 'renevo-file-request-manager' ),
			__( 'Submissions', 'renevo-file-request-manager' ),
			$capability,
			self::SLUG_SUBMISSIONS,
			array( new SubmissionsPage(), 'render' )
		);

		add_submenu_page(
			self::SLUG_DASHBOARD,
			__( 'Settings', 'renevo-file-request-manager' ),
			__( 'Settings', 'renevo-file-request-manager' ),
			$capability,
			self::SLUG_SETTINGS,
			array( new SettingsPage(), 'render' )
		);

		add_submenu_page(
			null, // phpcs:ignore WordPress.WP.CapitalPDangit.MisspelledClassName
			__( 'File Request', 'renevo-file-request-manager' ),
			__( 'File Request', 'renevo-file-request-manager' ),
			$capability,
			self::SLUG_EDITOR,
			array( new EditorPage(), 'render' )
		);
	}

	/**
	 * Adds a "Settings" link to the plugin row on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function add_plugin_action_links( $links ) {
		if ( ! current_user_can( Capabilities::capability() ) ) {
			return $links;
		}

		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=' . self::SLUG_SETTINGS ) ),
			esc_html__( 'Settings', 'renevo-file-request-manager' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
