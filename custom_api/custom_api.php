<?php
/*
Plugin Name: Custom API Plugin
Description: A custom API plugin for Test Api.
Version: 1.0
Author: Your Name
*/

// Đăng ký endpoint API
include_once (plugin_dir_path(__FILE__) . 'includes/Gift-post-type.php');
include_once (plugin_dir_path(__FILE__) . 'includes/Auth.php');
include_once (plugin_dir_path(__FILE__) . 'includes/User.php');
include_once (plugin_dir_path(__FILE__) . 'includes/Add-to-cart.php');
include_once (plugin_dir_path(__FILE__) . 'includes/Custom-woo-api.php');
include_once (plugin_dir_path(__FILE__) . 'includes/Product-api.php');

add_action('rest_api_init', 'custom_api_register_custom_post_type_endpoint');
add_filter('jwt_auth_token_before_sign', 'add_roles_to_jwt_token', 10, 2);
add_action('rest_api_init', 'register_settings_endpoint');
add_action('rest_api_init', 'register_api_logger');
add_action('rest_api_init', 'custom_cart_api_endpoint');
add_action('rest_api_init', 'custom_products_api_endpoint');
function custom_api_register_custom_post_type_endpoint()
{
    register_rest_route(
        'custom/v1',
        '/gift-post-type',
        array(
            'methods' => 'GET',
            'callback' => 'custom_api_get_gift_post_type',
            //'permission_callback' => 'check_user_role_admin',
        )
    );

    register_rest_route(
        'custom/v1',
        '/gift-post-type/create',
        array(
            'methods' => 'POST',
            'callback' => 'custom_api_create_gift_post',
            'permission_callback' => 'check_user_role_admin',
        )
    );
    register_rest_route(
        'custom/v1',
        '/gift-post-type/update/(?P<id>\d+)',  // Thay đổi route tùy thuộc vào cấu trúc URL bạn muốn
        array(
            'methods' => 'PUT',
            'callback' => 'custom_api_update_gift_post',
            'permission_callback' => 'check_user_role_admin',

        )
    );
    register_rest_route(
        'custom/v1',
        '/gift-post-type/delete/(?P<id>\d+)',  // Route với ID là một tham số động
        array(
            'methods' => 'DELETE',
            'callback' => 'custom_api_delete_gift_post',
            'permission_callback' => 'check_user_role_admin',
        )
    );
    // Đăng ký endpoint API
    register_rest_route(
        'custom/v1',
        '/decode_jwt_api',
        array(
            'methods' => 'GET',
            'callback' => 'check_user_role_admin'
        )
    );
    register_rest_route(
        'custom/v1',
        '/user-preferences/(?P<id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'custom_api_get_user_preferences',
        )
    );
    register_rest_route(
        'custom/v1',
        '/redeemable-products',
        array(
            'methods' => 'GET',
            'callback' => 'get_redeemable_products',
        )
    );
    register_rest_route(
        'custom/v1',
        '/accept-gift-request/(?P<gift_post_id>\d+)',
        array(
            'methods' => 'POST',
            'callback' => 'custom_api_accept_gift_request',
        )
    );
    register_rest_route(
        'custom/v1',
        '/reject-gift-request/(?P<gift_post_id>\d+)',
        array(
            'methods' => 'POST',
            'callback' => 'custom_api_reject_gift_request',
        )
    );
    register_rest_route(
        'custom/v1',
        '/referrer-post-type',
        array(
            'methods' => 'GET',
            'callback' => 'custom_api_get_referrer_post_type',
            'permission_callback' => 'check_user_role_admin',
        )
    );
    register_rest_route(
        'custom/v1',
        '/referrer-post-type/(?P<ref_id>\d+)',
        array(
            'methods' => 'GET',
            'callback' => 'custom_api_get_referrer_post_type',
            'permission_callback' => 'check_user_role_admin',
        )
    );
}
function register_api_logger()
{
    // Hook vào trước khi xử lý request API
    add_filter('rest_pre_dispatch', 'log_api_request', 10, 3);
}
// ======register api add_to_cart===========
function custom_cart_api_endpoint()
{
    register_rest_route(
        'custom/v1',
        '/add-to-cart',
        array(
            'methods' => 'POST',
            'callback' => 'custom_api_add_to_cart',
            'permission_callback' => 'custom_verify_jwt_token',
        )
    );
}
function custom_api_get_referrer_post_type($request)
{
    $params = $request->get_params();
    if (empty($params['ref_id'])) {
        // Thiết lập các tham số mặc định
        $args = array(
            'post_type' => 'referrer-post-type',
            'posts_per_page' => -1,
        );
    } else {
        $args = array(
            'post_type' => 'referrer-post-type',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'ref_id',
                    'value' => $params['ref_id'],
                    'compare' => '=',
                )
            )
        );
    }

    // Truy vấn dữ liệu từ Custom Post Type
    $custom_posts = new WP_Query($args);

    // Xử lý dữ liệu trước khi trả về (nếu cần)
    $formatted_posts = array();
    if ($custom_posts->have_posts()) {
        while ($custom_posts->have_posts()) {
            $custom_posts->the_post();

            // Lấy ID của bài đăng
            $post_id = get_the_ID();

            // Lấy thông tin về người được giới thiệu
            $receiver_id = get_post_meta($post_id, 'receiver_id', true);
            $receiver_info = get_userdata($receiver_id);

            // Tính tổng giá trị của các giao dịch mà người được giới thiệu đã thực hiện
            $transactions = wc_get_orders(
                array(
                    'customer' => $receiver_id,
                    'status' => 'completed', // chỉ tính các đơn hàng đã hoàn thành
                )
            );
            $total_spent = 0;
            foreach ($transactions as $transaction) {
                $total_spent += $transaction->get_total();
            }

            // Thêm thông tin vào mảng
            $formatted_posts[] = array(
                'receiver_id' => $receiver_id,
                'receiver_name' => $receiver_info->display_name,
                'receiver_email' => $receiver_info->user_email,
                'total_spent' => $total_spent,
                // Thêm các trường dữ liệu khác nếu cần
            );
        }
    }

    // Reset query
    wp_reset_postdata();

    // Trả về dữ liệu dưới dạng JSON
    return rest_ensure_response($formatted_posts);
}
// Trước khi gọi endpoint /wp-json/wc/v3
add_action('rest_api_init', function () {
    // Kiểm tra quyền truy cập trước khi xử lý yêu cầu
    add_filter('rest_pre_dispatch', function ($result, $server, $request) {
        // Kiểm tra nếu yêu cầu gọi endpoint /wp-json/wc/v3/orders
        if (strpos($request->get_route(), '/wc/v3/orders') !== false) {
            // Gọi hàm middleware-like để kiểm tra quyền truy cập và truyền tham số $request
            if (!check_access_endpoint($request)) {
                // Nếu không phải admin, lấy danh sách đơn hàng của người dùng hiện tại
                $orders = wc_get_orders(
                    array (
                        'customer' => get_user_id_by_jwt($request), // Lọc theo ID của người dùng hiện tại
                    )
                );

                // Chuyển đổi danh sách đơn hàng thành dạng mảng để trả về
                $order_data = array ();
                foreach ($orders as $order) {
                    $order_data[] = $order->get_data();
                }

                // Trả về danh sách đơn hàng của người dùng hiện tại dưới dạng một mảng JSON
                return $order_data;
            }
        }
        return $result;
    }, 10, 3);
});



