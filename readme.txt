=== Content Alerts & Subscriptions ===
Contributors: wpless
Tags: subscription, post notification, email updates, categories, taxonomy
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Allows users to subscribe to specific posts, pages, custom post types, or categories and receive email notifications upon updates.

== Description ==

Content Alerts & Subscriptions is a lightweight and powerful plugin that enables your visitors to subscribe to their favorite content. Whether it's a specific blog post, a product (CPT), or an entire category, your users will stay informed with automated email notifications.

Key Features:
*   **Subscribe to Any Content**: Supports Posts, Pages, and all Public Custom Post Types.
*   **Taxonomy Subscriptions**: Users can subscribe to Categories, Tags, or any custom taxonomy.
*   **Personal Dashboard**: A clean, tabbed interface for users to manage their subscriptions.
*   **Theme Integration**: Automatically adapts to your active theme's styling.
*   **Customizable Emails**: Easily edit Welcome, Unsubscribe, and Update notification templates with dynamic placeholders.
*   **Shortcode Support**: Use `[content_alerts_subscriptions_my_account]` to display the dashboard anywhere.
*   **WooCommerce & Ultimate Member Ready**: Seamlessly integrates into existing "My Account" or "Profile" pages.

== Installation ==

1. Upload the `content-alerts-subscriptions` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your enabled post types and taxonomies under 'Settings' -> 'Post Subscriptions'.
4. Set up your email templates in the 'Email Templates' tab.

== Frequently Asked Questions ==

= How do users unsubscribe? =
Users can manage and remove their subscriptions from the "Subscription Management" tab in their account dashboard.

= Can I place the subscribe button manually? =
Yes! In the settings, you can switch from "Auto" to "Manual" placement for each post type or taxonomy. Then, simply use the `[content_alerts_subscriptions_subscribe_button]` shortcode.

== Screenshots ==

1. The subscription dashboard with a tabbed interface.
2. Admin settings for enabling post types and taxonomies.
3. Customizable email template editor.

== Changelog ==

= 1.0.1 =
* Rebranded to Content Alerts & Subscriptions.
* Improved initialization sequence.
* Fixed SQL preparation for better standards compliance.

= 1.0.0 =
* Initial release.
