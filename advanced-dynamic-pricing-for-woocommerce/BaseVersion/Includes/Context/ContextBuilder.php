<?php

namespace ADP\BaseVersion\Includes\Context;

use ADP\BaseVersion\Includes\Compatibility\Addons\TmExtraOptionsCmp;
use ADP\BaseVersion\Includes\Compatibility\AeliaSwitcherCmp;
use ADP\BaseVersion\Includes\Compatibility\AlgWcCurrencySwitcherCmp;
use ADP\BaseVersion\Includes\Compatibility\Container\MixAndMatchCmp;
use ADP\BaseVersion\Includes\Compatibility\Container\SomewhereWarmBundlesCmp;
use ADP\BaseVersion\Includes\Compatibility\Container\WpcBundleCmp;
use ADP\BaseVersion\Includes\Compatibility\Container\YithBundlesCmp;
use ADP\BaseVersion\Includes\Compatibility\PriceBasedOnCountryCmp;
use ADP\BaseVersion\Includes\Compatibility\VillaThemeMultiCurrencyCmp;
use ADP\BaseVersion\Includes\Compatibility\WooCommerceMultiCurrencyCmp;
use ADP\BaseVersion\Includes\Compatibility\WoocsCmp;
use ADP\BaseVersion\Includes\Compatibility\YayCurrencyCmp;
use ADP\BaseVersion\Includes\Context;
use ADP\Factory;

class ContextBuilder
{
    public function buildDefault(): Context
    {
        $context = new Context();

        $this->registerCurrencyCompatibility($context);
        $this->registerContainerCompatibility($context);
        $this->registerAddonsCompatibility($context);

        return $context;
    }

    protected function registerContainerCompatibility(Context $context)
    {
        $context->getContainerCompatibilityManager()->register(new SomewhereWarmBundlesCmp($context));
        $context->getContainerCompatibilityManager()->register(new WpcBundleCmp($context));
        $context->getContainerCompatibilityManager()->register(new MixAndMatchCmp($context));
        $context->getContainerCompatibilityManager()->register(new YithBundlesCmp($context));
    }

    protected function registerCurrencyCompatibility(Context $context)
    {
        $woocsCmp = new WoocsCmp();
        if ($woocsCmp->isActive()) {
            $woocsCmp->modifyContext($context);
            $woocsCmp->prepareHooks();
        }

        $villaCmp = new VillaThemeMultiCurrencyCmp();
        if ($villaCmp->isActive()) {
            $villaCmp->modifyContext($context);
            $villaCmp->prepareHooks();
        }

        $aeliaCmp = new AeliaSwitcherCmp();
        if ($aeliaCmp->isActive()) {
            $aeliaCmp->modifyContext($context);
            $aeliaCmp->prepareHooks();
        }

        $algCmp = new AlgWcCurrencySwitcherCmp();
        if ($algCmp->isActive()) {
            $algCmp->modifyContext($context);
        }

        $yayCmp = new YayCurrencyCmp();
        if ($yayCmp->isActive()) {
            $yayCmp->modifyContext($context);
            $yayCmp->prepareHooks();
        }

        $priceBasedOnCountryCmp = new PriceBasedOnCountryCmp();
        if ($priceBasedOnCountryCmp->isActive()) {
            $priceBasedOnCountryCmp->modifyContext($context);
        }

        $wcMultiCurrencyCmp = new WooCommerceMultiCurrencyCmp($context);
    }

    protected function registerAddonsCompatibility(Context $context)
    {
        /** @var TmExtraOptionsCmp $tmExtraOptionsCmp */
        $tmExtraOptionsCmp = Factory::get("Compatibility_Addons_TmExtraOptionsCmp", $context);
        $tmExtraOptionsCmp->register();
    }
}
