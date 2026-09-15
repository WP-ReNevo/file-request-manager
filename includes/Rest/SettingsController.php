<?php
/**
 * Admin REST controller for the plugin-wide Settings screen.
 *
 * @package FileRequestManager
 */

namespace FileRequestManager\Rest;

use FileRequestManager\Core\Capabilities;
use FileRequestManager\Services\AllowedFileTypes;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin REST controller for the plugin-wide Settings screen, backed by the `renevo_settings` option.
 */
class SettingsController {

	const NAMESPACE_V1 = RequestsController::NAMESPACE_V1;
	const OPTION_NAME  = 'renevo_settings';

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'show' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return Capabilities::current_user_can_manage();
	}

	/**
	 * GET /settings
	 *
	 * @return WP_REST_Response
	 */
	public function show() {
		return rest_ensure_response( self::get_settings() );
	}

	/**
	 * PUT /settings
	 *
	 * @param WP_REST_Request $req Request.
	 * @return WP_REST_Response
	 */
	public function update( WP_REST_Request $req ) {
		$raw     = (array) $req->get_json_params();
		$current = self::get_settings();

		$types = isset( $raw['default_allowed_types'] ) && is_array( $raw['default_allowed_types'] )
			? array_values( array_filter( array_map( 'sanitize_key', $raw['default_allowed_types'] ), array( AllowedFileTypes::class, 'is_valid_key' ) ) )
			: $current['default_allowed_types'];

		$settings = array(
			'default_max_file_size_mb' => isset( $raw['default_max_file_size_mb'] ) ? max( 1, absint( $raw['default_max_file_size_mb'] ) ) : $current['default_max_file_size_mb'],
			'default_allowed_types'    => $types,
			'notification_email'       => isset( $raw['notification_email'] ) && is_email( $raw['notification_email'] ) ? sanitize_email( $raw['notification_email'] ) : $current['notification_email'],
			'store_ip_hash'            => isset( $raw['store_ip_hash'] ) ? (bool) $raw['store_ip_hash'] : $current['store_ip_hash'],
			'retention_days'           => isset( $raw['retention_days'] ) ? absint( $raw['retention_days'] ) : $current['retention_days'],
			'delete_data_on_uninstall' => isset( $raw['delete_data_on_uninstall'] ) ? (bool) $raw['delete_data_on_uninstall'] : $current['delete_data_on_uninstall'],
		);

		update_option( self::OPTION_NAME, $settings );

		return rest_ensure_response( $settings );
	}

	/**
	 * Gets the current settings, merged over defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = array(
			'default_max_file_size_mb' => 10,
			'default_allowed_types'    => array( 'pdf', 'jpg', 'jpeg', 'png' ),
			'notification_email'       => get_option( 'admin_email' ),
			'store_ip_hash'            => false,
			'retention_days'           => 0,
			'delete_data_on_uninstall' => false,
		);

		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $defaults );
	}
}
