<?php
/**
 * User Settings Dashboard for Notiva.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Notiva_My_Account
{

    public function __construct()
    {
        // Shortcode
        add_shortcode('notiva_my_account', array($this, 'render_shortcode'));

        // WooCommerce Integration
        add_filter('woocommerce_account_menu_items', array($this, 'woo_add_endpoint'));
        add_filter('woocommerce_get_query_vars', array($this, 'woo_query_vars'));
        add_action('woocommerce_account_notiva-subscriptions_endpoint', array($this, 'render_dashboard_content'));

        // Ultimate Member Integration
        add_filter('um_profile_tabs', array($this, 'um_add_tab'), 1000);
        add_action('um_profile_content_notiva_subscriptions_default', array($this, 'um_tab_content'));
    }

    /**
     * Render the [notiva_my_account] shortcode
     */
    public function render_shortcode()
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p><a href="%s">%s</a></p>',
                esc_url(wp_login_url(get_permalink())),
                esc_html__('Log in to view subscriptions', 'notiva')
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
        $subscriptions = Notiva_DB::get_user_subscriptions($user_id);

        if (empty($subscriptions)) {
            echo '<p>' . esc_html__('You do not have any active subscriptions.', 'notiva') . '</p>';
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

        $options = get_option('notiva_general_settings');
        $post_tab_label = isset($options['post_tab_label']) && !empty($options['post_tab_label']) ? $options['post_tab_label'] : __('Subscribed Content', 'notiva');
        $post_column_label = isset($options['post_column_label']) && !empty($options['post_column_label']) ? $options['post_column_label'] : __('Title', 'notiva');
        $taxonomy_tab_label = isset($options['taxonomy_tab_label']) && !empty($options['taxonomy_tab_label']) ? $options['taxonomy_tab_label'] : __('Subscribed Categories', 'notiva');
        $taxonomy_column_label = isset($options['taxonomy_column_label']) && !empty($options['taxonomy_column_label']) ? $options['taxonomy_column_label'] : __('Category / Term', 'notiva');
        $show_type_column = !isset($options['show_type_column']) || (isset($options['show_type_column']) && $options['show_type_column'] == 1);
        $show_date_column = !isset($options['show_date_column']) || (isset($options['show_date_column']) && $options['show_date_column'] == 1);
?>
		<div class="notiva-dashboard">
			<div class="notiva-tabs">
				<?php if (!empty($grouped['post'])): ?>
					<div class="notiva-tab active" data-target="notiva-tab-posts"><?php echo esc_html($post_tab_label); ?></div>
				<?php
        endif; ?>
				<?php if (!empty($grouped['term'])): ?>
					<div class="notiva-tab <?php echo empty($grouped['post']) ? 'active' : ''; ?>" data-target="notiva-tab-terms"><?php echo esc_html($taxonomy_tab_label); ?></div>
				<?php
        endif; ?>
			</div>

			<?php if (!empty($grouped['post'])): ?>
			<div id="notiva-tab-posts" class="notiva-tab-content active">
				<table class="notiva-table shop_table shop_table_responsive cart wp-block-table">
					<thead>
						<tr>
							<th><?php echo esc_html($post_column_label); ?></th>
							<?php if ($show_type_column): ?><th><?php echo esc_html__('Type', 'notiva'); ?></th><?php
            endif; ?>
							<?php if ($show_date_column): ?><th><?php echo esc_html__('Subscribed On', 'notiva'); ?></th><?php
            endif; ?>
							<th><?php echo esc_html__('Actions', 'notiva'); ?></th>
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
								<div class="notiva-subscribe-wrapper">
									<button class="notiva-subscribe-btn notiva-subscribed" data-object-id="<?php echo esc_attr($sub->object_id); ?>" data-object-type="post">
										<?php echo esc_html__('Unsubscribe', 'notiva'); ?>
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
			<div id="notiva-tab-terms" class="notiva-tab-content <?php echo empty($grouped['post']) ? 'active' : ''; ?>">
				<table class="notiva-table shop_table shop_table_responsive cart wp-block-table">
					<thead>
						<tr>
							<th><?php echo esc_html($taxonomy_column_label); ?></th>
							<?php if ($show_date_column): ?><th><?php echo esc_html__('Subscribed On', 'notiva'); ?></th><?php
            endif; ?>
							<th><?php echo esc_html__('Actions', 'notiva'); ?></th>
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
								<div class="notiva-subscribe-wrapper">
									<button class="notiva-subscribe-btn notiva-subscribed" data-object-id="<?php echo esc_attr($sub->object_id); ?>" data-object-type="term">
										<?php echo esc_html__('Unsubscribe', 'notiva'); ?>
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
                $new_items['notiva-subscriptions'] = __('Post Subscriptions', 'notiva');
            }
        }
        // If orders wasn't found, append it
        if (!isset($new_items['notiva-subscriptions'])) {
            $new_items['notiva-subscriptions'] = __('Post Subscriptions', 'notiva');
        }
        return $new_items;
    }

    /**
     * WooCommerce: Register query var for the custom endpoint.
     */
    public function woo_query_vars($vars)
    {
        $vars['notiva-subscriptions'] = 'notiva-subscriptions';
        return $vars;
    }

    /**
     * Ultimate Member: Add Profile Tab.
     */
    public function um_add_tab($tabs)
    {
        $tabs['notiva_subscriptions'] = array(
            'name' => __('Subscriptions', 'notiva'),
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
