<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base;

use ADP\BaseVersion\Includes\Enums\BaseEnum;

/**
 * @method static self IMMUTABLE()
 * @method static self READONLY_PRICE()
 * @method static self TEMPORARY()
 */
class CartItemAttributeEnum extends BaseEnum
{
    const __default = null;

    const IMMUTABLE = 'immutable';
    const READONLY_PRICE = 'readonly_price';
    const TEMPORARY = 'temporary';

    /**
     * @param self $variable
     *
     * @return bool
     */
    public function equals($variable)
    {
        return parent::equals($variable);
    }
}
