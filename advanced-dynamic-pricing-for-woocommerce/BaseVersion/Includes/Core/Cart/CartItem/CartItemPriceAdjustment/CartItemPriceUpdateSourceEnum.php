<?php

namespace ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemPriceAdjustment;

use ADP\BaseVersion\Includes\Enums\BaseEnum;

/**
 * @method static self SOURCE_SINGLE_ITEM_SIMPLE()
 * @method static self SOURCE_SINGLE_ITEM_RANGE()
 * @method static self SOURCE_PACKAGE_SIMPLE()
 * @method static self SOURCE_PACKAGE_SPLIT()
 * @method static self SOURCE_PACKAGE_RANGE()
 * @method static self SOURCE_ROLE()
 * @method static self SOURCE_PRODUCT_ONLY_LOAD()
 * @method static self SOURCE_FREE_ITEM()
 * @method static self SOURCE_AUTOADD_ITEM()
 */
class CartItemPriceUpdateSourceEnum extends BaseEnum
{
    const __default = null;

    const SOURCE_SINGLE_ITEM_SIMPLE = 'single_item_simple';
    const SOURCE_SINGLE_ITEM_RANGE = 'single_item_range';
    const SOURCE_PACKAGE_SIMPLE = 'package_simple';
    const SOURCE_PACKAGE_SPLIT = 'package_split';
    const SOURCE_PACKAGE_RANGE = 'package_range';
    const SOURCE_ROLE = 'role';
    const SOURCE_FREE_ITEM = 'free_item';
    const SOURCE_AUTOADD_ITEM = 'autoadd_item';

    const SOURCE_PRODUCT_ONLY_LOAD = 'product_only_load';

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

