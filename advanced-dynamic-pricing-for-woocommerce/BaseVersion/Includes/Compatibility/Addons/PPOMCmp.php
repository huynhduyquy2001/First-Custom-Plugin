<?php

namespace ADP\BaseVersion\Includes\Compatibility\Addons;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: PPOM for WooCommerce
 * Author: Themeisle
 *
 * @see https://themeisle.com/plugins/ppom-pro/
 */
class PPOMCmp
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function isActive()
    {
        return defined('PPOM_PATH');
    }

    /**
     * @param WcCartItemFacade $wcCartItemFacade
     *
     * @return array<int, CartItemAddon>
     */
    public function getAddonsFromCartItem(WcCartItemFacade $wcCartItemFacade)
    {
        $thirdPartyData = $wcCartItemFacade->getThirdPartyData();
        $ppomData = $thirdPartyData['ppom'] ?? [];
        $addonsData = json_decode(stripslashes($ppomData['ppom_option_price'] ?? "[]"), true);

        $addons = [];
        foreach ($addonsData as $data) {
            $key = $data['option_id'] ?? null;
            $value = "";
            $addonPrice = $data['price'] ?? null;

            if ($key === null || $value === null || $addonPrice === null) {
                continue;
            }

            $addon = new CartItemAddon($key, $value, $addonPrice);
            $addon->currency = $wcCartItemFacade->getCurrency();
            $addons[] = $addon;
        }

        return $addons;
    }
}
