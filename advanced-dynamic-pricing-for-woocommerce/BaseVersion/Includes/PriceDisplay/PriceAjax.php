<?php

namespace ADP\BaseVersion\Includes\PriceDisplay;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Core\Cart\CartItem\Type\Base\CartItemAddon;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml\GroupedProductPriceHtml;
use ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml\SimpleProductPriceHtml;
use ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml\VariableProductPriceHtml;
use ADP\BaseVersion\Includes\PriceDisplay\ConcreteProductPriceHtml\VariationProductPriceHtml;
use ADP\BaseVersion\Includes\PriceDisplay\DTO\CalculatePriceProductDTO;
use ADP\BaseVersion\Includes\PriceDisplay\DTO\CalculateProductPriceRequest;
use ADP\BaseVersion\Includes\PriceDisplay\DTO\CalculateSeveralProductPriceRequest;
use ADP\BaseVersion\Includes\PriceDisplay\PriceFormatters\TotalProductPriceFormatter;
use ADP\BaseVersion\Includes\ProductExtensions\ProductExtension;
use ADP\BaseVersion\Includes\WC\PriceFunctions;

defined('ABSPATH') or exit;

class PriceAjax
{
    const ACTION_GET_SUBTOTAL_HTML = 'get_price_product_with_bulk_table';
    const ACTION_CALCULATE_SEVERAL_PRODUCTS = 'adp_calculate_several_products';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Engine
     */
    protected $engine;

    /**
     * @var PriceFunctions
     */
    protected $priceFunctions;

    /**
     * @var string
     */
    protected $nonceParam;

    /**
     * @var string
     */
    protected $nonceName;

    /**
     * @param Context|Engine $contextOrEngine
     * @param Engine|null $deprecated
     */
    public function __construct($contextOrEngine, $deprecated = null)
    {
        $this->context = adp_context();
        $this->engine = $contextOrEngine instanceof Engine ? $contextOrEngine : $deprecated;
        $this->priceFunctions = new PriceFunctions();

        $this->nonceParam = 'wdp-request-price-ajax-nonce';
        $this->nonceName = 'wdp-request-price-ajax';
    }

    /**
     * @return string
     */
    public function getNonceParam(): string
    {
        return $this->nonceParam;
    }

    /**
     * @return string
     */
    public function getNonceName(): string
    {
        return $this->nonceName;
    }

