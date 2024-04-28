<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

class CartItemAddonsCollection
{
    /** @var array<int, CartItemAddon> */
    private $addons;

    public function __construct()
    {
        $this->addons = [];
    }

    /**
     * @param array<int, CartItemAddon> $addons
     */
    public static function ofList($addons)
    {
        $addonsObj = new self();

        array_walk($addons, function ($addon) use (&$addonsObj) {
            if ($addon instanceof CartItemAddon) {
                $addonsObj->put($addon);
            }
        });

        return $addonsObj;
    }

    public function copyTo(CartItemAddonsCollection $destination)
    {
        $destination->addons = [];

        foreach ($this->addons as $addon) {
            $destination->addons[$addon->hash()] = clone $addon;
        }
    }

    public function hash(): string
    {
        $addons = array_keys($this->addons);
        sort($addons);

        return md5(serialize($addons));
    }

    public function put(CartItemAddon $addon)
    {
        $this->addons[$addon->hash()] = $addon;
    }

    public function remove(CartItemAddon $addon)
    {
        unset($this->addons[$addon->hash()]);
    }

    public function contains(CartItemAddon $addon): bool
    {
        return isset($this->addons[$addon->hash()]);
    }

    public function toList()
    {
        return array_values($this->addons);
    }
}
