<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor;

use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\Rule\Rule;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;

defined('ABSPATH') or exit;

class ExclusivityAllStrategy
{
    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @param Rule $rule
     */
    public function __construct($rule)
    {
        $this->rule = $rule;
    }

    /**
     * @param array<int,ICartItem> $items
     *
     * @return array<int,ICartItem>
     */
    public function makeAffectedItemAsExclusive($items)
    {
        foreach ($items as $item) {
            $item->addAttr(CartItemAttributeEnum::IMMUTABLE());
        }

        return $items;
    }
}
