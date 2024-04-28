<?php
function custom_api_add_to_cart($request)
{

    if (!$request instanceof WP_REST_Request) {
        return new WP_Error('invalid_request', 'Invalid REST request.', array('status' => 400));
    }

    // Lấy token từ header Authorization
    $jwt_token = $request->get_header('Authorization');

    // Kiểm tra xem token có tồn tại không
    if (!$jwt_token) {
        return new WP_Error('jwt_missing', 'Authorization header with JWT token is missing', array('status' => 401));
    }

    // Bỏ tiền tố "Bearer" ra khỏi chuỗi token
    $jwt_token = str_replace('Bearer ', '', $jwt_token);

    // Thay YOUR_SECRET_KEY_HERE bằng khóa bí mật thực tế của bạn
    $secret_key = 'your-top-secrect-key';

    // Gọi hàm decode_jwt để giải mã token
    $decoded_data = decode_jwt($jwt_token, $secret_key);
    $user_id = $decoded_data['data']['user']['id'];
    // Kiểm tra xem giải mã có thành công không
    if (!$decoded_data) {
        return new WP_Error('jwt_invalid', 'JWT token is invalid or expired', array('status' => 401));
    }



    // Kiểm tra xem WooCommerce đã được kích hoạt không
    if (!class_exists('WooCommerce')) {
        return new WP_REST_Response(array('status' => 'error', 'message' => 'WooCommerce không được kích hoạt'), 500);
    }

    $product_id = $request['product_id'];
    $quantity = $request['quantity'];


    if (!$user_id) {
        return new WP_REST_Response(array('status' => 'error', 'message' => 'Người dùng chưa đăng nhập'), 403);
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        return new WP_REST_Response(array('status' => 'error', 'message' => 'Sản phẩm không được tìm thấy'), 404);
    }

    // Lấy thông tin giỏ hàng của người dùng từ transient
    $user_cart_key = 'custom_cart_items_' . $user_id;
    $user_cart_items = get_transient($user_cart_key);

    // Khởi tạo một mảng giỏ hàng mới nếu không tồn tại
    if (!$user_cart_items) {
        $user_cart_items = array();
    }

    // Kiểm tra xem sản phẩm đã tồn tại trong giỏ hàng của người dùng chưa
    if (isset($user_cart_items[$product_id])) {
        // Nếu đã tồn tại, cập nhật số lượng và thông tin sản phẩm
        $user_cart_items[$product_id]['quantity'] += $quantity;
    } else {
        // Nếu chưa tồn tại, thêm mới vào giỏ hàng của người dùng
        $product_data = wc_get_product($product_id);

        if ($product_data) {
            $product_name = $product_data->get_name();
            $product_image = $product_data->get_image('thumbnail'); // Link ảnh sản phẩm
            $product_price = $product_data->get_price(); // Giá sản phẩm

            // Thêm mới sản phẩm vào giỏ hàng
            $user_cart_items[$product_id] = array(
                'product_id' => $product_id,
                'name' => $product_name,
                'image' => $product_image,
                'price' => $product_price,
                'quantity' => $quantity
            );
        } else {
            // Nếu không tìm thấy thông tin sản phẩm, ghi nhận lỗi
            return new WP_REST_Response(array('status' => 'error', 'message' => 'Không thể tìm thấy thông tin sản phẩm'), 404);
        }
    }

    // Lưu thông tin giỏ hàng của người dùng vào transient
    set_transient($user_cart_key, $user_cart_items, 30 * DAY_IN_SECONDS); // Thời gian sống transient: 30 ngày

    // Trả về thông tin giỏ hàng của người dùng dưới dạng JSON
    return rest_ensure_response($user_cart_items);

}