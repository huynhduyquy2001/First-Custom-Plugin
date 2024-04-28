<?php

namespace ADP\BaseVersion\Includes\ProductExtensions;

use ADP\BaseVersion\Includes\Context;
use ReflectionException;

defined('ABSPATH') or exit;

class ProductExtension
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var \WC_Product
     */
    protected $product;

    /**
     * @param Context|\WC_Product $contextOrProduct
     * @param \WC_Product|null $deprecated
     */
    public function __construct($contextOrProduct, $deprecated = null)
    {
        $this->context = adp_context();
        $this->product = $contextOrProduct instanceof \WC_Product ? $contextOrProduct : $deprecated;
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return float|null
     */
    public function getCustomPrice()
    {
        try {
            $reflection = new \ReflectionClass($this->product);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $changes = $property->getValue($this->product);

            return $changes['adpCustomInitialPrice'] ?? null;
        } catch (ReflectionException $exception) {
            $property = null;
        }

        return null;
    }

    public function setCustomPrice($price)
    {
        try {
            $reflection = new \ReflectionClass($this->product);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $changes = $property->getValue($this->product);

            $price = $price !== null ? (float)$price : null;

            $property->setValue($this->product, array_merge($changes, ['adpCustomInitialPrice' => $price]));
        } catch (ReflectionException $exception) {
            $property = null;
        }
    }

    public function getProductPriceDependsOnPriceMode()
    {
        $product = $this->product;
        $priceMode = $this->context->getOption('discount_for_onsale');
        $initialPrice = $product->get_price('edit');

        try {
            $reflection = new \ReflectionClass($product);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $changes = $property->getValue($product);
            if ( isset($changes['price']) ) {
                $initialPrice = $changes['price'];
                unset($changes['price']);
            }
            $property->setValue($product, $changes);
        } catch (ReflectionException $exception) {
            $property = null;
        }

        if ($product->is_on_sale('edit')) {
            if ('sale_price' === $priceMode || 'discount_sale' === $priceMode) {
                $price = $product->get_sale_price('edit');
            } else {
                $price = $product->get_regular_price('edit');
            }
        } else {
            $price = $product->get_price('edit');
        }

        $product->set_price($initialPrice);

        return $price;
    }
}
