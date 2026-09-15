<?php
/**
 * Uninstall routine. Only deletes data if "Delete all data on uninstall" is enabled.
 *
 * @package FileRequestManager
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) || ! defined( 'ABSPATH' ) ) {
	exit;
}

$renevo_settings = get_option( 'renevo_settings', array() );

if ( empty( $renevo_settings['delete_data_on_uninstall'] ) ) {
	return;
}

global $wpdb;

$renevo_request_ids = get_posts(
	array(
		'post_type'      => 'file_request',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $renevo_request_ids as $renevo_request_id ) {
	wp_delete_post( $renevo_request_id, true );
}

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}renevo_submission_files" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}renevo_submissions" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

$renevo_upload_dir = wp_upload_dir();
$renevo_storage    = trailingslashit( $renevo_upload_dir['basedir'] ) . 'renevo-file-request-manager';

if ( is_dir( $renevo_storage ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();
	global $wp_filesystem;

	if ( $wp_filesystem ) {
		$wp_filesystem->delete( $renevo_storage, true );
	}
}

delete_option( 'renevo_settings' );
delete_option( 'renevo_db_version' );

foreach ( array( 'renevo_daily_maintenance', 'renevo_hourly_maintenance' ) as $renevo_hook ) {
	$renevo_timestamp = wp_next_scheduled( $renevo_hook );
	if ( $renevo_timestamp ) {
		wp_unschedule_event( $renevo_timestamp, $renevo_hook );
	}
}
