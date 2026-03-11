jQuery(document).ready(function ($) {
    $('.content-alerts-subscriptions-subscribe-wrapper').on('click', '.content-alerts-subscriptions-subscribe-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);

        if ($btn.hasClass('content-alerts-subscriptions-loading')) {
            return;
        }

        var object_id = $btn.data('object-id');
        var object_type = $btn.data('object-type');

        $btn.addClass('content-alerts-subscriptions-loading').text('Processing...');

        $.ajax({
            url: content_alerts_subscriptions_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'content_alerts_subscriptions_toggle_subscription',
                security: content_alerts_subscriptions_ajax_obj.nonce,
                object_id: object_id,
                object_type: object_type
            },
            success: function (response) {
                $btn.removeClass('content-alerts-subscriptions-loading');

                if (response.success) {
                    $btn.text(response.data.btn_text);
                    if (response.data.subscribed) {
                        $btn.removeClass('content-alerts-subscriptions-unsubscribed').addClass('content-alerts-subscriptions-subscribed');
                    } else {
                        $btn.removeClass('content-alerts-subscriptions-subscribed').addClass('content-alerts-subscriptions-unsubscribed');
                    }
                } else {
                    alert(response.data.message || 'An error occurred. Please try again.');
                    // Reset button text (basic fallback)
                    var btnText = $btn.hasClass('content-alerts-subscriptions-subscribed') ? 'Unsubscribe from Updates' : 'Subscribe to Updates';
                    $btn.text(btnText);
                }
            },
            error: function () {
                $btn.removeClass('content-alerts-subscriptions-loading');
                alert('Server error. Please try again.');
                var btnText = $btn.hasClass('content-alerts-subscriptions-subscribed') ? 'Unsubscribe from Updates' : 'Subscribe to Updates';
                $btn.text(btnText);
            }
        });
    });

    // Tab switching for the dashboard
    $('.content-alerts-subscriptions-dashboard').on('click', '.content-alerts-subscriptions-tab', function () {
        var $tab = $(this);
        var targetId = $tab.data('target');
        var $dashboard = $tab.closest('.content-alerts-subscriptions-dashboard');

        $dashboard.find('.content-alerts-subscriptions-tab').removeClass('active');
        $dashboard.find('.content-alerts-subscriptions-tab-content').removeClass('active');

        $tab.addClass('active');
        $('#' + targetId).addClass('active');
    });
});
