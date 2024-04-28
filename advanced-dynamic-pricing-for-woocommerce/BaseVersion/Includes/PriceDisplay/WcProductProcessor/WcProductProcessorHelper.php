<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\WcProductProcessor;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductSimple;
use ADP\BaseVersion\Includes\WC\DataStores\ProductVariationDataStoreCpt;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerCartItem;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductContainer;
use WC_Product;

class WcProductProcessorHelper
{
    /**
     * Builds a WooCommerce product from a child product ID and parent product ID.
     * Cut heavy operations to load faster with custom data store.
     * @param int|\WC_Product $theProduct The child product ID or an instance of WC_Product.
     * @param int|\WC_Product $theParentProduct The parent product ID or an instance of WC_Product.
     *
     * @return \WC_Product|null The built WooCommerce product, or null if not found.
     * @see ProductVariationDataStoreCpt
     *
     */
    public static function buildWcProductFromChildId($theProduct, $theParentProduct): ?\WC_Product
    {
        $context = adp_context();

        if (is_numeric($theParentProduct)) {
            $parent = CacheHelper::getWcProduct($theParentProduct);
        } elseif ($theParentProduct instanceof \WC_Product) {
            $parent = clone $theParentProduct;
            CacheHelper::loadVariationsPostMeta($parent->get_id());
        } else {
            $parent = null;
        }

        if (is_numeric($theProduct)) {
            if ($parent && $parent->is_type('variable')) {

                // We do not need to get product type if the parent product is known
                $overrideProductTypeQuery = function () {
                    return 'variation';
                };

                $applyDataStore = function () use ($parent) {
                    $data_store = new ProductVariationDataStoreCpt();
                    if (!is_null($parent)) {
                        $data_store->addParent($parent);
                    }

                    return $data_store;
                };

                if ($context->isReplaceProductVariationDataStore()) {
                    add_filter('woocommerce_product-variation_data_store', $applyDataStore, 10);
                    add_filter('woocommerce_product_type_query', $overrideProductTypeQuery, 10);
                    $product = CacheHelper::getWcProduct($theProduct);
                    remove_filter('woocommerce_product_type_query', $overrideProductTypeQuery, 10);
                    remove_filter('woocommerce_product-variation_data_store', $applyDataStore, 10);
                } else {
                    $product = CacheHelper::getWcProduct($theProduct);
                }
            } else {
                $product = CacheHelper::getWcProduct($theProduct);
            }
        } elseif ($theProduct instanceof \WC_Product) {
            $product = clone $theProduct;

            try {
                $reflection = new \ReflectionClass($product);
                $property = $reflection->getProperty('changes');
                $property->setAccessible(true);
                $changes = $product->get_changes();

                $changes = array_filter([
                    'attributes' => $changes['attributes'] ?? null,
                    'adpCustomInitialPrice' => $changes['adpCustomInitialPrice'] ?? null
                ]);

                $property->setValue($product, $changes);
            } catch (\ReflectionException $exception) {
                $property = null;
            }
        } else {
            $product = null;
        }

        return $product ?: null;
    }

    public static function isCalculatingPartOfContainerProduct(WC_Product $wcProduct): bool
    {
        $context = adp_context();
        $bundleProduct = self::getBundleProductFromBundled($wcProduct);

        if ($bundleProduct === null) {
            return false;
        }

        $cmp = $context->getContainerCompatibilityManager()->getCompatibilityFromContainerWcProduct($bundleProduct);

        if ($cmp === null) {
            return false;
        }

        $containerItem = $cmp->adaptContainerWcProduct($bundleProduct);

        foreach ($containerItem->getItems() as $bundledItem) {
            if (intval($bundledItem->getWcItem()->getProduct()->get_id()) === intval($wcProduct->get_id())) {
                return true;
            }
        }

        return false;
    }

    public static function getBundleProductFromBundled(WC_Product $wcProduct): ?WC_Product
    {
        $context = adp_context();
        $bundleProduct = ($GLOBALS['product'] ?? null);

        if (
            $bundleProduct === null
            || is_string($bundleProduct)
            || !$context->getContainerCompatibilityManager()->isContainerProduct($bundleProduct)
        ) {
            return null;
        }

        return $bundleProduct;
    }

    public static function tmpItemsToProcessedProduct($context, $product, $tmpItems, $tmpFreeItems, $tmpListOfFreeCartItemChoices)
    {
        $allItemsAreBasic = null;
        $allItemsAreContainers = null;
        foreach ($tmpItems as $item) {
            if ($item instanceof ContainerCartItem) {
                $allItemsAreBasic = false;
                if ($allItemsAreContainers === null) {
                    $allItemsAreContainers = true;
                }
            } elseif ($item instanceof BasicCartItem) {
                $allItemsAreContainers = false;
                if ($allItemsAreBasic === null) {
                    $allItemsAreBasic = true;
                }
            } else {
                $allItemsAreBasic = false;
                $allItemsAreContainers = false;
            }
        }

        if ($allItemsAreBasic === true) {
            $processedProduct = new ProcessedProductSimple(
                $context,
                $product,
                $tmpItems,
                $tmpFreeItems,
                $tmpListOfFreeCartItemChoices
            );
        } elseif ($allItemsAreContainers === true) {
            $processedProduct = new ProcessedProductContainer(
                $context,
                $product,
                $tmpItems,
                $tmpFreeItems,
                $tmpListOfFreeCartItemChoices
            );
        } else {
            return null;
        }

        return $processedProduct;
    }
}
