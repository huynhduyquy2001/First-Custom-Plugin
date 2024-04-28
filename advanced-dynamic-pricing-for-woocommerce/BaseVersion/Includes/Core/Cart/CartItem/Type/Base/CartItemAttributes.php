<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

class CartItemAttributes
{
    /** @var array<int, CartItemAttributeEnum> */
    private $attributes;

    public function __construct()
    {
        $this->attributes = [];
    }

    public function copyTo(CartItemAttributes $destination)
    {
        $destination->attributes = [];

        foreach ($this->attributes as $attribute) {
            $destination->attributes[$attribute->getValue()] = clone $attribute;
        }
    }

    public function hash(): string
    {
        $attr = array_values($this->attributes);
        sort($attr);

        return md5(serialize($attr));
    }

    public function put(CartItemAttributeEnum $attr)
    {
        $this->attributes[$attr->getValue()] = $attr;
    }

    public function remove(CartItemAttributeEnum $attr)
    {
        unset($this->attributes[$attr->getValue()]);
    }

    public function contains(CartItemAttributeEnum $attr): bool
    {
        return isset($this->attributes[$attr->getValue()]);
    }

    /**
     * @return CartItemAttributeEnum[]
     */
    public function toList()
    {
        return array_values($this->attributes);
    }
}
