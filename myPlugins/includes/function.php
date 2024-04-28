<?php
function custom_checkout_fields($fields)
{
    // Thêm trường tùy chỉnh vào phần thanh toán
    $fields['billing']['custom_field'] = array(
        'label' => __('Custom Field', 'woocommerce'),
        'placeholder' => _x('Enter custom field...', 'placeholder', 'woocommerce'),
        'required' => false,
        'clear' => false,
        'type' => 'text'
    );

    return $fields;
}




function custom_checkout_update_order_meta($order_id)
{
    // Kiểm tra xem trường góp ý đã được gửi hay chưa
    if (!empty($_POST['custom_field'])) {
        // Lấy dữ liệu từ trường góp ý và làm sạch nó trước khi lưu vào database
        $custom_field_value = sanitize_text_field($_POST['custom_field']);
        error_log("custom_field_value" . $custom_field_value);
        // Lưu dữ liệu vào meta của đơn hàng
        update_post_meta($order_id, 'custom_field', $custom_field_value);
    }
}



function custom_checkout_process()
{
    if (!isset($_POST['custom_field']) || empty($_POST['custom_field'])) {
        wc_add_notice(__('Vui lòng nhập thông tin custom field.', 'woocommerce'), 'error');
    }
}



function display_custom_field_in_order_admin($order)
{
    // Lấy giá trị của trường tùy chỉnh từ meta của đơn hàng
    $custom_field_value = get_post_meta($order->get_id(), 'custom_field', true);
    error_log("custom_field_value" . $custom_field_value);
    // Kiểm tra xem trường tùy chỉnh có dữ liệu không
    if (!empty($custom_field_value)) {
        // Hiển thị trường tùy chỉnh
        // Hiển thị trường tùy chỉnh với margin-top
        echo '<p class="form-field form-field-wide wc-customer-user">'; // Thay 20px bằng giá trị margin-top mong muốn
        echo '<p>' . __('Custom Field', 'your-text-domain') . '</p>';
        echo '<input type="text" name="custom_field" value="' . esc_attr($custom_field_value) . '" />';
        echo '</p>';
    }
}

//========================================================================================================================================================

// Thêm trường tùy chỉnh vào trang chỉnh sửa sản phẩm trong WooCommerce
function custom_add_sku_field()
{
    global $woocommerce, $post;

    echo '<div class="options_group">';

    // Trường SKU mới
    woocommerce_wp_text_input(
        array(
            'id' => '_custom_note',
            'label' => __('Ghi chú mới', 'woocommerce'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __('Nhập ghi chú mới mới cho sản phẩm.', 'woocommerce')
        )
    );

    echo '</div>';
}



// Lưu dữ liệu của trường tùy chỉnh khi sản phẩm được cập nhật hoặc tạo mới
function custom_save_sku_field($post_id)
{
    $custom_sku = isset($_POST['_custom_note']) ? sanitize_text_field($_POST['_custom_note']) : '';
    update_post_meta($post_id, '_custom_note', $custom_sku);
}




// Thêm trường tùy chỉnh vào trang thêm danh mục mới trong WooCommerce
function custom_add_category_field()
{
    ?>
    <div class="form-field">
        <label for="custom_category_description"><?php _e('Ghi chú tùy chỉnh', 'woocommerce'); ?></label>
        <textarea name="custom_category_description" id="custom_category_description" rows="5" cols="40"></textarea>
        <p class="description"><?php _e('Nhập mô tả tùy chỉnh cho danh mục.', 'woocommerce'); ?></p>
    </div>
    <?php
}



// Lưu dữ liệu của trường tùy chỉnh khi danh mục được thêm mới
function custom_save_category_field($term_id)
{
    if (isset($_POST['custom_category_description'])) {
        $custom_category_description = sanitize_text_field($_POST['custom_category_description']);
        update_term_meta($term_id, 'custom_category_description', $custom_category_description);
    }
}