    protected function checkNonceOrDie()
    {
        if (wp_verify_nonce($_REQUEST[$this->nonceParam] ?? null, $this->nonceName) === false) {
            wp_die(__('Invalid nonce specified', 'advanced-dynamic-pricing-for-woocommerce'),
                __('Error', 'advanced-dynamic-pricing-for-woocommerce'), ['response' => 403]);
        }
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function register()
    {
        add_action(
            "wp_ajax_nopriv_" . self::ACTION_GET_SUBTOTAL_HTML,
            [$this, "ajaxCalculatePrice"]
        );
        add_action(
            "wp_ajax_" . self::ACTION_GET_SUBTOTAL_HTML,
            [$this, "ajaxCalculatePrice"]
        );

        add_action(
            "wp_ajax_nopriv_" . self::ACTION_CALCULATE_SEVERAL_PRODUCTS,
            [$this, "ajaxCalculateSeveralProducts"]
        );
        add_action(
            "wp_ajax_" . self::ACTION_CALCULATE_SEVERAL_PRODUCTS,
            [$this, "ajaxCalculateSeveralProducts"]
        );
    }

    public function ajaxCalculatePrice()
    {
        $this->checkNonceOrDie();

        try {
            $request = CalculateProductPriceRequest::fromArray($_REQUEST);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
            return;
        }

        $context = $this->context;

        $context->setProps(
            [
                $context::ADMIN => false,
                $context::AJAX => false,
                $context::WC_PRODUCT_PAGE => $request->getPageData()->isProduct(),
            ]
        );

        try {
            $result = $this->calculatePrice($request->getProduct());
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
            return;
        }

        if ($result === null) {
            wp_send_json_error();
        } else {
            wp_send_json_success($result);
        }
    }

    public function ajaxCalculateSeveralProducts()
    {
        $this->checkNonceOrDie();

        try {
            $request = CalculateSeveralProductPriceRequest::fromArray($_REQUEST);
        } catch (\Exception $e) {
            wp_send_json_error($e->getMessage());
            return;
        }

        if (count($request->getProducts()) === 0) {
            wp_send_json_success([]);
            return;
        }

        $context = $this->context;
        $context->setProps(
            [
                $context::ADMIN => false,
                $context::AJAX => false,
                $context::WC_PRODUCT_PAGE => $request->getPageData()->isProduct(),
            ]
        );

        $result = [];
        foreach ($request->getProducts() as $productDTO) {
            try {
                $result[$productDTO->getProductId()] = $this->calculatePrice($productDTO);
            } catch (\Exception $e) {

            }
        }

        wp_send_json_success($result);
    }

    /**
     * @throws \Exception
     */
    protected function calculatePrice(CalculatePriceProductDTO $productDTO): ?array
    {
        $productId = $productDTO->getProductId();
        $qty = $productDTO->getQty();
        $attributes = $productDTO->getAttributes();
        $customPrice = $productDTO->getCustomPrice();

        $product = CacheHelper::getWcProduct($productId);
        if ($customPrice !== null) {
            $productExt = new ProductExtension($product);
            $productExt->setCustomPrice($customPrice);
        }

        if ($product instanceof \WC_Product_Variation && array_filter($attributes)) {
            $product->set_attributes(array_filter($attributes));
        }

        /** @var array<int, CartItemAddon> $cartItemAddons */
        $cartItemAddons = [];
        foreach ( $productDTO->getAddons() as $addonDTO ) {
            $cartItemAddons[] = new CartItemAddon(
                "",
                $addonDTO->getValue(),
                $addonDTO->getPrice()
            );
        }

        $processedProduct = $this->engine->getProductProcessor()->calculateWithProductWrapper(
            new WcProductCalculationWrapper($product, [], $cartItemAddons),
            $qty
        );

        if (is_null($processedProduct)) {
            throw new \Exception("Processed product is null!");
        }

        $priceDisplay = $this->engine->getPriceDisplay();
        $strikethrough = $priceDisplay::priceHtmlIsAllowToStrikethroughPrice($this->context);

        $totalProductPriceFormatter = new TotalProductPriceFormatter($this->context);
        /** @var ConcreteProductPriceHtml $prodPriceDisplay */
        $prodPriceDisplay = ProductPriceDisplay::create($this->context, $processedProduct);
        if (!$prodPriceDisplay) {
            throw new \Exception("ProductPriceDisplay is missing!");
        }
        $prodPriceDisplay->withStriked($strikethrough);

        if ($prodPriceDisplay instanceof SimpleProductPriceHtml || $prodPriceDisplay instanceof VariationProductPriceHtml) {
            if (!$priceDisplay->priceHtmlIsModifyNeeded()) {
                return array(
                    'price_html' => $prodPriceDisplay->getPriceHtml(),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => $totalProductPriceFormatter->getHtmlNotIsModifyNeeded($product, $qty),
                    'original_price' => $this->priceFunctions->getPriceToDisplay($product),
                    'discounted_price' => $this->priceFunctions->getPriceToDisplay($product),
                    'original_subtotal' => $this->priceFunctions->getPriceToDisplay($product, array('qty' => $qty)),
                    'discounted_subtotal' => $this->priceFunctions->getPriceToDisplay($product, array('qty' => $qty)),
                );
            } elseif (!$processedProduct->areRulesAppliedAtAll()) {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => $totalProductPriceFormatter->getHtmlAreRulesNotApplied($product, $qty),
                    'original_price' => $prodPriceDisplay->getOriginalPrice(),
                    'discounted_price' => $prodPriceDisplay->getDiscountedPrice(),
                    'original_subtotal' => $prodPriceDisplay->getOriginalSubtotal($qty),
                    'discounted_subtotal' => $prodPriceDisplay->getDiscountedSubtotal($qty),
                );
            } else {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => $totalProductPriceFormatter->getHtmlProcessedProductSimple($processedProduct),
                    'original_price' => $prodPriceDisplay->getOriginalPrice(),
                    'discounted_price' => $prodPriceDisplay->getDiscountedPrice(),
                    'original_subtotal' => $prodPriceDisplay->getOriginalSubtotal($qty),
                    'discounted_subtotal' => $prodPriceDisplay->getDiscountedSubtotal($qty),
                );
            }
        } elseif ($prodPriceDisplay instanceof VariableProductPriceHtml) {
            if (!$priceDisplay->priceHtmlIsModifyNeeded()) {
                return array(
                    'price_html' => $prodPriceDisplay->getPriceHtml(),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => "",

                    'lowest_original_price' => $prodPriceDisplay->getLowestOriginalPrice(),
                    'highest_original_price' => $prodPriceDisplay->getHighestOriginalPrice(),
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => $prodPriceDisplay->getLowestOriginalSubtotal($qty),
                    'highest_original_subtotal' => $prodPriceDisplay->getHighestOriginalSubtotal($qty),
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            } elseif (!$processedProduct->areRulesApplied()) {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => $totalProductPriceFormatter->getHtmlAreRulesNotApplied($product, $qty),

                    'lowest_original_price' => $prodPriceDisplay->getLowestOriginalPrice(),
                    'highest_original_price' => $prodPriceDisplay->getHighestOriginalPrice(),
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => $prodPriceDisplay->getLowestOriginalSubtotal($qty),
                    'highest_original_subtotal' => $prodPriceDisplay->getHighestOriginalSubtotal($qty),
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            } else {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => "",

                    'lowest_original_price' => $prodPriceDisplay->getLowestOriginalPrice(),
                    'highest_original_price' => $prodPriceDisplay->getHighestOriginalPrice(),
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => $prodPriceDisplay->getLowestOriginalSubtotal($qty),
                    'highest_original_subtotal' => $prodPriceDisplay->getHighestOriginalSubtotal($qty),
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            }
        } elseif ($prodPriceDisplay instanceof GroupedProductPriceHtml) {
            if (!$priceDisplay->priceHtmlIsModifyNeeded()) {
                return array(
                    'price_html' => $prodPriceDisplay->getPriceHtml(),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => "",

                    'lowest_original_price' => "",
                    'highest_original_price' => "",
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => "",
                    'highest_original_subtotal' => "",
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            } elseif (!$processedProduct->areRulesApplied()) {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => $totalProductPriceFormatter->getHtmlAreRulesNotApplied($product, $qty),

                    'lowest_original_price' => "",
                    'highest_original_price' => "",
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => "",
                    'highest_original_subtotal' => "",
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            } else {
                return array(
                    'price_html' => $prodPriceDisplay->getFormattedPriceHtml($prodPriceDisplay->getPriceHtml()),
                    'subtotal_html' => $prodPriceDisplay->getFormattedSubtotalHtml($qty),
                    'total_price_html' => "",

                    'lowest_original_price' => "",
                    'highest_original_price' => "",
                    'lowest_discounted_price' => $prodPriceDisplay->getLowestDiscountedPrice(),
                    'highest_discounted_price' => $prodPriceDisplay->getHighestDiscountedPrice(),

                    'lowest_original_subtotal' => "",
                    'highest_original_subtotal' => "",
                    'lowest_discounted_subtotal' => $prodPriceDisplay->getLowestDiscountedSubtotal($qty),
                    'highest_discounted_subtotal' => $prodPriceDisplay->getHighestDiscountedSubtotal($qty),
                );
            }
        }

        throw new \Exception("Unsupported type of processed product: " . get_class($prodPriceDisplay));
    }
}
