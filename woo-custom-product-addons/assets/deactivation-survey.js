
// Deactivation Form
jQuery(document).ready(function () {
    jQuery(document).on('click','#deactivate-woo-custom-product-addons,#wcpa-aco_cancel,#wcpa-aco_skip', function (e) {
        var popup = document.getElementById('wcpa-aco-survey-form');
        var overlay = document.getElementById('wcpa-aco-survey-form-wrap');
        var openButton = document.getElementById(
            'deactivate-woo-custom-product-addons'
        );
        if (e.target.id == 'wcpa-aco-survey-form-wrap') {
            closePopup();
        }
        if (e.target === openButton) {
            e.preventDefault();
            popup.style.display = 'block';
            overlay.style.display = 'block';
        }
        if (e.target.id == 'wcpa-aco_skip') {
            e.preventDefault();
            var urlRedirect = document
                .querySelector('a#deactivate-woo-custom-product-addons')
                .getAttribute('href');
            window.location = urlRedirect;
        }
        if (e.target.id == 'wcpa-aco_cancel') {
            e.preventDefault();
            closePopup();
        }
    });

    function closePopup() {
        var popup = document.getElementById('wcpa-aco-survey-form');
        var overlay = document.getElementById('wcpa-aco-survey-form-wrap');
        popup.style.display = 'none';
        overlay.style.display = 'none';
        jQuery('#wcpa-aco-survey-form form')[0].reset();
        jQuery('#wcpa-aco-survey-form form .wcpa-aco-comments').hide();
        jQuery('#wcpa-aco-error').html('');
    }

    jQuery('#wcpa-aco-survey-form form').on('submit', function (e) {
        e.preventDefault();
        var valid = validate();
        if (valid) {
            var urlRedirect = document
                .querySelector('a#deactivate-woo-custom-product-addons')
                .getAttribute('href');
            var form = jQuery(this);
            var serializeArray = form.serializeArray();
            var actionUrl = 'https://feedback.acowebs.com/plugin.php';
            jQuery.ajax({
                type: 'post',
                url: actionUrl,
                data: serializeArray,
                contentType: 'application/javascript',
                dataType: 'jsonp',
                beforeSend: function () {
                    jQuery('#wcpa-aco_deactivate').prop('disabled', 'disabled');
                },
                success: function (data) {
                    window.location = urlRedirect;
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    window.location = urlRedirect;
                },
            });
        }
    });
    jQuery('#wcpa-aco-survey-form .wcpa-aco-comments textarea').on(
        'keyup',
        function () {
            validate();
        }
    );
    jQuery("#wcpa-aco-survey-form form input[type='radio']").on(
        'change',
        function () {
            validate();
            let val = jQuery(this).val();
            if (
                val == 'I found a bug' ||
                val == 'Plugin suddenly stopped working' ||
                val == 'Plugin broke my site' ||
                val == 'Other' ||
                val == "Plugin doesn't meets my requirement"
            ) {
                jQuery('#wcpa-aco-survey-form form .wcpa-aco-comments').show();
            } else {
                jQuery('#wcpa-aco-survey-form form .wcpa-aco-comments').hide();
            }
        }
    );
    function validate() {
        var error = '';
        var reason = jQuery(
            "#wcpa-aco-survey-form form input[name='Reason']:checked"
        ).val();
        if (!reason) {
            error += 'Please select your reason for deactivation';
        }
        if (
            error === '' &&
            (reason == 'I found a bug' ||
                reason == 'Plugin suddenly stopped working' ||
                reason == 'Plugin broke my site' ||
                reason == 'Other' ||
                reason == "Plugin doesn't meets my requirement")
        ) {
            var comments = jQuery(
                '#wcpa-aco-survey-form .wcpa-aco-comments textarea'
            ).val();
            if (comments.length <= 0) {
                error += 'Please specify <br /> (Provide email id as well, Our support team can reach you)';
            }
        }
        if (error !== '') {
            jQuery('#wcpa-aco-error').html(error);
            return false;
        }
        jQuery('#wcpa-aco-error').html('');
        return true;
    }
});
