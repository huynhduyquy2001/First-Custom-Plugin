<?php


namespace Acowebs\WCPA\Free;

use Exception;
use WC_Order_Factory;
use function wc_get_order_item_meta;
use function wc_update_order_item_meta;

class Order
{
    /**
     * @var false|mixed|string|void
     */
    private $show_price;

    public function __construct()
    {
        add_action(
            'woocommerce_checkout_create_order_line_item',
            array($this, 'checkout_create_order_line_item'),
            10,
            4
        );
        /** support for RFQ request quote plugin  */
        add_action(
            'rfqtk_woocommerce_checkout_create_order_line_item',
            array($this, 'rfqtk_checkout_create_order_line_item'),
            10,
            4
        );


        add_action('woocommerce_checkout_update_order_meta', array($this, 'checkout_order_processed'), 1, 1);
        /** support for block checkout */
        add_action('woocommerce_store_api_checkout_update_order_meta',
            array($this, 'checkout_order_processed'), 1, 1);


        add_action('woocommerce_checkout_subscription_created', array($this, 'checkout_subscription_created'), 10,
            1); //compatibility with subscription plugin


        add_filter('woocommerce_order_item_display_meta_value', array($this, 'display_meta_value'), 10, 3);

        add_action('woocommerce_after_order_itemmeta', array($this, 'order_item_line_item_html'), 10, 3);


        add_action('woocommerce_order_item_get_formatted_meta_data', array(
            $this,
            'order_item_get_formatted_meta_data',
        ), 10, 2);

        add_filter('woocommerce_display_item_meta', array($this, 'display_item_meta'), 10, 3);
    }

    //TODO to verify
    public function display_item_meta($html, $item, $args)
    {
        $html = str_replace('<strong class="wc-item-meta-label">' . WCPA_EMPTY_LABEL . ':</strong>', '', $html);

        return str_replace(WCPA_EMPTY_LABEL . ':', '', $html);
    }

    public function order_item_line_item_html($item_id, $item, $product)
    {
        $meta_data = $item->get_meta(WCPA_ORDER_META_KEY);

        if (is_array($meta_data) && count($meta_data)) {
            $firstKey = array_key_first($meta_data);
            if (is_string($firstKey)) {
////                include(plugin_dir_path(__FILE__).'meta-line-item.php');
//                $meta = new OrderMetaLineItem($item, $product);
//                $meta->render();

                echo '<div class="wcpa_order_meta" 
                 data-wcpa=\'' . htmlspecialchars(wp_json_encode($meta_data), ENT_QUOTES) . '\'
                   data-itemId="' . $item_id . '" >
                <div class="wcpa_skeleton_loader"></div>
				 <div class="wcpa_skeleton_label"></div>
				 <div class="wcpa_skeleton_field"></div></div>';
            } else {
                include(plugin_dir_path(__FILE__) . 'meta-line-item_v1.php');
            }
        }
    }

    /**
     * To hide showing wcpa meta as default order meta in admin end order details. As we are already showing this data in formatted mode
     */
    public function order_item_get_formatted_meta_data($formatted_meta, $item)
    {
        if (Config::get_config('show_meta_in_order') && did_action('woocommerce_before_order_itemmeta') > 0) {
            foreach ($formatted_meta as $meta_id => $v) {
                if ($this->wcpa_meta_by_meta_id($item, $meta_id)) {
                    unset($formatted_meta[$meta_id]);
                }
            }
        }

        return $formatted_meta;
    }

    private function wcpa_meta_by_meta_id($item, $meta_id)
    {
        $meta_data = $item->get_meta(WCPA_ORDER_META_KEY);


        if (is_array($meta_data) && count($meta_data)) {
            $firstKey = array_key_first($meta_data);
            if (is_string($firstKey)) {
                /** version 2 Format - including sections  */
                foreach ($meta_data as $sectionKey => $section) {
                    $form_rules = $section['extra']->form_rules;
                    foreach ($section['fields'] as $rowIndex => $row) {
                        foreach ($row as $colIndex => $field) {
                            if (isset($field['meta_id']) && ($meta_id == $field['meta_id'])) {
                                return ['form_rules' => $form_rules, 'field' => $field];
                            }
                        }
                    }
                }
            } else {
                /** version 1 Format */
                foreach ($meta_data as $v) {
                    if (isset($v['meta_id']) && ($meta_id == $v['meta_id'])) {
                        return $v;
                    }
                }
            }
        } else {
            return false;
        }

        return false;
    }

