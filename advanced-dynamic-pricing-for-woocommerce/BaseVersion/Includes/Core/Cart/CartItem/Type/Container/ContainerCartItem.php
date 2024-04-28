<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container;

use ADP\BaseVersion\Includes\Context\Container\ContainerCompatibility;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceUpdateTypeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\AbstractCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

class ContainerCartItem extends AbstractCartItem implements ICartItem
{
    /** @var float */
    protected $basePrice;

    /** @var CartItemPriceAdjustment[] */
    protected $basePriceAdjustments;

    /** @var string */
    protected $hash;

    /** @var array<int, ContainerPartCartItem> */
    protected $items;

    /** @var string */
    protected $mergeHash;

    /** @var ContainerCompatibility */
    protected $compatibility;

    /** @var ContainerPriceTypeEnum */
    protected $containerPriceTypeEnum;

    /** @var ContainerCartItemPrices */
    protected $containerAdditionalPrices;

    public function __construct(
        WcCartItemFacade $wcCartItemFacade,
        ContainerCompatibility $compatibility,
        ContainerPriceTypeEnum $containerPriceTypeEnum,
        float $originalPrice,
        float $basePrice,
        array $items,
        float $qty,
        int $pos = -1
    ) {
        parent::__construct($wcCartItemFacade, $originalPrice, $qty, $pos);
        $this->compatibility = $compatibility;
        $this->containerPriceTypeEnum = $containerPriceTypeEnum;
        $this->items = $items;

        $this->containerAdditionalPrices = ContainerCartItemPrices::ofOriginalPrices($basePrice);

        $this->basePrice = $basePrice;
        $this->basePriceAdjustments = [];

        $this->recalculateHash();
    }

    public function cleanAllAdjustments()
    {
        $this->price = $this->getOriginalPrice();
        $this->priceAdjustments = [];

        $this->basePrice = $this->getOriginalBasePrice();
        $this->basePriceAdjustments = [];

        $this->recalculateHash();
    }

    public function getContainerPriceTypeEnum(): ContainerPriceTypeEnum
    {
        return $this->containerPriceTypeEnum;
    }

    /**
     * @return ContainerPartCartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getOriginalBasePrice(): float
    {
        return $this->containerAdditionalPrices->getOriginalBasePrice();
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getCompatibility(): ContainerCompatibility
    {
        return $this->compatibility;
    }

    public function applyPriceAdjustment(CartItemPriceAdjustment $adjustment)
    {
        if (
            $this->hasAttr(CartItemAttributeEnum::READONLY_PRICE())
            || $this->hasAttr(CartItemAttributeEnum::IMMUTABLE())
        ) {
            return;
        }

        $totalPrice = $this->getBasePrice();
        foreach ($this->getItems() as $item) {
            if ($item->isPricedIndividually()) {
                $totalPrice += $item->getTotalPrice();
            }
        }

        if (!$totalPrice) {
            return;
        }

        $diff = ($totalPrice - $adjustment->getAmount()) / $totalPrice;

        $baseAdjBuilder = $adjustment->toBuilder();
        $baseAdjBuilder->originalPrice($this->getBasePrice());
        $baseAdjBuilder->newPrice($this->getBasePrice() * $diff);
        $baseAdjBuilder->amount($this->getBasePrice() * (1 - $diff));
        $newBaseAdjustment = $baseAdjBuilder->build();
        $this->basePriceAdjustments[] = $newBaseAdjustment;
        $this->basePrice = $newBaseAdjustment->getNewPrice();

        foreach ($this->getItems() as $item) {
            if ($item->isPricedIndividually()) {
                $itemAdjBuilder = $adjustment->toBuilder();
                $itemAdjBuilder->originalPrice($item->getPrice());
                $itemAdjBuilder->newPrice($item->getPrice() * $diff);
                $itemAdjBuilder->amount($item->getPrice() * (1 - $diff));
                $item->applyPriceAdjustment($itemAdjBuilder->build());
            }
        }

        parent::applyPriceAdjustment($adjustment);
    }

    /**
     * @return array<int, array<int, float>>
     */
    public function getBaseDiscounts(): array
    {
        $discounts = [];
        foreach ($this->basePriceAdjustments as $adjustment) {
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

    public function getMergeHash(): string
    {
        $this->recalculateMergeHash();
        return $this->mergeHash;
    }

    /**
     * @param ContainerPartCartItem[] $items
     */
    public function setItems(array $items)
    {
        $this->items = $items;

        return $this;
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
