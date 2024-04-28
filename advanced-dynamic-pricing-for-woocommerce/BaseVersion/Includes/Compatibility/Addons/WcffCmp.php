<?php

namespace ADP\BaseVersion\Includes\Compatibility\Addons;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WC Fields Factory
 * Author: Saravana Kumar K
 *
 * @see https://wcfieldsfactory.com/
 */
class WcffCmp
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function isActive()
    {
        return function_exists('wcff');
    }

    public function installRenderHooks()
    {
        if(!$this->isActive()) {
            return;
        }
        add_action('wp_loaded', function () {
            add_action('wp_print_styles', array($this, 'loadFrontendAssets'));
        });
    }

    public function loadFrontendAssets()
    {
        $context = $this->context;
        $baseVersionUrl = WC_ADP_PLUGIN_URL . "/BaseVersion/";
        if ($context->is($context::WC_PRODUCT_PAGE) || $context->is($context::PRODUCT_LOOP)) {
            wp_enqueue_script('wdp-wc-fields-factory', $baseVersionUrl . 'assets/js/wdp-wc-fields-factory.js', array('jquery'),
                WC_ADP_VERSION);
        }
    }

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     *
     * @return array<int, CartItemAddon>
     */
    public function getAddonsFromCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $addons = [];
        $cartItemData = $wcCartItemFacade->getData();

        if(!($cartItemData['wccpf_pricing_applied_price_option'] ?? false)) {
            $cartItemData = wcff()->persister->persist($cartItemData, $wcCartItemFacade->getProductId(), $wcCartItemFacade->getVariationId());
            $cartItemData = wcff()->negotiator->handle_custom_pricing($cartItemData, $wcCartItemFacade->getKey());
            $option_price = $cartItemData["data"]->get_price();

            $addon = new CartItemAddon(null, "", $option_price);
            $addon->currency = $wcCartItemFacade->getCurrency();

            $addons[] = $addon;
        }

        return $addons;
    }
}
