<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

class CartItemPrices
{
    protected $originalPrice;
    protected $originalPriceToDisplay;
    protected $minDiscountRangePrice;
    protected $trdPartyAdjustmentsTotal;

    public static function ofOriginalPrices(
        $originalPrice,
        $originalPriceToDisplay
    ): CartItemPrices {
        $obj = new self();

        $obj->originalPrice = $originalPrice;
        $obj->originalPriceToDisplay = $originalPriceToDisplay;
        $obj->minDiscountRangePrice = null;
        $obj->trdPartyAdjustmentsTotal = 0.0;

        return $obj;
    }

    public function hash(): string
    {
        return md5(serialize([
            $this->originalPrice,
            $this->originalPriceToDisplay,
            $this->minDiscountRangePrice,
            $this->trdPartyAdjustmentsTotal
        ]));
    }

    /**
     * @return mixed
     */
    public function getOriginalPrice()
    {
        return $this->originalPrice;
    }

    /**
     * @param mixed $originalPrice
     */
    public function setOriginalPrice($originalPrice): void
    {
        $this->originalPrice = $originalPrice;
    }

    /**
     * @return mixed
     */
    public function getOriginalPriceToDisplay()
    {
        return $this->originalPriceToDisplay;
    }

    /**
     * @param mixed $originalPriceToDisplay
     * @return CartItemPrices
     */
    public function setOriginalPriceToDisplay($originalPriceToDisplay)
    {
        $this->originalPriceToDisplay = $originalPriceToDisplay;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinDiscountRangePrice()
    {
        return $this->minDiscountRangePrice;
    }

    /**
     * @param mixed $minDiscountRangePrice
     * @return CartItemPrices
     */
    public function setMinDiscountRangePrice($minDiscountRangePrice)
    {
        $this->minDiscountRangePrice = $minDiscountRangePrice;
        return $this;
    }

    /**
     * @return float
     */
    public function getTrdPartyAdjustmentsTotal()
    {
        return $this->trdPartyAdjustmentsTotal;
    }

    /**
     * @param float $trdPartyAdjustmentsTotal
     */
    public function setTrdPartyAdjustmentsTotal($trdPartyAdjustmentsTotal)
    {
        $this->trdPartyAdjustmentsTotal = (float)$trdPartyAdjustmentsTotal;
        return $this;
    }
}
