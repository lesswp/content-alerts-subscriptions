<?php
/**
 * Content Alerts & Subscriptions Uninstall
 *
 * Uninstalling Content Alerts & Subscriptions deletes user subscriptions and plugin options.
 *
 * @package Content Alerts & Subscriptions\Uninstall
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// 1. Delete plugin options.
delete_option( 'content_alerts_subscriptions_general_settings' );
delete_option( 'content_alerts_subscriptions_email_settings' );
delete_option( 'content_alerts_subscriptions_db_version' );

// 2. Drop the custom database table.
$table_name = $wpdb->prefix . 'content_alerts_subscriptions_subscriptions';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
