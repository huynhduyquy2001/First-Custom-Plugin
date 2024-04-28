<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

interface IToPricingCartItemAdapter
{
    public function canAdaptFacade(WcCartItemFacade $facade): bool;

    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool;

    public function canAdaptWcProduct(\WC_Product $product): bool;

    public function adaptWcProduct(WcProductCalculationWrapper $wrapper): ?ICartItem;
}