// Đăng ký một custom REST API endpoint
add_action('rest_api_init', function () {
    register_rest_route(
        'custom/v1',
        '/share-facebook',
        array (
            'methods' => 'POST',
            'callback' => 'share_to_facebook',
        )
    );
});

// Hàm callback để xử lý yêu cầu API
function share_to_facebook($request)
{
    $message = 'Nội dung bài viết bạn muốn chia sẻ';
    $link = 'https://woocommerce.dev-tn.com/san-pham/card-man-hinh-asus-dual-rtx-2060-6gb-gddr6-6g-evo';
    $access_token = 'EAAZAsEqMspuQBO1l8ZAIjz0j48XIdaqdsk5nZAcWVcnvZCNSUM0pZBCEcbiQ2b0ZB4lq1ch1uByGvM9kvNauGS0IXjZABZAW3WvGCFUchBv58X58zWU3ko0YZBIQFRyPajgYZCo3gCnDhHCEl9Vtgqe4V45jEzJ2pBPpfZCjA2slGjThwOBLoSBwrUjOKqeuKk1yEEMKDGMzLeS5bZBABudIdPx8LkxGLoJZAsqaWx6FjNnidNhecjX1JSA1j';


    // Gửi yêu cầu API đến Facebook
    $facebook_api_url = 'https://graph.facebook.com/me/feed';
    $args = array(
        'message' => $message,
        'link' => $link,
        'access_token' => $access_token,
    );

    $response = wp_remote_post(
        $facebook_api_url,
        array(
            'method' => 'POST',
            'body' => $args,
        )
    );

    // Kiểm tra phản hồi từ Facebook và trả về kết quả
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        return new WP_REST_Response(array('success' => true, 'message' => 'Bài viết đã được chia sẻ thành công lên Facebook.'), 200);
    } else {
        return new WP_Error('share_failed', 'Có lỗi xảy ra khi chia sẻ bài viết lên Facebook.', array('status' => 500));
    }
}

function custom_products_api_endpoint()
{
    register_rest_route(
        'custom/v1',
        '/get-products',
        array(
            'methods' => 'GET',
            'callback' => 'custom_api_get_products',
            //'permission_callback' => 'custom_verify_jwt_token',
        )
    );
}













