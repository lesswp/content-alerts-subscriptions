<?php
/**
 * Database interactions for Content Alerts & Subscriptions.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content_Alerts_Subscriptions_DB Class.
 */
class Content_Alerts_Subscriptions_DB
{

    /**
     * Check DB version and update if necessary.
     */
    public static function check_version()
    {
        if (get_option('content_alerts_subscriptions_db_version') !== CONTENT_ALERTS_SUBSCRIPTIONS_VERSION) {
            self::create_tables();
            update_option('content_alerts_subscriptions_db_version', CONTENT_ALERTS_SUBSCRIPTIONS_VERSION);
        }
    }

    /**
     * Create database tables.
     */
    public static function create_tables()
    {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL auto_increment,
			user_id bigint(20) unsigned NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			object_type varchar(20) NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY object_id_type (object_id, object_type)
		) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Get the table name.
     *
     * @return string The table name.
     */
    public static function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'content_alerts_subscriptions_subscriptions';
    }

    /**
     * Insert a new subscription.
     *
     * @param int $user_id User ID.
     * @param int $object_id Object ID (post/term ID).
     * @param string $object_type Object type ('post' or 'term').
     * @return int|false The inserted row ID or false on failure.
     */
    public static function insert_subscription($user_id, $object_id, $object_type)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $data = array(
            'user_id' => $user_id,
            'object_id' => $object_id,
            'object_type' => $object_type,
            'created_at' => current_time('mysql'),
        );

        $format = array('%d', '%d', '%s', '%s');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert($table_name, $data, $format);

        self::clear_cache($user_id, $object_id, $object_type);

        return $wpdb->insert_id;
    }

    /**
     * Delete a subscription.
     *
     * @param int $user_id User ID.
     * @param int $object_id Object ID.
     * @param string $object_type Object type.
     * @return int|false Number of rows affected or false on error.
     */
    public static function delete_subscription($user_id, $object_id, $object_type)
    {
        global $wpdb;
        $table_name = self::get_table_name();

        $where = array(
            'user_id' => $user_id,
            'object_id' => $object_id,
            'object_type' => $object_type,
        );

        $where_format = array('%d', '%d', '%s');

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete($table_name, $where, $where_format);

        self::clear_cache($user_id, $object_id, $object_type);

        return $result;
    }

    /**
     * Check if a subscription exists.
     *
     * @param int $user_id User ID.
     * @param int $object_id Object ID.
     * @param string $object_type Object type ('post' or 'term').
     * @return bool True if subscription exists, false otherwise.
     */
    public static function has_subscription($user_id, $object_id, $object_type)
    {
        global $wpdb;
        $cache_key = "content_alerts_subscriptions_has_sub_{$user_id}_{$object_id}_{$object_type}";
        $cached = wp_cache_get($cache_key, 'content-alerts-subscriptions');

        if (false !== $cached) {
            return (bool)$cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->get_var(
            $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}content_alerts_subscriptions_subscriptions WHERE user_id = %d AND object_id = %d AND object_type = %s",
            $user_id,
            $object_id,
            $object_type
        )
        );
        $exists = (bool)$result;

        wp_cache_set($cache_key, $exists, 'content-alerts-subscriptions', 3600);

        return $exists;
    }

    /**
     * Get all subscriptions for a specific user.
     *
     * @param int $user_id User ID.
     * @return array Array of subscription objects.
     */
    public static function get_user_subscriptions($user_id)
    {
        global $wpdb;
        $cache_key = "content_alerts_subscriptions_user_subs_{$user_id}";
        $cached = wp_cache_get($cache_key, 'content-alerts-subscriptions');

        if (false !== $cached) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results(
            $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}content_alerts_subscriptions_subscriptions WHERE user_id = %d",
            $user_id
        )
        );
        wp_cache_set($cache_key, $results, 'content-alerts-subscriptions', 3600);

        return $results;
    }

    /**
     * Get all users subscribed to a specific object.
     *
     * @param int $object_id Object ID.
     * @param string $object_type Object type.
     * @return array Array of user IDs.
     */
    public static function get_subscribers($object_id, $object_type)
    {
        global $wpdb;
        $cache_key = "content_alerts_subscriptions_obj_subs_{$object_id}_{$object_type}";
        $cached = wp_cache_get($cache_key, 'content-alerts-subscriptions');

        if (false !== $cached) {
            return $cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_col(
            $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}content_alerts_subscriptions_subscriptions WHERE object_id = %d AND object_type = %s",
            $object_id,
            $object_type
        )
        );
        wp_cache_set($cache_key, $results, 'content-alerts-subscriptions', 3600);

        return $results;
    }

    /**
     * Check if a user has any subscriptions. Used for deciding whether to send welcome email.
     */
    public static function has_any_subscriptions($user_id)
    {
        global $wpdb;
        $cache_key = "content_alerts_subscriptions_has_any_{$user_id}";
        $cached = wp_cache_get($cache_key, 'content-alerts-subscriptions');

        if (false !== $cached) {
            return (bool)$cached;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->get_var(
            $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}content_alerts_subscriptions_subscriptions WHERE user_id = %d LIMIT 1",
            $user_id
        )
        );
        $exists = (bool)$result;

        wp_cache_set($cache_key, $exists, 'content-alerts-subscriptions', 3600);

        return $exists;
    }

    /**
     * Clear subscription cache for a user and object.
     */
    public static function clear_cache($user_id, $object_id, $object_type)
    {
        wp_cache_delete("content_alerts_subscriptions_has_sub_{$user_id}_{$object_id}_{$object_type}", 'content-alerts-subscriptions');
        wp_cache_delete("content_alerts_subscriptions_user_subs_{$user_id}", 'content-alerts-subscriptions');
        wp_cache_delete("content_alerts_subscriptions_obj_subs_{$object_id}_{$object_type}", 'content-alerts-subscriptions');
        wp_cache_delete("content_alerts_subscriptions_has_any_{$user_id}", 'content-alerts-subscriptions');
    }
}
