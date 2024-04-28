<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;
use ADP\Factory;
use ADP\BaseVersion\Includes\PriceDisplay\Processor;
use ADP\BaseVersion\Includes\CartProcessor\CartBuilder;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Quote for WooCommerce
 * Author: WPExperts.io
 *
 * @see https://woocommerce.com/products/quote-for-woocommerce/
 */

class WcQuoteCmp
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Processor
     */
    protected $productProcessor;

    /**
     * @var CartBuilder
     */
    protected $cartBuilder;

    public function __construct()
    {
        $this->context          = adp_context();
        $this->productProcessor = new Processor();
        $this->cartBuilder      = new CartBuilder();
    }

    public function isActive()
    {
        return class_exists('WC_QUOTE');
    }

    public function prepareHooks()
    {
        if ($this->isActive()) {
            add_action('wc_quote_session_changed', [ $this, 'update_session_quote_addon_price' ], 20 );
            add_action('wc_quote_add_to_quote', [$this, 'add_to_quote'], 20, 6);
        }
    }

    function add_to_quote($quote_item_key, $product_id, $quantity, $variation_id, $variation, $quote_item_data)
    {
        $quote_contents = WC()->session->get( 'wc_quotes' );
        $quote_contents[$quote_item_key]['offered_price'] = $quote_contents[$quote_item_key]['addons_price'];
        WC()->session->set('wc_quotes', $quote_contents );
    }

    function update_session_quote_addon_price()
    {
        $quote_contents = WC()->session->get( 'wc_quotes' );

        $productProcessor = $this->productProcessor;
        $cart             = $this->cartBuilder->create(WC()->customer, WC()->session);
        $productProcessor->withCart($cart);
        $this->cartBuilder->populateCart($cart, (object)['cart_contents' => $quote_contents]);
        $calc = Factory::callStaticMethod("Core_CartCalculator", 'make', $this->context);
        $calc->processCart($cart);

        $updated_quote = [];

        foreach ($cart->getItems() as $item) {
            $key = $item->getWcItem()->getKey();
            $product = $item->getWcItem()->getProduct();

            if ($product->is_type('variation')) {
                $variationId = $product->get_id();
                $productId   = $product->get_parent_id();
                $variation   = $product->get_variation_attributes();
            } else {
                $productId   = $product->get_id();
                $variationId = 0;
                $variation   = array();
            }

            $updated_quote[$key] = [
                'key'           => $key,
                'product_id'    => $productId,
                'variation_id'  => $variationId,
                'variation'     => $variation,

                'quantity'      => $item->getQty(),
                'offered_price' => $quote_contents[$key]['offered_price'] ?? $item->getPrice(),
                'addons_price'  => $item->getPrice(),

                'data'          => $product,
                'data_hash'     => wc_get_cart_item_data_hash( $product )
            ];
        }

        WC()->session->set('wc_quotes', $updated_quote );
    }
}
