<?php

namespace ADP\BaseVersion\Includes\PriceDisplay;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\CartProcessor\ToPricingCartItemAdapter\ToPricingCartItemAdapter;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\Cart;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\CartItemConverter;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAttributeEnum;
use ADP\BaseVersion\Includes\Core\CartCalculator;
use ADP\BaseVersion\Includes\Core\ICartCalculator;
use ADP\BaseVersion\Includes\Debug\ProductCalculatorListener;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductProcessor\IWcProductProcessor;
use ADP\BaseVersion\Includes\PriceDisplay\WcProductProcessor\WcProductProcessorHelper;
use ADP\BaseVersion\Includes\ProductExtensions\ProductExtension;
use ADP\Factory;
use Exception;
use WC_Product;
use WC_Product_Grouped;
use WC_Product_Variable;

defined('ABSPATH') or exit;

class Processor implements IWcProductProcessor
{
    const ERR_PRODUCT_WITH_NO_PRICE = 101;
    const ERR_TMP_ITEM_MISSING = 102;
    const ERR_PRODUCT_DOES_NOT_EXISTS = 103;
    const ERR_CART_DOES_NOT_EXISTS = 104;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var ICartCalculator
     */
    protected $calc;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var ProductCalculatorListener
     */
    protected $listener;

    /**
     * @var CartItemConverter
     */
    protected $cartItemConverter;

