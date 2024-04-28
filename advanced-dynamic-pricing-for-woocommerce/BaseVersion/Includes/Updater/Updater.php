<?php

namespace ADP\BaseVersion\Includes\Updater;

use ADP\Factory;

defined('ABSPATH') or exit;

class Updater
{
    const DB_VERSION_KEY = "wdp_db_version";

    const DB_LEVEL_KEY = "wdp_db_level";

    private static $db_updates = array(
        '2.2.3' => array(
            'migrateTo_2_2_3',
        ),
//		'3.0.0' => array(
//			'migrate_to_3_0_0',
//		),
        '3.1.0' => array(
            'migrateOptionsTo_3_1_0',
            'migrateFreeProductsTo_3_1_0',
        ),
        '3.2.1' => array(
            'migrate_options_to_3_2_1'
        ),
        '3.2.6' => array(
            'migrateFreeProductsTo_3_2_6'
        ),
        '3.3.1' => array(
            'migrateOptionsTo_3_3_1'
        ),
        '4.0.0' => array(
            'migrateConditionsTo_4_0_0',
            'migrateOptionsTo_4_0_0',
            'migrateSplitDiscountByTo_4_0_0',
            'migrateRuleTypeTo_4_0_0'
        ),
        '4.1.0' => array(
            'migrateSummaryTo_4_1_0'
        ),
        '4.1.3' => array(
            'migrateConditionsTo_4_1_3'
        ),
        '4.1.6' => array(
            'migrateSummaryTo_4_1_6',
            'migrateCompatibilityOptionsTo_4_1_6'
        ),
        '4.4.3' => array(
            'migratethemeOptionsTo_4_4_3',
        ),
        '4.5.3' => array(
            'migratePostalCodesConditionsTo_4_5_3',
            'migrateSpentConditionsTo_4_5_3'
        )
    );

    public static function update()
    {
        self::updateLevel();
        self::applyMigrations();
    }

    public static function cleanUp()
    {
        delete_option(self::DB_LEVEL_KEY);
        delete_option(self::DB_VERSION_KEY);
    }

    public static function applyMigrations()
    {
        $current_version = get_option(self::DB_VERSION_KEY, "");

        if (version_compare($current_version, WC_ADP_VERSION, '<')) {
            Factory::get("PluginActions", WC_ADP_PLUGIN_PATH . WC_ADP_PLUGIN_FILE)->singleInstall();

            foreach (self::$db_updates as $version => $update_callbacks) {
                if (version_compare($current_version, $version, '<')) {
                    foreach ($update_callbacks as $update_callback) {
                        UpdateFunctions::call_update_function($update_callback);
                    }
                }
            }

            update_option(self::DB_VERSION_KEY, WC_ADP_VERSION, false);
        }
    }

    public static function updateLevel()
    {
        $currentDBLevel = get_option(self::DB_LEVEL_KEY, "");
        $currentLevel = defined("WC_ADP_PRO_VERSION_PATH") ? "pro" : "base";

        if ( $currentDBLevel !== $currentLevel ) {
            if ($currentLevel === "base" ) {
                \ADP\BaseVersion\Includes\Database\Database::createDatabase();
            } else if ( $currentLevel === "pro" ) {
                \ADP\BaseVersion\Includes\Database\Database::createDatabase();
                \ADP\ProVersion\Includes\Database\Database::createDatabase();
            }

            update_option(self::DB_LEVEL_KEY, $currentLevel, false);
        }
    }
}
