<?php
/*
Plugin Name: wc_order_status_changes
Description: This is a simple custom plugin.
Version: 1.0
Author: Your Name
*/

// Định nghĩa các hàm xử lý trạng thái đơn hàng
function mysite_pending($order_id)
{
    send_order_email_notification($order_id, 'pending');
}

function mysite_failed($order_id)
{
    send_order_email_notification($order_id, 'failed');
}

function mysite_hold($order_id)
{
    send_order_email_notification($order_id, 'on-hold');
}

function mysite_processing($order_id)
{
    send_order_email_notification($order_id, 'processing');
}

function mysite_completed($order_id)
{
    send_order_email_notification($order_id, 'completed');
}

function mysite_refunded($order_id)
{
    send_order_email_notification($order_id, 'refunded');
}

function mysite_cancelled($order_id)
{
    send_order_email_notification($order_id, 'cancelled');
}

// Gắn các hàm xử lý vào các hook của WooCommerce
add_action('woocommerce_order_status_pending', 'mysite_pending');
add_action('woocommerce_order_status_failed', 'mysite_failed');
add_action('woocommerce_order_status_on-hold', 'mysite_hold');
add_action('woocommerce_order_status_processing', 'mysite_processing');
add_action('woocommerce_order_status_completed', 'mysite_completed');
add_action('woocommerce_order_status_refunded', 'mysite_refunded');
add_action('woocommerce_order_status_cancelled', 'mysite_cancelled');

// Hàm gửi email thông báo khi trạng thái của đơn hàng thay đổi
function send_order_email_notification($order_id, $status)
{
    $order = wc_get_order($order_id);
    $customer_email = $order->get_billing_email();

    // Kiểm tra xem địa chỉ email của khách hàng có tồn tại không
    if ($customer_email) {
        $subject = sprintf(__('Thông báo: Trạng thái đơn hàng #%s đã thay đổi', 'your-text-domain'), $order_id);
        $message = sprintf(__('Chào bạn, Đơn hàng #%s của bạn đã được cập nhật. Trạng thái mới của đơn hàng là: %s', 'your-text-domain'), $order_id, $status);

        // Gửi email
        if (wp_mail($customer_email, $subject, $message)) {
            error_log("Đã gửi mail");
        } else {
            error_log("Không gửi được");
        }
    }
}
