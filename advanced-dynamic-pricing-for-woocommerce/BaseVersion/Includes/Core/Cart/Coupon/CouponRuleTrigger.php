<?php

namespace ADP\BaseVersion\Includes\Core\Cart\Coupon;

class CouponRuleTrigger implements AdpCouponInterface
{
    /** @var string */
    private $code;

    /** @var int */
    private $ruleId;

    public function __construct(string $code, int $ruleId)
    {
        $this->code = $code;
        $this->ruleId = $ruleId;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getRuleId(): int
    {
        return $this->ruleId;
    }

    public function equals(AdpCouponInterface $coupon): bool
    {
        if (!$coupon instanceof CouponRuleTrigger) {
            return false;
        }

        return $this->code === $coupon->getCode()
            && $this->ruleId === $coupon->getRuleId();
    }
}
