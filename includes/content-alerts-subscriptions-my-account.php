<?php
/**
 * User Settings Dashboard for Content Alerts & Subscriptions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Content_Alerts_Subscriptions_My_Account
{

    public function __construct()
    {
        // Shortcode
        add_shortcode('content_alerts_subscriptions_my_account', array($this, 'render_shortcode'));

        // WooCommerce Integration
        add_filter('woocommerce_account_menu_items', array($this, 'woo_add_endpoint'));
        add_filter('woocommerce_get_query_vars', array($this, 'woo_query_vars'));
        add_action('woocommerce_account_content-alerts-subscriptions-subscriptions_endpoint', array($this, 'render_dashboard_content'));

        // Ultimate Member Integration
        add_filter('um_profile_tabs', array($this, 'um_add_tab'), 1000);
        add_action('um_profile_content_content_alerts_subscriptions_subscriptions_default', array($this, 'um_tab_content'));
    }

    /**
     * Render the [content_alerts_subscriptions_my_account] shortcode
     */
    public function render_shortcode()
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url(wp_login_url(get_permalink())),
                esc_html__('Log in to view subscriptions', 'content-alerts-subscriptions')
            );
        }

        ob_start();
        $this->render_dashboard_content();
        return ob_get_clean();
    }

    /**
     * The core dashboard content containing tabs and tables.
     */
    public function render_dashboard_content()
    {
        $user_id = get_current_user_id();
        $subscriptions = Content_Alerts_Subscriptions_DB::get_user_subscriptions($user_id);

        if (empty($subscriptions)) {
            echo '<p>' . esc_html__('You do not have any active subscriptions.', 'content-alerts-subscriptions') . '</p>';
            return;
        }

        // Organize by object type
        $grouped = array(
            'post' => array(),
            'term' => array(),
        );

        foreach ($subscriptions as $sub) {
            if (isset($grouped[$sub->object_type])) {
                $grouped[$sub->object_type][] = $sub;
            }
        }

        $options = get_option('content_alerts_subscriptions_general_settings');
        $post_tab_label = isset($options['post_tab_label']) && !empty($options['post_tab_label']) ? $options['post_tab_label'] : __('Subscribed Content', 'content-alerts-subscriptions');
        $post_column_label = isset($options['post_column_label']) && !empty($options['post_column_label']) ? $options['post_column_label'] : __('Title', 'content-alerts-subscriptions');
        $taxonomy_tab_label = isset($options['taxonomy_tab_label']) && !empty($options['taxonomy_tab_label']) ? $options['taxonomy_tab_label'] : __('Subscribed Categories', 'content-alerts-subscriptions');
        $taxonomy_column_label = isset($options['taxonomy_column_label']) && !empty($options['taxonomy_column_label']) ? $options['taxonomy_column_label'] : __('Category / Term', 'content-alerts-subscriptions');
        $show_type_column = !isset($options['show_type_column']) || (isset($options['show_type_column']) && $options['show_type_column'] == 1);
        $show_date_column = !isset($options['show_date_column']) || (isset($options['show_date_column']) && $options['show_date_column'] == 1);
?>
		<div class="content-alerts-subscriptions-dashboard">
			<div class="content-alerts-subscriptions-tabs">
				<?php if (!empty($grouped['post'])): ?>
					<div class="content-alerts-subscriptions-tab active" data-target="content-alerts-subscriptions-tab-posts"><?php echo esc_html($post_tab_label); ?></div>
				<?php
        endif; ?>
				<?php if (!empty($grouped['term'])): ?>
					<div class="content-alerts-subscriptions-tab <?php echo empty($grouped['post']) ? 'active' : ''; ?>" data-target="content-alerts-subscriptions-tab-terms"><?php echo esc_html($taxonomy_tab_label); ?></div>
				<?php
        endif; ?>
			</div>

			<?php if (!empty($grouped['post'])): ?>
			<div id="content-alerts-subscriptions-tab-posts" class="content-alerts-subscriptions-tab-content active">
				<table class="content-alerts-subscriptions-table shop_table shop_table_responsive cart wp-block-table">
					<thead>
						<tr>
							<th><?php echo esc_html($post_column_label); ?></th>
							<?php if ($show_type_column): ?><th><?php echo esc_html__('Type', 'content-alerts-subscriptions'); ?></th><?php
            endif; ?>
							<?php if ($show_date_column): ?><th><?php echo esc_html__('Subscribed On', 'content-alerts-subscriptions'); ?></th><?php
            endif; ?>
							<th><?php echo esc_html__('Actions', 'content-alerts-subscriptions'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($grouped['post'] as $sub):
                $post = get_post($sub->object_id);
                if (!$post)
                    continue;
?>
						<tr>
							<td><a href="<?php echo esc_url(get_permalink($sub->object_id)); ?>"><?php echo esc_html(get_the_title($sub->object_id)); ?></a></td>
							<?php if ($show_type_column): ?><td><?php echo esc_html(ucfirst($post->post_type)); ?></td><?php
                endif; ?>
							<?php if ($show_date_column): ?><td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub->created_at))); ?></td><?php
                endif; ?>
							<td>
								<div class="content-alerts-subscriptions-subscribe-wrapper">
									<button class="content-alerts-subscriptions-subscribe-btn content-alerts-subscriptions-subscribed" data-object-id="<?php echo esc_attr($sub->object_id); ?>" data-object-type="post">
										<?php echo esc_html__('Unsubscribe', 'content-alerts-subscriptions'); ?>
									</button>
								</div>
							</td>
						</tr>
						<?php
            endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
        endif; ?>

			<?php if (!empty($grouped['term'])): ?>
			<div id="content-alerts-subscriptions-tab-terms" class="content-alerts-subscriptions-tab-content <?php echo empty($grouped['post']) ? 'active' : ''; ?>">
				<table class="content-alerts-subscriptions-table shop_table shop_table_responsive cart wp-block-table">
					<thead>
						<tr>
							<th><?php echo esc_html($taxonomy_column_label); ?></th>
							<?php if ($show_date_column): ?><th><?php echo esc_html__('Subscribed On', 'content-alerts-subscriptions'); ?></th><?php
            endif; ?>
							<th><?php echo esc_html__('Actions', 'content-alerts-subscriptions'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($grouped['term'] as $sub):
                $term = get_term($sub->object_id);
                if (!$term || is_wp_error($term))
                    continue;
?>
						<tr>
							<td><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a></td>
							<?php if ($show_date_column): ?><td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($sub->created_at))); ?></td><?php
                endif; ?>
							<td>
								<div class="content-alerts-subscriptions-subscribe-wrapper">
									<button class="content-alerts-subscriptions-subscribe-btn content-alerts-subscriptions-subscribed" data-object-id="<?php echo esc_attr($sub->object_id); ?>" data-object-type="term">
										<?php echo esc_html__('Unsubscribe', 'content-alerts-subscriptions'); ?>
									</button>
								</div>
							</td>
						</tr>
						<?php
            endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php
        endif; ?>
		</div>
		<?php
    }

    /**
     * WooCommerce: Add the "Subscriptions" endpoint to the My Account menu.
     */
    public function woo_add_endpoint($items)
    {
        $new_items = array();
        foreach ($items as $key => $item) {
            $new_items[$key] = $item;
            if ('orders' === $key) {
                $new_items['content-alerts-subscriptions-subscriptions'] = __('Post Subscriptions', 'content-alerts-subscriptions');
            }
        }
        // If orders wasn't found, append it
        if (!isset($new_items['content-alerts-subscriptions-subscriptions'])) {
            $new_items['content-alerts-subscriptions-subscriptions'] = __('Post Subscriptions', 'content-alerts-subscriptions');
        }
        return $new_items;
    }

    /**
     * WooCommerce: Register query var for the custom endpoint.
     */
    public function woo_query_vars($vars)
    {
        $vars['content-alerts-subscriptions-subscriptions'] = 'content-alerts-subscriptions-subscriptions';
        return $vars;
    }

    /**
     * Ultimate Member: Add Profile Tab.
     */
    public function um_add_tab($tabs)
    {
        $tabs['content_alerts_subscriptions_subscriptions'] = array(
            'name' => __('Subscriptions', 'content-alerts-subscriptions'),
            'icon' => 'um-faicon-envelope',
            'custom' => true,
        );

        return $tabs;
    }

    /**
     * Ultimate Member: Profile Tab Content.
     */
    public function um_tab_content($args)
    {
        $this->render_dashboard_content();
    }
}


// End of class.
