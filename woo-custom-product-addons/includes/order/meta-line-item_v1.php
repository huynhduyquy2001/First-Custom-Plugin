<?php
if (is_array($meta_data) && count($meta_data)) {
    ?>
    <table>
        <tr>
            <th><?php _e('Options', 'woo-custom-product-addons') ?></th>
            <th><?php _e('Value', 'woo-custom-product-addons') ?></th>

            <th></th>
        </tr>

        <?php
        foreach ($meta_data as $k => $data) {
            if(!is_array($data)){
                continue;
            }
            if (in_array($data['type'], array('checkbox-group', 'select', 'radio-group')) && is_array($data['value'])) {
                $label_printed = false;
                foreach ($data['value'] as $l => $v) {
                    ?>
                    <tr class="item_wcpa">
                        <td class="name">
                            <?php
                            echo $label_printed ? '' : $data['label'];
                            $label_printed = true;
                            ?>
                        </td>

                        <td class="value" >
                            <div class="view">
                                <?php
                                if (isset($v['i'])) {
                                    echo '<strong>' . __('Label:', 'woo-custom-product-addons') . '</strong> ' . __($v['label'], 'woo-custom-product-addons') . '<br>';
                                    echo '<strong>' . __('Value:', 'woo-custom-product-addons') . '</strong> ' . $v['value'];
                                } else {
                                    echo $v;
                                }
                                ?>

                            </div>
                            <div class="edit" style="display: none;">
                                <?php
                                if (isset($v['i'])) {
                                        ?>
                                <?php echo '<strong>' . __('Label:', 'woo-custom-product-addons') . '</strong>'; ?>  <input type="text" name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k; ?>][<?php echo $l; ?>][label]"
                                        value="<?php echo $v['label'] ?>"> <br>
                                <?php echo '<strong>' . __('Value:', 'woo-custom-product-addons') . '</strong>'; ?> <input type="text" name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k; ?>][<?php echo $l; ?>][value]"
                                        value="<?php echo $v['value'] ?>">
                                        <?php
                                    } else {
                                        ?>
                                <input type="text" name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k; ?>][<?php echo $l; ?>]" value="<?php echo $v ?>">

                            <?php }
                                ?>


                            </div>
                        </td>

                        </td>

                        <td class="wc-order-edit-line-item" width="1%">
                            <div class = "wc-order-edit-line-item-actions edit" style="display: none;">
                                <a class="wcpa_delete-order-item tips" href="#" data-tip="<?php esc_attr_e('Delete item', 'woocommerce'); ?>"></a>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr class="item_wcpa">

                    <td class="name">

                        <?php
                        if ($data['type'] == 'hidden' && empty($data['label'])) {
                            echo $data['label'] . '[hidden]';
                        } else {
                            echo $data['label'];
                        }
                        ?>
                    </td>
                    <td class="value" >
                        <div class="view">

                            <?php
                            if ($data['type'] == 'color') {
                                echo '<span style = "color:' . $data['value'] . ';font-size: 20px;
            padding: 0;
    line-height: 0;">&#9632;</span>' . $data['value'];
                            } else {
                                echo nl2br($data['value']);
                            }
                            ?>
                        </div>

                        <div class="edit" style="display: none;">
                            <?php
                            if ($data['type'] == 'paragraph' || $data['type'] == 'header') {
                                echo $data['value'];
                                echo '<input type="hidden" 
                                       name="wcpa_meta[value][' . $item_id . '][' . $k . ']" 
                                       value="1">';
                            } else if($data['type'] == 'textarea' ) {
                                ?>
                                <textarea  name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k; ?>]" ><?php echo ($data['value']) ?></textarea>
                                <?php
                            }
                            else {
                                ?>
                                <input type="text" 
                                       name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k; ?>]" 
                                       value="<?php echo htmlspecialchars($data['value']) ?>">
                                       <?php
                                   }
                                   ?>

                        </div>
                    </td>


                    <td class = "wc-order-edit-line-item" width = "1%">
                        <div class = "wc-order-edit-line-item-actions edit" style="display: none;">
                            <a class="wcpa_delete-order-item tips" href="#" data-tip="<?php esc_attr_e('Delete item', 'woocommerce'); ?>"></a>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>


            <?php
        }
        ?>
        <tr>
            <!--   /* dummy field , it will help to iterate through all data for removing last item*/-->
        <input type="hidden" name="wcpa_meta[value][<?php echo $item_id; ?>][<?php echo $k + 99; ?>]" value="">

        </tr>
    </table>

    <?php
}



