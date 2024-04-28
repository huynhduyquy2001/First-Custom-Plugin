<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\Compatibility\WcSubscriptionsCmp;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class SubscriptionToPricingCartItemAdapter extends SimpleToPricingCartItemAdapter implements IToPricingCartItemAdapter
{
    /** @var WcSubscriptionsCmp */
    protected $wcSubscriptionsCmp;

    public function __construct()
    {
        parent::__construct();

        $this->wcSubscriptionsCmp = new WcSubscriptionsCmp();
    }

    public function canAdaptFacade(WcCartItemFacade $facade): bool
    {
        return $this->wcSubscriptionsCmp->isActive() && $this->wcSubscriptionsCmp->isRenewalSubscription($facade);
    }

    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool
    {
        /** @var Cart $cart */
        $item = parent::adapt($facade, $pos);

        if (!$item) {
            return false;
        }

        $item->addAttr(CartItemAttributeEnum::IMMUTABLE());

        if ($facade->isHasReadOnlyPrice()) {
            $item->addAttr(CartItemAttributeEnum::READONLY_PRICE());
        }

        $cart->addToCart($item);

        return true;
    }

    public function canAdaptWcProduct(\WC_Product $product): bool
    {
        return $this->wcSubscriptionsCmp->isActive() && $this->wcSubscriptionsCmp->isSubscriptionProduct($product);
    }
}
