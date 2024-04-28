<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\DTO;

class CalculatePricePageDataDTO
{
    private $isProduct;

    public function __construct(bool $isProduct)
    {
        $this->isProduct = $isProduct;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray($data): CalculatePricePageDataDTO
    {
        $pageData = $data['page_data'] ?? [];
        $isProduct = isset($pageData['is_product']) && wc_string_to_bool($pageData['is_product']);

        return new self($isProduct);
    }

    public function isProduct(): bool
    {
        return $this->isProduct;
    }
}
