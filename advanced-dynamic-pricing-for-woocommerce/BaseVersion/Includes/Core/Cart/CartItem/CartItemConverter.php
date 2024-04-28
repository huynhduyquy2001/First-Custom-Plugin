<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceUpdateSourceEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceUpdateTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\AutoAdd\AutoAddCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Free\FreeCartItem;
use ADP\BaseVersion\Includes\SpecialStrategies\OverrideCentsStrategy;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use Exception;
use ReflectionClass;

class CartItemConverter
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var OverrideCentsStrategy
     */
    private $overrideCentsStrategy;

    public function __construct()
    {
        $this->context = adp_context();
        $this->overrideCentsStrategy = new OverrideCentsStrategy();
    }

    public function fromBasicCartItemToFacade(BasicCartItem $cartItem): WcCartItemFacade
    {
        /** have to clone! because of split items are having the same WC_Product object */
        $facade = clone $cartItem->getWcItem();

        $productPrice = $cartItem->getOriginalPrice();
        foreach ($cartItem->getDiscounts() as $ruleId => $amounts) {
            $productPrice -= array_sum($amounts);
        }
        if ($this->context->getOption('is_calculate_based_on_wc_precision')) {
            $productPrice = round($productPrice, wc_get_price_decimals());
        }

        $facade->setOriginalPrice($facade->getProduct()->get_price('edit'));
        if ($cartItem->areRuleApplied()) {
            $productPrice = $this->overrideCentsStrategy->maybeOverrideCentsForItem($productPrice, $cartItem);
        }

        $facade->setNewPrice($productPrice);
        $facade->setHistory($cartItem->getHistory());
        $facade->setDiscounts($cartItem->getDiscounts());
        $facade->setPriceAdjustments($cartItem->getPriceAdjustments());

        $facade->setOriginalPriceWithoutTax($facade->getSubtotal() / $facade->getQty());
        $facade->setOriginalPriceTax($facade->getExactSubtotalTax() / $facade->getQty());
        $facade->setQty($cartItem->getQty());

        return $facade;
    }

    public function fromContainerCartItemToFacade(ContainerCartItem $cartItem): WcCartItemFacade
    {
        /** have to clone! because of split items are having the same WC_Product object */
        $facade = clone $cartItem->getWcItem();

        $facade->setContainerType();
        $facade->setContainerPriceType($cartItem->getContainerPriceTypeEnum());

        $productPrice = $cartItem->getOriginalBasePrice();
        foreach ($cartItem->getBaseDiscounts() as $ruleId => $amounts) {
            $productPrice -= array_sum($amounts);
        }
        if ($this->context->getOption('is_calculate_based_on_wc_precision')) {
            $productPrice = round($productPrice, wc_get_price_decimals());
        }

        $facade->setOriginalPrice($facade->getProduct()->get_price('edit'));
        if ($cartItem->isPriceChanged()) {
            $productPrice = $this->overrideCentsStrategy->maybeOverrideCentsForItem($productPrice, $cartItem);
        }

        $facade->setNewPrice($productPrice);
        $facade->setHistory($cartItem->getHistory());
        $facade->setDiscounts($cartItem->getDiscounts());
        $facade->setPriceAdjustments($cartItem->getPriceAdjustments());

        $facade->setOriginalPriceWithoutTax($facade->getSubtotal() / $facade->getQty());
        $facade->setOriginalPriceTax($facade->getExactSubtotalTax() / $facade->getQty());
        $facade->setQty($cartItem->getQty());

        return $facade;
    }

    /**
     * @param ContainerCartItem $cartItem
     * @return array<int, WcCartItemFacade>
     */
    public function fromContainerCartItemToChildrenFacades(ContainerCartItem $cartItem): array
    {
        return array_map(function ($item) use (&$cartItem) {
            $facade = clone $item->getWcItem();
            $facade->setContaineredType();
            $facade->setParentContainerPriceType($cartItem->getContainerPriceTypeEnum());
            $facade->setContaineredPricedIndividually($item->isPricedIndividually());
            $facade->setParentContainerCartItemHash($cartItem->getWcItem()->getKey());
            $facade->setQty($item->getQty());

            if ($cartItem->getContainerPriceTypeEnum()->equals(ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS())) {
                if ($item->isPricedIndividually()) {
                    $facade->setOriginalPrice($item->getOriginalPrice());

                    $productPrice = $item->getOriginalPrice();
                    foreach ($item->getDiscounts() as $ruleId => $amounts) {
                        $productPrice -= array_sum($amounts);
                    }
                    if ($this->context->getOption('is_calculate_based_on_wc_precision')) {
                        $productPrice = round($productPrice, wc_get_price_decimals());
                    }

                    $facade->setNewPrice($productPrice);
                    $facade->setHistory($item->getHistory());
                    $facade->setDiscounts($item->getDiscounts());
                    $facade->setPriceAdjustments($item->getPriceAdjustments());

                    $facade->setOriginalPriceWithoutTax($facade->getSubtotal() / $facade->getQty() / $cartItem->getQty() );
                    $facade->setOriginalPriceTax($facade->getExactSubtotalTax() / $facade->getQty() / $cartItem->getQty());
                    $facade->setQty($item->getQty() * $cartItem->getQty());
                }
            } else {
                $facade->getProduct()->set_price(0.0);
            }

            return $facade;
        }, $cartItem->getItems());
    }

    public function fromFacadeToFreeCartItem(WcCartItemFacade $facade, int $pos = -1): ?FreeCartItem
    {
        $ruleId = array_keys($facade->getHistory());
        $ruleId = reset($ruleId);

        $product = clone $facade->getProduct();

        try {
            $reflection = new ReflectionClass($product);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $property->setValue($product, array());
        } catch (Exception $e) {

        }

        try {
            $item = new FreeCartItem($product, 0, $ruleId, $facade->getAssociatedHash());
        } catch (Exception $e) {
            return null;
        }

        $item->setPos($pos);

        $item->setQtyAlreadyInWcCart($facade->getQty());

        $item->setVariation($facade->getVariation());
        $item->setCartItemData($facade->getThirdPartyData());

        if ($facade->getReplaceWithCoupon()) {
            $item->setReplaceWithCoupon(true);
            $item->setReplaceCouponCode($facade->getReplaceCouponCode());
        }

        $item->setSelected($facade->isSelectedFreeCartItem());

        return $item;
    }

    public function fromFreeCartItemToFacade(WcCartItemFacade $hostFacade, FreeCartItem $freeItem): WcCartItemFacade
    {
        $facade = $hostFacade;

        $rules = [$freeItem->getRuleId() => [$freeItem->getInitialPrice()]];

        $cartItemQty = $facade->getQty();
        $facade->setQty($freeItem->getQty());

        $facade->setOriginalPrice($facade->getProduct()->get_price('edit'));

        $facade->addAttribute($facade::ATTRIBUTE_FREE);

        $priceAdjBuilder = CartItemPriceAdjustment::builder()
            ->source(CartItemPriceUpdateSourceEnum::SOURCE_FREE_ITEM())
            ->originalPrice(floatval($facade->getProduct()->get_price('edit')))
            ->amount($freeItem->getInitialPrice())
            ->newPrice(0.0)
            ->ruleId($freeItem->getRuleId());

        if ($freeItem->isReplaceWithCoupon()) {
            // no need to change the price, it is already full
            $facade->setDiscounts(array());
            $priceAdjBuilder->type(CartItemPriceUpdateTypeEnum::REPLACED_BY_CART_ADJUSTMENT());
            $facade->setReplaceWithCoupon(true);
            $facade->setReplaceCouponCode($freeItem->getReplaceCouponCode());
        } else {
            $priceAdjBuilder->type(CartItemPriceUpdateTypeEnum::DEFAULT());
            $facade->setNewPrice(0);
            $facade->setDiscounts($rules);
        }

        $facade->setOriginalPriceWithoutTax($facade->getSubtotal() / $cartItemQty);
        $facade->setOriginalPriceTax($facade->getExactSubtotalTax() / $cartItemQty);
        $facade->setHistory($rules);
        $facade->setAssociatedHash($freeItem->getAssociatedGiftHash());
        $facade->setFreeCartItemHash($freeItem->hash());
        $facade->setSelectedFreeCartItem($freeItem->isSelected());
        $facade->setPriceAdjustments([$priceAdjBuilder->build()]);

        return $facade;
    }

    public function fromFacadeToAutoAddCartItem(WcCartItemFacade $facade, int $pos = -1): ?AutoAddCartItem
    {
        $ruleId = array_keys($facade->getHistory());
        $ruleId = reset($ruleId);

        $product = clone $facade->getProduct();

        try {
            $reflection = new ReflectionClass($product);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $property->setValue($product, array());
        } catch (Exception $e) {

        }

        try {
            $item = new AutoAddCartItem($product, 0, $ruleId, $facade->getAssociatedHash());
        } catch (Exception $e) {
            return null;
        }

        $item->setPos($pos);

        $item->setQtyAlreadyInWcCart($facade->getQty());

        $item->setVariation($facade->getVariation());
        $item->setCartItemData($facade->getThirdPartyData());

        if ($facade->getReplaceWithCoupon()) {
            $item->setReplaceWithCoupon(true);
            $item->setReplaceCouponCode($facade->getReplaceCouponCode());
        }

        $item->setCanBeRemoved($facade->autoAddCanBeRemoved());

        return $item;
    }

    public function fromAutoAddCartItemToFacade(
        WcCartItemFacade $hostFacade,
        AutoAddCartItem $autoAddItem
    ): WcCartItemFacade {
        $facade = $hostFacade;

        $rules = [
            $autoAddItem->getRuleId() => [
                $facade->getProduct()->get_price('edit') - $autoAddItem->getPrice()
            ]
        ];

        $cartItemQty = $facade->getQty();
        $facade->setQty($autoAddItem->getQty());

        $facade->setOriginalPrice($facade->getProduct()->get_price('edit'));

        $facade->addAttribute($facade::ATTRIBUTE_AUTOADD);

        if ($autoAddItem->isRecommended()) {
            $facade->addAttribute($facade::ATTRIBUTE_RECOMMENDED_AUTOADD);
        }

        $priceAdjBuilder = CartItemPriceAdjustment::builder()
            ->source(CartItemPriceUpdateSourceEnum::SOURCE_AUTOADD_ITEM())
            ->originalPrice(floatval($facade->getProduct()->get_price('edit')))
            ->amount(floatval($facade->getProduct()->get_price('edit') - $autoAddItem->getPrice()))
            ->newPrice($autoAddItem->getPrice())
            ->ruleId($autoAddItem->getRuleId());

        if ($autoAddItem->isReplaceWithCoupon()) {
            // no need to change the price, it is already full
            $facade->setDiscounts([]);
            $priceAdjBuilder->type(CartItemPriceUpdateTypeEnum::REPLACED_BY_CART_ADJUSTMENT());

            $facade->setReplaceWithCoupon(true);
            $facade->setReplaceCouponCode($autoAddItem->getReplaceCouponCode());
        } else {
            $priceAdjBuilder->type(CartItemPriceUpdateTypeEnum::DEFAULT());
            $facade->setNewPrice($autoAddItem->getPrice());
            $facade->setDiscounts($rules);
        }

        $facade->setOriginalPriceWithoutTax($facade->getSubtotal() / $cartItemQty);
        $facade->setOriginalPriceTax($facade->getExactSubtotalTax() / $cartItemQty);
        $facade->setHistory($rules);
        $facade->setAssociatedHash($autoAddItem->getAssociatedHash());
        $facade->setAutoAddCartItemHash($autoAddItem->hash());
        $facade->setAutoAddCanBeRemoved($autoAddItem->canBeRemoved());
        $facade->setPriceAdjustments([$priceAdjBuilder->build()]);

        return $facade;
    }
}