    public function checkout_order_processed($order_id)
    {
        $order = wc_get_order($order_id);
        $items = $order->get_items();
        if (is_array($items)) {
            foreach ($items as $item_id => $item) {
                $this->update_order_item($item, $order_id);
            }
        }
    }

    public function update_order_item($item)
    {
        if (!is_object($item)) {
            $item = WC_Order_Factory::get_order_item($item);
        }
        if (!$item) {
            return false;
        }
        $wcpa_meta_data = $item->get_meta(WCPA_ORDER_META_KEY);

        if(!is_array($wcpa_meta_data)){
            return ;
        }
        $quantity = $item->get_quantity();
        $save_price = Config::get_config('show_price_in_order_meta');
        foreach ($wcpa_meta_data as $sectionKey => $section) {
            $form_rules = $section['extra']->form_rules;
            foreach ($section['fields'] as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    $plain = $this->order_meta_plain($field, $form_rules, $save_price, $quantity);
                    if (isset($field['meta_id']) && is_numeric($field['meta_id'])) {
                        $item->update_meta_data(false, $plain, $field['meta_id']);
                        //* setting key false as it doesnt changed */

                    } else {
                        $item->add_meta_data(
                            'WCPA_id_' . $colIndex . '_' . $rowIndex . '_' . $sectionKey,
                            // why sectionKey at end? section can be contain '_', so splitting will result wrong
                            $plain
                        );
                    }

                }
            }
        }

        $item->save_meta_data();
        $meta_data = $item->get_meta_data();
        foreach ($meta_data as $meta) {
            $data = (object)$meta->get_data();
            if (($index = $this->check_wcpa_meta($data)) !== false) {
                $metaDataItem = &$wcpa_meta_data[$index->sectionKey]['fields'][$index->rowIndex][$index->colIndex];
                if (
                    $metaDataItem['type'] == 'hidden'
                || !Config::get_config('show_meta_in_order')
                ) {
                    $item->update_meta_data('_' . $metaDataItem['label'], $data->value, $data->id);
                } else {
                    $item->update_meta_data($metaDataItem['label'], $data->value, $data->id);
                }


                $metaDataItem['meta_id'] = $data->id;
            }
        }

