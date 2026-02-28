<?php
/**
 * Frontend subscription logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend subscription logic for Notiva.
 */
class Notiva_Frontend
{
    /**
     * @var bool Flag to prevent double rendering on taxonomies.
     */
    private $taxonomy_button_rendered = false;

    public function __construct()
    {
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Content hook for auto placement on posts
        add_filter('the_content', array($this, 'auto_append_button'), 20);

        // Hook for auto placement on taxonomies
        add_filter('get_the_archive_description', array($this, 'auto_append_term_button'), 20);
        add_filter('get_the_archive_title', array($this, 'auto_append_term_title_button'), 20);
        add_action('woocommerce_archive_description', array($this, 'auto_append_term_button_action'), 20);
        add_action('generate_after_archive_title', array($this, 'auto_append_term_button_action'), 20);

        // Shortcode for manual placement
        add_shortcode('notiva_subscribe_button', array($this, 'handle_subscribe_button_shortcode'));

        // Enable shortcodes in term descriptions
        add_filter('term_description', 'do_shortcode');
        add_filter('get_the_archive_description', 'do_shortcode');

        // AJAX handlers
        add_action('wp_ajax_notiva_toggle_subscription', array($this, 'ajax_toggle_subscription'));
    }

    /**
     * Common logic for post button placement.
     */
    private function should_render_post_button()
    {
        if (!(is_singular() || is_archive() || is_home() || is_search())) {
            return false;
        }

        $options = get_option('notiva_general_settings');
        $post_type = get_post_type();

        $enabled_pt = isset($options['enabled_post_types']) ? (array)$options['enabled_post_types'] : array('post');
        if (!in_array($post_type, $enabled_pt)) {
            return false;
        }

        $placements = isset($options['post_type_placements']) ? (array)$options['post_type_placements'] : array();
        $placement = isset($placements[$post_type]) ? $placements[$post_type] : 'auto';

        return 'auto' === $placement;
    }

    /**
     * Common logic for taxonomy button placement.
     */
    private function should_render_taxonomy_button()
    {
        if (!(is_tax() || is_category() || is_tag())) {
            return false;
        }

        $options = get_option('notiva_general_settings');
        $q_obj = get_queried_object();

        if (!isset($q_obj->taxonomy)) {
            return false;
        }

        $enabled_tax = isset($options['enabled_taxonomies']) ? (array)$options['enabled_taxonomies'] : array('category');
        if (!in_array($q_obj->taxonomy, $enabled_tax)) {
            return false;
        }

        $placements = isset($options['taxonomy_placements']) ? (array)$options['taxonomy_placements'] : array();
        $placement = isset($placements[$q_obj->taxonomy]) ? $placements[$q_obj->taxonomy] : 'auto';

        return 'auto' === $placement;
    }

