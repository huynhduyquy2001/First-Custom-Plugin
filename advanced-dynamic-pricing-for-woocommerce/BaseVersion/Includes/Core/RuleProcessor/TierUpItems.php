<?php

namespace ADP\BaseVersion\Includes\Core\RuleProcessor;

use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\ICartItem;
use ADP\BaseVersion\Includes\Core\Rule\PackageRule;
use ADP\BaseVersion\Includes\Core\Rule\PackageRule\PackageRangeAdjustments;
use ADP\BaseVersion\Includes\Core\Rule\Rule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule;
use ADP\BaseVersion\Includes\Core\Rule\SingleItemRule\ProductsRangeAdjustments;
use ADP\BaseVersion\Includes\Core\Rule\Structures\Discount;
use ADP\BaseVersion\Includes\Core\Rule\Structures\RangeDiscount;
use ADP\BaseVersion\Includes\Core\Rule\Structures\SetDiscount;
use ADP\BaseVersion\Includes\Core\RuleProcessor\Structures\CartSet;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Basic\BasicCartItem;
use ADP\Factory;

defined('ABSPATH') or exit;

class TierUpItems
{
    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var ProductsRangeAdjustments|PackageRangeAdjustments
     */
    protected $handler;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @param SingleItemRule|PackageRule $rule
     * @param Cart $cart
     */
    public function __construct($rule, $cart)
    {
        $this->rule = $rule;
        $this->cart = $cart;
        $this->handler = $rule->getProductRangeAdjustmentHandler();
    }

    /**
     * @param array<int,ICartItem> $items
     *
     * @return array<int,ICartItem>
     */
    public function executeItems($items)
    {
        $tierItems = [];
        foreach ($items as $item) {
            $tierItems[] = TierItemProxy::ofCartItem($item);
        }

        foreach ($this->handler->getRanges() as $range) {
            $tierItems = $this->processRange($tierItems, $range);
        }

        foreach ($tierItems as $index => $tierItem) {
            if (!$tierItem->hasMark(TierItemProxy::MARK_CALCULATED)) {
                unset($tierItems[$index]);
                array_splice($tierItems, 0, 0, array($tierItem));
            }
        }
        $tierItems = array_values($tierItems);

        foreach ($tierItems as $tierItem) {
            $tierItem->removeMark(TierItemProxy::MARK_CALCULATED);
        }

        return array_map(function ($tierItem) {
            return $tierItem->getItem();
        }, $tierItems);
    }

    /**
     * @param array<int,ICartItem> $items
     * @param float $customQty
     *
     * @return array<int,ICartItem>
     */
    public function executeItemsWithCustomQty($items, $customQty)
    {
        if ($customQty === floatval(0)) {
            return $items;
        }

        $tierItems = [];
        foreach ($items as $item) {
            $tierItems = TierItemProxy::ofCartItem($item);
        }

        foreach ($this->handler->getRanges() as $range) {
            if (!is_null($customQty) && $range->isIn($customQty)) {
                $tierItems = $this->processRange(
                    $tierItems,
                    new RangeDiscount($range->getFrom(), $customQty, $range->getData())
                );
                break;
            }

            $tierItems = $this->processRange($tierItems, $range);
        }

        return array_map(function ($tierItem) {
            return $tierItem->getItem();
        }, $tierItems);
    }

    /**
     * @param array<int,CartSet> $cartSets
     *
     * @return array<int,CartSet>
     */
    public function executeSets($cartSets)
    {
        $tierItems = [];
        foreach ($cartSets as $cartSet) {
            $tierItems = TierItemProxy::ofCartSet($cartSet);
        }

        foreach ($this->handler->getRanges() as $range) {
            $tierItems = $this->processRange($tierItems, $range);
        }

        return array_map(function ($tierItem) {
            return $tierItem->getItem();
        }, $tierItems);
    }

