<?php

namespace ADP\BaseVersion\Includes\Core\Cart\Coupon;

interface AdpCouponInterface
{
    public function getRuleId();

    public function equals(AdpCouponInterface $coupon): bool;
}
