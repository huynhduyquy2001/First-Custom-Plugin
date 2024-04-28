<?php

namespace ADP\BaseVersion\Includes\Compatibility\Wpml;

use ADP\BaseVersion\Includes\Context;
use ADP\BaseVersion\Includes\Context\Currency;
use ADP\BaseVersion\Includes\Context\CurrencyController;
use ADP\BaseVersion\Includes\Core\Rule\Internationalization\RuleTranslator;
use ADP\BaseVersion\Includes\Core\Rule\Rule;

defined('ABSPATH') or exit;

/**
 * Plugin Name: WPML - WooCommerce Multilingual
 * Author: OnTheGoSystems
 *
 * @see http://wpml.org/documentation/related-projects/woocommerce-multilingual/
 */
class WpmlCmp
{
    /**
     * @var \woocommerce_wpml|null
     */
    protected $wcWpml;

    /**
     * @var \SitePress|null
     */
    protected $sitepress;

    /**
     * @var WpmlObjectInternationalization
     */
    private $oi;

    public function __construct()
    {
        $this->loadRequirements();
    }

    public function replaceVariationDataStore() {
        if ( ! has_action('adp_replace_variation_data_store', '__return_false')) {
            add_filter('adp_replace_variation_data_store', '__return_false');
        }
    }

    public function modifyContext(Context $context)
    {
        if($this->sitepress) {
            $locale = $this->sitepress->get_locale($this->sitepress->get_this_lang());
            $context->setLanguage(new Context\Language($locale));

            if (isset($this->wcWpml->multi_currency)) {
                $context->currencyController = new CurrencyController(
                    $context,
                    $this->getDefaultCurrency()
                );
                $context->currencyController->setCurrentCurrency($this->getCurrentCurrency());
            }
        }

    }

    public function addFilterPreloadedListLanguages()
    {
        if ( ! has_action('wdp_preloaded_list_languages', [$this, 'preloadedListLanguages'])) {
            add_filter('wdp_preloaded_list_languages', [$this, 'preloadedListLanguages'], 10, 1);
        }
    }

    public function preloadedListLanguages($list)
    {
        if($this->sitepress) {
            $list = [];
            $languages = $this->sitepress->get_languages();
            foreach ($languages as $lang) {
                $list[] = [
                    'id'   => $lang['default_locale'],
                    'text' => $lang['english_name'],
                ];
            }
        }
        return $list;
    }

    public function isActiveWcWpml()
    {
        return ! is_null($this->wcWpml) && ($this->wcWpml instanceof \woocommerce_wpml);
    }

    public function isActiveSitepress()
    {
        return ! is_null($this->sitepress) && ($this->sitepress instanceof \SitePress);
    }

    public function loadRequirements()
    {
        if (!did_action('plugins_loaded')) {
            _doing_it_wrong(__FUNCTION__, sprintf(__('%1$s should not be called earlier the %2$s action.',
                'advanced-dynamic-pricing-for-woocommerce'), 'load_requirements', 'plugins_loaded'), WC_ADP_VERSION);
        }

        $this->sitepress = isset($GLOBALS['sitepress']) ? $GLOBALS['sitepress'] : null;
        $this->wcWpml = isset($GLOBALS['woocommerce_wpml']) ? $GLOBALS['woocommerce_wpml'] : null;

        if ($this->sitepress) {
            $this->oi = new WpmlObjectInternationalization($this->sitepress->get_current_language());
        } else {
            $this->oi = null;
        }

        add_filter( 'wcml_load_multi_currency_in_ajax', "__return_true");
    }

    public function shouldTranslate()
    {
        return boolval(apply_filters('adp_should_translate_wpml', true));
    }

    /**
     * @return WpmlObjectInternationalization|null
     */
    public function getObjectInternationalization()
    {
        return $this->oi;
    }

    /**
     * @param Rule $rule
     *
     * @return Rule
     */
    public function changeRuleCurrency($rule): Rule
    {
        if (isset($this->wcWpml->multi_currency)) {
            $currency = $this->wcWpml->multi_currency->get_client_currency();
//            $rate     = $this->wcWpml->multi_currency->exchange_rate_services->get_currency_rate($currency);

            $rates = $this->wcWpml->multi_currency->get_exchange_rates();
            if (!isset($rates[$currency])) {
                return $rule;
            }
            $rate = floatval($rates[$currency]);

            if ($rate) {
                $rule = RuleTranslator::setCurrency($rule, $rate);
            }
        }

        return $rule;
    }

    /**
     * @return Currency|null
     * @throws \Exception
     */
    protected function getDefaultCurrency()
    {
        $symbols      = CurrencyController::getDefaultCurrencySymbols();

        return new Currency(
            $this->wcWpml->multi_currency->get_default_currency(),
            $symbols[$this->wcWpml->multi_currency->get_default_currency()] ?? '',
        1.0
        );
    }

    /**
     * @return Currency|null
     * @throws \Exception
     */
    protected function getCurrentCurrency()
    {
        $symbols      = CurrencyController::getDefaultCurrencySymbols();

        if ( $this->wcWpml->multi_currency->get_client_currency() === $this->wcWpml->multi_currency->get_default_currency() ) {
            return $this->getDefaultCurrency();
        }

        // sometimes the "rate" property of default currency contains non "1" value

        return new Currency(
            $this->wcWpml->multi_currency->get_client_currency(),
            $symbols[$this->wcWpml->multi_currency->get_client_currency()] ?? '',
            1.0
        );
    }
}