        $wcpa_meta_data = apply_filters('wcpa_order_meta_data', $wcpa_meta_data, $item);
        $item->update_meta_data(WCPA_ORDER_META_KEY, $wcpa_meta_data);
        $item->save_meta_data();
    }

    public function order_meta_plain($v, $form_rules, $show_price = true, $quantity = 1, $product = false)
    {

        $metaValue = '';
        $value = $v['value'];
        switch ($v['type']) {


            case 'date':
            case 'datetime-local':
            $meta_custom_date_format = Config::get_config('meta_custom_date_format');
            $format = isset($v['dateFormat']) ? $v['dateFormat'] : false;
            if (is_array($value)) {
                if (isset($value['start'])) {
                    $metaValue = ($meta_custom_date_format?formattedDate($value['start'], $format): $value['start']).
                        __(' to ', 'woo-custom-product-addons') .
                        ($meta_custom_date_format?formattedDate($value['end'], $format):$value['end']);
                } else {
                    $metaValue = '';
                    foreach ($value as $dt) {
                        $metaValue .= ($meta_custom_date_format?formattedDate($dt, $format):$dt) . ', ';
                    }
                    $metaValue = trim($metaValue, ',');
                }
            } else {
                $metaValue = ($meta_custom_date_format?formattedDate($value, $format):$value);
            }


                break;
            default:
                if (is_array($value) && in_array($v['type'], ['select', 'radio-group', 'checkbox-group'])) {
                    $metaValue = implode(
                        "\r\n",
                        array_map(
                            function ($val) {
                                if ($val['i'] === 'other') {
                                    $_return = $val['label'] . ': ' . $val['value'];
                                } else {
//                                    $_return = $val['label'];
                                    $_return = orderMetaValueForDb($val['label'],$val['value']);
                                }

                                return $_return;
                            },
                            $value
                        )
                    );

                } else {
                    $metaValue = $value;
                }


                break;
            //TODO check content field
        }

        return $metaValue;
    }


    private function check_wcpa_meta($meta)
    {
        preg_match("/WCPA_id_(.*)/", $meta->key, $matches);
        if ($matches && count($matches)) {
            $pattern = "/([0-9]+)_([0-9]+)_(.*)/";
            preg_match($pattern, $matches[1], $index);
            if (count($index) == 4) {
                return (object)[
                    'sectionKey' => $index[3],
                    'rowIndex' => $index[2],
                    'colIndex' => $index[1]
                ];
            }

            return false;
        } else {
            return false;
        }
    }

    /**
     * Prepare addon values as plain text, it can be stored as order line item meta
     * This data can be utilized even if WCPA plugin is inActive
     * Also 3rd party plugins might be using this data, even it is not compatible with product addon, this raw data will be accessible
     */
    //TODO handle version 1 Data
    public function checkout_subscription_created($subscription)
    {
        $items = $subscription->get_items();
        $order_id = $subscription->get_id();
        if (is_array($items)) {
            foreach ($items as $item_id => $item) {
                $this->update_order_item($item, $order_id);
            }
        }
    }

    public function checkout_create_order_line_item($item, $cart_item_key, $values, $order)
    {
        if (empty($values[WCPA_CART_ITEM_KEY])) {
            return;
        }


        $item->add_meta_data(WCPA_ORDER_META_KEY, $values[WCPA_CART_ITEM_KEY]);

    }

    public function rfqtk_checkout_create_order_line_item($item, $cart_item_key, $values, $order)
    {
        if (empty($values[WCPA_CART_ITEM_KEY])) {
            return;
        }


        $item->add_meta_data(WCPA_ORDER_META_KEY, $values[WCPA_CART_ITEM_KEY]);
        $item->save();
    }

    /**
     * Display   formatted meta value
     *
     * @param $display_value
     * @param null $meta
     * @param null $item
     *
     * @return mixed|void
     */
    public function display_meta_value($display_value, $meta = null, $item = null)
    {
        if ($item != null && $meta !== null) {
            $wcpa_data = $this->wcpa_meta_by_meta_id($item, $meta->id);
        } else {
            $wcpa_data = false;
        }
        $out_display_value = $display_value;
        if ($wcpa_data) {
            if (isset($wcpa_data['form_rules'])) {
                $form_rules = $wcpa_data['form_rules'];
                $field = $wcpa_data['field'];
            } else {
                $form_rules = isset($wcpa_data['form_data']->form_rules) ? $wcpa_data['form_data']->form_rules : [];
                $field = $wcpa_data;
            }


            //TODO check currency and taxrate
            $metaDisplay = new MetaDisplay(false);
            $out_display_value = $metaDisplay->display($field, $form_rules);

        }

        return $out_display_value;
    }

    public function saveOrderMeta($itemId, $data)
    {

        try {

            if (is_array($data)) {

                foreach ($data as $sectionKey => $section) {
                    $data[$sectionKey]['extra'] = (object)$data[$sectionKey]['extra'];
                }
                \wc_update_order_item_meta($itemId, WCPA_ORDER_META_KEY, $data);


                $this->update_order_item($itemId);
                return true;
            }


        } catch (\Exception $e) {
            return false;
        }

    }



    public function getOrderMeta($itemId)
    {
        $meta_data = \wc_get_order_item_meta($itemId, WCPA_ORDER_META_KEY);
        if (is_array($meta_data) && count($meta_data)) {
            return $meta_data;
        }
        return false;
    }

}