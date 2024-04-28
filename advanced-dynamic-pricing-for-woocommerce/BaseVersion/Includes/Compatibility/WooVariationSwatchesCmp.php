<?php

namespace ADP\BaseVersion\Includes\Compatibility;

use ADP\BaseVersion\Includes\Context;

defined('ABSPATH') or exit;

/**
 * Plugin Name: Variation Swatches for WooCommerce
 * Author: Emran Ahmed
 *
 * @see https://wordpress.org/plugins/woo-variation-swatches/
 */
class WooVariationSwatchesCmp
{

    /**
     * @var Context
     */
    protected $context;

    public function __construct()
    {
        $this->context = adp_context();
    }

    public function isActive(): bool
    {
        return class_exists("Woo_Variation_Swatches");
    }

    public function applyCompatibility()
    {
        add_action('admin_enqueue_scripts', function () {
            if (!defined("WC_ADP_PLUGIN_FILE")) {
                return;
            }

            if (adp_context()->isPluginAdminPage()) {
                wp_deregister_script('wc-enhanced-select');

            }

        }, 100, 0);
    }
}
