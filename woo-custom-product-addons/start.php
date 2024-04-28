<?php
/*
 * Plugin Name: WooCommerce Custom Product Addons Free
 * Version: 3.0.7
 * Plugin URI: https://acowebs.com
 * Description: WooCommerce Product add-on plugin. Add custom fields to your WooCommerce product page. With an easy-to-use Custom Form Builder, now you can add extra product options quickly.
 * Author URI: https://acowebs.com
 * Author: Acowebs
 * Requires at least: 4.0
 * Tested up to: 6.4
 * Requires PHP: 7.2
 * Text Domain: woo-custom-product-addons
 * WC requires at least: 3.3.0
 * WC tested up to: 8.6
 */
/**
 *
 * WCPA:WooCommerce Custom Product Addons
 */


defined('ABSPATH') || exit;

if (!is_wcpa_pro_active()) {
    if (!defined('WCPA_FILE')) {
        define('WCPA_FILE', __FILE__);
    }


    define('WCPA_VERSION', '3.0.7');
    define('WCPA_PLUGIN_NAME', 'Woocommerce Custom Product Addons');

    define('WCPA_TOKEN', 'wcpa');
    define('WCPA_PATH', plugin_dir_path(WCPA_FILE));
    define('WCPA_URL', plugins_url('/', WCPA_FILE));

    define('WCPA_ASSETS_PATH', WCPA_URL . 'assets/');
    define('WCPA_ASSETS_URL', WCPA_URL . 'assets/');

    define('WCPA_PRODUCT_META_KEY', '_wcpa_product_meta');

    define('WCPA_ORDER_META_KEY', '_WCPA_order_meta_data');

    define('WCPA_PRODUCTS_TRANSIENT_KEY', 'wcpa_products_transient_ver_3');

    define('WCPA_EMPTY_LABEL', 'wcpa_empty_label');

    define('WCPA_CART_ITEM_KEY', 'wcpa_data');



    add_action('plugins_loaded', 'wcpa_load_plugin_textdomain');

    if (!version_compare(PHP_VERSION, '7.0', '>=')) {
        add_action('admin_notices', 'wcpa_fail_php_version');
    } elseif (!version_compare(get_bloginfo('version'), '5.0', '>=')) {
        add_action('admin_notices', 'wcpa_fail_wp_version');
    } else {
        require WCPA_PATH . 'includes/helper.php';
        require WCPA_PATH . 'includes/main.php';
    }


    /**
     * Load Plugin textdomain.
     *
     * Load gettext translate for Plugin text domain.
     *
     * @return void
     * @since 1.0.0
     *
     */
    function wcpa_load_plugin_textdomain()
    {
        load_plugin_textdomain('woo-custom-product-addons');
    }

    /**
     * Plugin admin notice for minimum PHP version.
     *
     * Warning when the site doesn't have the minimum required PHP version.
     *
     * @return void
     * @since 3.0.0
     *
     */
    function wcpa_fail_php_version()
    {
        /* translators: %s: PHP version. */
        $message = sprintf(esc_html__(WCPA_PLUGIN_NAME . ' requires PHP version %s+, plugin is currently NOT RUNNING.',
            'woo-custom-product-addons'), '7.0');
        $html_message = sprintf('<div class="error">%s</div>', wpautop($message));
        echo wp_kses_post($html_message);
    }

    /**
     * Plugin admin notice for minimum WordPress version.
     *
     * Warning when the site doesn't have the minimum required WordPress version.
     *
     * @return void
     * @since 3.0.0
     *
     */
    function wcpa_fail_wp_version()
    {
        /* translators: %s: WordPress version. */
        $message = sprintf(esc_html__(WCPA_PLUGIN_NAME . ' requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.',
            'woo-custom-product-addons'), '5.0');
        $html_message = sprintf('<div class="error">%s</div>', wpautop($message));
        echo wp_kses_post($html_message);
    }

    /**
     *  Giving compatablity with backward,
     * @return string
     */
    function wcpa_is_wcpa_product($product_id)
    {
        return  Acowebs\WCPA\Free\has_form($product_id);
    }
}


// Declare compatibility with custom order tables for WooCommerce.
add_action(
    'before_woocommerce_init',
    function () {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__,
                true);
        }
    }
);
function is_wcpa_pro_active()
{
    if (in_array('woo-custom-product-addons-pro/start.php',
        apply_filters('active_plugins', get_option('active_plugins')))) {
        return true;
    }
    if (is_multisite()) {
        $plugins = get_site_option('active_sitewide_plugins');
        if (isset($plugins['woo-custom-product-addons-pro/start.php'])) {
            return true;
        }
    }

    return false;
}


