<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\CartProcessor\OriginalPriceCalculation;
use ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter\ToPricingAddonsAdapter;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Context\Container\ContainerCompatibility;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPartCartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\Factory;

abstract class AbstractContainerCompatibility implements ContainerCompatibility
{
    abstract protected function getContext(): Context;

    abstract public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float;

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    abstract public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float;

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    abstract public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float;

    abstract public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum;

    abstract public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool;

    public function adaptContainerPartCartItem(WcCartItemFacade $facade): ContainerPartCartItem
    {
        $origPriceCalc = new OriginalPriceCalculation();
        $origPriceCalc->withContext($this->getContext());

        Factory::callStaticMethod(
            'PriceDisplay_PriceDisplay',
            'processWithout',
            array($origPriceCalc, 'process'),
            $facade
        );

        $qty = floatval(apply_filters('wdp_get_product_qty', $facade->getQty(), $facade));

        $product = $facade->getProduct();
        $reflection = new \ReflectionClass($product);
        $property = $reflection->getProperty('data');
        $property->setAccessible(true);
        $basePrice = $property->getValue($product)['price'];

        $initialPrice = $this->calculatePartOfContainerPrice($facade);
        $initialPrice = (new ToPricingAddonsAdapter())->addAddonsToInitialPriceWithFacade($initialPrice, $facade);

        return new ContainerPartCartItem(
            $facade,
            floatval($basePrice),
            $this->isPartOfContainerFacadePricedIndividually($facade),
            $initialPrice,
            $qty
        );
    }

    public function adaptContainerCartItem(
        WcCartItemFacade $facade,
        array $children,
        int $pos
    ): ContainerCartItem {
        $containerItem = new ContainerCartItem(
            $facade,
            $this,
            $this->getContainerPriceTypeByParentFacade($facade),
            $this->calculateContainerPrice($facade, $children),
            $this->calculateContainerBasePrice($facade, $children),
            array_map([$this, 'adaptContainerPartCartItem'], $children),
            floatval(apply_filters('wdp_get_product_qty', $facade->getQty(), $facade)),
            $pos
        );

        if (!$facade->isVisible() || $facade->isImmutable()) {
            $containerItem->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        if ($facade->isHasReadOnlyPrice()) {
            $containerItem->addAttr(CartItemAttributeEnum::READONLY_PRICE());
        }

        return $containerItem;
    }

    public function adaptContainerWcProduct(\WC_Product $product, $cartItemData = []): ?ContainerCartItem
    {
        $facade = WcCartItemFacade::createFromProduct($this->getContext(), $product, $cartItemData);

        $partOfContainerFacades = [];
        $childItemsPrice = 0.0;
        $newItems = [];
        foreach ($this->getListOfPartsOfContainerFromContainerProduct($product) as $containerPartProduct) {
            $newItem = $this->adaptContainerPartProduct($containerPartProduct);

            $newItems[] = $newItem;
            $partOfContainerFacades[] = $newItem->getWcItem();

            if ($containerPartProduct->isPricedIndividually()) {
                $childItemsPrice += (float)$containerPartProduct->getProduct()->get_price('edit') * $containerPartProduct->getQty();
            }
        }

        $basePrice = $this->calculateContainerBasePrice($facade, $partOfContainerFacades);

        $containerItem = new ContainerCartItem(
            $facade,
            $this,
            $this->getContainerPriceTypeByParentFacade($facade),
            $basePrice + $childItemsPrice,
            $this->calculateContainerBasePrice($facade, $partOfContainerFacades),
            $newItems,
            1.0,
            -1
        );

        if (!$facade->isVisible()) {
            $containerItem->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        return $containerItem;
    }

    protected function adaptContainerPartProduct(ContainerPartProduct $containerPartProduct): ContainerPartCartItem {
        $facade = WcCartItemFacade::createFromProduct($this->getContext(), $containerPartProduct->getProduct());
        $facade->setQty($containerPartProduct->getQty());

        $origPriceCalc = new OriginalPriceCalculation();
        $origPriceCalc->withContext($this->getContext());

        Factory::callStaticMethod(
            'PriceDisplay_PriceDisplay',
            'processWithout',
            array($origPriceCalc, 'process'),
            $facade
        );

        return new ContainerPartCartItem(
            $facade,
            $containerPartProduct->getPrice(),
            $containerPartProduct->isPricedIndividually(),
            $containerPartProduct->getPrice(),
            $containerPartProduct->getQty()
        );
    }
}
