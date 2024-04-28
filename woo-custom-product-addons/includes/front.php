<?php

namespace Acowebs\WCPA\Free;


class Front
{

    public $assets_url;
    public $version;
    public $token;
    public $wcpaProducts = [];

    public function __construct()
    {
        $this->assets_url = WCPA_ASSETS_URL;
        $this->version = WCPA_VERSION;
        $this->token = WCPA_TOKEN;

        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles'], 10);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts'], 99);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 10);

        add_filter('woocommerce_product_add_to_cart_text', array($this, 'add_to_cart_text'), 10, 2);
        add_filter('woocommerce_loop_add_to_cart_args', array($this, 'add_to_cart_args'), 10, 2);
        add_filter('woocommerce_product_supports', array($this, 'product_supports'), 10, 3);
        add_filter('woocommerce_product_add_to_cart_url', array($this, 'add_to_cart_url'), 20, 2);

        add_filter('post_class', array($this, 'product_class'), 10, 3);


        add_filter('woocommerce_paypal_payments_product_supports_payment_request_button',
            array($this, 'show_checkout_button'), 10, 2);

        add_filter('wc_stripe_hide_payment_request_on_product_page', array($this, 'remove_checkout_button'), 10, 2);

        add_filter('wcpay_payment_request_is_product_supported', array($this, 'show_checkout_button'), 10, 2);


        add_action('woocommerce_single_product_summary', array($this, 'show_warnings'), 30);


        //TODO test
        add_filter('woocommerce_email_format_string', array($this, 'email_format_string'), 2, 10);


        new Render();
        new Process();
        new Cart();
        new Order();

    }

    /**
     * Provide tags to use in email  templates
     * {wcpa_id_<element_id>}
     */
    public function email_format_string($string, $obj)
    {
        // $email

        if (is_string($string) && preg_match_all('/\{(\s)*?wcpa_id_([^}]*)}/', $string, $matches)) {
            if (isset($obj->id)) {
                $order = $obj->object;
                if ($order && method_exists($order, 'get_items')) {
                    foreach ($matches[2] as $k => $match) {
                        foreach ($order->get_items() as $item_id => $item_data) {
                            $meta_data = $item_data->get_meta(WCPA_ORDER_META_KEY);
                            foreach ($meta_data as $v) {
                                if ($v['form_data']->elementId === $match) {
                                    $val = $this->order_meta_plain($v, false);
                                    $string = str_replace('{wcpa_id_' . $match . '}', $val, $string);
                                }
                            }
                        }

                        $string = str_replace('{wcpa_id_' . $match . '}', '', $string);
                    }
                }
            }
        }


        return $string;
    }

    /**
     * Show the reasons to admin  why wcpa  fields  are not rendered
     */
    public function show_warnings()
    {
        global $product;
        if (!$product->is_purchasable() && ($product->is_type(['simple', 'variable']))) {
            $product_id = $product->get_id();
            // check if admin user
            if (current_user_can('manage_options') && $this->is_wcpa_product($product_id)) {
                echo '<p style="color:red">' . __('WCPA fields will show only if product has set price',
                        'woo-custom-product-addons') . '</p>';
            }
        }
    }

    /**
     * Check a product has form assigned
     *
     * @param $product_id
     *
     * @return bool
     */
    public function is_wcpa_product($product_id)
    {
        if (!$this->wcpaProducts) {
            $form = new Form();
            $this->wcpaProducts = $form->get_wcpaProducts();
        }

        return in_array($product_id, $this->wcpaProducts['full']);
    }

    /**
     * Disable or remove direct payment buttons(Paypal/Stripe) from product detail page
     *
     * @param $allow
     * @param $product
     *
     * @return false
     * @since 3.0.0
     */
    public function remove_checkout_button($allow, $product)
    {

        if (!$allow) {
            if (is_object($product)) {
                if (method_exists($product, 'get_id')) {
                    $id = $product->get_id();
                } else if (isset($product->ID)) {
                    $id = $product->ID;
                } else {
                    $id = 0;
                }
            } else {
                $id = $product;
            }

            if ($this->is_wcpa_product($id)) {
                return true;
            }
        }

        return $allow;
    }

    public function show_checkout_button($allow, $product)
    {

        if ($allow) {
            if (is_object($product)) {
                if (method_exists($product, 'get_id')) {
                    $id = $product->get_id();
                } else if (isset($product->ID)) {
                    $id = $product->ID;
                } else {
                    $id = 0;
                }
            } else {
                $id = $product;
            }

            if ($this->is_wcpa_product($id)) {
                return false;
            }
        }

        return $allow;
    }


    /**
     * return permalink for wcpa product,
     * If direct Purchasable product, return  the original $url
     *
     * @param $url
     * @param $product
     *
     * @return mixed
     */
    public function add_to_cart_url($url, $product)
    {
        $product_id = $product->get_id();
        if ($this->is_wcpa_product($product_id) && !$this->is_direct_purchasable_product($product_id) && !$product->is_type('external')) {
            return $product->get_permalink();
        } else {
            return $url;
        }
    }

    public function is_direct_purchasable_product($product_id)
    {
        if (!$this->wcpaProducts) {
            $form = new Form();
            $this->wcpaProducts = $form->get_wcpaProducts();
        }

        return in_array($product_id, $this->wcpaProducts['direct_purchasable']);
    }

    public function product_class($classes = array(), $class = false, $product_id = false)
    {
        if ($product_id && $this->is_wcpa_product($product_id)) {
            $classes[] = 'wcpa_has_options';
        }

        return $classes;
    }

    public function add_to_cart_text($text, $product)
    {
        $product_id = $product->get_id();

        if ($this->is_wcpa_product($product_id) && $product->is_in_stock() && !$this->is_direct_purchasable_product($product_id)) {
            $text = Config::get_config('add_to_cart_text', 'Select options', true);
        }

        return $text;
    }

    /**
     * Remove ajax add to cart feature for wcpa products.
     *
     * @param $support
     * @param $feature
     * @param $product
     *
     * @return bool
     */
    public function product_supports($support, $feature, $product)
    {
        $product_id = $product->get_id();
        if ($feature == 'ajax_add_to_cart' && $this->is_wcpa_product($product_id) && !$this->is_direct_purchasable_product($product_id)) {
            $support = false;
        }

        return $support;
    }

    public function add_to_cart_args($args, $product)
    {
        $product_id = $product->get_id();

        if ($this->is_wcpa_product($product_id) && $product->is_in_stock() && !$this->is_direct_purchasable_product($product_id)) {
            $class = Config::get_config('add_to_cart_button_class');
            if (isset($args['class']) && is_string($args['class'])) {
                $args['class'] .= ' ' . $class;
            }
        }

        return $args;
    }

    public function enqueue_styles()
    {
        $design = Config::get_config('active_design', false);


        if ($design === false || !isset($design['active'])) {
            wp_register_style($this->token . '-frontend', esc_url($this->assets_url) . 'css/base.css', array(),
                $this->version);
        } else {
            if ($design['cssCode'] && !empty($design['cssCode'])) {
                add_action('wp_head', function () use ($design) {
                    echo '<style>' . $design['cssCode'] . '</style>';
                }, 10);
            }
            wp_register_style($this->token . '-frontend',
                esc_url($this->assets_url) . 'css/' . $design['active']['style'] . '.css', array(), $this->version);
        }
        if (Config::get_config('enqueue_cs_js_all_pages') || is_product()) {
            wp_enqueue_style($this->token . '-frontend');
        }

    }

    /**
     * Load frontend Javascript.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function admin_enqueue_scripts()
    {
        $this->registerFrontScripts(true);

//        wp_enqueue_script($this->token . '-googlemapplace');


    }

    public function registerFrontScripts($isAdmin = false)
    {
        wp_enqueue_script('wp-hooks');


        wp_register_script($this->token . '-datepicker', esc_url($this->assets_url) . 'js/datepicker.js', array(),
            $this->version, true);

        wp_register_script($this->token . '-select', esc_url($this->assets_url) . 'js/select.js', array(), $this->version,
            true);
        wp_register_script($this->token . '-front', esc_url($this->assets_url) . 'js/front-end.js', array('wp-hooks'),
            $this->version, true);


        $_validation_messages = Config::get_config('wcpa_validation_strings');
        $validation_messages = [];
        /** remove validation_ prefix in keys */
        foreach ($_validation_messages as $key => $v) {
            $validation_messages[str_replace('validation_', '', $key)] = $v;
        }


        $wcpa_global_vars = array(
            'api_nonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : null,
            'root' => rest_url($this->token . '/front/'),
            'assets_url' => $this->assets_url,
            'date_format' => __(get_option('date_format'), 'woo-custom-product-addons'),
            'time_format' => __(get_option('time_format'), 'woo-custom-product-addons'),
            'validation_messages' => $validation_messages,

            'ajax_add_to_cart' => Config::get_config('ajax_add_to_cart'),

        );
        $wcpa_global_vars['i18n_view_cart'] = esc_attr__('View cart', 'woocommerce');


        if ($isAdmin) {
            $wcpa_global_vars['cart_url'] = null;
            $wcpa_global_vars['is_cart'] = false;
        } else {
            $wcpa_global_vars['cart_url'] = apply_filters('woocommerce_add_to_cart_redirect',
                wc_get_cart_url(), null);
            $wcpa_global_vars['is_cart'] = is_cart();
        }


        $init_triggers = Config::get_config('wcpa_init_triggers', []);
        if (!is_array($init_triggers) && !empty($init_triggers)) {
            $init_triggers = [$init_triggers];
        }

        $init_triggers2 = Config::get_config('plugin_init_triggers');
        if (!empty($init_triggers2)) {
            $init_triggers2 = explode(',', $init_triggers2);
        } else {
            $init_triggers2 = [];
        }
        $wcpa_global_vars['init_triggers'] = array_unique(array_merge($init_triggers,$init_triggers2, array(
            'wcpt_product_modal_ready',
            'qv_loader_stop',
            'quick_view_pro:load',
            'elementor/popup/show',
            'xt_wooqv-product-loaded',
            'woodmart-quick-view-displayed',
            'porto_init_countdown',
            'woopack.quickview.ajaxload',
//            'acoqvw_quickview_loaded',
            'quick-view-displayed',
            'update_lazyload',
            'riode_load',
            'yith_infs_added_elem',
            'jet-popup/show-event/after-show',
            'etheme_quick_view_content_loaded',
//            'awcpt_wcpa_init',
            'wc_backbone_modal_loaded' // barn2 restaurant booking

        )));

        wp_localize_script($this->token . '-front', $this->token . '_front', $wcpa_global_vars);


    }

    public function enqueue_scripts()
    {
        $this->registerFrontScripts();
    }


}
