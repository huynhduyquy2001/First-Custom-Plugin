<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment;

class CartItemPriceAdjustmentBuilder
{
    /** @var CartItemPriceUpdateTypeEnum */
    private $type;

    /** @var CartItemPriceUpdateSourceEnum */
    private $source;

    private $originalPrice;
    private $amount;
    private $newPrice;

    private $ruleId;

    public static function ofCartItemPriceAdjustment(CartItemPriceAdjustment $adjustment)
    {
        $builder = new self();
        $builder->type = $adjustment->getType();
        $builder->source = $adjustment->getSource();
        $builder->originalPrice = $adjustment->getOriginalPrice();
        $builder->amount = $adjustment->getAmount();
        $builder->newPrice = $adjustment->getNewPrice();
        $builder->ruleId = $adjustment->getRuleId();

        return $builder;
    }

    public function type(CartItemPriceUpdateTypeEnum $type): CartItemPriceAdjustmentBuilder
    {
        $this->type = $type;
        return $this;
    }

    public function source(CartItemPriceUpdateSourceEnum $source): CartItemPriceAdjustmentBuilder
    {
        $this->source = $source;
        return $this;
    }

    public function originalPrice(float $originalPrice): CartItemPriceAdjustmentBuilder
    {
        $this->originalPrice = $originalPrice;
        return $this;
    }

    public function amount(float $amount): CartItemPriceAdjustmentBuilder
    {
        $this->amount = $amount;
        return $this;
    }

    public function newPrice(float $newPrice): CartItemPriceAdjustmentBuilder
    {
        $this->newPrice = $newPrice;
        return $this;
    }

    public function ruleId(int $ruleId): CartItemPriceAdjustmentBuilder
    {
        $this->ruleId = $ruleId;
        return $this;
    }

    public function build(): CartItemPriceAdjustment
    {
        return new CartItemPriceAdjustment(
            $this->type,
            $this->source,
            $this->originalPrice,
            $this->amount,
            $this->newPrice,
            $this->ruleId
        );
    }
}
