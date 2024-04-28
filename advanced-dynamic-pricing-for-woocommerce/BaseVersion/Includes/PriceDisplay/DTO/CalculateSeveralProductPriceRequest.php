<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\DTO;

class CalculateSeveralProductPriceRequest
{
    /**
     * @var array<int, CalculatePriceProductDTO>
     */
    private $products;

    /**
     * @var CalculatePricePageDataDTO
     */
    private $pageData;

    public function __construct(array $products, CalculatePricePageDataDTO $pageData)
    {
        $this->products = array_filter($products, function ($product) {
            return $product instanceof CalculatePriceProductDTO;
        });
        $this->pageData = $pageData;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray($data): CalculateSeveralProductPriceRequest
    {
        return new self(
            array_map(
                function ($productData) {
                    return CalculatePriceProductDTO::fromArray($productData);
                },
                $data['products_list'] ?? []
            ),
            CalculatePricePageDataDTO::fromArray($data)
        );
    }

    /**
     * @return array<int, CalculatePriceProductDTO>
     */
    public function getProducts(): array
    {
        return $this->products;
    }

    public function getPageData(): CalculatePricePageDataDTO
    {
        return $this->pageData;
    }
}
