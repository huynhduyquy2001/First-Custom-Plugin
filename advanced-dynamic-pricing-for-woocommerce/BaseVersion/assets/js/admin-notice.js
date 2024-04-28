jQuery(document).ready(function ($) {
    $(document).on('click', function (e) {
        if ($(e.target).closest('.adp-notice .notice-dismiss').length) {
            var notice = $(e.target).closest('.adp-notice');
            var key = notice.attr('data-adp-notice-key');

            $.post(ajaxurl, {
                action: 'adp_notice_dismiss',
                key: key,
            });
        }
    });
});