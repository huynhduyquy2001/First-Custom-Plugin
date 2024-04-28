<?php

namespace ADP\BaseVersion\Includes\Compatibility;


defined('ABSPATH') or exit;

/**
 * Plugin Name: Klarna On-Site Messaging for WooCommerce
 * Author: krokedil, klarna
 *
 * @see https://krokedil.se/ 
 */

class KlarnaOnSiteMessagingCmp
{
    public function __construct()
    {

    }

    public function isActive()
    {
        return class_exists("\Klarna_OnSite_Messaging_For_WooCommerce");
    }

    public function prepareHooks()
    {
        if ($this->isActive()) {
            add_filter('pre_do_shortcode_tag', [$this, 'beforeShortcode'], 10, 4);
            add_filter('do_shortcode_tag', [$this, 'afterShortcode'], 10, 4);
            add_filter('woocommerce_available_variation', array($this, 'hookWcAvailableVariation'), 10, 3);
        }
    }

    public function hookWcAvailableVariation($args, $product, $variation) {
        $price = adp_functions()->getDiscountedProductPrice($variation, 1);
        $args['display_price'] = $price;
        return $args;
    }

    function getDiscountedProductPrice($price, $product) {
        return adp_functions()->getDiscountedProductPrice($product, 1);
    }

    function beforeShortcode($return, $tag, $attr, $m) {
        if('onsite_messaging' == $tag) {
            add_filter( 'woocommerce_product_get_price', [$this, 'getDiscountedProductPrice'], 10 , 2 );
        }
        return $return;
    }
    
    function afterShortcode($output, $tag, $attr, $m) {
        if('onsite_messaging' == $tag) {
            remove_filter( 'woocommerce_product_get_price', [$this, 'getDiscountedProductPrice'], 10 , 2 );
        }
        return $output;
    }
}
