<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor\BulkDiscount;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\BaseVersion\Includes\Core\Rule\PackageRule;
use ADP\BaseVersion\Includes\Core\Rule\Structures\Discount;
use ADP\BaseVersion\Includes\Core\Rule\Structures\SetDiscount;
use ADP\BaseVersion\Includes\Core\RuleProcessor\PriceCalculator;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartSet;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartSetCollection;
use ADP\Factory;

class PackageRuleBulkDiscountProcessor
{
    /**
     * @var Context
     */
    private $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function calculateMeasurement(
        PackageRule $rule,
        Cart $cart,
        CartSetCollection $collection
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

                    return $item->getPrice();
                };
            } else {
                if ($measurement->equals(BulkMeasurementEnum::WEIGHT())) {
                    $calculationCallback = function ($item) {
                        /**
                         * @var BasicCartItem $item
                         */

                        return $item->getWeight();
                    };
                } else {
                    $calculationCallback = function ($item) {return 0.0;};
                    $this->context->handleError(
                        new \Exception("Unknown measurement value: " . var_export($measurement, true))
                    );
                }
            }
        }

        $value = floatval(0);

        if ($handler::GROUP_BY_DEFAULT === $handler->getGroupBy()) {
            $value = array_sum(array_map(function ($set) use ($calculationCallback) {
                /**
                 * @var CartSet $set
                 */
                $valueSet = 0;
                foreach ($set->getItems() as $item) {
                    $valueSet += $calculationCallback($item) * $set->getQty();
                }

                return $valueSet;
            }, $collection->getSets()));
        } elseif ($handler::GROUP_BY_PRODUCT === $handler->getGroupBy()) {
            $products = [];

            $value = array_sum(array_map(function ($set) use (&$products, $calculationCallback) {
                /**
                 * @var CartSet $set
                 * @var BasicCartItem[] $items
                 */
                $items = $set->getItems();
                $valueSet = floatval(0);

                foreach ($items as $item) {
                    if (!in_array($item->getWcItem()->getProductId(), $products)) {
                        $products[] = $item->getWcItem()->getProductId();
                        $valueSet += $calculationCallback($item) * $set->getQty();
                    }
                }

                return $valueSet;
            }, $collection->getSets()));
        } elseif ($handler::GROUP_BY_VARIATION === $handler->getGroupBy()) {
            $variations = [];
            $value = array_sum(array_map(function ($set) use (&$variations, $calculationCallback) {
                /**
                 * @var CartSet $set
                 * @var BasicCartItem[] $items
                 */
                $items = $set->getItems();
                $valueSet = floatval(0);

                foreach ($items as $item) {
                    if (!in_array($item->getWcItem()->getVariationId(), $variations)) {
                        $variations[] = $item->getWcItem()->getVariationId();
                        $valueSet += $calculationCallback($item) * $set->getQty();
                    }
                }

                return $valueSet;
            }, $collection->getSets()));
        } elseif ($handler::GROUP_BY_CART_POSITIONS === $handler->getGroupBy()) {
            $value = array_sum(array_map(function ($set) use ($calculationCallback) {
                /**
                 * @var CartSet $set
                 */
                $valueSet = floatval(0);

                foreach ($set->getItems() as $item) {
                    $valueSet += $calculationCallback($item) * $set->getQty();
                }

                return $valueSet;
            }, $collection->getSets()));
        } elseif ($handler::GROUP_BY_SETS === $handler->getGroupBy()) {
            $value = array_sum(array_map(function ($set) use ($calculationCallback) {
                /**
                 * @var CartSet $set
                 */
                $valueSet = $set->getQty(); // exception, always calculates qty

                return $valueSet;
            }, $collection->getSets()));
        } elseif ($handler::GROUP_BY_ALL_ITEMS_IN_CART === $handler->getGroupBy()) {
            $value = array_sum(
                array_map(function ($set) use ($calculationCallback) {
                    /**
                     * @var CartSet $set
                     */
                    $valueSet = 0;
                    foreach ($set->getItems() as $item) {
                        $valueSet += $calculationCallback($item) * $set->getQty();
                    }

                    return $valueSet;
                }, $collection->getSets())
            );

            $value += array_sum(
                array_map(function ($item) use ($calculationCallback) {
                    $facade = $item->getWcItem();

                    return $facade->isVisible() ? $calculationCallback($item) : floatval(0);
                }, $cart->getItems())
            );
        } elseif ($handler::GROUP_BY_PRODUCT_CATEGORIES === $handler->getGroupBy()) {
            $usedCategoryIds = array();
            $value = floatval(0);
            foreach ($collection->getSets() as $set) {
                foreach ($set->getItems() as $item) {
                    $usedCategoryIds += $item->getWcItem()->getProduct()->get_category_ids();
                    $value += $calculationCallback($item) * $set->getQty();
                }
            }
            $usedCategoryIds = array_unique($usedCategoryIds);

            if ($usedCategoryIds) {
                foreach ($cart->getItems() as $item) {
                    $facade = $item->getWcItem();
                    if (!$facade->isVisible()) {
                        continue;
                    }

                    $product = $facade->getProduct();

                    if (count(array_intersect($product->get_category_ids(), $usedCategoryIds))) {
                        $value += $calculationCallback($item);
                    }
                }
            }
        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_PRODUCTS === $handler->getGroupBy()) {
            $selectedProductIds = $handler->getSelectedProductIds();

            $value = floatval(0);
            if ($selectedProductIds) {
                foreach ($collection->getSets() as $set) {
                    foreach ($set->getItems() as $item) {
                        $facade = $item->getWcItem();

                        if (in_array($facade->getProduct()->get_id(), $selectedProductIds)) {
                            $value += $calculationCallback($item) * $set->getQty();
                        }
                    }
                }

                foreach ($cart->getItems() as $item) {
                    $facade = $item->getWcItem();
                    if (!$facade->isVisible()) {
                        continue;
                    }

                    if (in_array($facade->getProduct()->get_id(), $selectedProductIds)) {
                        $value += $calculationCallback($item);
                    }
                }
            }
        } elseif ($handler::GROUP_BY_PRODUCT_SELECTED_CATEGORIES === $handler->getGroupBy()) {
            $selectedCategoryIds = $handler->getSelectedCategoryIds();

            $value = floatval(0);
            if ($selectedCategoryIds) {
                foreach ($collection->getSets() as $set) {
                    foreach ($set->getItems() as $item) {
                        $facade = $item->getWcItem();

                        if (count(array_intersect($facade->getProduct()->get_category_ids(),
                            $selectedCategoryIds))) {
                            $value += $calculationCallback($item) * $set->getQty();
                        }
                    }
                }

                foreach ($cart->getItems() as $item) {
                    $facade = $item->getWcItem();
                    if (!$facade->isVisible()) {
                        continue;
                    }

                    if (count(array_intersect($facade->getProduct()->get_category_ids(), $selectedCategoryIds))) {
                        $value += $calculationCallback($item);
                    }
                }
            }
        } elseif ($handler::GROUP_BY_META_DATA === $handler->getGroupBy()) {
            $productsByMeta = array();
            $value = array_sum(array_map(function ($set) use (&$productsByMeta, $calculationCallback) {
                /**
                 * @var CartSet $set
                 * @var BasicCartItem[] $items
                 */
                $items = $set->getItems();
                $valueSet = floatval(0);

                foreach ($items as $item) {
                    $facade = $item->getWcItem();

                    $meta = $facade->getProduct()->get_meta_data();

                    usort($meta, function ($a, $b) {
                        return strcmp($a->__get('key'), $b->__get('key'));
                    });

                    $meta[] = $facade->getProductId();
                    $meta[] = $facade->getVariationId();

                    $hash = md5(json_encode($meta));

                    if (!in_array($hash, $productsByMeta)) {
                        $productsByMeta[] = $hash;
                        $valueSet += $calculationCallback($item) * $set->getQty();
                    }
                }

                return $valueSet;
            }, $collection->getSets()));
        }

        return $value;
    }


    /**
     * @param PackageRule $rule
     * @param Cart $cart
     * @param numeric $rangeValueToCompare
     * @param CartSetCollection $cartSetCollectionToApply
     * @return void
     */
    public function discountItems(
        PackageRule $rule,
        Cart $cart,
        $rangeValueToCompare,
        CartSetCollection $cartSetCollectionToApply
    ) {
        $handler = $rule->getProductRangeAdjustmentHandler();
        $ranges = $handler->getRanges();

        foreach ($ranges as $range) {
            if ($range->isIn($rangeValueToCompare)) {
                $discount = $range->getData();
                /** @var PriceCalculator $priceCalculator */
                $priceCalculator = Factory::get("Core_RuleProcessor_PriceCalculator", $rule, $discount);

                if ($discount instanceof SetDiscount) { //have to check child class first
                    foreach ($cartSetCollectionToApply->getSets() as $set) {
                        $priceCalculator->calculatePriceForSet($set, $cart, $handler);
                    }
                } elseif ($discount instanceof Discount) {
                    foreach ($cartSetCollectionToApply->getSets() as $set) {
                        foreach ($set->getItems() as $item) {
                            $priceCalculator->applyItemDiscount($item, $cart, $handler);
                        }
                    }
                }
                break;
            }
        }
    }

}
