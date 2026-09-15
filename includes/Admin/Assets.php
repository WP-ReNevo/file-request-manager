<?php
/**
 * Conditional asset registration/enqueue.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditional asset registration/enqueue.
 */
class Assets {

	/**
	 * Registers (does not enqueue) the frontend bundle.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		$asset = $this->read_asset_file( 'frontend' );

		wp_register_script(
			'renevo-frontend',
			RENEVO_URL . 'build/frontend.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		wp_register_style( 'renevo-frontend', RENEVO_URL . 'build/frontend.css', array(), $asset['version'] );

		wp_set_script_translations( 'renevo-frontend', 'renevo-file-request-manager', RENEVO_PATH . 'languages' );
	}

	/**
	 * Enqueues the admin editor React app, only on the ReNevo editor screen.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 0 !== strpos( $page, 'renevo' ) ) {
			return;
		}

		$plain_asset = $this->read_asset_file( 'admin' );
		wp_enqueue_style( 'renevo-admin', RENEVO_URL . 'build/admin.css', array(), $plain_asset['version'] );

		if ( AdminMenu::SLUG_SETTINGS === $page ) {
			$settings_asset = $this->read_asset_file( 'settings-app' );

			wp_enqueue_script( 'renevo-settings-app', RENEVO_URL . 'build/settings-app.js', $settings_asset['dependencies'], $settings_asset['version'], true );
			wp_enqueue_style( 'renevo-settings-app', RENEVO_URL . 'build/settings-app.css', array( 'wp-components' ), $settings_asset['version'] );
			wp_set_script_translations( 'renevo-settings-app', 'renevo-file-request-manager', RENEVO_PATH . 'languages' );

			return;
		}

		if ( AdminMenu::SLUG_EDITOR !== $page ) {
			return;
		}

		// The "Request details" tab's description field uses the classic
		// TinyMCE/quicktags editor (wp.editor.initialize()), so its scripts
		// need to be enqueued here.
		wp_enqueue_editor();

		$asset        = $this->read_asset_file( 'editor-app' );
		$dependencies = array_unique( array_merge( $asset['dependencies'], array( 'editor' ) ) );

		wp_enqueue_script( 'renevo-editor-app', RENEVO_URL . 'build/editor-app.js', $dependencies, $asset['version'], true );
		wp_enqueue_style( 'renevo-editor-app', RENEVO_URL . 'build/editor-app.css', array( 'wp-components' ), $asset['version'] );
		wp_set_script_translations( 'renevo-editor-app', 'renevo-file-request-manager', RENEVO_PATH . 'languages' );

		wp_localize_script(
			'renevo-editor-app',
			'renevoEditorApp',
			array(
				'adminUrl'    => admin_url( 'admin.php' ),
				'requestsUrl' => admin_url( 'admin.php?page=' . AdminMenu::SLUG_REQUESTS ),
				'requestId'   => isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'adminEmail'  => get_option( 'admin_email' ),
				'siteName'    => get_bloginfo( 'name' ),
				'faviconUrl'  => get_site_icon_url( 32 ),
			)
		);
	}

	/**
	 * Localizes data onto the block's editor script (handle derived by WP core from the block name).
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$handle = 'renevo-file-request-editor-script';

		if ( ! wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_localize_script(
			$handle,
			'renevoBlockEditor',
			array(
				'adminUrl' => admin_url( 'admin.php' ),
			)
		);
	}

	/**
	 * Reads a wp-scripts-generated `.asset.php` file for a given bundle.
	 *
	 * @param string $handle Bundle name, matching the webpack entry (e.g. "frontend").
	 * @return array{dependencies: string[], version: string}
	 */
	private function read_asset_file( $handle ) {
		$path = RENEVO_PATH . 'build/' . $handle . '.asset.php';

		if ( file_exists( $path ) ) {
			return include $path;
		}

		return array(
			'dependencies' => array( 'wp-element', 'wp-i18n', 'wp-api-fetch' ),
			'version'      => RENEVO_VERSION,
		);
	}
}
