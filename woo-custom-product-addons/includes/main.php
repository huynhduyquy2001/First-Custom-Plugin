<?php

namespace Acowebs\WCPA\Free;


if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Product addon Plugin by Acowebs
 *
 * The main plugin handler class is responsible for initializing Plugin.
 *
 * @since 3.0.0
 */
class Main
{

    /**
     * Instance.
     *
     * Holds the plugin instance.
     *
     * @since 3.0.0
     * @access public
     * @static
     *
     * @var Plugin
     */
    public static $instance = null;

    public static $cartError = [];

    public $form;
    public $options;
    public $admin;

    private function __construct()
    {
        $this->register_autoloader();

        add_action('init', [$this, 'init'], 0);
        add_filter('woocommerce_locate_template', array($this, 'woo_template'), 1, 3);
    }

    /**
     * Register autoloader.
     *
     * @since @since 3.0.0
     * @access private
     */
    private function register_autoloader()
    {
        require_once WCPA_PATH.'/includes/autoloader.php';

        Autoloader::run();
    }

    /**
     * This carterror object can be used to decide whether to autofill the form fields if  the add to cart have validation errors
     *
     * @param $productId
     * @param $status
     *
     * @since 3.0.0
     */
    public static function setCartError($productId, $status)
    {
        self::$cartError[$productId] = $status;
    }

    /**
     * Instance.
     *
     * Ensures only one instance of the plugin class is loaded or can be loaded.
     *
     * @return Plugin An instance of the class.
     * @since 3.0.0
     * @access public
     * @static
     *
     */
    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    function woo_template($template, $template_name, $template_path)
    {
        if ($template_name == 'cart/cart-item-data.php') {
            $override_template = Config::get_config('override_cart_meta_template');
            if(!$override_template) {
                return $template;
            }

            $plugin_path = untrailingslashit(plugin_dir_path(WCPA_FILE)).'/templates/';
            // Look within passed path within the theme - this is priority
            $template = locate_template(
                array(
                    $template_path.$template_name,
                    $template_name
                )
            );

            if ( ! $template && file_exists($plugin_path.$template_name)) {
                $template = $plugin_path.$template_name;
            }
        }


        return $template;
    }

    /**
     * Init.
     *
     * Initialize  Plugin. Register  support for all the
     * @since 3.0.0
     * @access public
     */
    public function init()
    {
        $this->init_components();
    }

    /**
     * Init components.
     *
     *
     * @since 3.0.0
     * @access private
     */
    private function init_components()
    {
        /**
         * All backend API has to initiallize outside is_admin(), as REST URL is not part of wp_admin
         */
        new BackendApi();


        $isWooActive = $this->is_woocommerce_active();
        if ($isWooActive) {
            $front = new Front();
        }
        if (is_admin()) {
            $this->admin = new Admin($isWooActive);
        }
    }

    /**
     * Check if WooCommerce installed and activated
     * @return bool
     */
    static function is_woocommerce_active()
    {
        if (class_exists('WooCommerce')) {
            return true;
        }
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return true;
        }
        if (is_multisite()) {
            $plugins = get_site_option('active_sitewide_plugins');
            if (isset($plugins['woocommerce/woocommerce.php'])) {
                return true;
            }
        }

        return false;
    }


    /**
     * Clone.
     *
     * Disable class cloning and throw an error on object clone.
     *
     * The whole idea of the singleton design pattern is that there is a single
     * object. Therefore, we don't want the object to be cloned.
     *
     * @access public
     * @since 3.0.0
     */
    public function __clone()
    {
        // Cloning instances of the class is forbidden.
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'woo-custom-product-addons'), '1.0.0');
    }

    /**
     * Wakeup.
     *
     * Disable unserializing of the class.
     *
     * @access public
     * @since 3.0.0
     */
    public function __wakeup()
    {
        // Unserializing instances of the class is forbidden.
        _doing_it_wrong(__FUNCTION__, esc_html__('Something went wrong.', 'woo-custom-product-addons'), '1.0.0');
    }
}

Main::instance();
