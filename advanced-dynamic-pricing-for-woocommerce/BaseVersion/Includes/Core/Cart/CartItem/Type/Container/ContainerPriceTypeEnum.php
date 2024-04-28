<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container;

use ADP\BaseVersion\Includes\Enums\BaseEnum;

/**
 * @method static self FIXED()
 * @method static self BASE_PLUS_SUM_OF_SUB_ITEMS()
 */
class ContainerPriceTypeEnum extends BaseEnum
{
    const __default = null;

    const FIXED = 'fixed';
    const BASE_PLUS_SUM_OF_SUB_ITEMS = 'base_plus_sum';

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