    /**
     * @param array<int,TierItemProxy> $elements
     * @param RangeDiscount $range
     *
     * @return array<int,TierItemProxy>
     */
    protected function processRange($elements, $range)
    {
        $processedQty = 1;
        $newElements = array();
        $indexOfItemsToProcess = array();
        foreach ($elements as $element) {
            if ($element->hasMark(TierItemProxy::MARK_CALCULATED)) {
                $newElements[] = $element;
                $processedQty += $element->getQty();
                continue;
            }

            if ($range->isLess($processedQty)) {
                if ($range->isIn($processedQty + $element->getQty())) {
                    $requireQty = $processedQty + $element->getQty() - $range->getFrom();

                    if ($requireQty > 0) {
                        $newItem = clone $element;
                        $newItem->setQty($requireQty);
                        $newElements[] = $newItem;
                        $indexOfItemsToProcess[] = count($newElements) - 1;
                        $processedQty += $requireQty;
                    }

                    if (($element->getQty() - $requireQty) > 0) {
                        $newItem = clone $element;
                        $newItem->setQty($element->getQty() - $requireQty);
                        $newElements[] = $newItem;
                        $processedQty += $element->getQty() - $requireQty;
                    }
                } elseif ($range->isGreater($processedQty + $element->getQty())) {
                    $requireQty = $range->getQtyInc();

                    if ($requireQty > 0) {
                        $newItem = clone $element;
                        $newItem->setQty($requireQty);
                        $newElements[] = $newItem;
                        $indexOfItemsToProcess[] = count($newElements) - 1;
                        $processedQty += $requireQty;
                    }

                    if (($element->getQty() - $requireQty) > 0) {
                        $newItem = clone $element;
                        $newItem->setQty($element->getQty() - $requireQty);
                        $newElements[] = $newItem;
                        $processedQty += $element->getQty() - $requireQty;
                    }

                } else {
                    $newElements[] = $element;
                    $processedQty += $element->getQty();
                }
            } elseif ($range->isIn($processedQty)) {
                $requireQty = $range->getTo() + 1 - $processedQty;
                $requireQty = $requireQty < $element->getQty() ? $requireQty : $element->getQty();

                if ($requireQty > 0) {
                    $newItem = clone $element;
                    $newItem->setQty($requireQty);
                    $newElements[] = $newItem;
                    $indexOfItemsToProcess[] = count($newElements) - 1;
                    $processedQty += $requireQty;
                }

                if (($element->getQty() - $requireQty) > 0) {
                    $newItem = clone $element;
                    $newItem->setQty($element->getQty() - $requireQty);
                    $newElements[] = $newItem;
                    $processedQty += $element->getQty() - $requireQty;
                }

            } elseif ($range->isGreater($processedQty)) {
                $newElements[] = $element;
                $processedQty += $element->getQty();
            }
        }

        $discount = $range->getData();
        /** @var PriceCalculator $priceCalculator */
        $priceCalculator = Factory::get("Core_RuleProcessor_PriceCalculator", $this->rule, $discount);
        foreach ($indexOfItemsToProcess as $index) {
            $tierItem = $newElements[$index];
            $elementToProcess = $tierItem->getItem();

            if ($elementToProcess instanceof CartSet) {
                if ($discount instanceof SetDiscount) {
                    $priceCalculator->calculatePriceForSet($elementToProcess, $this->cart, $this->handler);
                } elseif ($discount instanceof Discount) {
                    foreach ($elementToProcess->getItems() as $element) {
                        $priceCalculator->applyItemDiscount($element, $this->cart, $this->handler);
                    }
                }
                $tierItem->addMark(TierItemProxy::MARK_CALCULATED);
            } elseif ($elementToProcess instanceof ICartItem) {
                $priceCalculator->applyItemDiscount($elementToProcess, $this->cart, $this->handler);
                $tierItem->addMark(TierItemProxy::MARK_CALCULATED);
            }
        }

        return $newElements;
    }
}
