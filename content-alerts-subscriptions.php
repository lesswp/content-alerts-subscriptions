<?php
/**
 * Plugin Name: Content Alerts & Subscriptions
 * Description: Allows users to subscribe to specific posts, pages, custom post types, or categories and receive email notifications upon updates.
 * Version: 1.0.1
 * Author: Anuj Pandey
 * Author URI: https://github.com/lesswp/content-alerts-subscriptions
 * Text Domain: content-alerts-subscriptions
 * Domain Path: /languages
* License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Content Alerts & Subscriptions constants.
define('CONTENT_ALERTS_SUBSCRIPTIONS_VERSION', '1.0.1');
define('CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR', __DIR__ . '/');
define('CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files immediately to satisfy activation hooks and early calls.
require_once CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/content-alerts-subscriptions-db.php';
require_once CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/content-alerts-subscriptions-emails.php';
require_once CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/content-alerts-subscriptions-admin.php';
require_once CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/content-alerts-subscriptions-frontend.php';
require_once CONTENT_ALERTS_SUBSCRIPTIONS_PLUGIN_DIR . 'includes/content-alerts-subscriptions-my-account.php';

/**
 * Main Content Alerts & Subscriptions class to manage plugin lifecycle.
 */
class Content_Alerts_Subscriptions_Manager
{
    /**
     * @var Content_Alerts_Subscriptions_Manager
     */
    protected static $instance = null;

    /**
     * Get singleton instance.
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->init_components();
    }

    /**
     * Initialize components and hooks.
     */
    private function init_components()
    {
        // Check DB version for updates in admin
        if (class_exists('Content_Alerts_Subscriptions_DB')) {
            add_action('admin_init', array('Content_Alerts_Subscriptions_DB', 'check_version'));
        }

        // Load translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Component instantiations
        if (is_admin()) {
            new Content_Alerts_Subscriptions_Admin();
        }
        if (class_exists('Content_Alerts_Subscriptions_Frontend')) {
            new Content_Alerts_Subscriptions_Frontend();
        }
        if (class_exists('Content_Alerts_Subscriptions_My_Account')) {
            new Content_Alerts_Subscriptions_My_Account();
        }
        if (class_exists('Content_Alerts_Subscriptions_Emails')) {
            new Content_Alerts_Subscriptions_Emails();
        }
    }

    /**
     * Load plugin textdomain.
     */
    public function load_textdomain()
    {
    // Handled by WordPress.org
    }

    /**
     * Handle plugin activation.
     */
    public static function activate()
    {
        // Content_Alerts_Subscriptions_DB is already included at the top level
        if (class_exists('Content_Alerts_Subscriptions_DB')) {
            Content_Alerts_Subscriptions_DB::create_tables();
        }

        // Initialize default settings if they don't exist
        if (!get_option('content_alerts_subscriptions_general_settings')) {
            update_option('content_alerts_subscriptions_general_settings', array(
                'enabled_post_types' => array('post'),
                'enabled_taxonomies' => array('category'),
                'post_type_placements' => array('post' => 'auto'),
                'taxonomy_placements' => array('category' => 'auto'),
                'post_tab_label' => 'Post Subscriptions',
                'post_column_label' => 'Post',
                'taxonomy_tab_label' => 'Category Subscriptions',
                'taxonomy_column_label' => 'Category',
                'show_type_column' => 1,
                'show_date_column' => 1,
            ));
        }
    }
}

/**
 * Initialize the plugin.
 */
add_action('plugins_loaded', array('Content_Alerts_Subscriptions_Manager', 'get_instance'), 1);

/**
 * Register activation hook.
 */
register_activation_hook(__FILE__, array('Content_Alerts_Subscriptions_Manager', 'activate'));
