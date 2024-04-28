<?php

namespace ADP\BaseVersion\Includes\PriceDisplay\WcProductProcessor;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Debug\ProductCalculatorListener;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedGroupedProduct;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductContainer;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedProductSimple;
use ADP\BaseVersion\Includes\PriceDisplay\ProcessedVariableProduct;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductCalculationWrapper;
use ADP\Factory;
use Exception;
use WC_Product;

class InCartWcProductProcessor implements IWcProductProcessor
{

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ProductCalculatorListener
     */
    protected $listener;

    /**
     * @var Cart|null
     */
    protected $cart;

    public function __construct()
    {
        $this->context = adp_context();
        $this->listener = new ProductCalculatorListener();
    }

    public function withCart(Cart $cart)
    {
        $this->cart = $cart;
    }

    protected function isCartExists(): bool
    {
        return isset($this->cart);
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param WC_Product|int $theProduct
     * @param float $qty
     * @param array $cartItemData
     *
     * @return ProcessedProductSimple|ProcessedVariableProduct|ProcessedGroupedProduct|null
     */
    public function calculateProduct($theProduct, $qty = 1.0, $cartItemData = array())
    {
        if (is_numeric($theProduct)) {
            $product = CacheHelper::getWcProduct($theProduct);
        } elseif ($theProduct instanceof WC_Product) {
            $product = clone $theProduct;
        } else {
            $this->context->handleError(new Exception("Product does not exists"));

            return null;
        }

        return $this->calculateWithProductWrapper(
            new WcProductCalculationWrapper($product, $cartItemData, []),
            $qty
        );
    }

    /**
     * @param WcProductCalculationWrapper $wrapper
     * @param float $qty
     *
     * @return ProcessedGroupedProduct|ProcessedProductSimple|ProcessedVariableProduct|null
     */
    public function calculateWithProductWrapper(WcProductCalculationWrapper $wrapper, float $qty = 1.0){
        $product = $wrapper->getWcProduct();
        $cartItemData = $wrapper->getCartItemData();

        if ($product instanceof \WC_Product_Grouped) {
            /** @var $processed ProcessedGroupedProduct */
            $processed = Factory::get("PriceDisplay_ProcessedGroupedProduct", $product, $qty);
            $children = array_filter(
                array_map('wc_get_product', $product->get_children()),
                'wc_products_array_filter_visible_grouped'
            );

            foreach ($children as $childId) {
                $processedChild = $this->calculateSimpleProductWrapper(
                    new WcProductCalculationWrapper(
                        WcProductProcessorHelper::buildWcProductFromChildId($childId, $product),
                        $cartItemData,
                        []
                    ),
                    $qty
                );

                if (is_null($processedChild)) {
                    continue;
                }

                $processed->useChild($processedChild);
            }
        } elseif ($product instanceof \WC_Product_Variable) {
            /** @var $processed ProcessedVariableProduct */
            $processed = Factory::get("PriceDisplay_ProcessedVariableProduct", $this->context, $product, $qty);
            $children = $product->get_visible_children();

            foreach ($children as $childId) {
                $processedChild = $this->calculateSimpleProductWrapper(
                    new WcProductCalculationWrapper(
                        WcProductProcessorHelper::buildWcProductFromChildId($childId, $product),
                        $cartItemData,
                        []
                    ),
                    $qty
                );

                if (is_null($processedChild)) {
                    continue;
                }

                $processed->useChild($processedChild);
            }
        } elseif ( WcProductProcessorHelper::isCalculatingPartOfContainerProduct($product) ) {
            $containerProduct = WcProductProcessorHelper::getBundleProductFromBundled($product);
            $processedParent = $this->calculateSimpleProductWrapper(
                new WcProductCalculationWrapper($containerProduct, $cartItemData, []),
                $qty
            );

            $processed = null;
            if ($processedParent instanceof ProcessedProductContainer) {
                foreach ($processedParent->getContainerItemsByPos() as $containerItem) {
                    if ($containerItem->getProduct()->get_id() === $product->get_id()) {
                        $processed = $containerItem;
                    }
                }
            }
        } else {
            $processed = $this->calculateSimpleProductWrapper(
                new WcProductCalculationWrapper($product, $cartItemData, []),
                $qty
            );
        }

        return $processed;
    }

    /**
     * @param WcProductCalculationWrapper $wrapper
     * @param float $qty
     *
     * @return ProcessedProductSimple|ProcessedProductContainer|null
     */
    protected function calculateSimpleProductWrapper(
        WcProductCalculationWrapper $wrapper,
        float $qty = 1.0
    ): ?ProcessedProductSimple {
        if (!$this->isCartExists()) {
            return null;
        }

        $product = $wrapper->getWcProduct();
        $cartItemData = $wrapper->getCartItemData();
        $addons = $wrapper->getAddons();

        $tmpItems = [];
        $currentQty = 0.0;
        $qtyAlreadyInCart = floatval(0);

        $cartItems = [];
        foreach ( $this->cart->getItems() as $loopItem ) {
            $cartItems[] = clone $loopItem;
        }

        if ( has_filter("adp_in_cart_wc_product_processor_cart_items") ) {
            $cartItems = apply_filters(
                "adp_in_cart_wc_product_processor_cart_items",
                $cartItems
            );
        } else {
            if ($this->context->getOption("process_product_strategy_after_use_price") === "first") {
                $cartItems = InCartWcProductProcessorPredefinedSortCallbacks::cartItemsAsIs($cartItems);
            } elseif ($this->context->getOption("process_product_strategy_after_use_price") === "last") {
                $cartItems = InCartWcProductProcessorPredefinedSortCallbacks::cartItemsInReverseOrder($cartItems);
            } elseif ($this->context->getOption("process_product_strategy_after_use_price") === "cheapest") {
                $cartItems = InCartWcProductProcessorPredefinedSortCallbacks::sortCartItemsByPriceAsc($cartItems);
            } elseif ($this->context->getOption("process_product_strategy_after_user_price") === "most_expensive") {
                $cartItems = InCartWcProductProcessorPredefinedSortCallbacks::sortCartItemsByPriceDesc($cartItems);
            }
        }


        foreach ($cartItems as $loopItem) {
            $loopProduct = $loopItem->getWcItem()->getProduct();

            $condition = $loopItem->getWcItem()->getProduct()->get_id() === $product->get_id();

            if ($product instanceof \WC_Product_Variation && $loopProduct instanceof \WC_Product_Variation) {
                $loopProductVariationAttributes = $loopProduct->get_variation_attributes();

                foreach ($product->get_variation_attributes() as $key => $value) {
                    $condition &= !isset($loopProductVariationAttributes[$key]) || $loopProductVariationAttributes[$key] !== $value;
                }
            }

            // cart item data
            foreach ($loopItem->getWcItem()->getThirdPartyData() as $key => $value) {
                $condition &= !isset($cartItemData[$key]) || $cartItemData[$key] !== $value;
            }

            // addons
            $condition &= count($loopItem->getAddons()) == count($addons);
            foreach ($loopItem->getAddons() as $loopAddon) {
                $match = false;
                foreach ($addons as $addon) {
                    if ($loopAddon->getKey() == $addon->getKey()) {
                        $match = true;
                        $condition &= $loopAddon->getPrice() == $addon->getPrice();
                        break;
                    }
                }
                $condition &= $match;
            }

            if ($condition) {
                $requiredQty = $qty - $currentQty;

                if ($requiredQty > $loopItem->getQty()) {
                    $tmpItems[] = clone $loopItem;
                    $currentQty += $loopItem->getQty();
                } elseif ($requiredQty === $loopItem->getQty()) {
                    $tmpItems[] = clone $loopItem;
                    break;
                } else {
                    $newLoopItem = clone $loopItem;
                    $newLoopItem->setQty($requiredQty);
                    $tmpItems[] = $newLoopItem;
                    break;
                }
            }

            if ($loopProduct->get_id() === $product->get_id()) {
                $qtyAlreadyInCart += $loopItem->getQty();
            }
        }

        $qtyAlreadyInCart -= array_sum(
            array_map(
                function ($item) {
                    return $item->getQty();
                },
                $tmpItems
            )
        );

        if (count($tmpItems) === 0) {
            return null;
        }

        $tmpItems = apply_filters("adp_before_processed_product", $tmpItems, $this);

        $processedProduct = WcProductProcessorHelper::tmpItemsToProcessedProduct(
            $this->context,
            $product,
            $tmpItems,
            [],
            []
        );

        $processedProduct->setQtyAlreadyInCart($qtyAlreadyInCart);
        $this->listener->processedProduct($processedProduct);

        return $processedProduct;
    }

    /**
     * @return ProductCalculatorListener
     */
    public function getListener(): ProductCalculatorListener
    {
        return $this->listener;
    }

    /**
     * @return Cart|null
     */
    public function getCart(): ?Cart
    {
        return $this->cart;
    }
}
