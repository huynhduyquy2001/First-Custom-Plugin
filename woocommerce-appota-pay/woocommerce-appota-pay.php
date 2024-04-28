<?php
/*
 * Plugin Name: WooCommerce Appota Pay Gateway
 * Plugin URI: https://appotapay.com/
 * Description: Add a payment method to WooCommerce using AppotaPay Gateway.
 * Author: nghiapm
 * Author URI: https://appotapay.com/
 * Version: 1.0
 * Text Domain: woocommerce-gateway-appotapay
 */


require_once __DIR__ . '/vendor/autoload.php';
use Firebase\JWT\JWT;
require plugin_dir_path( __FILE__ ) . 'includes/ErrorCodePayment.php';
require  plugin_dir_path( __FILE__ ) .'template/RenderHtml.php';
require plugin_dir_path( __FILE__ ) . 'includes/AppotaPay.php';
require plugin_dir_path( __FILE__ ) .'../../../wp-includes/version.php';
//write_log("wordpres version = $wp_version");


/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

add_filter( 'woocommerce_payment_gateways', 'misha_add_gateway_class' );
function misha_add_gateway_class( $gateways ) {
    $gateways[] = 'WC_Appota_Gateway'; // your class name is here
    return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'appota_init_gateway_class' );




add_action( 'rest_api_init', 'appota_pay_rest_routes' );
// Function to register our new routes from the controller.
function appota_pay_rest_routes() {
    $controller = new WC_Appota_Gateway();
    $controller->register_routes();
}

define('C_WC_SITE_URL',rtrim(plugin_dir_url(__FILE__),'/'));






