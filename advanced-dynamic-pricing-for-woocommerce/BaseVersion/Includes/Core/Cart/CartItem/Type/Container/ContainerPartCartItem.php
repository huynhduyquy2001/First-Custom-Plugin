<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

class ContainerPartCartItem extends BasicCartItem
{
    /** @var float */
    protected $basePrice;

    /** @var bool */
    protected $pricedIndividually;

    public function __construct(
        WcCartItemFacade $wcCartItemFacade,
        float $basePrice,
        bool $pricedIndividually,
        float $originalPrice,
        $qty,
        $pos = -1
    ) {
        parent::__construct($wcCartItemFacade, $originalPrice, $qty, $pos);
        $this->basePrice = $basePrice;
        $this->pricedIndividually = $pricedIndividually;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function isPricedIndividually(): bool
    {
        return $this->pricedIndividually;
    }
}
