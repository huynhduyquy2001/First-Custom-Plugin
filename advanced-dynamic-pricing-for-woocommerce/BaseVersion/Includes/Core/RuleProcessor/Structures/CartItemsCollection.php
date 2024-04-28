<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor\Structures;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;

defined('ABSPATH') or exit;

class CartItemsCollection
{
    /**
     * @var ICartItem[]
     */
    private $items = array();

    /**
     * @var int
     */
    private $ruleId;

    public function __construct($ruleId)
    {
        $this->ruleId = $ruleId;
    }

    public function __clone()
    {
        $newItems = array();
        foreach ($this->items as $item) {
            $newItems[] = clone $item;
        }

        $this->items = $newItems;
    }

    /**
     * @param ICartItem $item_to_add
     *
     * @return boolean
     */
    public function add(ICartItem $item_to_add)
    {
        $added = false;
        foreach ($this->items as $item) {
            /**
             * @var $item ICartItem
             */
            if ($item->getHash() === $item_to_add->getHash() && ($item->getOriginalPrice() === $item_to_add->getOriginalPrice())) {
                $item->setQty($item->getQty() + $item_to_add->getQty());
                $added = true;
                break;
            }
        }

        if (!$added) {
            $this->items[] = $item_to_add;
        }

        $this->sort_items();

        return true;
    }

    private function sort_items()
    {
        return;
        usort($this->items, function ($item_a, $item_b) {
            /**
             * @var $item_a BasicExistingCartItem
             * @var $item_b BasicExistingCartItem
             */
            if (!$item_a->hasAttr(CartItemAttributeEnum::TEMPORARY()) && $item_b->hasAttr(CartItemAttributeEnum::TEMPORARY())) {
                return -1;
            }

            if ($item_a->hasAttr(CartItemAttributeEnum::TEMPORARY()) && !$item_b->hasAttr(CartItemAttributeEnum::TEMPORARY())) {
                return 1;
            }

            return 0;
        });

    }

    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * @return array<int, ICartItem>
     */
    public function get_items()
    {
        return $this->items;
    }

    public function getHash()
    {
        $hashes = array_map(function ($item) {
            return $item->getHash();
        }, $this->items);

        return md5(json_encode($hashes));
    }

    public function purge()
    {
        $this->items = array();
    }

    public function getCount()
    {
        return count($this->items);
    }

    public function getTotalQty()
    {
        $totalQty = 0;
        foreach ($this->items as $item) {
            $totalQty += $item->getQty();
        }

        return $totalQty;
    }

    public function getTotalSum()
    {
        $totalSum = 0;
        foreach ($this->items as $item) {
            $totalSum += $item->getTotalPrice();
        }

        return $totalSum;
    }

    /**
     * @param string $hash
     *
     * @return ICartItem|null
     */
    public function getItemByHash($hash)
    {
        foreach ($this->items as $item) {
            if ($item->getHash() === $hash) {
                return clone $item;
            }
        }

        return null;
    }

    /**
     * @param string $hash
     *
     * @return ICartItem|null
     */
    public function getNotEmptyItemWithReferenceByHash($hash)
    {
        foreach ($this->items as $item) {
            if ($item->getHash() === $hash && $item->getQty() > 0) {
                return $item;
            }
        }

        return null;
    }

    public function removeItemByHash($hash)
    {
        foreach ($this->items as $index => $item) {
            if ($item->getHash() === $hash) {
                unset($this->items[$index]);
                $this->items = array_values($this->items);

                return true;
            }
        }

        return false;
    }
}
