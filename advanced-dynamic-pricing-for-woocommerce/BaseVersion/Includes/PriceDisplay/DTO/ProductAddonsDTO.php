<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\DTO;

class ProductAddonsDTO
{
    /**
     * @var string
     */
    private $value;

    /**
     * @var float
     */
    public $price = 0.0;

    private function __construct(string $value, float $price)
    {
        $this->value = $value;
        $this->price = $price;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray($data): ProductAddonsDTO
    {
        if (!isset($data['value'])) {
            throw new \Exception("Addon value is missing");
        }
        $value = $data['value'];

        if (!isset($data['price'])) {
            throw new \Exception("Addon price is missing");
        }
        $customPrice = floatval($data['price']);

        return new ProductAddonsDTO($value, $customPrice);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}
