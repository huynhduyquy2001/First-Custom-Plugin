<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor\BulkDiscount;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;
use ADP\BaseVersion\Includes\Core\Rule\Structures\Filter;
use ADP\BaseVersion\Includes\Core\RuleProcessor\PriceCalculator;
use ADP\BaseVersion\Includes\Core\RuleProcessor\ProductFiltering;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartItemsCollection;
use ADP\Factory;

class SingleItemRuleBulkDiscountProcessor
{
    /**
     * @var Context
     */
    private $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    /**
     * @param SingleItemRule $rule
     * @param CartItemsCollection $collection
     * @return BasicCartItem[][]
     */
    public function calculateItems(SingleItemRule $rule, CartItemsCollection $collection)
    {
        $handler = $rule->getProductRangeAdjustmentHandler();

        $groupedItems = [];

        if ($handler::GROUP_BY_DEFAULT === $handler->getGroupBy()) {
            $groupedItems[] = $collection->get_items();
        } elseif ($handler::GROUP_BY_PRODUCT === $handler->getGroupBy()) {
            foreach ($collection->get_items() as $item) {
                $facade = $item->getWcItem();

                if (!isset($groupedItems[$facade->getProductId()])) {
                    $groupedItems[$facade->getProductId()] = array();
                }

                $groupedItems[$facade->getProductId()][] = $item;
            }
        } elseif ($handler::GROUP_BY_VARIATION === $handler->getGroupBy()) {
            foreach ($collection->get_items() as $item) {
                $facade = $item->getWcItem();

                if (!isset($groupedItems[$facade->getVariationId()])) {
                    $groupedItems[$facade->getVariationId()] = array();
                }

                $groupedItems[$facade->getVariationId()][] = $item;
            }
        } elseif ($handler::GROUP_BY_CART_POSITIONS === $handler->getGroupBy()) {
            foreach ($collection->get_items() as $item) {
                $facade = $item->getWcItem();

                if (!isset($groupedItems[$facade->getKey()])) {
                    $groupedItems[$facade->getKey()] = array();
                }

                $groupedItems[$facade->getKey()][] = $item;
            }
        } elseif ($handler::GROUP_BY_ALL_ITEMS_IN_CART === $handler->getGroupBy()) {
            $groupedItems[] = $collection->get_items();
        } elseif ($handler::GROUP_BY_PRODUCT_CATEGORIES === $handler->getGroupBy()) {
            $groupedItems[] = $collection->get_items();
        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_PRODUCTS === $handler->getGroupBy()) {
            $groupedItems[] = $collection->get_items();
        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_CATEGORIES === $handler->getGroupBy()) {
            $groupedItems[] = $collection->get_items();
        } elseif ($handler::GROUP_BY_META_DATA === $handler->getGroupBy()) {
            foreach ($collection->get_items() as $item) {
                /**
                 * @var BasicCartItem $item
                 */
                $facade = $item->getWcItem();

                $meta = $facade->getProduct()->get_meta_data();

                usort($meta, function ($a, $b) {
                    return strcmp($a->__get('key'), $b->__get('key'));
                });

                $meta[] = $facade->getProductId();
                $meta[] = $facade->getVariationId();

                $key = md5(json_encode($meta));

                if (!isset($groupedItems[$key])) {
                    $groupedItems[$key] = array();
                }

                $groupedItems[$key][] = $item;
            }
        }

        return $groupedItems;
    }

