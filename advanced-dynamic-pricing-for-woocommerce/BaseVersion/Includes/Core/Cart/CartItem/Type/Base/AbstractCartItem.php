<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceUpdateTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

abstract class AbstractCartItem
{
    /** @var CartItemAddonsCollection */
    protected $addons;

    /** @var CartItemAttributes */
    protected $attributes;

    /** @var int */
    protected $initialCartPosition;

    /** @var WcCartItemFacade */
    protected $wcItem;

    /** @var float */
    protected $qty;

    /** @var float */
    protected $weight;

    /** @var CartItemPrices */
    protected $additionalPrices;

    /** @var CartItemPriceAdjustment[] */
    protected $priceAdjustments;

    /** @var float */
    protected $price;

    public function __construct(
        WcCartItemFacade $wcCartItemFacade,
        float $originalPrice,
        float $qty,
        int $pos = -1
    ) {
        $this->wcItem = $wcCartItemFacade;
        $this->qty = $qty;
        $this->weight = $wcCartItemFacade->getProduct()->get_weight("edit");
        $this->weight = $this->weight !== "" ? floatval($this->weight) : null;
        $this->initialCartPosition = is_numeric($pos) ? (int)$pos : -1;

        $this->addons = new CartItemAddonsCollection();
        $this->attributes = new CartItemAttributes();

        $this->price = $originalPrice;
        $this->priceAdjustments = [];

        $this->additionalPrices = CartItemPrices::ofOriginalPrices(
            $originalPrice,
            $originalPrice
        );
    }

    public function __clone()
    {
        $this->wcItem = clone $this->wcItem;
        $this->addons = clone $this->addons;
        $this->attributes = clone $this->attributes;
    }

    public function cleanAllAdjustments()
    {
        $this->priceAdjustments = [];
        $this->price = $this->getOriginalPrice();

        $this->recalculateHash();
    }

    // addons methods

    /**
     * @param CartItemAddon $addon
     */
    public function addAddon(CartItemAddon $addon)
    {
        $this->addons->put($addon);
        $this->recalculateHash();
    }

    /**
     * @param array<int, CartItemAddon> $addons
     */
    public function setAddons(array $addons)
    {
        $this->addons = CartItemAddonsCollection::ofList($addons);
        $this->recalculateHash();

        return $this;
    }

    /**
     * @return array<int, CartItemAddon>
     */
    public function getAddons()
    {
        return $this->addons->toList();
    }

    public function getAddonsAmount(): float
    {
        return (float)array_sum(array_map(function ($addon) {
            return $addon->price;
        }, $this->getAddons()));
    }
    // addons methods finish

    // attribute methods
    public function hasAttr(CartItemAttributeEnum $attribute): bool
    {
        return $this->attributes->contains($attribute);
    }

    public function addAttr(...$attributes)
    {
        foreach ($attributes as $attribute) {
            $this->attributes->put($attribute);
        }

        $this->recalculateHash();

        return $this;
    }

    public function removeAttr(...$attributes)
    {
        foreach ($attributes as $attribute) {
            $this->attributes->remove($attribute);
        }

        $this->recalculateHash();
    }

    public function copyAttributesTo(ICartItem $cartItem)
    {
        $this->attributes->copyTo($cartItem->attributes);
    }

    /**
     * @return CartItemAttributeEnum[]
     */
    public function getAttributes()
    {
        return $this->attributes->toList();
    }

    // attribute finish

    // initial cart pos methods
    /** @param int $initialCartPosition */
    public function setInitialCartPosition($initialCartPosition)
    {
        $this->initialCartPosition = $initialCartPosition;
        $this->recalculateHash();

        return $this;
    }

    public function getInitialCartPosition(): int
    {
        return $this->initialCartPosition;
    }
    // initial cart pos methods finish

    /** @return WcCartItemFacade */
    public function getWcItem(): WcCartItemFacade
    {
        return $this->wcItem;
    }

    // qty methods
    public function getQty(): float
    {
        return $this->qty;
    }

    /** @param float $qty */
    public function setQty($qty)
    {
        $this->qty = floatval($qty);

        return $this;
    }

    // qty methods finish

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight)
    {
        $this->weight = $weight;

        return $this;
    }

    public function prices(): CartItemPrices
    {
        return $this->additionalPrices;
    }

    protected function applyPriceAdjustment(CartItemPriceAdjustment $adjustment)
    {
        $this->priceAdjustments[] = $adjustment;
        $this->price = $adjustment->getNewPrice();
        $this->recalculateHash();
    }

    public function getPriceAdjustments(): array
    {
        return $this->priceAdjustments;
    }

    public function getOriginalPrice(): float
    {
        return $this->prices()->getOriginalPrice();
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function getHistory(): array
    {
        $history = [];
        foreach ($this->priceAdjustments as $adjustment) {
            if (!isset($history[$adjustment->getRuleId()])) {
                $history[$adjustment->getRuleId()] = [];
            }

            $history[$adjustment->getRuleId()][] = $adjustment->getAmount();
        }

        return $history;
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function getDiscounts(): array
    {
        $discounts = [];
        foreach ($this->priceAdjustments as $adjustment) {
            if (!$adjustment->getType()->equals(CartItemPriceUpdateTypeEnum::DEFAULT())) {
                continue;
            }

            if (!isset($discounts[$adjustment->getRuleId()])) {
                $discounts[$adjustment->getRuleId()] = [];
            }

            $discounts[$adjustment->getRuleId()][] = $adjustment->getAmount();
        }

        return $discounts;
    }

    public function getTotalPrice()
    {
        return $this->getPrice() * $this->qty;
    }

    public function areRuleApplied(): bool
    {
        foreach ($this->getHistory() as $amounts) {
            if (floatval(array_sum($amounts)) !== floatval(0)) {
                return true;
            }
        }

        return false;
    }

    public function isPriceChanged(): bool
    {
        foreach ($this->getDiscounts() as $amounts) {
            if (floatval(array_sum($amounts)) !== floatval(0)) {
                return true;
            }
        }

        return false;
    }

    public function isHistoryEqualsDiscounts(): bool
    {
        return $this->getDiscounts() === $this->getHistory();
    }

    abstract protected function recalculateHash();
}
