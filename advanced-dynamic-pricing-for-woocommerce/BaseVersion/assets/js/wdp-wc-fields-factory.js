jQuery(document).ready(function ($) {
    var $form = $('form.cart');

    if (!$('.variations_form').length) {
        $form.trigger('adp_variations_form');
    }

    $('[data-has_field_rules]').on('change', function () {
        var variation_id = $('[name="variation_id"]').val();
        var product_id = $form.find('[name="product_id"]').val();
        if (!product_id) {
            product_id = $form.find('[type=submit]').val();
        }

        $form.trigger('adp_found_variation', [{ product_id, variation_id }]);
    });

    $form.on('wdp_get_custom_data', function () {
        var customData = {};
        $('[data-has_pricing_rules]').each(function () {
            var name = $(this).attr('data-fkey'),
                value = $(this).val();

            customData[name] = value;
        });

        return customData;
    });
});