    /**
     * @param Context|ICartCalculator|null $contextOrCalc
     * @param ICartCalculator|null $deprecated
     */
    public function __construct($contextOrCalc = null, $deprecated = null)
    {
        $this->context = adp_context();
        $this->listener = new ProductCalculatorListener();
        $calc = $contextOrCalc instanceof ICartCalculator ? $contextOrCalc : $deprecated;

        if ($calc instanceof ICartCalculator) {
            $this->calc = $calc;
        } else {
            $this->calc = Factory::callStaticMethod("Core_CartCalculator", 'make', $this->listener);
            /** @see CartCalculator::make() */
        }

        $this->cartItemConverter = new CartItemConverter();
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param Cart $cart
     */
    public function withCart(Cart $cart)
    {
        $items = $cart->getItems();
        foreach ($items as $index => $cartItem) {
            $items[$index] = clone $cartItem;
        }
        $cart->setItems($items);

        $this->cart = $cart;
    }

    protected function isCartExists(): bool
    {
        return isset($this->cart);
    }

    /**
     * @param WC_Product|int $theProduct
     * @param float $qty
     * @param array $cartItemData
     *
     * @return ProcessedProductSimple|ProcessedVariableProduct|ProcessedGroupedProduct|ProcessedProductContainer|null
     */
    public function calculateProduct($theProduct, $qty = 1.0, $cartItemData = array())
    {
        if (is_numeric($theProduct)) {
            $product = CacheHelper::getWcProduct($theProduct);
        } elseif ($theProduct instanceof WC_Product) {
            $product = clone $theProduct;
        } else {
            $this->context->handleError(new Exception("Product does not exists",
                self::ERR_PRODUCT_DOES_NOT_EXISTS));

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
    public function calculateWithProductWrapper(WcProductCalculationWrapper $wrapper, float $qty = 1.0)
    {
        $product = $wrapper->getWcProduct();
        $cartItemData = $wrapper->getCartItemData();

        if ($product instanceof WC_Product_Grouped) {
            /** @var $processed ProcessedGroupedProduct */
            $processed = Factory::get("PriceDisplay_ProcessedGroupedProduct", $product, $qty);
            $children = array_filter(
                array_map('wc_get_product', $product->get_children()),
                'wc_products_array_filter_visible_grouped'
            );

            foreach ($children as $childId) {
                $processedChild = $this->checkCacheFroProcessedProduct($childId, $qty, [], $cartItemData);

                if (is_null($processedChild)) {
                    $processedChild = $this->calculateSimpleProductWrapper(
                        new WcProductCalculationWrapper(
                            WcProductProcessorHelper::buildWcProductFromChildId($childId, $product),
                            $cartItemData,
                            $wrapper->getAddons()
                        ),
                        $qty
                    );
                }

                if (is_null($processedChild)) {
                    continue;
                }

                $processed->useChild($processedChild);
            }
        } elseif ($product instanceof WC_Product_Variable) {
            /** @var $processed ProcessedVariableProduct */
            $processed = Factory::get("PriceDisplay_ProcessedVariableProduct", $this->context, $product, $qty);
            $children = $product->get_visible_children();

            foreach ($children as $childId) {
                $processedChild = $this->checkCacheFroProcessedProduct($childId, $qty, [], $cartItemData);

                if (is_null($processedChild)) {
                    $processedChild = $this->calculateSimpleProductWrapper(
                        new WcProductCalculationWrapper(
                            WcProductProcessorHelper::buildWcProductFromChildId($childId, $product),
                            $cartItemData,
                            $wrapper->getAddons()
                        ),
                        $qty
                    );
                }

                if (is_null($processedChild)) {
                    continue;
                }

                $processed->useChild($processedChild);
            }
        } elseif ( WcProductProcessorHelper::isCalculatingPartOfContainerProduct($product) ) {
            $containerProduct = WcProductProcessorHelper::getBundleProductFromBundled($product);
            $processedParent = $this->checkCacheFroProcessedProduct($containerProduct, $qty, [], $cartItemData);

            if ( is_null($processedParent) ) {
                $processedParent = $this->calculateSimpleProductWrapper(
                    new WcProductCalculationWrapper($containerProduct, $cartItemData, $wrapper->getAddons()),
                    $qty
                );
            }

            $processed = null;
            if ($processedParent instanceof ProcessedProductContainer) {
                foreach ($processedParent->getContainerItemsByPos() as $containerItem) {
                    if ($containerItem->getProduct()->get_id() === $product->get_id()) {
                        $processed = $containerItem;
                    }
                }
            }
        } else {
            $processed = $this->checkCacheFroProcessedProduct($product, $qty, [], $cartItemData);

            if ( is_null($processed) ) {
                $processed = $this->calculateSimpleProductWrapper(
                    new WcProductCalculationWrapper($product, $cartItemData, $wrapper->getAddons()),
                    $qty
                );
            }
        }

        return $processed;
    }

    protected function checkCacheFroProcessedProduct($prodID, $qty, $variationAttributes, $cartItemData)
    {
        if ($prodID && $processedProduct = CacheHelper::maybeGetProcessedProductToDisplay(
                $prodID,
                $variationAttributes,
                $qty,
                $cartItemData,
                $this->cart,
                $this->calc
            )) {
            return $processedProduct;
        } else {
            return null;
        }
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
            $this->context->handleError(new Exception("Cart does not exists", self::ERR_CART_DOES_NOT_EXISTS));

            return null;
        }

        $product = $wrapper->getWcProduct();
        $cartItemData = $wrapper->getCartItemData();
        $prodID = $product->get_id();

        $variationAttributes = $product instanceof \WC_Product_Variation ? $product->get_variation_attributes() : [];

        if ($prodID && $processedProduct = CacheHelper::maybeGetProcessedProductToDisplay(
                $prodID,
                $variationAttributes,
                $qty,
                $cartItemData,
                $this->cart,
                $this->calc
            )) {
            return $processedProduct;
        }

        $productExt = new ProductExtension($this->context, $product);
        $productExt->withContext($this->context);

        if ($product->get_price('edit') === '') {
            $this->context->handleError(new Exception("Empty price", self::ERR_PRODUCT_WITH_NO_PRICE));

            return null;
        }

        $cartItemData = apply_filters('adp_calculate_product_price_data', $cartItemData, $product, $this->context);

        if ($productExt->getCustomPrice() === null) {
            $productExt->setCustomPrice(
                apply_filters("adp_product_get_price", null, $product, $variationAttributes, 1, array(), null)
            );
        }

        $currencySwitcher = $this->context->currencyController;

        /**
         * Why do we use '==' instead of '==='?
         * @see \ADP\BaseVersion\Includes\CurrencyController::isCurrencyChanged()
         */
        if ($this->cart->getCurrency() == $currencySwitcher->getDefaultCurrency()) {

            if ($productExt->getCustomPrice() !== null) {
                $product->set_price($productExt->getCustomPrice());
            } else {
                $product->set_price($currencySwitcher->getDefaultCurrencyProductPrice($product));
            }

            $salePrice = $currencySwitcher->getDefaultCurrencyProductSalePrice($product);
            if ($salePrice !== null) {
                $product->set_sale_price($salePrice);
            }
            $product->set_regular_price($currencySwitcher->getDefaultCurrencyProductRegularPrice($product));
        } elseif ($this->cart->getCurrency() == $currencySwitcher->getCurrentCurrency()) {

            if ($productExt->getCustomPrice() !== null) {
                $product->set_price(
                    $currencySwitcher->getCurrentCurrencyProductPriceWithCustomPrice(
                        $product,
                        $productExt->getCustomPrice()
                    )
                );
            } else {
                $product->set_price($currencySwitcher->getCurrentCurrencyProductPrice($product));
            }

            $salePrice = $currencySwitcher->getCurrentCurrencyProductSalePrice($product);
            if ($salePrice !== null) {
                $product->set_sale_price($salePrice);
            }
            $product->set_regular_price($currencySwitcher->getCurrentCurrencyProductRegularPrice($product));
        }

        $cart = clone $this->cart;

        $item = (new ToPricingCartItemAdapter())->adaptWcProduct($wrapper);
        $item->setQty($qty);

        $item->addAttr(CartItemAttributeEnum::TEMPORARY());
        $item->setAddons($wrapper->getAddons());

        $cart->addToCart($item);
        $this->listener->startCartProcessProduct($product);
        $this->calc->processItem($cart, $item);
        $this->listener->finishCartProcessProduct($product);

        $tmpItems = array();
        $qtyAlreadyInCart = floatval(0);
        foreach ($cart->getItems() as $loopCartItem) {
            if ($loopCartItem->getWcItem()->getKey() === $item->getWcItem()->getKey()) {
                if ($loopCartItem->hasAttr(CartItemAttributeEnum::TEMPORARY())) {
                    $tmpItems[] = $loopCartItem;
                }
            }

            if ($loopCartItem->getWcItem()->getProduct()->get_id() === $item->getWcItem()->getProduct()->get_id()) {
                $qtyAlreadyInCart += $loopCartItem->getQty();
            }
        }
        $tmpFreeItems = array();
        foreach ($cart->getFreeItems() as $loopCartItem) {
            if ($loopCartItem->hasAttr($loopCartItem::ATTR_TEMP)) {
                $tmpFreeItems[] = $loopCartItem;
            }
        }

        $tmpListOfFreeCartItemChoices = array();
        foreach ($cart->getListOfFreeCartItemChoices() as $freeCartItemChoices) {
            if ($freeCartItemChoices->hasAttr($freeCartItemChoices::ATTR_TEMP)) {
                $tmpListOfFreeCartItemChoices[] = $freeCartItemChoices;
            }
        }

        $qtyAlreadyInCart = $qtyAlreadyInCart - array_sum(array_map(function ($item) {
                return $item->getQty();
            }, $tmpItems));

        if (count($tmpItems) === 0) {
            $this->context->handleError(new Exception("Temporary item is missing", self::ERR_TMP_ITEM_MISSING));

            return null;
        }

        $tmpItems = apply_filters("adp_before_processed_product", $tmpItems, $this);
        $processedProduct = WcProductProcessorHelper::tmpItemsToProcessedProduct(
            $this->context,
            $product,
            $tmpItems,
            $tmpFreeItems,
            $tmpListOfFreeCartItemChoices
        );
        $processedProduct->setQtyAlreadyInCart($qtyAlreadyInCart);
        CacheHelper::addProcessedProductToDisplay($item->getWcItem(), $qty, $processedProduct, $this->cart, $this->calc);
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
     * @return Cart
     */
    public function getCart(): Cart
    {
        return $this->cart;
    }
}
