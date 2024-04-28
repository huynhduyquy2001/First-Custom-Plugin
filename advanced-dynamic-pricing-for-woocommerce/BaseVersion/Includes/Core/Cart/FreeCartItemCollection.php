<?php

namespace ADP\BaseVersion\Includes\Core\Cart;

class FreeCartItemCollection
{
    /** @var array<string, FreeCartItem> */
    private $collection;

    private function __construct()
    {
        $this->collection = [];
    }

    public static function empty(): FreeCartItemCollection
    {
        return new self();
    }

    /** @param array<int, FreeCartItem> $list */
    public static function fromList(array $list): FreeCartItemCollection
    {
        $collection = new self();

        foreach ($list as $item) {
            $collection->put($item);
        }

        return $collection;
    }

    public function put(FreeCartItem $item)
    {
        $hash = $item->hash();

        if (isset($this->collection[$hash])) {
            $this->collection[$hash]->setQty($item->getQty());
        } else {
            $this->collection[$hash] = $item;
        }

        return $this;
    }

    public function merge(FreeCartItemCollection $collection)
    {
        foreach ($collection->asList() as $item) {
            $this->put($item);
        }

        return $this;
    }

    /** @return array<int, FreeCartItem> */
    public function asList(): array
    {
        return array_values($this->collection);
    }

    public function toArray()
    {
        return array_map(function ($item) {
            return $item->toArray();
        }, $this->collection);
    }
}
