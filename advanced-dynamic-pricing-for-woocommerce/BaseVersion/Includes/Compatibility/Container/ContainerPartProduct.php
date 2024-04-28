<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

class ContainerPartProduct
{
    /** @var \WC_Product */
    protected $parent;

    /** @var \WC_Product */
    protected $product;

    /** @var float */
    protected $price;

    /** @var float */
    protected $qty;

    /** @var bool */
    protected $pricedIndividually;

    private function __construct($parent, $product, $price, $qty, $pricedIndividually)
    {
        $this->parent = $parent;
        $this->product = $product;
        $this->price = $price;
        $this->qty = $qty;
        $this->pricedIndividually = $pricedIndividually;
    }

    public static function of(
        \WC_Product $parent,
        \WC_Product $product,
        float $price,
        float $qty,
        bool $pricedIndividually
    ): ContainerPartProduct {
        return new self($parent, $product, $price, $qty, $pricedIndividually);
    }

    public function getParent(): \WC_Product
    {
        return $this->parent;
    }

    public function getProduct(): \WC_Product
    {
        return $this->product;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQty(): float
    {
        return $this->qty;
    }

    /**
     * @return bool
     */
    public function isPricedIndividually(): bool
    {
        return $this->pricedIndividually;
    }
}
