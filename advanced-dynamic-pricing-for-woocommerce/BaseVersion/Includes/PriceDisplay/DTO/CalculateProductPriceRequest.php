<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\DTO;

class CalculateProductPriceRequest
{
    /**
     * @var CalculatePriceProductDTO
     */
    private $product;

    /**
     * @var CalculatePricePageDataDTO
     */
    private $pageData;

    public function __construct(CalculatePriceProductDTO $product, CalculatePricePageDataDTO $pageData)
    {
        $this->product = $product;
        $this->pageData = $pageData;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray($data): CalculateProductPriceRequest
    {
        return new self(
            CalculatePriceProductDTO::fromArray($data),
            CalculatePricePageDataDTO::fromArray($data)
        );
    }

    public function getProduct(): CalculatePriceProductDTO
    {
        return $this->product;
    }

    public function getPageData(): CalculatePricePageDataDTO
    {
        return $this->pageData;
    }
}
