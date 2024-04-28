<?php

namespace ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter;

use ADP\BaseVersion\Includes\CartProcessor\OriginalPriceCalculation;
use ADP\BaseVersion\Includes\Compatibility\Addons\FlexibleProductFieldsCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\PPOMCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\ThemehighExtraOptionsProCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\TmExtraOptionsCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\WcCustomProductAddonsCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\WcffCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\WcProductAddonsCmp;
use ADP\BaseVersion\Includes\Compatibility\Addons\YithAddonsCmp;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddonsCollection;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class ToPricingAddonsAdapter
{
    public function hasAddons(WcCartItemFacade $facade): bool
    {
        return count($this->performAddonsCompatibilityChain($facade)) > 0;
    }

    public function addAddonsToInitialPriceWithFacade(float $initialPrice, WcCartItemFacade $facade): float
    {
        return $initialPrice + array_sum(array_column($this->performAddonsCompatibilityChain($facade), 'price'));
    }

    /**
     * @param float $initialPrice
     * @param array<int, CartItemAddon> $addons
     * @return float
     */
    public function addAddonsToInitialPrice(float $initialPrice, array $addons): float
    {
        return $initialPrice + array_sum(array_column($addons, 'price'));
    }

    public function adaptAddonsFromFacadeAndPutIntoPricingCartItem(
        OriginalPriceCalculation $origPriceCalc,
        WcCartItemFacade $facade,
        ICartItem $cartItem
    ) {
        $addons = $this->performAddonsCompatibilityChain($facade);

        if (count($addons) > 0) {
            $cartItem->setAddons($addons);

            $initialCost = $this->calculateInitialCostWithFacade(
                $origPriceCalc,
                $facade,
                CartItemAddonsCollection::ofList($addons)
            );
            $cartItem->prices()->setOriginalPrice($initialCost);
            $cartItem->prices()->setOriginalPriceToDisplay($initialCost);
        } else {
            $cartItem->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
        }
    }

    /**
     * @param OriginalPriceCalculation $origPriceCalc
     * @param WcProductCalculationWrapper $wrapper
     * @param ICartItem $cartItem
     * @return void
     */
    public function adaptAddonsFromWrapperAndPutIntoPricingCartItem(
        OriginalPriceCalculation $origPriceCalc,
        WcProductCalculationWrapper $wrapper,
        ICartItem $cartItem
    ) {
        $addons = $wrapper->getAddons();

        if (count($addons) > 0) {
            $cartItem->setAddons($addons);

            $initialCost = $this->calculateInitialCost(
                $origPriceCalc,
                CartItemAddonsCollection::ofList($addons)
            );
            $cartItem->prices()->setOriginalPrice($initialCost);
            $cartItem->prices()->setOriginalPriceToDisplay($initialCost);
        } else {
            $cartItem->prices()->setTrdPartyAdjustmentsTotal($origPriceCalc->trdPartyAdjustmentsAmount);
        }
    }

    /**
     * @param WcCartItemFacade $facade
     * @return array<int, CartItemAddon>
     */
    protected function performAddonsCompatibilityChain(WcCartItemFacade $facade): array
    {
        $context = adp_context();
        $addons = [];

        if (($tmCmp = new TmExtraOptionsCmp($context)) && $tmCmp->isActive()) {
            $addons = $tmCmp->getAddonsFromCartItem($facade);
        } elseif (($themeHighCmp = new ThemehighExtraOptionsProCmp()) && $themeHighCmp->isActive()) {
            $addons = $themeHighCmp->getAddonsFromCartItem($facade);
        } elseif (($wcProductAddonsCmp = new WcProductAddonsCmp()) && $wcProductAddonsCmp->isActive()) {
            $addons = $wcProductAddonsCmp->getAddonsFromCartItem($facade);
        } elseif (($wcCustomProductAddonsCmp = new WcCustomProductAddonsCmp()) && $wcCustomProductAddonsCmp->isActive()) {
            $addons = $wcCustomProductAddonsCmp->getAddonsFromCartItem($facade);
        } elseif (($yithAddonsCmp = new YithAddonsCmp()) && $yithAddonsCmp->isActive()) {
            $addons = $yithAddonsCmp->getAddonsFromCartItem($facade);
        } elseif (($flexibleProductFieldsCmp = new FlexibleProductFieldsCmp()) && $flexibleProductFieldsCmp->isActive()) {
            $addons = $flexibleProductFieldsCmp->getAddonsFromCartItem($facade);
        } elseif (($ppomCmp = new PPOMCmp()) && $ppomCmp->isActive()) {
            $addons = $ppomCmp->getAddonsFromCartItem($facade);
        } elseif (($wcffCmp = new WcffCmp()) && $wcffCmp->isActive()) {
            $addons = $wcffCmp->getAddonsFromCartItem($facade);
        }

        return $addons;
    }

    protected function calculateInitialCostWithFacade(
        OriginalPriceCalculation $origPriceCalc,
        WcCartItemFacade $facade,
        CartItemAddonsCollection $addonsCollection
    ) {
        $initialCost = $origPriceCalc->basePrice;

        $initialCost += array_sum(array_column($addonsCollection->toList(), 'price'));

        if ($facade->isImmutable() && $facade->getHistory()) {
            foreach ($facade->getHistory() as $amounts) {
                $initialCost += array_sum($amounts);
            }
        }

        return $initialCost;
    }

    protected function calculateInitialCost(
        OriginalPriceCalculation $origPriceCalc,
        CartItemAddonsCollection $addonsCollection
    ) {
        $initialCost = $origPriceCalc->basePrice;
        $initialCost += array_sum(array_column($addonsCollection->toList(), 'price'));

        return $initialCost;
    }
}
