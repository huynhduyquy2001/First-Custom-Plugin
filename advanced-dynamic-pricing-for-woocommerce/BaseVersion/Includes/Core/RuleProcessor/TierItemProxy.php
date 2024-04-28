<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartSet;

class TierItemProxy
{
    const MARK_CALCULATED = 'calculated';

    /** @var ICartItem|CartSet */
    private $item;

    /** @var array<int, string> */
    private $marks;

    private function __construct()
    {
        $this->item = null;
        $this->marks = [];
    }

    public function __clone() {
        $this->item = clone $this->item;
    }

    public static function ofCartItem(ICartItem $cartItem): TierItemProxy
    {
        $tierItem = new self();
        $tierItem->item = clone $cartItem;

        return $tierItem;
    }

    public static function ofCartSet(CartSet $cartSet): TierItemProxy
    {
        $tierItem = new self();
        $tierItem->item = clone $cartSet;

        return $tierItem;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function getQty(): float
    {
        return $this->item->getQty();
    }

    public function setQty(float $qty)
    {
        $this->item->setQty($qty);
    }

    public function hasMark(string $mark): bool
    {
        return in_array($mark, $this->marks);
    }

    /**
     * @param array $marks
     */
    public function addMark(...$marks)
    {
        $this->marks = $marks;
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
    }

    /**
     * @return array<int, string>
     */
    public function getMarks()
    {
        return $this->marks;
    }
}
