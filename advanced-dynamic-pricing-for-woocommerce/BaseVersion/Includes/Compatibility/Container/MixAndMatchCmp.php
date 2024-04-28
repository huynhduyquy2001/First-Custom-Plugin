<?php

namespace ADP\BaseVersion\Includes\Compatibility\Container;

use ADP\BaseVersion\Includes\CartProcessor\CartProcessor;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;
use ADP\BaseVersion\Includes\WC\WcCartItemFacade;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WooCommerce Mix and Match Products
 * Author: Kathy Darling, Matty Cohen
 *
 * @see http://www.woocommerce.com/products/woocommerce-mix-and-match-products/
 */
class MixAndMatchCmp extends AbstractContainerCompatibility
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    protected function getContext(): Context
    {
        return $this->context;
    }

    public function addFilters()
    {
        add_filter('adp_product_get_price', function ($price, $product, $variation, $qty, $trdPartyData, $facade) {
            if ($facade === null) {
                return $price;
            }

            if ($this->isMixAndMatchChild($facade)) {
                $price = $product->get_price();
            }

            return $price;
        }, 10, 6);
    }

    public function isActive(): bool
    {
        return class_exists("WC_Mix_and_Match") || class_exists("WC_Mix_and_Match");
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isMixAndMatchParent(WcCartItemFacade $facade)
    {
        return wc_mnm_is_container_cart_item($facade->getData());
    }

    /**
     * @param WcCartItemFacade $facade
     *
     * @return bool
     */
    public function isMixAndMatchChild(WcCartItemFacade $facade)
    {
        return wc_mnm_maybe_is_child_cart_item($facade->getData());
    }

    /**
     * @return bool
     */
    public function isMixAndMatchProduct($product)
    {
        return $product instanceof \WC_Product_Mix_and_Match;
    }

    public function isContainerFacade(WcCartItemFacade $facade): bool
    {
        return $this->isMixAndMatchParent($facade);
    }

    public function isFacadeAPartOfContainer(WcCartItemFacade $facade): bool
    {
        return $this->isMixAndMatchChild($facade);
    }

    public function isContainerProduct(\WC_Product $wcProduct): bool
    {
        return $this->isMixAndMatchProduct($wcProduct);
    }

    public function isFacadeAPartOfContainerFacade(WcCartItemFacade $partOfContainerFacade, WcCartItemFacade $bundle): bool
    {
        $bundledData = $partOfContainerFacade->getThirdPartyData();
        $bundleData = $bundle->getThirdPartyData();

        return isset($bundledData['mnm_container'])
            && $bundle->getKey() === $bundledData['mnm_container']
            && isset($bundleData['mnm_contents'])
            && in_array($partOfContainerFacade->getKey(), $bundleData['mnm_contents'], true);
    }

    public function getListOfPartsOfContainerFromContainerProduct(\WC_Product $product): array
    {
        // at long as mix and match does not have FIXED containers this declaration is useless
        return [];
    }

    public function calculatePartOfContainerPrice(WcCartItemFacade $facade): float
    {
        $product = $facade->getProduct();

        if ( isset($product->mnm_child_item) && $product->mnm_child_item instanceof \WC_MNM_Child_Item ) {
            $childItemPrice = $product->mnm_child_item->get_raw_price();

            // for unknown reason MNM plugin changes the child product prices using hooks
            // for our convenience enforcing the price with setter
            $product->set_price($childItemPrice);
        } else {
            $childItemPrice = floatval($product->get_price("edit"));
        }

        return $childItemPrice;
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerPrice(WcCartItemFacade $facade, array $children): float
    {
        $wcProductMixAndMatch = $facade->getProduct();
        $basePrice = floatval($wcProductMixAndMatch->get_price());
        $childItemsPrice = 0.0;
        foreach ($children as $child) {
            $childProduct = $child->getProduct();
            $childItemQty = $childProduct->is_sold_individually() ? 1 : $child->getQty() / $facade->getQty();
            $childItemPrice = floatval($childProduct->get_price('edit')) * $childItemQty;
            $childItemsPrice += $childItemPrice;
        }

        return $basePrice + $childItemsPrice;
    }

    /**
     * @param WcCartItemFacade $facade
     * @param array<int, WcCartItemFacade> $children
     * @return float
     */
    public function calculateContainerBasePrice(WcCartItemFacade $facade, array $children): float
    {
        return floatval(CartProcessor::getProductPriceDependsOnPriceMode($facade->getProduct()));
    }

    public function getContainerPriceTypeByParentFacade(WcCartItemFacade $facade): ?ContainerPriceTypeEnum
    {
        return ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS();
    }

    public function isPartOfContainerFacadePricedIndividually(WcCartItemFacade $facade): ?bool
    {
        $product = $facade->getProduct();

        if (!(isset($product->mnm_child_item) && $product->mnm_child_item instanceof \WC_MNM_Child_Item)) {
            return false;
        }

        return $product->mnm_child_item->is_priced_individually("edit");
    }

    public function overrideContainerReferenceForPartOfContainerFacadeAfterPossibleDuplicates(
        WcCartItemFacade $partOfContainerFacade,
        WcCartItemFacade $containerFacade
    ) {
        $partOfContainerFacade->setThirdPartyData('mnm_container', $containerFacade->getKey());

        $parentFacadeThirdPartyData = $containerFacade->getThirdPartyData();
        $bundledItems = $parentFacadeThirdPartyData['mnm_contents'] ?? null;
        if ($bundledItems === null) {
            return;
        }

        $i = array_search($partOfContainerFacade->getOriginalKey(), $bundledItems);
        if ($i !== false) {
            $bundledItems = array_replace(
                $bundledItems,
                [$i => $partOfContainerFacade->getKey()]
            );

            $containerFacade->setThirdPartyData('mnm_contents', $bundledItems);
        }
    }
}
