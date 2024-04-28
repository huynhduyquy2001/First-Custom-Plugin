<?php

namespace ADP\BaseVersion\Includes\Shortcodes;

use ADP\BaseVersion\Includes\Cache\CacheHelper;
use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\CustomizerExtensions\CustomizerExtensions;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\VolumePricingTable\ProductVolumePricingTableProperties;
use ADP\BaseVersion\Includes\VolumePricingTable\RangeDiscountTable;
use ADP\BaseVersion\Includes\VolumePricingTable\RangeDiscountTableDisplay;
use ADP\Factory;

defined('ABSPATH') or exit;

class ProductRangeDiscountTableShortcode
{
    const NAME = 'adp_product_bulk_rules_table';

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
     * @param Context|CustomizerExtensions $contextOrCustomizer
     * @param null $deprecated
     */
    public function __construct($contextOrCustomizer, $customizerOrEngine, $deprecated = null)
    {
        $this->context    = adp_context();
        $this->customizer = $contextOrCustomizer instanceof CustomizerExtensions ? $contextOrCustomizer : $customizerOrEngine;
        $this->engine     = $customizerOrEngine instanceof Engine ? $customizerOrEngine : $deprecated;
    }

    public function withContext(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @param CustomizerExtensions $customizer
     */
    public static function register($customizer, $engine)
    {
        $shortcode = new self($customizer, $engine);
        add_shortcode(self::NAME, array($shortcode, 'getContent'));
    }

    public function getContent($args)
    {
        /** @var RangeDiscountTable $table */
        /** @var RangeDiscountTableDisplay $tableDisplay */
        $table = Factory::get("VolumePricingTable_RangeDiscountTable", $this->customizer, $this->engine);
        $tableDisplay = Factory::get("VolumePricingTable_RangeDiscountTableDisplay", $this->customizer, $this->engine);

        $productTableOptions = new ProductVolumePricingTableProperties();

        if ( ! empty($args['layout'])
             && in_array(
                 $args['layout'],
                 array($productTableOptions::LAYOUT_VERBOSE, $productTableOptions::LAYOUT_SIMPLE)
             )
        ) {
            $productTableOptions->tableLayout = $args['layout'];
        }

        $productTableOptions->isSimpleLayoutForcePercentage = isset($args['force_percentage'])
                                                              && wc_string_to_bool($args['force_percentage']);

        $table->setProductContextOptions($productTableOptions);

        global $product;
        if ( $product ) {
            $productForTable = $product;
        } else {
            $productForTable = ! empty($args['id']) ? CacheHelper::getWcProduct(intval($args['id'])) : null;
        }

        $tableDisplay->hookLoadAssets();

        return $tableDisplay->getProductTableContent($productForTable);
    }
}
