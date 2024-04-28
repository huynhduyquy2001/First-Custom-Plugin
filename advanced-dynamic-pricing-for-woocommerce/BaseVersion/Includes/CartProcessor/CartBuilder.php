<?php

namespace ADP\BaseVersion\Includes\CartProcessor;

use ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter\ToPricingCartItemAdapter;
use ADP\BaseVersion\Includes\Compatibility\PointsAndRewardsForWoocommerceCmp;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartContext;
use ADP\BaseVersion\Includes\WC\WcAdpMergedCouponHelper;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;
use ADP\BaseVersion\Includes\WC\WcCouponFacade;
use ADP\BaseVersion\Includes\WC\WcCustomerConverter;
use ADP\BaseVersion\Includes\WC\WcCustomerSessionFacade;
use ADP\Factory;
use WC_Cart;
use WC_Coupon;
use WC_Customer;

defined('ABSPATH') or exit;

class CartBuilder
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param WC_Customer|null $wcCustomer
     * @param \WC_Session_Handler|null $wcSession
     *
     * @return Cart
     */
    public function create($wcCustomer, $wcSession)
    {
        $context = $this->context;
        /** @var WcCustomerConverter $converter */
        $converter = Factory::get("WC_WcCustomerConverter", $context);
        $customer = $converter->convertFromWcCustomer($wcCustomer, $wcSession);
        $customerId = $customer->getId();
        //in case account was created during checkout
        if ($customerId === 0 && is_user_logged_in()) {
            $newWcCustomer = new \WC_Customer($wcSession->get_customer_id());

            $reflection = new \ReflectionClass($newWcCustomer);
            $property = $reflection->getProperty('changes');
            $property->setAccessible(true);
            $property->setValue($newWcCustomer, $wcCustomer->get_changes());

            $customer = $converter->convertFromWcCustomer($newWcCustomer, $wcSession);
        }
        $userMeta = get_user_meta($customerId);
        $customer->setMetaData($userMeta ? $userMeta : array());

        $cartContext = new CartContext($customer, $context);
        /** @var WcCustomerSessionFacade $wcSessionFacade */
        $wcSessionFacade = Factory::get("WC_WcCustomerSessionFacade", $wcSession);
        $cartContext->withSession($wcSessionFacade);

        /** @var Cart $cart */
        $cart = Factory::get('Core_Cart_Cart', $cartContext);

        return $cart;
    }

    /**
     *
     * @param Cart $cart
     * @param WC_Cart $wcCart
     */
    public function populateCart($cart, $wcCart)
    {
        $pos = 0;

        $adapter = new ToPricingCartItemAdapter();
        foreach ($wcCart->cart_contents as $cartKey => $wcCartItem) {
            $wrapper = new WcCartItemFacade($this->context, $wcCartItem, $cartKey);
            $wrapper->withContext($this->context);

            if ($wrapper->isClone()) {
                continue;
            }

            if ( ! $adapter->adaptFacadeAndPutIntoCart($cart, $wrapper, $pos) ) {
                continue;
            }

            $pos++;
        }

        /** Save applied coupons. It needs for detect free (gifts) products during current calculation and notify about them. */
        $this->addOriginCoupons($cart, $wcCart);
    }

    /**
     * @param Cart $cart
     * @param WC_Cart $wcCart
     */
    public function addOriginCoupons($cart, $wcCart)
    {
        if (!($wcCart instanceof WC_Cart)) {
            return;
        }

        $adpCoupons = $cart->getContext()->getSession()->getAdpCoupons();

        $pointAndRewardsCmp = new PointsAndRewardsForWoocommerceCmp();

        foreach ($wcCart->get_coupons() as $codeAsKey => $wcCoupon) {
            /** @var $wcCoupon WC_Coupon */
            $couponCode = $wcCoupon->get_code('edit');

            if ($pointAndRewardsCmp->isActive()
                && $pointAndRewardsCmp->isPointsAndRewardsCoupon($codeAsKey, $wcCoupon)
            ) {
                $couponCode = $pointAndRewardsCmp->getPointsAndRewardsCoupon($codeAsKey, $wcCoupon);
            }

            if ($this->context->isUseMergedCoupons()) {
                $mergedCoupon = WcAdpMergedCouponHelper::loadOfCoupon($wcCoupon);

                if ((new \WC_Discounts(WC()->cart))->is_coupon_valid($wcCoupon)) {
                    if ($mergedCoupon->hasRuleTriggerPart()) {
                        $cart->addRuleTriggerCoupon($couponCode);
                    } elseif (!$mergedCoupon->hasAdpPart()) {
                        $cart->addOriginCoupon($couponCode);
                    }
                }
            } else {
                if ($wcCoupon->is_valid()) {
                    if ($wcCoupon->get_discount_type('edit') === WcCouponFacade::TYPE_ADP_RULE_TRIGGER) {
                        $cart->addRuleTriggerCoupon($couponCode);
                    } elseif (!$wcCoupon->get_meta('adp', true) && !in_array($couponCode, $adpCoupons)) {
                        $cart->addOriginCoupon($couponCode);
                    }
                }
            }
        }
    }
}
