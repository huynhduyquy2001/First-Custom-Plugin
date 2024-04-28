<?php
defined('ABSPATH') or exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php _e('Hide rules wizard',
            'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <legend class="screen-reader-text">
                <span><?php _e('Hide rules wizard',
                        'advanced-dynamic-pricing-for-woocommerce') ?></span></legend>
            <label for="create_blank_rule">
                <input name="create_blank_rule" value="0" type="hidden">
                <input <?php checked($options['create_blank_rule']) ?>
                    name="create_blank_rule" id="create_blank_rule" type="checkbox">
            </label>
        </fieldset>
    </td>
</tr>
