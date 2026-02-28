<?php
/**
 * Email notifications for Notiva.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Notiva_Emails
{

    public function __construct()
    {
        // Hook into post updates
        add_action('post_updated', array($this, 'maybe_send_post_update_emails'), 10, 3);

        // Hook into post publications (newly published)
        add_action('transition_post_status', array($this, 'maybe_send_term_publish_emails'), 10, 3);
    }

    /**
     * Send notification email when a post is updated.
     */
    public function maybe_send_post_update_emails($post_id, $post_after, $post_before)
    {
        // Check if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Only send for published posts that were already published (an update)
        if ($post_before->post_status !== 'publish' || $post_after->post_status !== 'publish') {
            return;
        }

        // Check if post type is enabled
        $options = get_option('notiva_general_settings');
        $enabled_pt = isset($options['enabled_post_types']) ? (array)$options['enabled_post_types'] : array();

        if (!in_array($post_after->post_type, $enabled_pt)) {
            return;
        }

        // Ensure it's not a revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Avoid infinite loops
        remove_action('post_updated', array($this, 'maybe_send_post_update_emails'), 10);

        // Call the internal email sender logic
        $this->send_notification_emails($post_id, 'post');

        // Re-add action
        add_action('post_updated', array($this, 'maybe_send_post_update_emails'), 10, 3);
    }

    /**
     * Send notification email to taxonomy subscribers when a new post is published in that taxonomy.
     */
    public function maybe_send_term_publish_emails($new_status, $old_status, $post)
    {
        // Check if it's an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Only trigger on a new publish (not an update)
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }

        // Check if post type is enabled
        $options = get_option('notiva_general_settings');
        $enabled_pt = isset($options['enabled_post_types']) ? (array)$options['enabled_post_types'] : array();

        if (!in_array($post->post_type, $enabled_pt)) {
            return;
        }

        // Get enabled taxonomies
        $enabled_tax = isset($options['enabled_taxonomies']) ? (array)$options['enabled_taxonomies'] : array();
        if (empty($enabled_tax)) {
            return;
        }

        // Get all terms for this post in the enabled taxonomies
        $terms_to_notify = array();
        foreach ($enabled_tax as $tax) {
            $terms = wp_get_post_terms($post->ID, $tax, array('fields' => 'ids'));
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term_id) {
                    $terms_to_notify[] = $term_id;
                }
            }
        }

        $terms_to_notify = array_unique($terms_to_notify);

        if (empty($terms_to_notify)) {
            return;
        }

        // Collect all unique subscribers for these terms
        $users_to_email = array();
        foreach ($terms_to_notify as $term_id) {
            $subscribers = Notiva_DB::get_subscribers($term_id, 'term');
            if (!empty($subscribers)) {
                foreach ($subscribers as $sub) {
                    $users_to_email[] = $sub;
                }
            }
        }

        $users_to_email = array_unique($users_to_email);

        /* We'll handle sending per-term now to allow {taxonomy_name} placeholder context */


        if (empty($users_to_email)) {
            return;
        }

        $email_options = get_option('notiva_email_settings');
        $subject_template = isset($email_options['taxonomy_notification_email_subject']) ? $email_options['taxonomy_notification_email_subject'] : __('New Post in {taxonomy_name}: {post_title}', 'notiva');
        $content_template = isset($email_options['taxonomy_notification_email_content']) ? $email_options['taxonomy_notification_email_content'] : __("Hi {user_name},\n\nA new post has been published in {taxonomy_name}: {post_title}.\n\nCheck it out here: {post_link}", 'notiva');

        $post_title = get_the_title($post->ID);
        $post_link = get_permalink($post->ID);

        $sent_users = array();
        foreach ($terms_to_notify as $term_id) {
            $subscribers = Notiva_DB::get_subscribers($term_id, 'term');
            if (empty($subscribers)) {
                continue;
            }

            $term = get_term($term_id);
            $taxonomy_name = '';
            if ($term && !is_wp_error($term)) {
                $tax_obj = get_taxonomy($term->taxonomy);
                $taxonomy_name = $tax_obj ? $tax_obj->labels->singular_name : $term->taxonomy;
            }

            foreach ($subscribers as $user_id) {
                if (in_array($user_id, $sent_users)) {
                    continue;
                }

                $user = get_userdata($user_id);
                if (!$user) {
                    continue;
                }

                $subject = $this->replace_tags($subject_template, $user, $post_title, $post_link, $taxonomy_name, $term->name, get_term_link($term));
                $content = $this->replace_tags($content_template, $user, $post_title, $post_link, $taxonomy_name, $term->name, get_term_link($term));
                $content = wpautop($content);

                $this->send_email($user->user_email, $subject, $content);
                $sent_users[] = $user_id;
            }
        }
    }

    /**
     * Process and send notification emails to all subscribers of this object.
     */
    public function send_notification_emails($object_id, $object_type)
    {
        $subscribers = Notiva_DB::get_subscribers($object_id, $object_type);
        if (empty($subscribers)) {
            return; // No one to email
        }

        $options = get_option('notiva_email_settings');
        $subject_template = isset($options['notification_email_subject']) ? $options['notification_email_subject'] : __('Update: {post_title}', 'notiva');
        $content_template = isset($options['notification_email_content']) ? $options['notification_email_content'] : __("Hi {user_name},\n\nThere has been an update to {post_title}.\n\nCheck it out here: {post_link}", 'notiva');

        list($post_title, $post_link, $taxonomy_name, $term_name, $term_link) = $this->get_object_info($object_id, $object_type);

        foreach ($subscribers as $user_id) {
            $user = get_userdata($user_id);
            if (!$user)
                continue;

            $subject = $this->replace_tags($subject_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
            $content = $this->replace_tags($content_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
            $content = wpautop($content);

            $this->send_email($user->user_email, $subject, $content);
        }
    }

    /**
     * Send Welcome Email.
     */
    public function send_welcome_email($user_id, $object_id, $object_type)
    {
        // Ensure user exists
        $user = get_userdata($user_id);
        if (!$user)
            return;

        $options = get_option('notiva_email_settings');
        $subject_template = isset($options['welcome_email_subject']) ? $options['welcome_email_subject'] : __('Welcome to {post_title} Updates!', 'notiva');
        $content_template = isset($options['welcome_email_content']) ? $options['welcome_email_content'] : __("Hi {user_name},\n\nYou are now subscribed to updates for {post_title}.\n\nView it here: {post_link}\n\nThanks!", 'notiva');

        list($post_title, $post_link, $taxonomy_name, $term_name, $term_link) = $this->get_object_info($object_id, $object_type);

        $subject = $this->replace_tags($subject_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
        $content = $this->replace_tags($content_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
        $content = wpautop($content);

        $this->send_email($user->user_email, $subject, $content);
    }

    /**
     * Send Unsubscribe Email.
     */
    public function send_unsubscribe_email($user_id, $object_id, $object_type)
    {
        // Ensure user exists
        $user = get_userdata($user_id);
        if (!$user)
            return;

        $options = get_option('notiva_email_settings');
        $subject_template = isset($options['unsubscribe_email_subject']) ? $options['unsubscribe_email_subject'] : __('Unsubscribed from {post_title}', 'notiva');
        $content_template = isset($options['unsubscribe_email_content']) ? $options['unsubscribe_email_content'] : __("Hi {user_name},\n\nYou have successfully unsubscribed from updates for {post_title}.", 'notiva');

        list($post_title, $post_link, $taxonomy_name, $term_name, $term_link) = $this->get_object_info($object_id, $object_type);

        $subject = $this->replace_tags($subject_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
        $content = $this->replace_tags($content_template, $user, $post_title, $post_link, $taxonomy_name, $term_name, $term_link);
        $content = wpautop($content);

        $this->send_email($user->user_email, $subject, $content);
    }

    /**
     * Helper function to replace placeholder tags.
     */
    private function replace_tags($text, $user, $post_title, $post_link, $taxonomy_name = '', $term_name = '', $term_link = '')
    {
        $text = str_replace('{user_name}', $user->display_name, $text);
        $text = str_replace('{post_title}', $post_title, $text);
        $text = str_replace('{post_link}', $post_link, $text);
        $text = str_replace('{taxonomy_name}', $taxonomy_name, $text);
        $text = str_replace('{taxonomy_terms}', $term_name, $text);
        $text = str_replace('{taxonomy_terms_link}', $term_link, $text);
        return $text;
    }

    /**
     * Helper to get Post or Term title and link.
     */
    private function get_object_info($object_id, $object_type)
    {
        if ('post' === $object_type) {
            return array(get_the_title($object_id), get_permalink($object_id), '', '', '');
        }
        else {
            $term = get_term($object_id);
            if ($term && !is_wp_error($term)) {
                $tax_obj = get_taxonomy($term->taxonomy);
                $taxonomy_name = $tax_obj ? $tax_obj->labels->singular_name : $term->taxonomy;
                return array($term->name, get_term_link($term), $taxonomy_name, $term->name, get_term_link($term));
            }
        }
        return array('', '', '', '', '');
    }

    /**
     * Core email sending function.
     */
    private function send_email($to, $subject, $message)
    {
        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($to, $subject, $message, $headers);
    }
}


// End of class.
