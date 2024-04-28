<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor;

use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartCustomer;
use ADP\BaseVersion\Includes\Core\Rule\PackageRule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;
use ADP\BaseVersion\Includes\Core\Rule\Structures\RoleDiscount;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartItemsCollection;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartSetCollection;
use ADP\Factory;

defined('ABSPATH') or exit;

class RoleDiscountStrategy
{
    /**
     * @var SingleItemRule|PackageRule
     */
    protected $rule;

    /**
     * @param SingleItemRule|PackageRule $rule
     */
    public function __construct($rule)
    {
        $this->rule = $rule;
    }

    /**
     * @param CartCustomer $cartCustomer
     * @return array<int, RoleDiscount>
     */
    public function findMatchedRoleDiscounts(CartCustomer $cartCustomer): array
    {
        $roleDiscounts = $this->rule->getRoleDiscounts();

        if (!$roleDiscounts) {
            return [];
        }

        if (!($currentUserRoles = $cartCustomer->getRoles())) {
            return [];
        }

        $matchedRoleDiscounts = [];
        foreach ($roleDiscounts as $roleDiscount) {
            if (count(array_intersect($roleDiscount->getRoles(), $currentUserRoles))) {
                $matchedRoleDiscounts[] = $roleDiscount;
            }
        }
        return $matchedRoleDiscounts;
    }

    /**
     * @param Cart $cart
     * @param CartItemsCollection $collection
     */
    public function processItems(&$cart, &$collection)
    {
        foreach ($this->findMatchedRoleDiscounts($cart->getContext()->getCustomer()) as $roleDiscount) {
            if ( ! $roleDiscount->getDiscount()) {
                continue;
            }

            /** @var PriceCalculator $priceCalculator */
            $priceCalculator = Factory::get(
                "Core_RuleProcessor_PriceCalculator",
                $this->rule,
                $roleDiscount->getDiscount()
            );

            foreach ($collection->get_items() as &$item) {
                $priceCalculator->applyItemDiscount($item, $cart, $roleDiscount);
            }
        }
    }

    /**
     * @param Cart $cart
     * @param CartSetCollection $collection
     */
    public function processSets(&$cart, &$collection)
    {
        foreach ($this->findMatchedRoleDiscounts($cart->getContext()->getCustomer()) as $roleDiscount) {
            /** @var PriceCalculator $priceCalculator */
            $priceCalculator = Factory::get(
                "Core_RuleProcessor_PriceCalculator",
                $this->rule,
                $roleDiscount->getDiscount()
            );
            foreach ($collection->getSets() as $set) {
                $priceCalculator->calculatePriceForSet($set, $cart, $roleDiscount);
            }
        }
    }

}