    /**
     * @param SingleItemRule $rule
     * @param Cart $cart
     * @param CartItemsCollection $collection
     * @param BasicCartItem[] $items
     * @return float|int
     */
    public function calculateMeasurementValue(
        SingleItemRule $rule,
        Cart $cart,
        CartItemsCollection $collection,
        $items
    ) {
        $handler = $rule->getProductRangeAdjustmentHandler();

        $measurement = $handler->getMeasurement();

        if ($measurement->equals(BulkMeasurementEnum::QTY())) {
            $calculationCallback = function ($item) {
                /**
                 * @var BasicCartItem $item
                 */

                return $item->getQty();
            };
        } else {
            if ($measurement->equals(BulkMeasurementEnum::SUM())) {
                $calculationCallback = function ($item) {
                    /**
                     * @var BasicCartItem $item
                     */

                    return $item->getPrice() * $item->getQty();
                };
            } else {
                if ($measurement->equals(BulkMeasurementEnum::WEIGHT())) {
                    $calculationCallback = function ($item) {
                        /**
                         * @var BasicCartItem $item
                         */

                        return $item->getWeight() * $item->getQty();
                    };
                } else {
                    $calculationCallback = function ($item) {
                        return 0.0;
                    };
                    $this->context->handleError(
                        new \Exception("Unknown measurement value: " . var_export($measurement, true))
                    );
                }
            }
        }

        $value = floatval(0);

        if ($handler::GROUP_BY_DEFAULT === $handler->getGroupBy()) {
            $value = array_sum(array_map($calculationCallback, $items));
        } elseif ($handler::GROUP_BY_PRODUCT === $handler->getGroupBy()) {
            $value = array_sum(array_map($calculationCallback, $items));
        } elseif ($handler::GROUP_BY_VARIATION === $handler->getGroupBy()) {
            $value = array_sum(array_map($calculationCallback, $items));
        } elseif ($handler::GROUP_BY_CART_POSITIONS === $handler->getGroupBy()) {
            $value = array_sum(array_map($calculationCallback, $items));
        } elseif ($handler::GROUP_BY_ALL_ITEMS_IN_CART === $handler->getGroupBy()) {
            $value = array_sum(array_map(function ($item) use ($calculationCallback) {
                $facade = $item->getWcItem();

                return $facade->isVisible() ? $calculationCallback($item) : floatval(0);
            }, array_merge($collection->get_items(), $cart->getItems())));
        } elseif ($handler::GROUP_BY_PRODUCT_CATEGORIES === $handler->getGroupBy()) {
            $usedCategoryIds = array();
            foreach ($collection->get_items() as $item) {
                $product = $item->getWcItem()->getProduct();

                if ($product->is_type('variation') && $product->get_parent_id()) {
                    $product = CacheHelper::getWcProduct($product->get_parent_id());
                    $usedCategoryIds += $product->get_category_ids();
                } else {
                    $usedCategoryIds += $product->get_category_ids();
                }
            }
            $usedCategoryIds = array_unique($usedCategoryIds);

            /** @var ProductFiltering $productFiltering */
            $productFiltering = Factory::get("Core_RuleProcessor_ProductFiltering", $this->context);
            $productFiltering->prepare(Filter::TYPE_CATEGORY, $usedCategoryIds, 'in_list');

            // count items with same categories in WC cart
            $value = floatval(0);
            if ($usedCategoryIds) {
                foreach (array_merge($collection->get_items(), $cart->getItems()) as $cartItem) {
                    /** @var BasicCartItem $cartItem */
                    $facade = $cartItem->getWcItem();

                    if (!$facade->isVisible()) {
                        continue;
                    }

                    if ($productFiltering->checkProductSuitability($facade->getProduct())) {
                        $value += $calculationCallback($cartItem);
                    }
                }
            }

        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_PRODUCTS === $handler->getGroupBy()) {
            $selectedProductIds = $handler->getSelectedProductIds();

            $value = floatval(0);
            if ($selectedProductIds) {
                foreach (array_merge($collection->get_items(), $cart->getItems()) as $cartItem) {
                    /** @var BasicCartItem $cartItem */
                    $facade = $cartItem->getWcItem();

                    if (!$facade->isVisible()) {
                        continue;
                    }

                    if (in_array($facade->getProduct()->get_id(), $selectedProductIds)) {
                        $value += $calculationCallback($cartItem);
                    }
                }
            }
        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_CATEGORIES === $handler->getGroupBy()) {
            $selectedCategoryIds = $handler->getSelectedCategoryIds();

            $productFiltering = Factory::get("Core_RuleProcessor_ProductFiltering", $this->context);
            /** @var ProductFiltering $productFiltering */
            $productFiltering->prepare(Filter::TYPE_CATEGORY, $selectedCategoryIds, 'in_list');

            $value = floatval(0);
            if ($selectedCategoryIds) {
                foreach (array_merge($collection->get_items(), $cart->getItems()) as $cartItem) {
                    /** @var BasicCartItem $cartItem */
                    $facade = $cartItem->getWcItem();

                    if (!$facade->isVisible()) {
                        continue;
                    }

                    if ($productFiltering->checkProductSuitability($facade->getProduct())) {
                        $value += $calculationCallback($cartItem);
                    }
                }
            }
        } elseif ($handler::GROUP_BY_META_DATA === $handler->getGroupBy()) {
            $value = array_sum(array_map($calculationCallback, $items));
        }

        return $value;
    }

    /**
     * @param SingleItemRule $rule
     * @param Cart $cart
     * @param numeric $rangeValueToCompare
     * @param BasicCartItem[] $itemsToApply
     * @return void
     */
    public function discountItems(
        SingleItemRule $rule,
        Cart $cart,
        $rangeValueToCompare,
        $itemsToApply
    ) {
        $handler = $rule->getProductRangeAdjustmentHandler();
        $ranges = $handler->getRanges();

        foreach ($ranges as $range) {
            /** @var PriceCalculator $priceCalculator */
            $priceCalculator = Factory::get(
                "Core_RuleProcessor_PriceCalculator",
                $rule,
                $range->getData()
            );

            foreach ($itemsToApply as $item) {
                $price = $priceCalculator->calculatePrice($item, $cart);

                if ($price === null) {
                    continue;
                }

                $minPrice = $item->prices()->getMinDiscountRangePrice();

                if ($minPrice !== null) {
                    if ($price < $minPrice) {
                        $item->prices()->setMinDiscountRangePrice($price);
                    }
                } else {
                    $item->prices()->setMinDiscountRangePrice($price);
                }
            }
        }

        foreach ($ranges as $range) {
            if ($range->isIn($rangeValueToCompare)) {
                /** @var PriceCalculator $priceCalculator */
                $priceCalculator = Factory::get(
                    "Core_RuleProcessor_PriceCalculator",
                    $rule,
                    $range->getData()
                );

                foreach ($itemsToApply as $item) {
                    $priceCalculator->applyItemDiscount($item, $cart, $handler);
                }
            }
        }
    }
}
