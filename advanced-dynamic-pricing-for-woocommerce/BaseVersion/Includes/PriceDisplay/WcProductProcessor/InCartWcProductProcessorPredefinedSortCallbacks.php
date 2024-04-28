<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\WcProductProcessor;

use ADP\BaseVersion\Includes\SpecialStrategies\CompareStrategy;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;

class InCartWcProductProcessorPredefinedSortCallbacks
{
    public static function cartItemsAsIs($cartItems)
    {
        return $cartItems;
    }

    public static function cartItemsInReverseOrder($cartItems)
    {
        return array_reverse($cartItems);
    }

    public static function sortCartItemsByPriceDesc($cartItems)
    {
        $compare = new CompareStrategy();

        usort($cartItems, function ($a, $b) use (&$compare) {
            /**
             * @var BasicCartItem $a
             * @var BasicCartItem $b
             */
            if ($compare->floatsAreEqual($a->getPrice(), $b->getPrice())) {
                return 0;
            }

            return $compare->floatLessAndEqual($a->getPrice(), $b->getPrice()) ? 1 : -1;
        });

        return $cartItems;
    }

    public static function sortCartItemsByPriceAsc($cartItems)
    {
        $compare = new CompareStrategy();

        usort($cartItems, function ($a, $b) use (&$compare) {
            /**
             * @var BasicCartItem $a
             * @var BasicCartItem $b
             */

            if ($compare->floatsAreEqual($a->getPrice(), $b->getPrice())) {
                return 0;
            }

            return $compare->floatLessAndEqual($a->getPrice(), $b->getPrice()) ? -1 : 1;
        });

        return $cartItems;
    }
}
