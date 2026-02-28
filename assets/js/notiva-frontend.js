jQuery(document).ready(function ($) {
    $('.notiva-subscribe-wrapper').on('click', '.notiva-subscribe-btn', function (e) {
        e.preventDefault();

        var $btn = $(this);

        if ($btn.hasClass('notiva-loading')) {
            return;
        }

        var object_id = $btn.data('object-id');
        var object_type = $btn.data('object-type');

        $btn.addClass('notiva-loading').text('Processing...');

        $.ajax({
            url: notiva_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'notiva_toggle_subscription',
                security: notiva_ajax_obj.nonce,
                object_id: object_id,
                object_type: object_type
            },
            success: function (response) {
                $btn.removeClass('notiva-loading');

                if (response.success) {
                    $btn.text(response.data.btn_text);
                    if (response.data.subscribed) {
                        $btn.removeClass('notiva-unsubscribed').addClass('notiva-subscribed');
                    } else {
                        $btn.removeClass('notiva-subscribed').addClass('notiva-unsubscribed');
                    }
                } else {
                    alert(response.data.message || 'An error occurred. Please try again.');
                    // Reset button text (basic fallback)
                    var btnText = $btn.hasClass('notiva-subscribed') ? 'Unsubscribe from Updates' : 'Subscribe to Updates';
                    $btn.text(btnText);
                }
            },
            error: function () {
                $btn.removeClass('notiva-loading');
                alert('Server error. Please try again.');
                var btnText = $btn.hasClass('notiva-subscribed') ? 'Unsubscribe from Updates' : 'Subscribe to Updates';
                $btn.text(btnText);
            }
        });
    });

    // Tab switching for the dashboard
    $('.notiva-dashboard').on('click', '.notiva-tab', function () {
        var $tab = $(this);
        var targetId = $tab.data('target');
        var $dashboard = $tab.closest('.notiva-dashboard');

        $dashboard.find('.notiva-tab').removeClass('active');
        $dashboard.find('.notiva-tab-content').removeClass('active');

        $tab.addClass('active');
        $('#' + targetId).addClass('active');
    });
});
