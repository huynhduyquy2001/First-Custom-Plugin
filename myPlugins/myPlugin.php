<?php
/*
Plugin Name: My Plugin
Description: This is a simple custom plugin.
Version: 1.0
Author: Your Name
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
include_once (plugin_dir_path(__FILE__) . 'includes/function.php');
// Đặt mã của plugin ở đây
// Thêm trường tùy chỉnh vào trang thanh toán
add_filter('woocommerce_checkout_fields', 'custom_checkout_fields');
add_action('woocommerce_checkout_update_order_meta', 'custom_checkout_update_order_meta');
add_action('woocommerce_checkout_process', 'custom_checkout_process');
// Hiển thị thông tin trường tùy chỉnh trong trang quản lý đơn hàng
add_action('woocommerce_admin_order_data_after_order_details', 'display_custom_field_in_order_admin');
add_action('woocommerce_product_options_general_product_data', 'custom_add_sku_field');
add_action('woocommerce_process_product_meta', 'custom_save_sku_field');
add_action('product_cat_add_form_fields', 'custom_add_category_field');
add_action('created_term', 'custom_save_category_field');






