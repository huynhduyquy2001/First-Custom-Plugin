<?php


namespace Acowebs\WCPA\Free;

use WC_Tax;

class Cart
{

    public $config;
    /**
     * @var bool
     */



    public function __construct()
    {
        add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);

        add_filter('woocommerce_cart_item_class', array($this, 'cart_item_class'), 10, 3);
        /** poly lang cart item filter */
        add_filter('pllwc_translate_cart_item', array($this, 'pllwc_translate_cart_item'), 10);


    }


    public function cart_item_class($class, $cart_item)
    {

        if (isset($cart_item[WCPA_CART_ITEM_KEY]) && count($cart_item[WCPA_CART_ITEM_KEY])) {
            $class .= ' wcpa_cart_has_fields';
        }
        if (isset($cart_item['wcpaIgnore'])) {
            $class .= ' wcpa_prevent_quantity_change';
        }

        return $class;
    }





    public function pllwc_translate_cart_item($item)
    {
        if (isset($item['wcpa_options_price_start'])) {
            unset($item['wcpa_options_price_start']);
        }

        return $item;
    }




    public
    function get_item_data(
        $item_data,
        $cart_item
    )
    {
        if (!is_array($item_data)) {
            $item_data = array();
        }
        $this->config = [
            'show_meta_in_cart' => Config::get_config('show_meta_in_cart'),
            'show_meta_in_checkout' => Config::get_config('show_meta_in_checkout')
        ];


        $_product = $cart_item['data'];
        if(!$_product){
            return  $item_data;
        }
        if ((($this->config['show_meta_in_cart'] && !is_checkout()) ||
                (is_checkout() && $this->config['show_meta_in_checkout'])) &&
            isset($cart_item[WCPA_CART_ITEM_KEY]) &&

            is_array($cart_item[WCPA_CART_ITEM_KEY]) &&
            !empty($cart_item[WCPA_CART_ITEM_KEY])) {




            $metaDisplay = new MetaDisplay(true);

            foreach ($cart_item[WCPA_CART_ITEM_KEY] as $sectionKey => $section) {
                if(!isset($section['fields'])){
                    continue;
                }
                $form_rules = $section['extra']->form_rules;


                foreach ($section['fields'] as $rowIndex => $row) {
                    foreach ($row as $colIndex => $field) {

                        if (in_array($field['type'], array('header', 'content', 'hidden',))) {
                            continue;
                        }

                        if (!in_array($field['type'], array('separator'))) {


                            $item_data[] = array(
                                'type' => $field['type'],
                                'name' => is_array($field['name']) ? implode(',', $field['name']) : $field['name'],
                                'key' => isset($field['label']['label']) ? $field['label']['label'] : $field['label'],
                                'value' => $metaDisplay->display($field, $form_rules),
                            );
                        }
                    }
                }
            }
        }

        return $item_data;
    }

}
