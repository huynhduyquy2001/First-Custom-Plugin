<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment;

use ADP\BaseVersion\Includes\Enums\BaseEnum;

/**
 * @method static self DEFAULT()
 * @method static self REPLACED_BY_CART_ADJUSTMENT()
 */
class CartItemPriceUpdateTypeEnum extends BaseEnum
{
    const __default = null;

    const DEFAULT = 'default';
    const REPLACED_BY_CART_ADJUSTMENT = 'replaced_by_coupon';

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

