<?php

namespace ADP\BaseVersion\Includes\Compatibility\Addons;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\CartProcessor\CartBuilder;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartCustomer;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\Core\Rule\PersistentRule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule\ProductsAdjustment;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule\ProductsRangeAdjustments;
use ADP\BaseVersion\Includes\Core\RuleProcessor\RoleDiscountStrategy;
use ADP\BaseVersion\Includes\Core\RuleProcessor\SingleItemRuleProcessor;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Extra Product Options & Add-Ons for WooCommerce ( ex. WooCommerce TM Extra Product Options )
 * Author: ThemeComplete
 */
class TmExtraOptionsCmp
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function register()
    {
    }

    public function isActive()
    {
        return defined('THEMECOMPLETE_EPO_PLUGIN_FILE');
    }

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     *
     * @return array<int, CartItemAddon>
     */
    public function getAddonsFromCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $thirdPartyData = $wcCartItemFacade->getThirdPartyData();
        $addonsData     = $thirdPartyData['tmcartepo'] ?? [];

        $addons = [];
        foreach ($addonsData as $addonData) {
            $key   = $addonData['name'] ?? null;
            $value = $addonData['value'] ?? null;
            $price = $addonData['price'] ?? null;

            if ($key === null || $value === null || $price === null) {
                continue;
            }

            if (is_string($price)) {
                $price = str_replace($this->context->priceSettings->getThousandSeparator(), "", $price);
                $price = str_replace($this->context->priceSettings->getDecimalSeparator(), ".", $price);
                $price = (float)$price;
            }

            $addon           = new CartItemAddon($key, $value, $price);
            $addon->currency = $wcCartItemFacade->getCurrency();

            $addons[] = $addon;
        }

        return $addons;
    }

    public function removeKeysFromFreeCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $wcCartItemFacade->deleteThirdPartyData("tmhasepo");
        $wcCartItemFacade->deleteThirdPartyData("tmcartepo");
        $wcCartItemFacade->deleteThirdPartyData("tmcartfee");
        $wcCartItemFacade->deleteThirdPartyData("tmpost_data");
        $wcCartItemFacade->deleteThirdPartyData("tmdata");
        $wcCartItemFacade->deleteThirdPartyData("tm_cart_item_key");
        $wcCartItemFacade->deleteThirdPartyData("tm_epo_product_original_price");
        $wcCartItemFacade->deleteThirdPartyData("tm_epo_options_prices");
        $wcCartItemFacade->deleteThirdPartyData("tm_epo_product_price_with_options");
        $wcCartItemFacade->deleteThirdPartyData("associated_products_price");
    }

    public function removeKeysFromFreeWcCartItem(&$cartItemData)
    {
        unset($cartItemData["tmhasepo"]);
        unset($cartItemData["tmcartepo"]);
        unset($cartItemData["tmcartfee"]);
        unset($cartItemData["tmpost_data"]);
        unset($cartItemData["tmdata"]);
        unset($cartItemData["tm_cart_item_key"]);
        unset($cartItemData["tm_epo_product_original_price"]);
        unset($cartItemData["tm_epo_options_prices"]);
        unset($cartItemData["tm_epo_product_price_with_options"]);
        unset($cartItemData["associated_products_price"]);
    }


    /**
     * Calculates the rules for a given product.
     *
     * @param \WC_Product $product The product for which to calculate the rules.
     *
     * @return array The raw rules for the product.
     * @throws \Exception
     */
    public function calculateRulesForProduct(\WC_Product $product): array
    {
        $context = $this->context;

        $cartBuilder = new CartBuilder();
        $cart = $cartBuilder->create(WC()->customer, WC()->session);

        $objects = (new PersistentRuleRepository())->getCacheWithProduct($product);

        $rawRules = [];

        foreach ($objects as $object) {
            if ($object && $object->rule && $object->price !== null) {
                $matchedRuleProcessor = $object->rule->buildProcessor($context);
                if ($matchedRuleProcessor->isRuleMatchedCart($cart)) {
                    $rawRules[$object->rule->getId()] = self::buildRawRule($object->rule, $cart);
                }
            }
        }

        foreach (CacheHelper::loadActiveRules($context)->getRules() as $tmpRule) {
            if (!($tmpRule instanceof SingleItemRule)) {
                continue;
            }

            /** @var SingleItemRuleProcessor $ruleProcessor */
            $ruleProcessor = $tmpRule->buildProcessor($context);

            if (
                $ruleProcessor->isProductMatched($cart, $product, true)
                && ($rawRule = self::buildRawRule($ruleProcessor->getRule(), $cart))
            ) {
                $rawRules[$ruleProcessor->getRule()->getId()] = $rawRule;
            }
        }

        return $rawRules;
    }

    private static function buildRawRule(SingleItemRule $rule, Cart $cart): array
    {
        $currentResult = [];
        //
        if ($rule instanceof PersistentRule)
            $currentResult['rule_type'] = 'product_only';
        elseif (method_exists($rule, "isExclusive") AND $rule->isExclusive() )
            $currentResult['rule_type'] = 'exclusive';
        else
            $currentResult['rule_type'] = 'common';

        if ($handler = $rule->getProductAdjustmentHandler()) {
            $currentResult['product_discount'] = self::buildRawProductAdjustmentHandler($handler);
        }
        if ($handler = $rule->getProductRangeAdjustmentHandler()) {
            $currentResult['bulk_discount'] = self::buildRawProductRangeAdjustmentHandler($handler);
        }
        if ($roleDiscounts = $rule->getRoleDiscounts()) {
            $currentResult['role_discounts'] = self::buildRawRoleDiscounts($rule, $cart->getContext()->getCustomer());
        }
        return $currentResult;
    }

    private static function buildRawProductAdjustmentHandler(ProductsAdjustment $handler): array
    {
        return [
            'discount_type' => $handler->getDiscount()->getType(),
            'value' => $handler->getDiscount()->getValue(),
        ];
    }

    private static function buildRawProductRangeAdjustmentHandler(ProductsRangeAdjustments $handler): array
    {
        $ranges = $handler->getRanges();

        // you can set different discount type to every range, but we do not use this feature
        $discountType = $ranges[0]->getData()->getType();

        $rawRanges = [];
        foreach ($ranges as $range) {
            $rawRanges[] = [
                'from' => $range->getFrom(),
                'to' => $range->getTo(),
                'value' => $range->getData()->getValue(),
            ];
        }

        return [
            'mode' => $handler->getType(),
            'discount_type' => $discountType,
            'ranges' => $rawRanges
        ];
    }

    /**
     * @param SingleItemRule $rule
     * @param CartCustomer $cartCustomer
     * @return array
     */
    private static function buildRawRoleDiscounts(SingleItemRule $rule, CartCustomer $cartCustomer): array
    {
        $roleDiscounts = (new RoleDiscountStrategy($rule))->findMatchedRoleDiscounts($cartCustomer);

        if ( ! $roleDiscounts ) {
            return [];
        }

        $rawRoleDiscounts = [];

        foreach ($roleDiscounts as $roleDiscount) {
            $rawRoleDiscounts[] = [
                'discount_type' => $roleDiscount->getDiscount()->getType(),
                'value' => $roleDiscount->getDiscount()->getValue(),
            ];
        }

        return $rawRoleDiscounts;
    }
}
