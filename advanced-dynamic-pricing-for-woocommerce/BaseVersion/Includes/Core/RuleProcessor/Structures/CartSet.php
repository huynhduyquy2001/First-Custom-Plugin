<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor\Structures;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;

defined('ABSPATH') or exit;

class CartSet
{
    /**
     * @var string
     */
    private $hash;

    /**
     * @var ICartItem[]
     */
    private $items;

    /**
     * @var integer
     */
    private $qty;

    /**
     * @var int
     */
    private $ruleId;

    /**
     * @var array<int,int>
     */
    private $itemPositions;

    /**
     * @var array
     */
    protected $marks;

    /**
     * @param int $ruleId int
     * @param array<int,ICartItem> $cartItems
     * @param int $qty
     */
    public function __construct($ruleId, $cartItems, $qty = 1)
    {
        $this->ruleId = $ruleId;

        $plainItems = array();
        foreach (array_values($cartItems) as $index => $item) {
            if ($item instanceof ICartItem) {
                $plainItems[] = array(
                    'pos'  => $index,
                    'item' => $item,
                );
            } elseif (is_array($item)) {
                foreach ($item as $subItem) {
                    if ($subItem instanceof ICartItem) {
                        $plainItems[] = array(
                            'pos'  => $index,
                            'item' => $subItem,
                        );
                    }
                }
            }
        }

        usort($plainItems, function ($plainItemA, $plainItemB) {
            $itemA = $plainItemA['item'];
            $itemB = $plainItemB['item'];
            /**
             * @var $itemA ICartItem
             * @var $itemB ICartItem
             */

            $tmp_a = $itemA->hasAttr(CartItemAttributeEnum::TEMPORARY());
            $tmp_b = $itemB->hasAttr(CartItemAttributeEnum::TEMPORARY());

            if ( ! $tmp_a && $tmp_b) {
                return -1;
            }

            if ($tmp_a && ! $tmp_b) {
                return 1;
            }

            return 0;
        });

        $this->items         = array_column($plainItems, 'item');
        $this->itemPositions = array_column($plainItems, 'pos');

        $this->recalculateHash();
        $this->hash  = $this->getHash();
        $this->qty   = $qty;
        $this->marks = array();
    }

    private function sortItems()
    {
        usort($this->items, function ($itemA, $itemB) {
            /**
             * @var $itemA ICartItem
             * @var $itemB ICartItem
             */
            if ( ! $itemA->hasAttr(CartItemAttributeEnum::TEMPORARY()) && $itemB->hasAttr(CartItemAttributeEnum::TEMPORARY())) {
                return -1;
            }

            if ($itemA->hasAttr(CartItemAttributeEnum::TEMPORARY()) && ! $itemB->hasAttr(CartItemAttributeEnum::TEMPORARY())) {
                return 1;
            }

            return 0;
        });

    }

    public function __clone()
    {
        $newItems = array();
        foreach ($this->items as $item) {
            $newItems[] = clone $item;
        }

        $this->items = $newItems;
    }

    public function getTotalPrice()
    {
        return $this->getPrice() * $this->qty;
    }

    public function getPrice()
    {
        $price = 0.0;
        foreach ($this->items as $item) {
            $price += $item->getPrice() * $item->getQty();
        }

        return $price;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function recalculateHash()
    {
        $hashes = array_map(function ($item) {
            /**
             * @var $item ICartItem
             */
            return $item->getHash() . "_" . $item->getQty();
        }, $this->items);

        $this->hash = md5(json_encode($hashes));
    }

    /**
     * @return array<int, ICartItem>
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return array<int, int>
     */
    public function getPositions()
    {
        $positions = array_unique(array_values($this->itemPositions));
        sort($positions);

        return $positions;
    }

    public function incQty($qty)
    {
        $this->qty += $qty;
    }

    /**
     * @param int $index
     *
     * @return array<int, ICartItem>
     */
    public function getItemsByPosition($index)
    {
        $items = array();
        foreach ($this->getItemsByPositionWithReference($index) as $item) {
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param int $index
     *
     * @return array<int, ICartItem>
     */
    private function getItemsByPositionWithReference($index)
    {
        $items = array();
        foreach ($this->itemPositions as $internalIndex => $position) {
            if ($position === $index) {
                $items[] = $this->items[$internalIndex];
            }
        }

        return $items;
    }

    /**
     * @param string $mark
     *
     * @return bool
     */
    public function hasMark($mark)
    {
        return in_array($mark, $this->marks);
    }

    /**
     * @param array $marks
     */
    public function addMark(...$marks)
    {
        $this->marks = $marks;
        $this->recalculateHash();
    }

    /**
     * @param array $marks
     */
    public function removeMark(...$marks)
    {
        foreach ($marks as $mark) {
            $pos = array_search($mark, $this->marks);

            if ($pos !== false) {
                unset($this->marks[$pos]);
            }
        }

        $this->marks = array_values($this->marks);
        $this->recalculateHash();
    }

    /**
     * @param float $qty
     */
    public function setQty($qty)
    {
        $this->qty = $qty;
    }

    /**
     * @return float
     */
    public function getQty()
    {
        return $this->qty;
    }
}
