<?php

namespace ADP\BaseVersion\Includes\WC;

use ADP\BaseVersion\Includes\Compatibility\WcSubscriptionsCmp;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Container\ContainerPriceTypeEnum;

defined('ABSPATH') or exit;

class WcCartItemDisplayExtensions
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PriceFunctions
     */
    protected $priceFunctions;

    /**
     * @var WcSubscriptionsCmp
     */
    protected $subscriptionCmp;

    /**
     * @param null $deprecated
     */
    public function __construct($deprecated = null)
    {
        $this->context = adp_context();

        $this->priceFunctions  = new PriceFunctions();
        $this->subscriptionCmp = new WcSubscriptionsCmp();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function register()
    {
        // 10000 for WPC bundles
        add_filter('woocommerce_cart_item_price', array($this, 'wcCartItemPrice'), 10000, 3);
        add_filter('woocommerce_cart_item_subtotal', array($this, 'wcCartItemSubtotal'), 10000, 3);
    }

    /**
     * @param string $price formatted price after wc_price()
     * @param array $cartItem
     * @param string $cartItemKey
     *
     * @return string
     */
    public function wcCartItemPrice($price, $cartItem, $cartItemKey)
    {
        if ($this->context->getOption('show_striked_prices')) {
            $price = $this->wcMainCartItemPrice($price, $cartItem, $cartItemKey);
        }

        return $price;
    }

    /**
     * @param string $price formatted price after wc_price()
     * @param array $cartItem
     * @param string $cartItemKey
     *
     * @return string
     */
    public function wcCartItemSubtotal($price, $cartItem, $cartItemKey)
    {
        if ($this->context->getOption('show_striked_prices')) {
            $price = $this->wcMainCartItemSubtotal($price, $cartItem, $cartItemKey);
        }

        return $price;
    }

    /**
     * @param string $price formatted price after wc_price()
     * @param array $cartItem
     * @param string $cartItemKey
     *
     * @return string
     */
    protected function wcMainCartItemPrice($price, $cartItem, $cartItemKey)
    {
        if ($this->subscriptionCmp->isSetFreeTrial($cartItem)) {
            return $price;
        }

        $context = $this->context;
        $facade = new WcCartItemFacade($context, $cartItem, $cartItemKey);
        $subsCmp = new WcSubscriptionsCmp($context);

        $newPriceHtml = $price;
        $displayPricesIncludingTax = 'incl' === $context->getTaxDisplayCartMode();
        $oldPrice = $this->getOriginalPriceToDisplayForCartItem($facade, $displayPricesIncludingTax);
        $newPrice = $this->getCalculatedPriceToDisplayForCartItem($facade, $displayPricesIncludingTax);

        if ($oldPrice === null || $newPrice === null) {
            return $price;
        }

        $newPrice = apply_filters('wdp_cart_item_new_price', $newPrice, $cartItem, $cartItemKey);
        $oldPrice = apply_filters('wdp_cart_item_initial_price', $oldPrice, $cartItem, $cartItemKey);

        if (is_numeric($newPrice) && is_numeric($oldPrice)) {
            $oldPriceRounded = round($oldPrice, $this->context->priceSettings->getDecimals());
            $newPriceRounded = round($newPrice, $this->context->priceSettings->getDecimals());

            if ($newPriceRounded < $oldPriceRounded) {
                $priceHtml = $this->priceFunctions->formatSalePrice($oldPrice, $newPrice);

                if ($subsCmp->isSubscriptionProduct($facade->getProduct())) {
                    $priceHtml = $subsCmp->maybeAddSubsTail($facade->getProduct(), $priceHtml);
                }
            } elseif ($newPriceRounded === $oldPriceRounded) {
                $priceHtml = $this->priceFunctions->format($oldPrice);

                if ($subsCmp->isSubscriptionProduct($facade->getProduct())) {
                    $priceHtml = $subsCmp->maybeAddSubsTail($facade->getProduct(), $priceHtml);
                }
            } else {
                $priceHtml = $newPriceHtml;
            }
        } else {
            $priceHtml = $newPriceHtml;
        }

        return $priceHtml;
    }

    /**
     * @param string $price formatted price after wc_price()
     * @param array $cartItem
     * @param string $cartItemKey
     *
     * @return string
     */
    protected function wcMainCartItemSubtotal($price, $cartItem, $cartItemKey)
    {
        if ($this->subscriptionCmp->isSetFreeTrial($cartItem)) {
            return $price;
        }

        $context = $this->context;
        $facade = new WcCartItemFacade($context, $cartItem, $cartItemKey);

        $containerCmp = $this->context->getContainerCompatibilityManager()->getCompatibilityFromPartOfContainerFacade($facade);
        if ($containerCmp && ! $containerCmp->adaptContainerPartCartItem($facade)->isPricedIndividually()) {
            return $price;
        }

        $subsCmp = new WcSubscriptionsCmp($context);

        $newPriceHtml = $price;
        $displayPricesIncludingTax = 'incl' === $context->getTaxDisplayCartMode();
        $oldPrice = $this->getOriginalPriceToDisplayForCartItem($facade, $displayPricesIncludingTax);
        $newPrice = $this->getCalculatedPriceToDisplayForCartItem($facade, $displayPricesIncludingTax);

        $newPrice *= $facade->getQty();
        $oldPrice *= $facade->getQty();

        $newPrice = apply_filters('wdp_cart_item_subtotal', $newPrice, $cartItem, $cartItemKey);
        $oldPrice = apply_filters('wdp_cart_item_initial_subtotal', $oldPrice, $cartItem, $cartItemKey);

        if (is_numeric($newPrice) && is_numeric($oldPrice)) {
            $oldPriceRounded = round($oldPrice, $this->context->priceSettings->getDecimals());
            $newPriceRounded = round($newPrice, $this->context->priceSettings->getDecimals());

            if ($newPriceRounded < $oldPriceRounded) {
                $priceHtml = $this->priceFunctions->formatSalePrice($oldPrice, $newPrice);

                if ($displayPricesIncludingTax) {
                    if (!$context->getIsPricesIncludeTax() && $facade->getExactSubtotalTax() > 0) {
                        $priceHtml .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
                    }
                } else {
                    if ($context->getIsPricesIncludeTax() && $facade->getExactSubtotalTax() > 0) {
                        $priceHtml .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
                    }
                }

                if ($subsCmp->isSubscriptionProduct($facade->getProduct())) {
                    $priceHtml = $subsCmp->maybeAddSubsTail($facade->getProduct(), $priceHtml);
                }
            } elseif ($newPriceRounded === $oldPriceRounded) {
                $priceHtml = $this->priceFunctions->format($oldPrice);

                if ($subsCmp->isSubscriptionProduct($facade->getProduct())) {
                    $priceHtml = $subsCmp->maybeAddSubsTail($facade->getProduct(), $priceHtml);
                }
            } else {
                $priceHtml = $newPriceHtml;
            }
        } else {
            $priceHtml = $newPriceHtml;
        }

        return $priceHtml;
    }

    protected function getOriginalPriceToDisplayForCartItem(WcCartItemFacade $facade, bool $inclTax): ?float
    {
        $context = $this->context;
        $useRegularPriceForOriginalPrice = $context->getOption('regular_price_for_striked_price');

        if ($facade->isContainerType()) {
            $parentPriceType = $facade->getContainerPriceType();

            if ($parentPriceType === null) {
                return null;
            }

            if ($parentPriceType->equals(ContainerPriceTypeEnum::FIXED())) {
                if ($inclTax) {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax() + $facade->getRegularPriceTax();
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax();
                    }
                } else {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax();
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax();
                    }
                }
            } elseif ($parentPriceType->equals(ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS())) {
                $children = array_map(function ($key) {
                    return new WcCartItemFacade(WC()->cart->cart_contents[$key], $key);
                }, $facade->getContainerChildrenHashes());

                if ($inclTax) {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax() + $facade->getRegularPriceTax();

                        $originalPrice += array_sum(
                            array_map(function ($child) use ($facade) {
                                if ($child->isContaineredPricedIndividually()) {
                                    return ($child->getRegularPriceWithoutTax() + $child->getRegularPriceTax()) * $child->getQty() / $facade->getQty();
                                } else {
                                    return 0.0;
                                }
                            }, $children)
                        );
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax();

                        $originalPrice += array_sum(
                            array_map(function ($child) use ($facade) {
                                if ($child->isContaineredPricedIndividually()) {
                                    return ($child->getOriginalPriceWithoutTax() + $child->getOriginalPriceTax()) * $child->getQty() / $facade->getQty();
                                } else {
                                    return 0.0;
                                }
                            }, $children)
                        );
                    }
                } else {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax();

                        $originalPrice += array_sum(
                            array_map(function ($child) use ($facade) {
                                if ($child->isContaineredPricedIndividually()) {
                                    return $child->getRegularPriceWithoutTax() * $child->getQty() / $facade->getQty();
                                } else {
                                    return 0.0;
                                }
                            }, $children)
                        );
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax();

                        $originalPrice += array_sum(
                            array_map(function ($child) use ($facade) {
                                if ($child->isContaineredPricedIndividually()) {
                                    return $child->getOriginalPriceWithoutTax() * $child->getQty() / $facade->getQty();
                                } else {
                                    return 0.0;
                                }
                            }, $children)
                        );
                    }
                }
            } else {
                return null;
            }
        } elseif ($facade->isContaineredType()) {
            $parentPriceType = $facade->getParentContainerPriceType();

            if ($parentPriceType === null) {
                return null;
            }

            if ($parentPriceType->equals(ContainerPriceTypeEnum::FIXED())) {
                return null; // do nothing
            } elseif ($parentPriceType->equals(ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS())) {
                if ($inclTax) {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax() + $facade->getRegularPriceTax();
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax();
                    }
                } else {
                    if ($useRegularPriceForOriginalPrice) {
                        $originalPrice = $facade->getRegularPriceWithoutTax();
                    } else {
                        $originalPrice = $facade->getOriginalPriceWithoutTax();
                    }
                }
            } else {
                return null;
            }
        } else {
            if ($inclTax) {
                if ($useRegularPriceForOriginalPrice) {
                    $originalPrice = $facade->getRegularPriceWithoutTax() + $facade->getRegularPriceTax();
                } else {
                    $originalPrice = $facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax();
                }
            } else {
                if ($useRegularPriceForOriginalPrice) {
                    $originalPrice = $facade->getRegularPriceWithoutTax();
                } else {
                    $originalPrice = $facade->getOriginalPriceWithoutTax();
                }
            }
        }

        return $originalPrice;
    }

    protected function getCalculatedPriceToDisplayForCartItem(WcCartItemFacade $facade, bool $inclTax): ?float
    {
        $context = $this->context;

        if ($facade->isContainerType()) {
            $parentPriceType = $facade->getContainerPriceType();

            if ($parentPriceType === null) {
                return null;
            }

            if ($parentPriceType->equals(ContainerPriceTypeEnum::FIXED())) {
                if ($inclTax) {
                    $calculatedPrice = ($facade->getSubtotal() + $facade->getExactSubtotalTax()) / $facade->getQty();
                } else {
                    $calculatedPrice = $facade->getSubtotal() / $facade->getQty();
                }
            } elseif ($parentPriceType->equals(ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS())) {
                $children = array_map(function ($key) {
                    return new WcCartItemFacade(WC()->cart->cart_contents[$key], $key);
                }, $facade->getContainerChildrenHashes());

                if ($inclTax) {
                    $calculatedPrice = ($facade->getSubtotal() + $facade->getExactSubtotalTax()) / $facade->getQty();

                    $calculatedPrice += array_sum(
                        array_map(function ($child) use ($facade) {
                            if ($child->isContaineredPricedIndividually()) {
                                return ($child->getSubtotal() + $child->getExactSubtotalTax()) / $facade->getQty();
                            } else {
                                return 0.0;
                            }
                        }, $children)
                    );
                } else {
                    $calculatedPrice = $facade->getSubtotal() / $facade->getQty();

                    $calculatedPrice += array_sum(
                        array_map(function ($child) use ($facade) {
                            if ($child->isContaineredPricedIndividually()) {
                                return $child->getSubtotal() / $facade->getQty();
                            } else {
                                return 0.0;
                            }
                        }, $children)
                    );
                }
            } else {
                return null;
            }
        } elseif ($facade->isContaineredType()) {
            $parentPriceType = $facade->getParentContainerPriceType();

            if ($parentPriceType === null) {
                return null;
            }

            if ($parentPriceType->equals(ContainerPriceTypeEnum::FIXED())) {
                return null; // do nothing
            } elseif ($parentPriceType->equals(ContainerPriceTypeEnum::BASE_PLUS_SUM_OF_SUB_ITEMS())) {
                if ($inclTax) {
                    $calculatedPrice = ($facade->getSubtotal() + $facade->getExactSubtotalTax()) / $facade->getQty();
                } else {
                    $calculatedPrice = $facade->getSubtotal() / $facade->getQty();
                }
            } else {
                return null;
            }
        } else {
            if ($inclTax) {
                $calculatedPrice = ($facade->getSubtotal() + $facade->getExactSubtotalTax()) / $facade->getQty();
            } else {
                $calculatedPrice = $facade->getSubtotal() / $facade->getQty();
            }
        }

        return $calculatedPrice;
    }


}
