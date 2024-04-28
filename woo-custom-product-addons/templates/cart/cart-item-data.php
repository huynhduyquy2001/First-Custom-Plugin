<?php


if ( ! defined('ABSPATH')) {
    exit;
}
?>
<ul class="wcpa_cart_meta">
    <?php
    foreach ($item_data as $data) :
        $class = isset($data['type']) ? ' wcpa_cart_item_'.$data['type'] : '';
        ?>
        <li class="wcpa_cart_meta_item<?php
        echo $class; ?>">
            <?php
            if( ('wcpa_empty_label'!==$data['key'])){

                echo '<p class="wcpa_cart_meta_item-label">'.wp_kses_post($data['key']).':</p>';
            }
            ?>
            <div class="wcpa_cart_meta_item-value">
                <?php
                echo wp_kses_post(wpautop($data['display'])); ?>
            </div>
        </li>
    <?php
    endforeach; ?>
</ul>
