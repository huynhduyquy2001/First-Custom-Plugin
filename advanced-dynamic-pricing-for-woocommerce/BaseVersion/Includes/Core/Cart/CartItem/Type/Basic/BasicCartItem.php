<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceUpdateTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\AbstractCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

class BasicCartItem extends AbstractCartItem implements ICartItem
{
    /** @var float */
    protected $price;

    /** @var string */
    protected $hash;

    /** @var string */
    protected $mergeHash;

    /** @var CartItemPriceAdjustment[] */
    protected $priceAdjustments;

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     * @param float|string $originalPrice
     * @param float $qty
     * @param int $pos
     */
    public function __construct(WcCartItemFacade $wcCartItemFacade, $originalPrice, $qty, $pos = -1)
    {
        parent::__construct($wcCartItemFacade, (float)$originalPrice, $qty, $pos);

        $this->recalculateHash();
    }

    public function __clone()
    {
        parent::__clone();
        $this->additionalPrices = clone $this->additionalPrices;
        $this->priceAdjustments = array_map(function ($adj) {
            return clone $adj;
        }, $this->priceAdjustments);

        $this->recalculateHash();
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function applyPriceAdjustment(CartItemPriceAdjustment $adjustment)
    {
        if ($this->hasAttr(CartItemAttributeEnum::READONLY_PRICE()) || $this->hasAttr(CartItemAttributeEnum::IMMUTABLE())) {
            return;
        }

        $this->priceAdjustments[] = $adjustment;
        $this->price -= $adjustment->getAmount();
        $this->recalculateHash();
    }

    /**
     * @return CartItemPriceAdjustment[]
     */
    public function getPriceAdjustments(): array
    {
        return $this->priceAdjustments;
    }

    public function getOriginalPrice(): float
    {
        return $this->prices()->getOriginalPrice();
    }

    /**
     * @param int $initialCartPosition
     */
    public function setInitialCartPosition($initialCartPosition)
    {
        parent::setInitialCartPosition($initialCartPosition);
        $this->recalculateHash();
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

    public function getHash(): string
    {
        return $this->hash;
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

    public function getMergeHash(): string
    {
        $this->recalculateMergeHash();
        return $this->mergeHash;
    }

    public function isHistoryEqualsDiscounts(): bool
    {
        return $this->getDiscounts() === $this->getHistory();
    }

    // hash methods
    protected function recalculateHash()
    {
        // todo adjustments?

        $this->hash = md5(serialize([
            $this->getOriginalPrice(),
            $this->attributes->hash(),
            $this->getHistory(),
            $this->initialCartPosition,
            $this->addons->hash(),
        ]));
    }

    protected function recalculateMergeHash()
    {
        $this->mergeHash = md5(serialize([
            $this->getOriginalPrice(),
            $this->attributes->hash(),
            $this->wcItem->getKey(),
            $this->initialCartPosition,
            $this->addons->hash(),
            $this->additionalPrices->hash()
        ]));
    }

    public function calculateNonTemporaryHash(): string
    {
        $attributes = clone $this->attributes;
        $attributes->remove(CartItemAttributeEnum::TEMPORARY());

        return md5(serialize([
            $this->getOriginalPrice(),
            $attributes->hash(),
            $this->getHistory(),
            $this->wcItem->getKey(),
            $this->addons->hash(),
        ]));
    }
    // hash methods finish
}
