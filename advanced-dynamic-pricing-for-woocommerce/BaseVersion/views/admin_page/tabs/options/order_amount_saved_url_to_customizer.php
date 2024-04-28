<?php
defined('ABSPATH') or exit;
/**
 * @var string $amount_saved_customer_url
 */

?>

<tr valign="top">
    <th scope="row" class="titledesc"><?php _e('Amount saved', 'advanced-dynamic-pricing-for-woocommerce') ?></th>
    <td class="forminp forminp-checkbox">
        <fieldset>
            <label>
                <input <?php checked( $options['is_enable_backend_order_amount_saved'] ) ?> name="is_enable_backend_order_amount_saved" type="checkbox">
			    <?php _e( 'In the order backend', 'advanced-dynamic-pricing-for-woocommerce' ) ?>
            </label>
        </fieldset>
    </td>
</tr>
