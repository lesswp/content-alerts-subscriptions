<?php
/**
 * Plugin Name: Notiva - Post & Category Subscription Manager
 * Description: Allows users to subscribe to specific posts, pages, custom post types, or categories and receive email notifications upon updates.
 * Version: 1.0.1
 * Author: Anuj Pandey
 * Text Domain: notiva
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define Notiva constants.
define('NOTIVA_VERSION', '1.0.1');
define('NOTIVA_PLUGIN_DIR', __DIR__ . '/');
define('NOTIVA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files immediately to satisfy activation hooks and early calls.
require_once NOTIVA_PLUGIN_DIR . 'includes/notiva-db.php';
require_once NOTIVA_PLUGIN_DIR . 'includes/notiva-emails.php';
require_once NOTIVA_PLUGIN_DIR . 'includes/notiva-admin.php';
require_once NOTIVA_PLUGIN_DIR . 'includes/notiva-frontend.php';
require_once NOTIVA_PLUGIN_DIR . 'includes/notiva-my-account.php';

/**
 * Main Notiva class to manage plugin lifecycle.
 */
class Notiva_Manager
{
    /**
     * @var Notiva_Manager
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
        if (class_exists('Notiva_DB')) {
            add_action('admin_init', array('Notiva_DB', 'check_version'));
        }

        // Load translations
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Component instantiations
        if (is_admin()) {
            new Notiva_Admin();
        }
        if (class_exists('Notiva_Frontend')) {
            new Notiva_Frontend();
        }
        if (class_exists('Notiva_My_Account')) {
            new Notiva_My_Account();
        }
        if (class_exists('Notiva_Emails')) {
            new Notiva_Emails();
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
        // Notiva_DB is already included at the top level
        if (class_exists('Notiva_DB')) {
            Notiva_DB::create_tables();
        }

        // Initialize default settings if they don't exist
        if (!get_option('notiva_general_settings')) {
            update_option('notiva_general_settings', array(
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
add_action('plugins_loaded', array('Notiva_Manager', 'get_instance'), 1);

/**
 * Register activation hook.
 */
register_activation_hook(__FILE__, array('Notiva_Manager', 'activate'));
