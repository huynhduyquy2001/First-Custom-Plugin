<?php

namespace ADP\BaseVersion\Includes\Database;

use ADP\BaseVersion\Includes\Database\Models\Order;
use ADP\BaseVersion\Includes\Database\Models\OrderItem;
use ADP\BaseVersion\Includes\Database\Models\Rule;
use ADP\BaseVersion\Includes\Database\Models\PersistentRuleCache;
use ADP\BaseVersion\Includes\Database\Repository\ThemeModificationsRepository;
use ADP\BaseVersion\Includes\Settings\StoreStrategy;

defined('ABSPATH') or exit;

class Database
{
    public static function createDatabase()
    {
        Rule::createTable();
        Order::createTable();
        OrderItem::createTable();
        PersistentRuleCache::createTable();
    }

    public static function deleteDatabase()
    {
        Rule::deleteTable();
        Order::deleteTable();
        OrderItem::deleteTable();
        PersistentRuleCache::deleteTable();
        (new ThemeModificationsRepository())->drop();
        (new StoreStrategy())->drop();
    }

    public static function getOnlyRequiredChildPostMetaData($parentId)
    {
        global $wpdb;

        $requiredKeys = array(
            '_sale_price',
            '_regular_price',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_tax_status',
            '_tax_class',
            '_sku',
        );
        $requiredKeys = "'" . implode("','", $requiredKeys) . "'";

        $meta_list = $wpdb->get_results("
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE
				post_id IN (SELECT ID FROM $wpdb->posts WHERE post_parent = $parentId )
				AND
				(meta_key IN ( $requiredKeys ) OR meta_key LIKE 'attribute_%')
			ORDER BY post_id ASC", ARRAY_A);

        $post_data = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = $parentId ", OBJECT_K);

        $required_data = array();

        foreach ($post_data as $post_datum) {
            $post_datum->meta = array();

            $required_data[$post_datum->ID] = $post_datum;
        }

        foreach ($meta_list as $row) {
            $value                                                  = maybe_unserialize($row['meta_value']);
            $required_data[$row['post_id']]->meta[$row['meta_key']] = $value;
        }

        return $required_data;
    }

    /**
     * Retrieves the required product post metadata for a given product ID.
     *
     * @param int $productId The ID of the product.
     *
     * @return \stdClass|null The product post data with the required metadata,
     *                                     or null if the product does not exist.
     */
    public static function getOnlyRequiredProductPostMetaData(int $productId)
    {
        global $wpdb;

        $requiredKeys = array(
            '_sale_price',
            '_regular_price',
            '_sale_price_dates_from',
            '_sale_price_dates_to',
            '_tax_status',
            '_tax_class',
            '_sku',
        );
        $requiredKeys = "'" . implode("','", $requiredKeys) . "'";

        $metaList = $wpdb->get_results($wpdb->prepare("
			SELECT post_id, meta_key, meta_value
			FROM $wpdb->postmeta
			WHERE
				post_id = %d
				AND
				(meta_key IN ( $requiredKeys ) OR meta_key LIKE 'attribute_%')
			", $productId)
            , ARRAY_A);

        $postData = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE ID = %d", $productId)
        );

        if ( $postData === null ) {
            return null;
        }

        $postData->meta = [];

        foreach ($metaList as $row) {
            $postData->meta[$row['meta_key']] = maybe_unserialize($row['meta_value']);
        }

        return $postData;
    }

}
