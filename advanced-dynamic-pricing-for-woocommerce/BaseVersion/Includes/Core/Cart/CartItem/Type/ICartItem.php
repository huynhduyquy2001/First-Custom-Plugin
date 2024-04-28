<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment\CartItemPriceAdjustment;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemPrices;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

interface ICartItem
{
    public function __clone();

    public function addAddon(CartItemAddon $addon);

    public function setAddons(array $addons);

    public function getAddonsAmount(): float;

    public function hasAttr(CartItemAttributeEnum $attribute): bool;

    public function addAttr(...$attributes);

    public function removeAttr(...$attributes);

    public function copyAttributesTo(ICartItem $cartItem);

    public function getInitialCartPosition(): int;

    public function getWcItem(): WcCartItemFacade;

    public function getQty(): float;

    public function setQty(float $qty);

    public function getPrice(): float;

    public function applyPriceAdjustment(CartItemPriceAdjustment $adjustment);

    /**
     * @return CartItemPriceAdjustment[]
     */
    public function getPriceAdjustments(): array;

    public function getOriginalPrice(): float;

    public function setInitialCartPosition(int $initialCartPosition);

    /**
     * @return array<int, array<int, float>>
     */
    public function getHistory(): array;

    /**
     * @return array<int, array<int, float>>
     */
    public function getDiscounts(): array;

    public function getHash(): string;

    public function getTotalPrice();

    public function areRuleApplied(): bool;

    public function isPriceChanged(): bool;

    public function getMergeHash(): string;

    public function isHistoryEqualsDiscounts(): bool;

    public function prices(): CartItemPrices;

    public function calculateNonTemporaryHash(): string;
}
