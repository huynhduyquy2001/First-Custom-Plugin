<?php

namespace ADP\BaseVersion\Includes\VolumePricingTable;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\CustomizerExtensions\CustomizerExtensions;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\Factory;

defined('ABSPATH') or exit;

class RangeDiscountTableDisplay
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Engine
     */
    protected $engine;

    /**
     * @var CustomizerExtensions
     */
    protected $customizer;

    /**
     * @var RangeDiscountTable
     */
    protected $rangeDiscountTable;

    /**
     * @param Context|CustomizerExtensions $contextOrCustomizer
     * @param null $deprecated
     */
    public function __construct($contextOrCustomizer, $customizerOrEngine, $deprecated = null)
    {
        $this->context            = adp_context();
        $this->customizer         = $contextOrCustomizer instanceof CustomizerExtensions ? $contextOrCustomizer : $customizerOrEngine;
        $this->engine             = $customizerOrEngine instanceof Engine ? $customizerOrEngine : $deprecated;
        $this->rangeDiscountTable = Factory::get("VolumePricingTable_RangeDiscountTable", $this->customizer, $this->engine);        
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    public function installRenderHooks()
    {
        add_action('wp_loaded', function () {
            $themeOptions = $this->customizer->getThemeOptions();
            if ($this->context->getOption('show_matched_bulk_table')) {
                $actions = array($themeOptions->productBulkTable->options->tablePositionAction);

                foreach (apply_filters('wdp_product_bulk_table_action', $actions) as $action) {
                    add_action($action, array($this, 'echoProductTableContent'), 50, 2);
                }
            }

            if ($this->context->getOption('show_category_bulk_table')) {
                $actions = array($themeOptions->categoryBulkTable->options->tablePositionAction);

                foreach (apply_filters('wdp_category_bulk_table_action', $actions) as $action) {
                    add_action($action, array($this, 'echoCategoryTableContent'), 50, 2);
                }
            }

            if (
                $this->context->getOption('show_matched_bulk_table')
                || $this->context->getOption('show_category_bulk_table')
            ) {
                $this->loadAssets();
            }
        });
    }

    public function echoProductTableContent()
    {
        global $product;

        echo $this->getProductTableContent($product);
    }

    public function getProductTableContent($product): string
    {
        if ($product instanceof \WC_Product_Variable) {
            return $this->getTableContentWithDefaultVariation($product);
        } else {
            return $this->rangeDiscountTable->getProductTableContent($product);
        }
    }

    protected function getTableContentWithDefaultVariation(\WC_Product_Variable $product): string
    {
        $attributes = [];
        foreach ($product->get_variation_attributes() as $attrName => $options) {
            $attributes[wc_variation_attribute_name($attrName)] = $product->get_variation_default_attribute($attrName);
        }

        $variationId = \WC_Data_Store::load('product')->find_matching_product_variation($product, $attributes);

        return $this->rangeDiscountTable->getProductTableContent($variationId, $attributes);
    }

    public function echoCategoryTableContent()
    {
        echo $this->rangeDiscountTable->getCategoryTableContent();
    }

    public function loadAssets()
    {
        add_action('wp_print_styles', array($this, 'hookLoadAssets'));
    }

    public function hookLoadAssets()
    {
        $context        = $this->context;
        $baseVersionUrl = WC_ADP_PLUGIN_URL . "/BaseVersion/";

        wp_enqueue_style('wdp_pricing-table', $baseVersionUrl . 'assets/css/pricing-table.css', array(),
            WC_ADP_VERSION);
        wp_enqueue_style('wdp_deals-table', $baseVersionUrl . 'assets/css/deals-table.css', array(), WC_ADP_VERSION);

        if ($context->is($context::WC_PRODUCT_PAGE) || $context->is($context::PRODUCT_LOOP)) {
            wp_enqueue_script(
                'wdp_deals',
                $baseVersionUrl . 'assets/js/frontend.js',
                ['jquery'],
                WC_ADP_VERSION
            );
        }

        $scriptData = array(
            'ajaxurl'         => admin_url('admin-ajax.php'),
            'js_init_trigger' => apply_filters('wdp_bulk_table_js_init_trigger', ""),
        );

        wp_localize_script('wdp_deals', 'script_data', $scriptData);
    }
}