function appota_init_gateway_class() {
    class WC_Appota_Gateway extends WC_Payment_Gateway {

        const PREFIX_REST_WPRESS = '/wp-json/';

       // call api pay with bank
        private $status_order_pay_with_bank_success;
        private $rule_status_order_pay_with_bank_success;
//        private $status_order_pay_with_bank_false;
//        private $rule_status_order_pay_with_bank_false;
        private $is_emptly_cart_pay_with_bank_success;
//        private $is_emptly_cart_pay_with_bank_false;
        private $is_reduce_stock_pay_with_bank_success;
        private $is_reduce_stock_pay_with_bank_false;

        // call url redirect
        private  $status_order_when_call_redirect_success;
        private  $rule_status_order_when_call_redirect_success;
        private  $status_order_when_call_redirect_false;
        private  $rule_status_order_when_call_redirect_false;
        private  $is_emptly_cart_when_call_redirect_success;
        private  $is_emptly_cart_when_call_redirect_false;
        private  $is_reduce_stock_when_call_redirect_success;
        private  $is_reduce_stock_when_call_redirect_false;

        private $secret_key;
//        private static $secret_key_s;
        private $partner_code;
        private $api_key;
        private  $sandbox_mode;
        private $namespace = 'appota/v1';

        /**
         * Icon URL, set in constructor
         * @var string
         */
        public $icon;

        /**
         * Class constructor, more about it in Step 3
         */
        public function __construct() {
            global $woocommerce;

            $this->id = 'appota';
            $this->has_fields = false;
            $this->method_title = __('APPOTAPAY', 'woocommerce');
            $this->liveurl = 'https://payment.dev.appotapay.com';
            $this->testurl = 'https://payment.appotapay.com';
            $this->icon =C_WC_SITE_URL. '/img/appotapay_payment.png';

            //load the setting
            $this->init_form_fields();
            $this->init_settings();

            //Load admin config
            $this->title = $this->get_option('title');
            $this->api_key = $this->get_option('api_key');
            $this->partner_code = $this->get_option('partner_code');
            $this->secret_key = $this->get_option('secret_key');
            $this->description = $this->get_option('description');
            $this->enabled = $this->get_option('enabled');

            // status order,stock,cart

            //status when call api pay with bank
            $this->status_order_pay_with_bank_success = $this->get_option('status_order_pay_with_bank_success');
            $this->rule_status_order_pay_with_bank_success = $this->convert_string_to_array(',',$this->get_option('rule_status_order_pay_with_bank_success'));

            /** status !=200, error sign, don't have anything action  */
//            $this->status_order_pay_with_bank_false =  $this->get_option('status_order_pay_with_bank_false');
//            $this->rule_status_order_pay_with_bank_false = $this->convert_string_to_array(',',$this->get_option('rule_status_order_pay_with_bank_false'));
            $this->is_emptly_cart_pay_with_bank_success = $this->convert_bool($this->get_option('is_emptly_cart_pay_with_bank_success'));
//            $this->is_emptly_cart_pay_with_bank_false = $this->convert_bool($this->get_option('is_emptly_cart_pay_with_bank_false'));
            $this->is_reduce_stock_pay_with_bank_success = $this->convert_bool($this->get_option('is_reduce_stock_pay_with_bank_success'));
//            $this->is_reduce_stock_pay_with_bank_false = $this->convert_bool($this->get_option('is_reduce_stock_pay_with_bank_false'));

            // status when call api redirect
            $this->status_order_when_call_redirect_success = $this->get_option('status_order_when_call_redirect_success');
            $this->rule_status_order_when_call_redirect_success = $this->convert_string_to_array(',',$this->get_option('rule_status_order_when_call_redirect_success'));
            $this->status_order_when_call_redirect_false =  $this->get_option('status_order_when_call_redirect_false');
            $this->rule_status_order_when_call_redirect_false = $this->convert_string_to_array(',', $this->get_option('rule_status_order_when_call_redirect_false'));
            $this->is_emptly_cart_when_call_redirect_success = $this->convert_bool($this->get_option('is_emptly_cart_when_call_redirect_success'));
            $this->is_emptly_cart_when_call_redirect_false = $this->convert_bool($this->get_option('is_emptly_cart_when_call_redirect_false'));
            $this->is_reduce_stock_when_call_redirect_success = $this->convert_bool($this->get_option('is_reduce_stock_when_call_redirect_success'));
            $this->is_reduce_stock_when_call_redirect_false = $this->convert_bool($this->get_option('is_reduce_stock_when_call_redirect_false'));

            $this->sandbox_mode = $this->convert_bool($this->get_option('sandbox_mode'));





//            write_log('info config auth user : ');
//            write_log(" title = $this->title, api_key = $this->api_key, partner_code = $this->partner_code,secret_key =  $this->secret_key, enabled =  $this->enabled, description=  $this->description");
//
//            write_log('info config status order: ');
//            write_log(" status_order_pay_with_bank_success = ".json_encode($this->status_order_pay_with_bank_success)." rule_status_order_pay_with_bank_success =".json_encode($this->rule_status_order_pay_with_bank_success).
//                            "is_emptly_cart_pay_with_bank_success = $this->is_emptly_cart_pay_with_bank_success, is_reduce_stock_pay_with_bank_success = $this->is_reduce_stock_pay_with_bank_success".
//
//                             "status_order_when_call_redirect_success =". json_encode($this->status_order_when_call_redirect_success). "rule_status_order_when_call_redirect_success =". json_encode($this->rule_status_order_when_call_redirect_success). "status_order_when_call_redirect_false =". json_encode($this->status_order_when_call_redirect_false). "rule_status_order_when_call_redirect_false =". json_encode($this->rule_status_order_when_call_redirect_false).
//
//                              "is_emptly_cart_when_call_redirect_success =". $this->is_emptly_cart_when_call_redirect_success.", is_emptly_cart_when_call_redirect_false =". $this->is_emptly_cart_when_call_redirect_false.", is_reduce_stock_when_call_redirect_success =". $this->is_reduce_stock_when_call_redirect_success.", is_reduce_stock_when_call_redirect_false =". $this->is_reduce_stock_when_call_redirect_false
//
//                         ."sendboxMode = ".boolval($this->send_box_mode) );



            if(!$this->is_valid_for_use()){
                write_log('validate false: disnable Payment');
                $this->enabled = 'no';
            }

            // list action for handle payment
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        }

        // Register our routes.
        public function register_routes() {
            register_rest_route( $this->namespace, 'handle-request-pay', array(
                // Here we register the readable endpoint for collections.
                array(
                    'methods'   => 'GET',
                    'callback'  => array($this,'handleReponsePay'),
                    // Register our schema callback.
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                ),
            ) );
            register_rest_route( $this->namespace, 'false-request-pay', array(
                // Here we register the readable endpoint for collections.
                array(
                    'methods'   => 'GET',
                    'callback'  => array($this,'handleFalseRequestPay'),
                    // Register our schema callback.
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                ),
            ) );

            register_rest_route( $this->namespace, 'IPN-request-pay', array(
                // Here we register the readable endpoint for collections.
                array(
                    'methods' => 'POST',
                    'callback'  => array($this,'handleIPNRequestPay'),
                    // Register our schema callback.
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                ),
            ) );


        }

        public function payment_fields(){
            if ( $this->description ) {
                // you can instructions for test mode, I mean test card numbers etc.
                if ( $this->sandbox_mode ) {
                    $this->description  = trim( $this->description );
                }
                // display the description with <p> tags etc.
                echo wpautop( wp_kses_post( $this->description ) );
            }
            ?>
            <style type="text/css">
                div.payment_method_appota {
                    display: block !important;
                }
                li.payment_method_appota label[for=payment_method_appota]
                {
                    float: left;
                    display: block;
                    background: #f5f5f5;
                    width: 100%;
                    margin-left: 0;
                }
                li.payment_method_appota label[for=payment_method_appota] img
                {
                   width: 100%;
                }
            </style>
            <?php
        }

        /**
         * Check permissions for the posts.
         *
         * @param WP_REST_Request $request Current request.
         */
        public function get_items_permissions_check( $request ) {
            return true;
        }

        // Sets up the proper HTTP status code for authorization.
        public function authorization_status_code() {

            $status = 401;

            if ( is_user_logged_in() ) {
                $status = 403;
            }

            return $status;
        }

        /*
        * We're processing the payments here
        */
        public function process_payment( $order_id ) {
            write_log('process_payment');
            $result = $this->payWithbank($order_id);

            //handle and redirect
            if (isset($result['errorCode']) && $result['errorCode'] === ErrorCodePayment::SUCCESS_CODE) {
                write_log('start callbackUrl to Pay');
                $this->updateStatusOrderWhenCallPayWithBankSuccess($order_id);
                $appotaPayRedirectURL = $result['paymentUrl'];
                return array(
                    'result' => 'success',
                    'redirect' => $appotaPayRedirectURL
                );
            }  else {
                write_log('------false flase flase---');
                /**  don't action anything why false format, sign,... , system payment don;t save anything info */
                return array(
                    'result' => 'success',
                    'redirect' => get_site_url(null,'wp-json/'.'appota/v1/false-request-pay')
                );
            }
        }

        /**
         * @param $orderId
         */
        public function updateStatusOrderWhenCallPayWithBankSuccess($orderId){
            write_log('start updateStatusOrderWhenCallPayWithBankSuccess');
            if(empty($this->status_order_pay_with_bank_success)) {
               return false;
            }
            if(empty($this->rule_status_order_pay_with_bank_success)) {
                return false;
            }
            $this->changeStatusOrder($orderId,$this->status_order_pay_with_bank_success,$this->rule_status_order_pay_with_bank_success,'change status',$this->is_reduce_stock_pay_with_bank_success,$this->is_emptly_cart_pay_with_bank_success);
        }


//        /**
//         * @param $orderId
//         */
//        public function updateStatusOrderWhenCallPayWithBankFalse($orderId){
//            write_log('infunction updateStatusOrderWhenCallPayWithBankFalse');
//            if(empty($this->status_order_pay_with_bank_false)) {
//                exit(1);
//            }
//            if(empty($this->rule_status_order_pay_with_bank_false)) {
//                exit(1);
//            }
//            write_log('start change status ');
//            self::changeStatusOrder($orderId,$this->status_order_pay_with_bank_false,$this->rule_status_order_pay_with_bank_false,'change status',$this->is_reduce_stock_pay_with_bank_false,$this->is_emptly_cart_pay_with_bank_false);
//            write_log('end updateStatusOrderWhenCallPayWithBankFalse');
//        }





        /**
         * Plugin options, we deal with it in Step 3 too
         */
        public function init_form_fields(){
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Sử dụng phương thức', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Tiêu đề', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Tiêu đề của phương thức thanh toán bạn muốn hiển thị cho người dùng.', 'woocommerce'),
                    'default' => __('AppotaPay', 'woocommerce'),
                    'desc_tip' => true,
                ),
                'description' => array(
                    'title' => __('Mô tả phương thức thanh toán', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('Mô tả của phương thức thanh toán bạn muốn hiển thị cho người dùng.', 'woocommerce'),
                    'default' => __('Thanh toán với AppotaPay. Đảm bảo an toàn tuyệt đối cho mọi giao dịch', 'woocommerce')
                ),

//                'language' => array(
//                    'title' => __('Ngôn ngữ (language) vi or en', 'woocommerce'),
//                    'type' => 'text',
//                    'description' => 'Ngôn ngữ (language)',
//                    'default' => 'vi',
//                    'desc_tip' => true,
//                ),


                'account_config' => array(
                    'title' => __('Cấu hình tài khoản', 'woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'api_key' => array(
                    'title' => __('API_KEY', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Api Key được cung cấp khi bạn đăng ký với Appota Pay', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,

                ),
                'partner_code' => array(
                    'title' => __('PARTNER_CODE', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Mã partner code được AppoPay cấp khi bạn đăng ký tích hợp website.', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,

                ),
                'secret_key' => array(
                    'title' => __('SECRET_KEY', 'woocommerce'),
                    'type' => 'password',
                    'description' => __('Mã bảo mật khi bạn đăng ký với Appota Pay', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,

                ),
                'sandbox_mode' => array(
                    'title' => __('Sandbox Mode', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Sử dụng AppotaPay Sandbox', 'woocommerce'),
                    'default' => 'yes',
                    'description' => 'AppotaPay Sandbox được sử đụng kiểm tra phương thức thanh toán.',
                ),
                'process_pay_config' => array(
                    'title' => __('Cấu hình process payment', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'Vui lòng không chỉnh sửa bất kỳ thông tin nào trong mục này nếu không hiểu về proccess payment của woocomerce và 
                                       không hiểu về cổng Appotal Pay'.'</br>'.
                                       'Thông số config default đã đủ để chạy được lưu trình thanh toán, chỉ chỉnh sửa khi hiểu về lưu trình thanh toán.'
                                       ,
                ),
                'process_pay_info' => array(
                    'title' => __('Mô tả cơ bản về Appota Pay', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'Lưu trình thanh toán ở Appota Pay gồm 2 bước, ở mỗi bước có một số trạng thái cart, order, stock được mở cho admin chỉnh sửa. '.'</br>'.
                        'Bước 1: Call api pay with bank: /api/v1/orders/payment/bank'. '</br>'.
                        'Bước 2: Call url payment  và xử lý kết quả trong url redirect hoặc IPN'.'</br>'.
                        'Thông tin chi tiết tham khảo api doc của Appota Pay'
                ,
                ),


                /** start pay step 1  */

                'start_pay_1' => array(
                    'title' => __('1) Call Api: /api/v1/orders/payment/bank', 'woocommerce'),
                    'type' => 'title',
                    'description' => '',
                ),
                'status_order_pay_with_bank_success' => array(
                    'title' => __('Status Order If Succees', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => '',
                    'description' => 'Cập nhập trạng thái order sau khi call url bước 1 và status success. Vui lòng chỉ nhập status order tồn tại trong hệ thống, nếu status order không tồn tại, sẽ mặc định dùng 
                                      Status default.Để trống thì không update thông tin gì.',
                ),
                'rule_status_order_pay_with_bank_success' => array(
                    'title' => __('Danh sách status order được phép thay đổi nếu thành công', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => '',
                    'description' => 'Liệt kê list status order được phép đổi trạng thái khi call url bước 1 thành công. Để trống đồng nghĩa với không cho phép update',
                ),

//                'status_order_pay_with_bank_false' => array(
//                    'title' => __('Status Order If False', 'woocommerce'),
//                    'type' => 'text',
//                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
//                    'default' => 'pending',
//                    'description' => 'Vui lòng chỉ nhập status order tồn tại trong hệ thống, nếu status order không tồn tại, sẽ mặc định dùng
//                                      Status default. Để trống thì không update thông tin gì.',
//                ),
//
//                'rule_status_order_pay_with_bank_false' => array(
//                    'title' => __('Danh sách status order được phép thay đổi nếu thất bại', 'woocommerce'),
//                    'type' => 'text',
//                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
//                    'default' => 'pending,pending',
//                    'description' => 'Liệt kê list status order được phép đổi trạng thái khi payment thất bại. Để trống đồng nghĩa với không cho phép update',
//                ),

                'is_emptly_cart_pay_with_bank_success' => array(
                    'title' => __('Xóa giỏ hàng nếu thành công', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'no',
                    'description' => 'Tích nếu cho phép xóa giỏ hàng khi call Api bước 1 thành công.',
                ),
//                'is_emptly_cart_pay_with_bank_false' => array(
//                    'title' => __('Xóa giỏ hàng nếu thất bại', 'woocommerce'),
//                    'type' => 'checkbox',
//                    'label' => __('Đồng ý', 'woocommerce'),
//                    'default' => 'no'
//                ),

                'is_reduce_stock_pay_with_bank_success' => array(
                    'title' => __('Giảm số hàng trong kho nếu thành công', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'no',
                    'description' => 'Giảm số hàng trong kho khi call api bước 1 thành công. Mode này chỉ có tác dụng nếu bạn config quản lý kho theo chuẩn woocommerce.',

                ),
//                'is_reduce_stock_pay_with_bank_false' => array(
//                    'title' => __('Giảm số hàng trong khoông nếu thất bại', 'woocommerce'),
//                    'type' => 'checkbox',
//                    'label' => __('Đồng ý', 'woocommerce'),
//                    'default' => 'no',
//                    'description' => 'Mode này chỉ có tác dụng nếu bạn config quản lý kho theo chuẩn woocommerce',
//                ),
                'call_url_false_info' => array(
                    'title' => __('Không cho phép chỉnh sửa nếu call api bước 1 lỗi', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'Khi call api bước 1 lỗi( có thể false sign,thiếu param, lỗi config,...), hệ thông payment không ghi nhận bất cứ thông tin nào về order'.'</br>'.
                        'Không cần chỉnh sửa hay update bất cứ thông tin gì'. '</br>'.
                        'Cố tình chỉnh sửa thông tin khi false bước 1 có thể dẫn tới bug về bảo mật'
                )
                ,


                /** start pay step 2  */

                'start_pay_2' => array(
                    'title' => __('2) Call Url payment', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'Call url nhận được sau bước 1 để tiến hành payment',
                ),
                'status_order_when_call_redirect_success' => array(
                    'title' => __('Status Order If Succees', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => 'processing',
                    'description' => 'Cập nhật status order nếu call api bước 2 success. Thông tin được cập nhật ở url Redirect hoặc IPN.'.'</br>'.
                                     'Vui lòng chỉ nhập status order tồn tại trong hệ thống, nếu status order không tồn tại, sẽ mặc định dùng 
                                      Status default. Để trống tức là không update thông tin gì.',
                ),
                'rule_status_order_when_call_redirect_success' => array(
                    'title' => __('Danh sách status được phép thay đổi nếu pay thành công', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => 'pending,',
                    'description' => 'Liệt kê list status order được phép đổi trạng thái khi payment thành công. Các status cách nhau bằng dấu ,. Để trống đồng nghĩa với không cho phép update.',
                ),
                'status_order_when_call_redirect_false' => array(
                    'title' => __('Status Order If False', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => 'cancelled',
                    'description' => 'Cập nhật status order nếu call api bước 2 false. False được hiểu là pass sign nhưng có status khác success.'.'</br>'.
                                    'Vui lòng chỉ nhập status order tồn tại trong hệ thống, nếu status order không tồn tại, sẽ mặc định dùng Status default.Để trống thì không update thông tin gì.',
                ),
                'rule_status_order_when_call_redirect_false' => array(
                    'title' => __('Danh sách status order được phép thay đổi nếu payment thất bại', 'woocommerce'),
                    'type' => 'text',
                    'label' => __('Sử dụng AppotaPay kiểm thử', 'woocommerce'),
                    'default' => 'pending,',
                    'description' => 'Liệt kê list status order được phép đổi trạng thái khi payment thất bại. Các status cách nhau bằng dấu ,. Để trống đồng nghĩa với không cho phép update.',
                ),
                'is_emptly_cart_when_call_redirect_success' => array(
                    'title' => __('Xóa giỏ hàng nếu thành công', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'yes'
                ),
                'is_emptly_cart_when_call_redirect_false' => array(
                    'title' => __('Tiếp tục thanh toán nếu thất bại', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'yes'
                ),
                'is_reduce_stock_when_call_redirect_success' => array(
                    'title' => __('Giảm số hàng trong kho nếu thành công', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'yes',
                    'description' => 'Mode này chỉ có tác dụng nếu bạn config quản lý kho theo chuẩn woocommerce',

                ),
                'is_reduce_stock_when_call_redirect_false' => array(
                    'title' => __('Giảm số hàng trong kho nếu thất bại', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Đồng ý', 'woocommerce'),
                    'default' => 'no',
                    'description' => 'Mode này chỉ có tác dụng nếu bạn config quản lý kho theo chuẩn woocommerce',
                ),
                'description_sccess_false' => array(
                    'title' => __('Mô tả định nghĩa false và success', 'woocommerce'),
                    'type' => 'title',
                    'description' => 'Success được hiểu là pass các bước bảo mật sign,jwt,... và có status thành success.'.'</br>'.
                        'False được hiểu là pass các bước bảo mật sing, jwt,.. và có status false.'. '</br>'.
                        'Tất cả các trường hợp false xác thực và bảo mật đều không được xử lý vì lý do an toàn. Khi call api và false bước bảo mật, woocommerce sẽ xử lý khi order timeout và chuyển status về cancel.'
                )
            );

        }


        /**
         * handle url reidrect of AppotaPay
         */
        public  function handleReponsePay() {
            write_log('----------------start handle reponsePay--------------------------------------------');
//            $secretKey = 'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ';
            $rs = AppotaPay::verifyAndGetDataRedirectUrl($this->secret_key);

            // pass signature
            if(!empty($rs)){
                $orderId = $rs['orderId'];
                $order =  wc_get_order($orderId);

                if(ErrorCodePayment::SUCCESS_CODE === (int)$rs['errorCode']) {
                    $this->changeStatusOrderWhenCallRedirectSuccess($orderId) ;
                    echo_with_content_type('html', $this->rederHtmlMessage($rs));
                }  else {
                    /** appota pay only alow one pay with one orderId then false order, try  emplty cart, not reduce stock and continue pay with other orderId */
                    $this->changeStatusOrderWhenCallRedirectFalse($orderId);
                    // error code != , show message error and detail order
                    echo_with_content_type('html', $this->rederHtmlMessage($rs));
                }
            } else { // secretKey error or hacker edit url call back
                write_log("false sign, reject request, don't action anything ");
                /**  dont't update anything why sign false   **/
                echo_with_content_type('html',RenderHtml::renderHtmlFalseAuthen());
            }
        }


        /**
         * @param $orderId
         */
        public function changeStatusOrderWhenCallRedirectSuccess($orderId){
            write_log('infunctin changeStatusOrderWhenCallRedirectSuccess');
            if(empty($this->status_order_when_call_redirect_success)) {
                return;
            }
            if(empty($this->rule_status_order_when_call_redirect_success)) {
                return;
            }
            $this->changeStatusOrder($orderId,$this->status_order_when_call_redirect_success,$this->rule_status_order_when_call_redirect_success,'change status',$this->is_reduce_stock_when_call_redirect_success,$this->is_emptly_cart_when_call_redirect_success);
            write_log('end changeStatusOrderWhenCallRedirectSuccess');
        }


        /**
         * @param $orderId
         */
        public  function changeStatusOrderWhenCallRedirectFalse($orderId){
            write_log('infunctin changeStatusOrderWhenCallRedirect   False');
            if(empty($this->status_order_when_call_redirect_false)) {
                return;
            }
            if(empty($this->rule_status_order_when_call_redirect_false)) {
                return;
            }
            $this->changeStatusOrder($orderId,$this->status_order_when_call_redirect_false,$this->rule_status_order_when_call_redirect_false,'change status',$this->is_reduce_stock_when_call_redirect_false,$this->is_emptly_cart_when_call_redirect_false);
        }


        /**
         * @param $orderId
         * @param string $status
         * @param string $message
         * @param bool $reduceStock
         * @param bool $emplyCart
         * @return mixed
         *
         * change status order( No internal wc- prefix is required ),  update cart, stock
         * $ruleStatusCurrent array without wc- prefix
         * if status does't not exist, use status default for woocomerce
         * if $ruleStatusCurrent != emptly, status current in $ruleStatusCurrent :  allow change , else don't allow change
         *
         * don't have transation in process, don't have anything integrity
         */

        public function changeStatusOrder($orderId, string $status, array $ruleStatusCurrent = [], string $message='',bool $reduceStock= false, bool $emptyCart = false ){
            write_log("in function changeStatusOrder with orderId = $orderId");
            $order = wc_get_order($orderId);
            if(!$order){
                return false ;
            }

            $statusOrderCurrent  = $order->get_status();

//            write_log('status =');
//            write_log($status);
//            write_log('ruleStatusCurrent =');
//            write_log($ruleStatusCurrent);
//            write_log('emptyCart='.$emptyCart);



            // update status order
            if(!empty($status) && !empty($ruleStatusCurrent) && in_array($statusOrderCurrent,$ruleStatusCurrent) ){
                write_log('start update status order ');
                if(!$order->update_status( $status, __($message)) ){
                    write_log("Update status order false, orderId = $orderId , $message = $message ");
                } else {
                    write_log("Update status order success, orderId = $orderId , $message = $message ");
                }
            } else {
                write_log("dont't allow update status order ");
            }

            //reduces Stock
            if($reduceStock){
                write_log('start reduce stock');
                /** todo check version, edit if write all version  */
                wc_reduce_stock_levels($orderId);
            }

            if($emptyCart){
                // Remove cart
                if(!empty(WC()->cart)){
                    write_log('start emptly cart ');
                    WC()->cart->empty_cart();
                }
            }


            write_log('end function change status order, cart, stock');
        }




        public  function checkStatusCurrentCanBeChange($orderId, array $statusRule ) {
            $order = wc_get_order($orderId);
            write_log('status order now='.$order->get_status());
            return in_array($order->get_status(),$statusRule);
        }

        /**
         * handle when create request pay false
         * false when call POST /api/v1/orders/payment/bank why anything errors ( error parram, sign,...) ==> systems pay don't
         * save anything order,  don't handle anything action with order,stock, cart.
         */
        public  function  handleFalseRequestPay(){
            echo_with_content_type('html',RenderHtml::renderHtmlFalseAuthen());
        }

        public function  handleIPNRequestPay(){
            write_log('---------------------------------------in funtion handle Ipn Reqeust Pay');
//            $secretKey = 'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ';
            $rs = AppotaPay::verifyAndGetDataIPN($this->secret_key);
            write_log('result IPN = ');
            write_log($rs);

            // pass signature
            if(!empty($rs)){
                /** todo update status order **/
                $orderId = $rs['orderId'];
                $order =  wc_get_order($orderId);

                if(ErrorCodePayment::SUCCESS_CODE === (int)$rs['errorCode']) {
                    //update status order false
                    $this->changeStatusOrderWhenCallRedirectSuccess($orderId) ;
                    return new WP_REST_Response(["status"=> "ok"], 200);
                }
                else {
                   /** appotay pay only call IPN when success, if this case ==> error   */
                    write_log('have error system when response false in IPN ');
                    exit(1);
                }

            } else { // secretKey error or hacker edit url call back
                /**  dont't update anything why sign false   **/
                write_log('sign false , config error or hacker attacked');
            }
        }


        /**
         * @param array $data
         * @return string
         */
        public  function rederHtmlMessage(array $data){
            return RenderHtml::renderHtmlMessageWhenPassAuthen($data);
        }

        /**
         * @return bool
         */
        function is_valid_for_use()
        {
            // check valid currency
            if (!in_array(get_woocommerce_currency(),  array('VND', 'VNĐ')))  {
                write_log('currency partner not math AppotaPay, require VND');
                return false;
            }

            if(!$this->enabled){
                write_log('please enable plugin');
                return false;
            }

            return true;
        }

        /*
         * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
         */
        public function payment_scripts() {

        }

        /*
          * Fields validation, more in Step 5
         */
        public function validate_fields() {


        }


        /**
         * @param $data
         * @return bool
         */
        public function convert_bool($data ){
            switch ($data){
                case 'yes':
                    return true;
                case 'no':
                    return false;
                default:
                    return false;
            }
        }

        /**
         * @param $separator
         * @param $string
         * @param null $limit
         * @return false|string|string[]
         */
        public function convert_string_to_array($separator, $string, $limit = null){
            if(empty($string)){
                return [];
            }
            if(is_null($limit)){
                $rs =  explode($separator, $string);
            } else {
                $rs =  explode($separator, $string, $limit);
            }

            if(!empty($rs)){
                return $rs;
            }
            return [];
        }





        /*
         * In case you need a webhook, like PayPal IPN etc
         */
        public function webhook() {
//            dump('in webhook ');
////            dd($rs);
//            $secretKey = 'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ';
////            $rs = AppotaPay::verifyAndGetDataRedirectUrl($secretKey);
////            dump('in webhook ');
////            dd($rs);
//            write_log('result webhook =');
////            write_log($rs);
        }

        /**
         * @param string $order_id
         * @return false|mixed
         */
        public function payWithbank( string $order_id){
            $order = new WC_Order( $order_id );
            $order_id = strval($order_id);
            $amount = intval($order->get_total());
            $ip_customer = $order->get_customer_ip_address();
            write_log("ip customer address = $ip_customer ");

//            $config = [
//                'partner_code' => 'TEST',
//                'api_key' => 'oMhJpkz7K6HDcR6S',
//                'secret_key' => 'DcR6S0pkz7K6HMqTzf1a5suBJk2WoMhJ'
//            ];

            $config = [
                'partner_code' => $this->partner_code,
                'api_key' => $this->api_key,
                'secret_key' => $this->secret_key
            ];

            write_log('config authen = ');
            write_log($config);


            $orderDetails = [
                'order_id' => $order_id,
                'order_info' => 'Thanh toán đơn hàng',
                'amount' => $amount
            ];
            $paymentDetails = [
//                'bank_code' => 'SHB',
//                'method' => 'ATM',
                'client_ip' => $ip_customer,
                /** todo when done, change url notiUrl t domain  */
                'notiUrl' =>   get_site_url() .'/wp-json/appota/v1/IPN-request-pay',
                'redirectUrl' =>  get_site_url().'/wp-json'.'/appota/v1/handle-request-pay',
            ];

            $appotaPay = new AppotaPay($config);
            return  $appotaPay->makeBankPayment($orderDetails, $paymentDetails, $this->sandbox_mode);
        }
    }
}


if (!function_exists('write_log')) {

    function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            }  else {
                error_log($log);
            }
        }
    }

}


if (!function_exists('echo_with_content_type')) {
    /**
     * @param string $cotent_type
     * @param $data
     * @param bool $exit
     */
    function echo_with_content_type(string $cotent_type= 'html' , $data , bool $exit = true ){
        switch ( $cotent_type ) {
            case 'text':
                header( 'Content-Type: text/plain; charset=' . get_option( 'blog_charset' ) );
                echo $data;
                break;
            case 'xml': // I guess if you really need to
                header( 'Content-Type: application/xml; charset=' . get_option( 'blog_charset' )  );
                echo $data;
                break;
            case 'html':
                header( 'Content-Type: text/html; charset= ' . get_option( 'blog_charset' )  );
                echo $data;
                break;
            default:
                break;
        }
        if($exit){
            exit(1);
        }
    }
}









