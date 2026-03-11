<?php
/**
 * Admin Settings for Content Alerts & Subscriptions.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content_Alerts_Subscriptions_Admin Class.
 */
class Content_Alerts_Subscriptions_Admin
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_options_page(
            __('Post Subscriptions', 'content-alerts-subscriptions'),
            __('Post Subscriptions', 'content-alerts-subscriptions'),
            'manage_options',
            'content-alerts-subscriptions-settings',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';
?>
		<div class="wrap">
			<h1><?php esc_html_e('Content Alerts & Subscriptions Settings', 'content-alerts-subscriptions'); ?></h1>

			<h2 class="nav-tab-wrapper">
				<a href="?page=content-alerts-subscriptions-settings&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('General Settings', 'content-alerts-subscriptions'); ?></a>
				<a href="?page=content-alerts-subscriptions-settings&tab=emails" class="nav-tab <?php echo $active_tab == 'emails' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Email Templates', 'content-alerts-subscriptions'); ?></a>
			</h2>
			<form method="post" action="options.php">
			<?php
        if ($active_tab == 'general') {
            settings_fields('content_alerts_subscriptions_general_group');
            do_settings_sections('content-alerts-subscriptions-settings-general');
        }
        else {
            settings_fields('content_alerts_subscriptions_emails_group');
            do_settings_sections('content-alerts-subscriptions-settings-emails');
        }
        submit_button();
?>
			</form>
		</div>
		<?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {
        /**
         * General Tab
         */
        register_setting('content_alerts_subscriptions_general_group', 'content_alerts_subscriptions_general_settings', array($this, 'sanitize'));

        add_settings_section(
            'content_alerts_subscriptions_general_section', // ID
            __('General Settings', 'content-alerts-subscriptions'), // Title
            array($this, 'print_general_section_info'), // Callback
            'content-alerts-subscriptions-settings-general' // Page
        );

        add_settings_field(
            'enabled_post_types',
            __('Enabled Post Types', 'content-alerts-subscriptions'),
            array($this, 'enabled_post_types_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'enabled_taxonomies',
            __('Enabled Taxonomies', 'content-alerts-subscriptions'),
            array($this, 'enabled_taxonomies_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'post_tab_label',
            __('Post Tab Label', 'content-alerts-subscriptions'),
            array($this, 'post_tab_label_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'post_column_label',
            __('Post Column Label', 'content-alerts-subscriptions'),
            array($this, 'post_column_label_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'taxonomy_tab_label',
            __('Taxonomy Tab Label', 'content-alerts-subscriptions'),
            array($this, 'taxonomy_tab_label_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'taxonomy_column_label',
            __('Taxonomy Column Label', 'content-alerts-subscriptions'),
            array($this, 'taxonomy_column_label_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'show_type_column',
            __('Show "Type" Column in Dashboard', 'content-alerts-subscriptions'),
            array($this, 'show_type_column_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );

        add_settings_field(
            'show_date_column',
            __('Show "Subscribed On" Column in Dashboard', 'content-alerts-subscriptions'),
            array($this, 'show_date_column_callback'),
            'content-alerts-subscriptions-settings-general',
            'content_alerts_subscriptions_general_section'
        );
        /**
         * Emails Tab
         */
        register_setting('content_alerts_subscriptions_emails_group', 'content_alerts_subscriptions_email_settings', array($this, 'sanitize_email'));

        add_settings_section(
            'content_alerts_subscriptions_emails_section',
            'Email Templates',
            array($this, 'print_emails_section_info'),
            'content-alerts-subscriptions-settings-emails'
        );

        add_settings_field(
            'welcome_email',
            'Welcome Email (Subject & Content)',
            array($this, 'welcome_email_callback'),
            'content-alerts-subscriptions-settings-emails',
            'content_alerts_subscriptions_emails_section'
        );

        add_settings_field(
            'unsubscribe_email',
            'Unsubscribe Email (Subject & Content)',
            array($this, 'unsubscribe_email_callback'),
            'content-alerts-subscriptions-settings-emails',
            'content_alerts_subscriptions_emails_section'
        );

        add_settings_field(
            'notification_email',
            'Notification Email (Subject & Content)',
            array($this, 'notification_email_callback'),
            'content-alerts-subscriptions-settings-emails',
            'content_alerts_subscriptions_emails_section'
        );

        add_settings_field(
            'taxonomy_notification_email',
            'Taxonomy Notification Email (Subject & Content)',
            array($this, 'taxonomy_notification_email_callback'),
            'content-alerts-subscriptions-settings-emails',
            'content_alerts_subscriptions_emails_section'
        );
    }

    /**
     * Sanitize each setting field as needed
     */
    public function sanitize($input)
    {
        $new_input = array();
        if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
            $new_input['enabled_post_types'] = array_map('sanitize_text_field', $input['enabled_post_types']);
        }
        if (isset($input['enabled_taxonomies']) && is_array($input['enabled_taxonomies'])) {
            $new_input['enabled_taxonomies'] = array_map('sanitize_text_field', $input['enabled_taxonomies']);
        }
        if (isset($input['post_type_placements']) && is_array($input['post_type_placements'])) {
            foreach ($input['post_type_placements'] as $pt => $placement) {
                $new_input['post_type_placements'][sanitize_text_field($pt)] = sanitize_text_field($placement);
            }
        }
        if (isset($input['taxonomy_placements']) && is_array($input['taxonomy_placements'])) {
            foreach ($input['taxonomy_placements'] as $tax => $placement) {
                $new_input['taxonomy_placements'][sanitize_text_field($tax)] = sanitize_text_field($placement);
            }
        }
        if (isset($input['post_tab_label'])) {
            $new_input['post_tab_label'] = sanitize_text_field($input['post_tab_label']);
        }
        if (isset($input['post_column_label'])) {
            $new_input['post_column_label'] = sanitize_text_field($input['post_column_label']);
        }
        if (isset($input['taxonomy_tab_label'])) {
            $new_input['taxonomy_tab_label'] = sanitize_text_field($input['taxonomy_tab_label']);
        }
        if (isset($input['taxonomy_column_label'])) {
            $new_input['taxonomy_column_label'] = sanitize_text_field($input['taxonomy_column_label']);
        }
        if (isset($input['show_type_column'])) {
            $new_input['show_type_column'] = 1;
        }
        else {
            $new_input['show_type_column'] = 0;
        }
        if (isset($input['show_date_column'])) {
            $new_input['show_date_column'] = 1;
        }
        else {
            $new_input['show_date_column'] = 0;
        }

        return $new_input;
    }

    public function sanitize_email($input)
    {
        $new_input = array();
        if (isset($input['welcome_email_subject'])) {
            $new_input['welcome_email_subject'] = sanitize_text_field($input['welcome_email_subject']);
        }
        if (isset($input['welcome_email_content'])) {
            $new_input['welcome_email_content'] = wp_kses_post($input['welcome_email_content']);
        }
        if (isset($input['unsubscribe_email_subject'])) {
            $new_input['unsubscribe_email_subject'] = sanitize_text_field($input['unsubscribe_email_subject']);
        }
        if (isset($input['unsubscribe_email_content'])) {
            $new_input['unsubscribe_email_content'] = wp_kses_post($input['unsubscribe_email_content']);
        }
        if (isset($input['notification_email_subject'])) {
            $new_input['notification_email_subject'] = sanitize_text_field($input['notification_email_subject']);
        }
        if (isset($input['notification_email_content'])) {
            $new_input['notification_email_content'] = wp_kses_post($input['notification_email_content']);
        }
        if (isset($input['taxonomy_notification_email_subject'])) {
            $new_input['taxonomy_notification_email_subject'] = sanitize_text_field($input['taxonomy_notification_email_subject']);
        }
        if (isset($input['taxonomy_notification_email_content'])) {
            $new_input['taxonomy_notification_email_content'] = wp_kses_post($input['taxonomy_notification_email_content']);
        }

        return $new_input;
    }


    public function print_general_section_info()
    {
        printf('<p>%s</p>', esc_html__('Configure the general settings for subscriptions below:', 'content-alerts-subscriptions'));
    }

    public function print_emails_section_info()
    {
        printf('<p>%s</p>', esc_html__('Configure the email templates sent to subscribers. You can use the following placeholders:', 'content-alerts-subscriptions'));
        print '<ul>';
        printf('<li><code>{user_name}</code> - %s</li>', esc_html__("The subscriber's display name.", 'content-alerts-subscriptions'));
        printf('<li><code>{post_title}</code> - %s</li>', esc_html__('The title of the post/category subscribed to or updated.', 'content-alerts-subscriptions'));
        printf('<li><code>{post_link}</code> - %s</li>', esc_html__('The URL to the post/category.', 'content-alerts-subscriptions'));
        printf('<li><code>{taxonomy_name}</code> - %s</li>', esc_html__('The name of the category/taxonomy (e.g. "Category").', 'content-alerts-subscriptions'));
        printf('<li><code>{taxonomy_terms}</code> - %s</li>', esc_html__('The name of the specific term(s) (e.g. "News").', 'content-alerts-subscriptions'));
        printf('<li><code>{taxonomy_terms_link}</code> - %s</li>', esc_html__('The URL to the term(s) archive page.', 'content-alerts-subscriptions'));
        print '</ul>';
    }

    public function enabled_post_types_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $enabled = isset($options['enabled_post_types']) ? (array)$options['enabled_post_types'] : array();
        $placements = isset($options['post_type_placements']) ? (array)$options['post_type_placements'] : array();

        $post_types = get_post_types(array('public' => true), 'objects');

        echo '<table style="width: 100%; max-width: 600px; border-collapse: collapse;">';
        foreach ($post_types as $post_type) {
            if ($post_type->name === 'attachment') {
                continue;
            }
            $placement = isset($placements[$post_type->name]) ? $placements[$post_type->name] : 'auto';

            echo '<tr style="border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 10px 0;">';
            printf(
                '<label><input type="checkbox" name="content_alerts_subscriptions_general_settings[enabled_post_types][]" value="%s" %s> <strong>%s</strong></label>',
                esc_attr($post_type->name),
                checked(in_array($post_type->name, $enabled), true, false),
                esc_html($post_type->label)
            );
            echo '</td>';
            echo '<td style="padding: 10px 0;">';
            printf(
                '<label><input type="radio" name="content_alerts_subscriptions_general_settings[post_type_placements][%s]" value="auto" %s> Auto</label> &nbsp; ',
                esc_attr($post_type->name),
                checked($placement, 'auto', false)
            );
            printf(
                '<label><input type="radio" name="content_alerts_subscriptions_general_settings[post_type_placements][%s]" value="manual" %s> Manual</label>',
                esc_attr($post_type->name),
                checked($placement, 'manual', false)
            );
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        printf('<p class="description"><strong>%s</strong> %s<br><strong>%s</strong> %s</p>',
            esc_html__('Auto', 'content-alerts-subscriptions'),
            esc_html__('automatically appends the button to the end of the post content.', 'content-alerts-subscriptions'),
            esc_html__('Manual', 'content-alerts-subscriptions'),
            esc_html__('requires you to place the [content_alerts_subscriptions_subscribe_button] shortcode within the post content or template.', 'content-alerts-subscriptions')
        );
    }

    public function enabled_taxonomies_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $enabled = isset($options['enabled_taxonomies']) ? (array)$options['enabled_taxonomies'] : array();
        $placements = isset($options['taxonomy_placements']) ? (array)$options['taxonomy_placements'] : array();

        $taxonomies = get_taxonomies(array('public' => true), 'objects');

        echo '<table style="width: 100%; max-width: 600px; border-collapse: collapse;">';
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->name === 'post_format') {
                continue;
            }
            $placement = isset($placements[$taxonomy->name]) ? $placements[$taxonomy->name] : 'auto';

            echo '<tr style="border-bottom: 1px solid #ddd;">';
            echo '<td style="padding: 10px 0;">';
            printf(
                '<label><input type="checkbox" name="content_alerts_subscriptions_general_settings[enabled_taxonomies][]" value="%s" %s> <strong>%s</strong></label>',
                esc_attr($taxonomy->name),
                checked(in_array($taxonomy->name, $enabled), true, false),
                esc_html($taxonomy->label)
            );
            echo '</td>';
            echo '<td style="padding: 10px 0;">';
            printf(
                '<label><input type="radio" name="content_alerts_subscriptions_general_settings[taxonomy_placements][%s]" value="auto" %s> Auto</label> &nbsp; ',
                esc_attr($taxonomy->name),
                checked($placement, 'auto', false)
            );
            printf(
                '<label><input type="radio" name="content_alerts_subscriptions_general_settings[taxonomy_placements][%s]" value="manual" %s> Manual</label>',
                esc_attr($taxonomy->name),
                checked($placement, 'manual', false)
            );
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
        printf('<p class="description"><strong>%s</strong> %s<br><strong>%s</strong> %s</p>',
            esc_html__('Auto', 'content-alerts-subscriptions'),
            esc_html__('automatically appends the button to the top of the category/term archive page.', 'content-alerts-subscriptions'),
            esc_html__('Manual', 'content-alerts-subscriptions'),
            esc_html__('requires you to place the shortcode in the category description or template.', 'content-alerts-subscriptions')
        );
    }

    public function post_tab_label_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $label = isset($options['post_tab_label']) ? $options['post_tab_label'] : 'Subscribed Content';
        printf(
            '<input type="text" name="content_alerts_subscriptions_general_settings[post_tab_label]" value="%s" class="regular-text" />',
            esc_attr($label)
        );
    }

    public function taxonomy_tab_label_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $label = isset($options['taxonomy_tab_label']) ? $options['taxonomy_tab_label'] : 'Subscribed Categories';
        printf(
            '<input type="text" name="content_alerts_subscriptions_general_settings[taxonomy_tab_label]" value="%s" class="regular-text" />',
            esc_attr($label)
        );
    }

    public function show_type_column_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        // Default to checked (1)
        $is_checked = !isset($options['show_type_column']) || (isset($options['show_type_column']) && $options['show_type_column'] == 1);
        printf(
            '<label><input type="checkbox" name="content_alerts_subscriptions_general_settings[show_type_column]" value="1" %s> %s</label>',
            checked($is_checked, true, false),
            esc_html__('Yes, show the Post Type column in the user dashboard.', 'content-alerts-subscriptions')
        );
    }

    public function show_date_column_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        // Default to checked (1)
        $is_checked = !isset($options['show_date_column']) || (isset($options['show_date_column']) && $options['show_date_column'] == 1);
        printf(
            '<label><input type="checkbox" name="content_alerts_subscriptions_general_settings[show_date_column]" value="1" %s> %s</label>',
            checked($is_checked, true, false),
            esc_html__('Yes, show the Subscribed On date column in the user dashboard.', 'content-alerts-subscriptions')
        );
    }

    public function post_column_label_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $label = isset($options['post_column_label']) ? $options['post_column_label'] : 'Title';
        printf(
            '<input type="text" name="content_alerts_subscriptions_general_settings[post_column_label]" value="%s" class="regular-text" />',
            esc_attr($label)
        );
    }

    public function taxonomy_column_label_callback()
    {
        $options = get_option('content_alerts_subscriptions_general_settings');
        $label = isset($options['taxonomy_column_label']) ? $options['taxonomy_column_label'] : 'Category / Term';
        printf(
            '<input type="text" name="content_alerts_subscriptions_general_settings[taxonomy_column_label]" value="%s" class="regular-text" />',
            esc_attr($label)
        );
    }
    public function welcome_email_callback()
    {
        $options = get_option('content_alerts_subscriptions_email_settings');
        $subject = isset($options['welcome_email_subject']) ? $options['welcome_email_subject'] : 'Welcome to {post_title} Updates!';
        $content = isset($options['welcome_email_content']) ? $options['welcome_email_content'] : "Hi {user_name},\n\nYou are now subscribed to updates for {post_title}.\n\nView it here: {post_link}\n\nThanks!";

        printf(
            '<input type="text" style="width: 100%%; margin-bottom: 10px;" name="content_alerts_subscriptions_email_settings[welcome_email_subject]" value="%s" placeholder="Email Subject" />',
            esc_attr($subject)
        );

        wp_editor($content, 'welcome_email_content', array(
            'textarea_name' => 'content_alerts_subscriptions_email_settings[welcome_email_content]',
            'textarea_rows' => 5,
        ));
    }

    public function unsubscribe_email_callback()
    {
        $options = get_option('content_alerts_subscriptions_email_settings');
        $subject = isset($options['unsubscribe_email_subject']) ? $options['unsubscribe_email_subject'] : 'Unsubscribed from {post_title}';
        $content = isset($options['unsubscribe_email_content']) ? $options['unsubscribe_email_content'] : "Hi {user_name},\n\nYou have successfully unsubscribed from updates for {post_title}.";

        printf(
            '<input type="text" style="width: 100%%; margin-bottom: 10px;" name="content_alerts_subscriptions_email_settings[unsubscribe_email_subject]" value="%s" placeholder="Email Subject" />',
            esc_attr($subject)
        );

        wp_editor($content, 'unsubscribe_email_content', array(
            'textarea_name' => 'content_alerts_subscriptions_email_settings[unsubscribe_email_content]',
            'textarea_rows' => 5,
        ));
    }

    public function notification_email_callback()
    {
        $options = get_option('content_alerts_subscriptions_email_settings');
        $subject = isset($options['notification_email_subject']) ? $options['notification_email_subject'] : 'Update: {post_title}';
        $content = isset($options['notification_email_content']) ? $options['notification_email_content'] : "Hi {user_name},\n\nThere has been an update to {post_title}.\n\nCheck it out here: {post_link}";

        printf(
            '<input type="text" style="width: 100%%; margin-bottom: 10px;" name="content_alerts_subscriptions_email_settings[notification_email_subject]" value="%s" placeholder="Email Subject" />',
            esc_attr($subject)
        );

        wp_editor($content, 'notification_email_content', array(
            'textarea_name' => 'content_alerts_subscriptions_email_settings[notification_email_content]',
            'textarea_rows' => 5,
        ));
    }

    public function taxonomy_notification_email_callback()
    {
        $options = get_option('content_alerts_subscriptions_email_settings');
        $subject = isset($options['taxonomy_notification_email_subject']) ? $options['taxonomy_notification_email_subject'] : 'New Post in {taxonomy_name}: {post_title}';
        $content = isset($options['taxonomy_notification_email_content']) ? $options['taxonomy_notification_email_content'] : "Hi {user_name},\n\nA new post has been published in {taxonomy_name}: {post_title}.\n\nCheck it out here: {post_link}";

        printf(
            '<input type="text" style="width: 100%%; margin-bottom: 10px;" name="content_alerts_subscriptions_email_settings[taxonomy_notification_email_subject]" value="%s" placeholder="Email Subject" />',
            esc_attr($subject)
        );

        wp_editor($content, 'taxonomy_notification_email_content', array(
            'textarea_name' => 'content_alerts_subscriptions_email_settings[taxonomy_notification_email_content]',
            'textarea_rows' => 5,
        ));
    }
}


// End of class.
