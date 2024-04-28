<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container;

class ContainerCartItemPrices
{
    private $originalBasePrice;

    public static function ofOriginalPrices(
        $originalBasePrice
    ): ContainerCartItemPrices {
        $obj = new self();

        $obj->originalBasePrice = $originalBasePrice;

        return $obj;
    }

    /**
     * @return mixed
     */
    public function getOriginalBasePrice()
    {
        return $this->originalBasePrice;
    }
}
