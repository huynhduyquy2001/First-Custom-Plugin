<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\CartProcessor\OriginalPriceCalculation;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\Factory;

class SimpleToPricingCartItemAdapter implements IToPricingCartItemAdapter
{
    /** @var Context */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function canAdaptFacade(WcCartItemFacade $facade): bool
    {
        return true;
    }

    public function adapt(WcCartItemFacade $facade, int $pos = -1): ?BasicCartItem
    {
        try {
            $origPriceCalc = new OriginalPriceCalculation($this->context);
            $origPriceCalc->withContext($this->context);
        } catch (\Exception $e) {
            return null;
        }

        Factory::callStaticMethod(
            'PriceDisplay_PriceDisplay',
            'processWithout',
            array($origPriceCalc, 'process'),
            $facade
        );

        $qty = floatval(apply_filters('wdp_get_product_qty', $facade->getQty(), $facade));

        $addonsAdapter = new ToPricingAddonsAdapter();
        if ( $addonsAdapter->hasAddons($facade) ) {
            $initialCost = $origPriceCalc->basePrice ?? 0.0;
            $initialCost = $this->removeImmutableAdjustmentsFromInitialPrice($initialCost, $facade);
            $initialCost = $addonsAdapter->addAddonsToInitialPriceWithFacade($initialCost, $facade);
            $item = new BasicCartItem($facade, $initialCost, $qty, $pos);
            $item->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
        } else {
            /** Build generic item */
            $initialCost = $origPriceCalc->priceToAdjust;
            $initialCost = $this->removeImmutableAdjustmentsFromInitialPrice($initialCost, $facade);
            $item = new BasicCartItem($facade, $initialCost, $qty, $pos);
            $item->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
            /** Build generic item end */
        }

        if ($origPriceCalc->isReadOnlyPrice || $facade->isHasReadOnlyPrice()) {
            $item->addAttr(CartItemAttributeEnum::READONLY_PRICE());
        }

        if ($facade->isImmutable()) {
            foreach ($facade->getPriceAdjustments() as $priceAdjustment) {
                $item->applyPriceAdjustment($priceAdjustment);
            }
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        if (!$facade->isVisible()) {
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        return $item;
    }

    protected function removeImmutableAdjustmentsFromInitialPrice(float $initialPrice, WcCartItemFacade $facade) {
        if ($facade->isImmutable() && $facade->getHistory()) {
            foreach ($facade->getHistory() as $amounts) {
                $initialPrice += array_sum($amounts);
            }
        }

        return $initialPrice;
    }

    public function adaptFacadeAndPutIntoCart($cart, WcCartItemFacade $facade, int $pos): bool
    {
        /** @var Cart $cart */

        $item = $this->adapt($facade, $pos);

        if (!$item) {
            return false;
        }

        $cart->addToCart($item);

        return true;
    }

    public function canAdaptWcProduct(\WC_Product $product): bool
    {
        return true;
    }

    public function adaptWcProduct(WcProductCalculationWrapper $wrapper): ?ICartItem
    {
        $pos = -1;

        $facade = WcCartItemFacade::createFromProduct(
            $this->context,
            $wrapper->getWcProduct(),
            $wrapper->getCartItemData()
        );
        $facade->withContext($this->context);

        try {
            $origPriceCalc = new OriginalPriceCalculation($this->context);
            $origPriceCalc->withContext($this->context);
        } catch (\Exception $e) {
            return null;
        }

        Factory::callStaticMethod(
            'PriceDisplay_PriceDisplay',
            'processWithout',
            array($origPriceCalc, 'process'),
            $facade
        );

        $qty = floatval(apply_filters('wdp_get_product_qty', $facade->getQty(), $facade));

        if ( $wrapper->getAddons() ) {
            $initialCost = $origPriceCalc->basePrice;
            $initialCost = $this->removeImmutableAdjustmentsFromInitialPrice($initialCost, $facade);
            $initialCost = (new ToPricingAddonsAdapter())->addAddonsToInitialPrice($initialCost, $wrapper->getAddons());
            $item = new BasicCartItem($facade, $initialCost, $qty, $pos);
            $item->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
        } else {
            /** Build generic item */
            $initialCost = $origPriceCalc->priceToAdjust;
            $initialCost = $this->removeImmutableAdjustmentsFromInitialPrice($initialCost, $facade);
            $item = new BasicCartItem($facade, $initialCost, $qty, $pos);
            $item->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
            /** Build generic item end */
        }

        if ($origPriceCalc->isReadOnlyPrice) {
            $item->addAttr(CartItemAttributeEnum::READONLY_PRICE());
        }

        if ($facade->isImmutable()) {
            foreach ($facade->getPriceAdjustments() as $priceAdjustment) {
                $item->applyPriceAdjustment($priceAdjustment);
            }
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        if (!$facade->isVisible()) {
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        return $item;
    }
}
