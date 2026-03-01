<?php
/**
 * Notiva Uninstall
 *
 * Uninstalling Notiva deletes user subscriptions and plugin options.
 *
 * @package Notiva\Uninstall
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete plugin options.
delete_option( 'notiva_general_settings' );
delete_option( 'notiva_email_settings' );
delete_option( 'notiva_db_version' );

// 2. Drop the custom database table.
$table_name = $wpdb->prefix . 'notiva_subscriptions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
