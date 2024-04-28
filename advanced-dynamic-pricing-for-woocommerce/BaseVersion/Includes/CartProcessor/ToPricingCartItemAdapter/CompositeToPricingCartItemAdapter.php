<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\Compatibility\SomewhereWarmCompositesCmp;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class CompositeToPricingCartItemAdapter extends SimpleToPricingCartItemAdapter implements IToPricingCartItemAdapter
{
    /** @var SomewhereWarmCompositesCmp */
    protected $compositeCmp;

    public function __construct()
    {
        parent::__construct();

        $this->compositeCmp = new SomewhereWarmCompositesCmp();
    }

    public function canAdaptFacade(WcCartItemFacade $facade): bool
    {
        return $this->compositeCmp->isActive() && $this->compositeCmp->isCompositeItem($facade);
    }

    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool
    {
        /** @var Cart $cart */
        $item = parent::adapt($facade, $pos);

        if (!$item) {
            return false;
        }

        if ($this->compositeCmp->isAllowToProcessPricedIndividuallyItems()) {
            if ($this->compositeCmp->isCompositeItemNotPricedIndividually($facade)) {
                $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
            }
        } else {
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        if ($facade->isHasReadOnlyPrice()) {
            $item->addAttr(CartItemAttributeEnum::READONLY_PRICE());
        }

        $cart->addToCart($item);

        return true;
    }

    public function canAdaptWcProduct(\WC_Product $product): bool
    {
        return $this->compositeCmp->isActive() && $this->compositeCmp->isCompositeProduct($product);
    }
}
