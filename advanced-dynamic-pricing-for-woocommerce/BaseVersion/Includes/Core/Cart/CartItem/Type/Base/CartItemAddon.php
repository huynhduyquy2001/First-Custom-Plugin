<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

use ADP\BaseVersion\Includes\Context\Currency;

defined('ABSPATH') or exit;

class CartItemAddon
{
    /**
     * @var string
     */
    public $key = "";

    /**
     * @var string
     */
    public $label = "";

    /**
     * @var mixed
     */
    public $value = null;

    /**
     * @var Currency
     */
    public $currency;

    /**
     * @var float
     */
    public $price = 0.0;

    public function __construct($key, $value, $price)
    {
        $this->key = (string)$key;
        $this->value = $value;
        $this->price = (float)$price;

        $this->currency = adp_context()->currencyController->getDefaultCurrency();
        $this->label = (string)$key;
    }

    public function hash(): string
    {
        return md5(serialize([
            $this->key,
            $this->label,
            $this->value,
            $this->currency->hash(),
            $this->price
        ]));
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @return Currency
     */
    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }
}
