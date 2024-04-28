<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor\BulkDiscount;

use ADP\BaseVersion\Includes\Enums\BaseEnum;

/**
 * @method static self QTY()
 * @method static self SUM()
 * @method static self WEIGHT()
 */
class BulkMeasurementEnum extends BaseEnum
{
    const __default = self::QTY;

    const QTY = 'qty';
    const SUM = 'sum';
    const WEIGHT = 'weight';

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
