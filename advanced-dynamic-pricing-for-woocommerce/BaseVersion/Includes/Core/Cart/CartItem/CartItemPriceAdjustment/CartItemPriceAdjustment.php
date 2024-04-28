<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment;

class CartItemPriceAdjustment
{
    /** @var CartItemPriceUpdateTypeEnum */
    private $type;

    /** @var CartItemPriceUpdateSourceEnum */
    private $source;

    private $originalPrice;
    private $amount;
    private $newPrice;

    private $ruleId;

    public function __construct(
        CartItemPriceUpdateTypeEnum $type,
        CartItemPriceUpdateSourceEnum $source,
        float $originalPrice,
        float $amount,
        float $newPrice,
        int $ruleId
    ) {
        $this->type = $type;
        $this->source = $source;
        $this->originalPrice = $originalPrice;
        $this->amount = $amount;
        $this->newPrice = $newPrice;
        $this->ruleId = $ruleId;
    }

    public static function builder(): CartItemPriceAdjustmentBuilder
    {
        return new CartItemPriceAdjustmentBuilder();
    }

    public function toBuilder(): CartItemPriceAdjustmentBuilder
    {
        return CartItemPriceAdjustmentBuilder::ofCartItemPriceAdjustment($this);
    }

    public function getType(): CartItemPriceUpdateTypeEnum
    {
        return $this->type;
    }

    public function getSource(): CartItemPriceUpdateSourceEnum
    {
        return $this->source;
    }

    public function getOriginalPrice(): float
    {
        return $this->originalPrice;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getNewPrice(): float
    {
        return $this->newPrice;
    }

    public function getRuleId(): int
    {
        return $this->ruleId;
    }

    public static function ofDict($dict): CartItemPriceAdjustment
    {
        return new CartItemPriceAdjustment(
            new CartItemPriceUpdateTypeEnum($dict['type']),
            new CartItemPriceUpdateSourceEnum($dict['source']),
            $dict['originalPrice'],
            $dict['amount'],
            $dict['newPrice'],
            $dict['ruleId']
        );
    }

    public function toDict(): array
    {
        return [
            'type' => $this->type->getValue(),
            'source' => $this->source->getValue(),
            'originalPrice' => $this->originalPrice,
            'amount' => $this->amount,
            'newPrice' => $this->newPrice,
            'ruleId' => $this->ruleId
        ];
    }
}
