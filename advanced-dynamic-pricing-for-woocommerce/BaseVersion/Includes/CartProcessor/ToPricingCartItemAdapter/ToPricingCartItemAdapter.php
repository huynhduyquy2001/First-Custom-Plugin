<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class ToPricingCartItemAdapter
{
    /**
     * @var self
     */
    protected static $instance = null;

    /** @var array<int, IToPricingCartItemAdapter> */
    protected $chain;

    public function __construct()
    {
        $this->chain = [
            new CompositeToPricingCartItemAdapter(),
            new SubscriptionToPricingCartItemAdapter(),
            new ContainerToPricingCartItemAdapter(),
            new SimpleToPricingCartItemAdapter()
        ];
    }

    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool
    {
        foreach ($this->chain as $adapter) {
            if ($adapter->canAdaptFacade($facade)) {
                return $adapter->adaptFacadeAndPutIntoCart($cart, $facade, $pos);
            }
        }

        return false;
    }

    public function adaptWcProduct(WcProductCalculationWrapper $wrapper): ?ICartItem
    {
        foreach ($this->chain as $adapter) {
            if ($adapter->canAdaptWcProduct($wrapper->getWcProduct())) {
                return $adapter->adaptWcProduct($wrapper);
            }
        }

        return null;
    }


}