    public function enqueue_scripts()
    {
        // Enqueue CSS
        wp_enqueue_style(
            'notiva-frontend-css',
            NOTIVA_PLUGIN_URL . 'assets/css/notiva-frontend.css',
            array(),
            NOTIVA_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'notiva-frontend-js',
            NOTIVA_PLUGIN_URL . 'assets/js/notiva-frontend.js',
            array('jquery'),
            NOTIVA_VERSION,
            true
        );

        wp_localize_script(
            'notiva-frontend-js',
            'notiva_ajax_obj',
            array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('notiva_subscription_nonce'),
        )
        );
    }

    /**
     * Automatically append to post content if settings allow.
     */
    public function auto_append_button($content)
    {
        if (!is_main_query()) {
            return $content;
        }

        $options = get_option('notiva_general_settings');
        $placements = isset($options['post_type_placements']) ? (array)$options['post_type_placements'] : array();

        if (is_singular() || is_archive() || is_home() || is_search()) {
            $post_type = get_post_type();
            $enabled_pt = isset($options['enabled_post_types']) ? (array)$options['enabled_post_types'] : array();

            if ($this->should_render_post_button()) {
                $content .= $this->render_subscribe_button();
            }
        }

        return $content;
    }

    /**
     * Automatically append to term description if settings allow.
     */
    public function auto_append_term_button($description)
    {
        if ($this->taxonomy_button_rendered) {
            return $description;
        }

        if ($this->should_render_taxonomy_button()) {
            $description .= $this->render_subscribe_button();
            $this->taxonomy_button_rendered = true;
        }

        return $description;
    }

    public function auto_append_term_title_button($title)
    {
        if ($this->taxonomy_button_rendered) {
            return $title;
        }

        if ($this->should_render_taxonomy_button()) {
            // Check if description is likely to render a button
            $desc = term_description();
            if (empty(trim(wp_strip_all_tags($desc)))) {
                $title .= $this->render_subscribe_button();
                $this->taxonomy_button_rendered = true;
            }
        }

        return $title;
    }

    /**
     * Action wrapper for custom theme archive hooks.
     */
    public function auto_append_term_button_action()
    {
        echo wp_kses_post($this->auto_append_term_button(''));
    }

    /**
     * Shortcode handler. Only renders if placement is set to 'manual'.
     */
    public function handle_subscribe_button_shortcode($atts = array())
    {
        if (is_singular() || is_home() || is_archive() || is_search()) {
            if ($this->should_render_post_button()) {
                // Auto is enabled, so don't render shortcode to avoid duplicates
                return '';
            }
        }

        if (is_tax() || is_category() || is_tag()) {
            if ($this->should_render_taxonomy_button()) {
                // Auto is enabled, so don't render shortcode to avoid duplicates
                return '';
            }
        }

        return $this->render_subscribe_button($atts);
    }

    /**
     * Output the button HTML.
     */
    public function render_subscribe_button($atts = array())
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="notiva-subscribe-wrapper"><p><a href="%s">%s</a></p></div>',
                esc_url(wp_login_url(get_permalink())),
                esc_html__('Log in to subscribe to updates', 'notiva')
            );
        }

        $user_id = get_current_user_id();
        $object_id = 0;
        $object_type = 'post';

        if (is_singular()) {
            $object_id = get_the_ID();
            $object_type = 'post';
        }
        elseif (is_tax() || is_category() || is_tag()) {
            $q_obj = get_queried_object();
            if (isset($q_obj->term_id)) {
                $object_id = $q_obj->term_id;
                $object_type = 'term';
            }
        }
        elseif ((is_home() || is_archive() || is_search()) && !is_singular()) {
            // Support for post loop on homepage/archives
            $object_id = get_the_ID();
            $object_type = 'post';
        }
        else {
            // If trying to use shortcode outside of a singular post or term archive without passing attributes
            // (we haven't implemented explicit attribute passing yet, default to current queried object loop)
            return '';
        }

        if (!$object_id) {
            return '';
        }

        $is_subscribed = Notiva_DB::has_subscription($user_id, $object_id, $object_type);

        $btn_text = $is_subscribed ? __('Unsubscribe from Updates', 'notiva') : __('Subscribe to Updates', 'notiva');
        $btn_class = $is_subscribed ? 'notiva-subscribe-btn notiva-subscribed' : 'notiva-subscribe-btn notiva-unsubscribed';

        ob_start();
?>
		<div class="notiva-subscribe-wrapper">
			<button class="<?php echo esc_attr($btn_class); ?>" 
				data-object-id="<?php echo esc_attr($object_id); ?>" 
				data-object-type="<?php echo esc_attr($object_type); ?>">
				<?php echo esc_html($btn_text); ?>
			</button>
		</div>
		<?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX request to toggle subscription.
     */
    public function ajax_toggle_subscription()
    {
        check_ajax_referer('notiva_subscription_nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to subscribe.', 'notiva')));
        }

        $user_id = get_current_user_id();
        $object_id = isset($_POST['object_id']) ? intval($_POST['object_id']) : 0;
        $object_type = isset($_POST['object_type']) ? sanitize_text_field(wp_unslash($_POST['object_type'])) : 'post';

        if (!$object_id) {
            wp_send_json_error(array('message' => __('Invalid object.', 'notiva')));
        }

        $is_subscribed = Notiva_DB::has_subscription($user_id, $object_id, $object_type);
        $emails_class = new Notiva_Emails();

        if ($is_subscribed) {
            // Unsubscribe
            Notiva_DB::delete_subscription($user_id, $object_id, $object_type);

            // Send unsubscribe email
            $emails_class->send_unsubscribe_email($user_id, $object_id, $object_type);

            wp_send_json_success(array(
                'subscribed' => false,
                'btn_text' => __('Subscribe to Updates', 'notiva')
            ));
        }
        else {
            // Subscribe
            Notiva_DB::insert_subscription($user_id, $object_id, $object_type);

            // Send welcome email
            $emails_class->send_welcome_email($user_id, $object_id, $object_type);

            wp_send_json_success(array(
                'subscribed' => true,
                'btn_text' => __('Unsubscribe from Updates', 'notiva')
            ));
        }
    }
}


// End of class.
