<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\DTO;

class CalculatePriceProductDTO
{
    /**
     * @var int
     */
    private $productId;

    /**
     * @var float
     */
    private $qty;

    /**
     * @var float|null
     */
    private $customPrice;

    /**
     * @var array<int, string>
     */
    private $attributes;

    /**
     * @var array<int, ProductAddonsDTO>
     */
    private $addons;

    private function __construct(int $productId, float $qty, $customPrice, array $attributes, array $addons)
    {
        $this->productId = $productId;
        $this->qty = $qty;
        $this->customPrice = $customPrice;
        $this->attributes = $attributes;
        $this->addons = $addons;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray($data): CalculatePriceProductDTO
    {
        if (!isset($data['product_id'])) {
            throw new \Exception("Product id is missing");
        }
        $productId = $data['product_id'];
        if (!is_numeric($productId)) {
            throw new \Exception("Product id is not a number: " . $productId);
        }
        $productId = intval($productId);


        if (!isset($data['qty'])) {
            throw new \Exception("Qty is missing");
        }
        $qty = $data['qty'];
        if (!is_numeric($qty)) {
            throw new \Exception("Qty is not a number: " . $qty);
        }
        $qty = floatval($qty);


        $customPrice = isset($data['custom_price']) ? self::parseCustomPrice($data['custom_price']) : null;
        $attributes = $data['attributes'] ?? [];

        $addons = array_map(function ($addon) {
            return ProductAddonsDTO::fromArray($addon);
        }, $data['addons'] ?? []);

        return new CalculatePriceProductDTO($productId, $qty, $customPrice, $attributes, $addons);
    }

    protected static function parseCustomPrice(string $customPrice): ?float
    {
        $result = null;

        if (preg_match('/\d+\\' . wc_get_price_decimal_separator() . '\d+/', $customPrice, $matches) !== false) {
            $result = floatval(reset($matches));
        }

        return $result;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQty(): float
    {
        return $this->qty;
    }

    public function getCustomPrice(): ?float
    {
        return $this->customPrice;
    }

    /**
     * @return array<int,string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<int,ProductAddonsDTO>
     */
    public function getAddons(): array
    {
        return $this->addons;
    }
}
