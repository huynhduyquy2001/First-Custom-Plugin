<?php

namespace ADP\BaseVersion\Includes\PriceDisplay;


use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;

class WcProductCalculationWrapper
{
    /**
     * @var \WC_Product
     */
    protected $wcProduct;

    /**
     * @var array
     */
    protected $cartItemData = [];

    /**
     * @var array<int, CartItemAddon>
     */
    protected $addons = [];


    public function __construct(
        \WC_Product $wcProduct,
        array $cartItemData = [],
        array $addons = []
    ) {
        $this->wcProduct = $wcProduct;
        $this->cartItemData = $cartItemData;
        $this->addons = array_filter($addons, function ($addon) {
            return $addon instanceof CartItemAddon;
        });
    }

    /**
     * @return \WC_Product
     */
    public function getWcProduct(): \WC_Product
    {
        return $this->wcProduct;
    }

    /**
     * @return array
     */
    public function getCartItemData(): array
    {
        return $this->cartItemData;
    }

    /**
     * @return array<int, CartItemAddon>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }
}
