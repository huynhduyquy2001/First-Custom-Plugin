<?php

namespace ADP\BaseVersion\Includes\Shortcodes;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Engine;
use ADP\BaseVersion\Includes\CustomizerExtensions\CustomizerExtensions;
use ADP\BaseVersion\Includes\Database\Repository\PersistentRuleRepository;
use ADP\BaseVersion\Includes\Database\Repository\RuleRepository;
use ADP\BaseVersion\Includes\VolumePricingTable\RangeDiscountTable;
use ADP\Factory;

defined('ABSPATH') or exit;

class CategoryRangeDiscountTableShortcode
{
    const NAME = 'adp_category_bulk_rules_table';

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
        $rangeDiscountTable = Factory::get(
            "VolumePricingTable_RangeDiscountTable",
            $this->customizer,
            $this->engine
        );

        return $rangeDiscountTable->getCategoryTableContent();
    }
}
