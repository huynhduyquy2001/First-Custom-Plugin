<?php

namespace ADP\BaseVersion\Includes\Core;

use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Database\RulesCollection;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;

interface ICartCalculator
{
    /**
     * @param Cart $cart
     *
     * @return bool
     */
    public function processCart(&$cart);

    /**
     * @param Cart $cart
     * @param ICartItem $item
     *
     * @return bool
     */
    public function processItem(&$cart, $item);

    /**
     * @return RulesCollection
     */
    public function getRulesCollection();
}
