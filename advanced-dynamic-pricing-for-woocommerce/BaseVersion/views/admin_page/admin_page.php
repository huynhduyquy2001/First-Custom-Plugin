<?php

use ADP\BaseVersion\Includes\AdminExtensions\AdminPage;
use ADP\BaseVersion\Includes\AdminExtensions\AdminPage\AdminTabInterface;

defined('ABSPATH') or exit;

/**
 * @var $this AdminPage
 * @var $tabs AdminTabInterface[]
 * @var $current_tab AdminTabInterface
 * @var \ADP\Settings\OptionsManager $options
 */
?>

<div class="wrap">
    <h2></h2>
</div>

<div class="wrap woocommerce">

    <div class="wdp-rules-actions">
        <div class="wdp-rules-add-rule-action">
            <div class="wdp-title">
                <?php _e('Pricing Rules', 'advanced-dynamic-pricing-for-woocommerce'); ?>
            </div>
            <?php if (isset($_GET['tab']) && $_GET['tab'] === 'rules' || empty($_GET['tab'])): ?>
                <button class="button add-rule wdp-addlist-item loading">
                    <?php _e('Add rule', 'advanced-dynamic-pricing-for-woocommerce'); ?>
                </button>
            <?php endif ?>
            <?php if (isset($_GET['tab']) && $_GET['tab'] === 'product_collections'): ?>
                <button class="button add-product-collection wdp-add-list-item">
                    <?php _e('Add collection', 'advanced-dynamic-pricing-for-woocommerce') ?>
                </button>
            <?php endif ?>
        </div>
        <div class="wdp-rules-recalculate-cache-action">
            <div style="display: inline-block; width: 100%;">
                <div id="progressBarBlock" style="padding: 0; width: 100%;">
                    <div id="progressBar"></div>
                </div>
            </div>
            <?php if($options->getOption('support_shortcode_products_on_sale') || $options->getOption('support_shortcode_products_bogo') || $options->getOption('support_persistence_rules')): ?>
            <div class="wdp-row">
                <div class="wdp-column wdp-column-select">
                    <!-- <span class="wdp-select-icon"></span> -->
                    <select name="recalculace_selector">
                        <option value=""><?php _e('Cache recalculation', 'advanced-dynamic-pricing-for-woocommerce') ?></option>
                        <?php if($options->getOption('support_persistence_rules')): ?>
                            <option value="recalculate_persistence_cache"><?php _e('Recalculate Product only rules cache', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                        <?php endif;
                        if($options->getOption('support_shortcode_products_on_sale')): ?>
                            <option value="rebuild_onsale_list"><?php _e('Update Onsale List', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                        <?php endif;
                        if($options->getOption('support_shortcode_products_bogo')): ?>
                            <option value="rebuild_bogo_list"><?php _e('Update Bogo List', 'advanced-dynamic-pricing-for-woocommerce'); ?></option>
                        <?php endif;?>
                    </select>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <h2 class="wcp_tabs_container nav-tab-wrapper">
        <span class="wcp_tabs_container_list">
            <?php foreach ($tabs as $tab_key => $tab_handler): ?>
                <a class="nav-tab <?php echo($tab_key === $current_tab::getKey() ? 'nav-tab-active' : ''); ?>"
                   href="admin.php?page=wdp_settings&tab=<?php echo $tab_key; ?>"><?php echo $tab_handler::getTitle(); ?></a>
            <?php endforeach; ?>
        </span>
    </h2>

    <div class="wdp_settings ui-page-theme-a">
        <div class="wdp_settings_container">
            <?php
            $this->renderCurrentTab();
            ?>
        </div>
    </div>

</div>
