<?php


namespace Acowebs\WCPA\Free;

use stdClass;
use WC_AJAX;
use WC_Product;
use WC_Session_Handler;
use WP_REST_Response;

class Process
{

    private $processed_data = array();
    private $form_data = array();
    private $fields = false;
    private $product = false;
    private $product_id = false;
    private $quantity = 1;
    private $token;
    private $orderAgainData = false;
    /**
     * @var mixed
     */
    private $formConf;
    private $formulas;
    /**
     * @var false|WC_Product|null
     */
    private $parentProduct = false;

    public function __construct()
    {
        $this->token = WCPA_TOKEN;
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 4);
        add_filter('wcpa_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 4);
        add_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'), 10, 4);
        add_action('rest_api_init', array($this, 'register_routes'));

        add_action('wc_ajax_wcpa_ajax_add_to_cart', array($this, 'ajax_add_to_cart'));

        add_filter('woocommerce_order_again_cart_item_data', array($this, 'order_again_cart_item_data'), 50, 3);

        add_filter('pllwc_add_cart_item_data', array($this, 'pllwc_cart_item_data'), 10, 2); // polylang
    }

    public function pllwc_cart_item_data($cart_item_data, $item)
    {
        if (isset($item[WCPA_CART_ITEM_KEY])) {
            $cart_item_data[WCPA_CART_ITEM_KEY] = $item[WCPA_CART_ITEM_KEY];
        }
        if (isset($item['wcpa_cart_rules'])) {
            $cart_item_data['wcpa_cart_rules'] = $item['wcpa_cart_rules'];
        }

        return $cart_item_data;
    }

    public function order_again_cart_item_data($cart_item_data, $item, $order)
    {
        $meta_data = $item->get_meta(WCPA_ORDER_META_KEY);
        $this->orderAgainData = $meta_data;
        $product_id = (int)$item->get_product_id();
        $variation_id = (int)$item->get_variation_id();
        $quantity = $item->get_quantity();

        $passed = $this->add_to_cart_validation(true,
            $product_id, $quantity, $variation_id, true);
        if (!$passed) {
// set error
            $product = $item->get_product();
            $name = '';
            if ($product) {
                $name = $product->get_name();
            }
            wc_add_notice(sprintf(
            /* translators: %s Product Name */
                __('Addon options of product %s has been changed, Cannot proceed with older data. 
            You can go to product page and fill the addon fields again inorder to make new order',
                    'woo-custom-product-addons'),
                $name),
                'error');

            return $cart_item_data;
        }

        $cart_item_data = $this->add_cart_item_data($cart_item_data, $product_id, $variation_id, $quantity);

        /** remove validation as already done */
        remove_filter('woocommerce_add_to_cart_validation', array($this, 'add_to_cart_validation'));

        return $cart_item_data;
    }

    /**
     * @param $passed
     * @param $product_id
     * @param int $qty
     * @param false $variation_id
     * @param false $variations Optional, it will be passed for order again validation action
     * @param false $cart_item_data Optional, it will be passed for order again validation action
     *
     * @return bool
     */
    public function add_to_cart_validation(
        $passed,
        $product_id,
        $qty = 1,
        $variation_id = false
    )
    {
        if ((($pid = wp_get_post_parent_id($product_id)) != 0) &&
            ($variation_id == false)
        ) {
            $variation_id = $product_id;
            $product_id = $pid;
        }


        /**
         * ignore checking if $passed is false, as it can be validation error thrown by other plugins or woocommerce itself
         */
        if ($passed === true) {
            /** must pas $product-id, dont pass $variation id */
            $this->setFields($product_id);

            $this->set_product($product_id, $variation_id, $qty);

            $status = $this->read_form();
            if ($status !== false) {
                $this->process_cl_logic();
                $passed = $this->validateFormData();
            } else {
                $passed = false;
            }
        }

        Main::setCartError($product_id, !$passed);


        return $passed;
    }

    /**
     * Initiate form fields if not initiated already,
     *
     * @param $product_id id must be product parent id, dont pass variation id
     *
     * @since 5.0
     */
    public function setFields($product_id)
    {

        $this->fields = false;
        $wcpaProduct = new Product();
        $data = $wcpaProduct->get_fields($product_id);

        if (!$data['fields']) {
            return;
        }
        $this->fields = $data['fields'];
        $this->formConf = $data['config'];
    }


    /** set product object, it can use where product objects need
     *
     * @param $product_id
     * @param bool $variation_id
     * @param int $quantity
     */
    public function set_product($product_id, $variation_id = false, $quantity = 1)
    {

        if ($variation_id != false) {
            $this->parentProduct = wc_get_product($product_id);
            $product_id = $variation_id;
        }

        $this->product = wc_get_product($product_id);
        $this->product_id = $product_id;

        $this->quantity = $quantity;
    }

    /** Read user submitted data
     *
     * @param $product_id
     *
     * @since 5.0
     */
    public function read_form()
    {
        if (!$this->fields) {
            return;
        }
        $this->form_data = [];

        $fieldTemp = new stdClass();

        foreach ($this->fields as $sectionKey => $section) {
            $fieldTemp->{$sectionKey} = clone $section;
            $this->form_data[$section->extra->key]['extra'] = (object)[
                'section_id' => $section->extra->section_id,
                'clStatus' => 'visible',
                'key' => $section->extra->key,
                'price' => 0,
                'form_id' => $section->extra->form_id,

                'parentKey' => isset($section->extra->parentKey) ? $section->extra->parentKey : false,
                'form_rules' => $section->extra->form_rules
            ];

            $status = $this->_read_form($section, $fieldTemp);
            if ($status === false) {
                /** file field can cause error if no files */
                return false;
            }

        }
        $this->fields = $fieldTemp;
    }

    public function _read_form($section, &$fieldTemp)
    {
        $readForm = new ReadForm($this);


        $hide_empty = Config::get_config('hide_empty_data', false);
        $zero_as_empty = false;
        foreach ($section->fields as $rowIndex => $row) {
            foreach ($row as $colIndex => $field) {
                $field = apply_filters('wcpa_form_field', $field, $this->product_id);
                $form_data = extractFormData($field);

                if (in_array($field->type, array('content', 'separator', 'header'))) {
                    continue;
                }


                if ($this->orderAgainData === false) {
                    $_fieldValue = $readForm->_read_form($field, $hide_empty, $zero_as_empty);
                } else {
                    $_fieldValue = $readForm->read_from_order_data($this->orderAgainData, $field, $hide_empty,
                        $zero_as_empty);
                }


                $fieldValue = $_fieldValue;


                if (isEmpty($fieldValue) && $hide_empty) {
                    continue;
                }
                if ($zero_as_empty && ($fieldValue === 0 || $fieldValue === '0')) {
                    continue;
                }
                $label = (isset($field->label)) ? (($field->label == '') ? WCPA_EMPTY_LABEL : $field->label) : WCPA_EMPTY_LABEL;

                $this->form_data[$section->extra->key]['fields'][$rowIndex][$colIndex] = [
                    'type' => $field->type,
                    'name' => isset($field->name) ? $field->name : $field->elementId,
                    'label' => $label,
                    'elementId' => $field->elementId,
                    'value' => $fieldValue,
                    //  value fill be false for if the value not set
                    'clStatus' => 'visible',
                    'price' => false,

                    'form_data' => $form_data,


                ];

                if ($field->type == 'date' || $field->type == 'datetime-local') {
                    $dateFormat = getDateFormat($field);

                    $this->form_data[$section->extra->key]['fields'][$rowIndex][$colIndex]['dateFormat'] = $dateFormat;
                }


            }
        }
    }


    /**
     * Process conditional logic with user submited data
     *
     * @param $product_id
     *
     * @since 5.0
     */
    public function process_cl_logic()
    {
        $processed_ids = array();
        $processed_sections = array();
        $cLogic = new CLogic($this->form_data, $this->fields, $this->product, $this->parentProduct, $this->quantity);
        if ($this->fields) {
            foreach ($this->fields as $sectionKey => $section) {
                $sectionClStatus = 'visible';


                foreach ($section->fields as $rowIndex => $row) {
                    foreach ($row as $colIndex => $field) {
                        if (isset($field->enableCl) && $field->enableCl && isset($field->relations) && is_array(
                                $field->relations
                            )) {
                            $clStatus = $cLogic->evalConditions(
                                $field->cl_rule,
                                $field->relations
                            ); // returns false if it catch error
                            $processed_ids[] = isset($field->elementId) ? $field->elementId : false;

                            if ($clStatus !== false) {
                                /** we have to keep the cl status even if the field has not set while read_form. It needs to check validation required  */
                                if (!isset($this->form_data[$sectionKey]['fields'][$rowIndex])) {
                                    $this->form_data[$sectionKey]['fields'][$rowIndex] = [];
                                }
                                if (!isset($this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex])) {
                                    $this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex] = [];
                                }
                                $this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex]['clStatus'] = $clStatus;
                                if ($field->cl_dependency) {
                                    $cLogic->processClDependency($field->cl_dependency, $processed_ids);
                                }
                            }
                            $cLogic->setFormData($this->form_data);
                        }
                    }
                }
            }
        }
    }

    public function validateFormData()
    {
        $validation = new FormValidation($this->product, $this->quantity);
        if ($this->fields) {
            foreach ($this->fields as $sectionKey => $section) {
                if ($this->form_data[$sectionKey]['extra']->clStatus === 'hidden') {
                    /** in PHP end, disable status also treat as hidden, so no need to compare 'disable' */
                    continue;
                }
                foreach ($section->fields as $rowIndex => $row) {
                    foreach ($row as $colIndex => $field) {

                        if (!isset($this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex])) {
                            if (isset($field->required) && $field->required) {
                                $validation->validate($field, ['value' => false]); // calling this to set error message

                                return false;
                            }
                            continue;
                        }
                        $dField = $this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex];
                        if ($dField['clStatus'] === 'hidden') {

                            continue;
                        }
                        if (!isset($this->form_data[$sectionKey]['fields'][$rowIndex][$colIndex]['type'])) {
                            continue;
                        }

                        if (in_array($field->type, ['content', 'separator', 'header'])) {
                            continue;
                        }
                        $status = $validation->validate($field, $dField);
                        if ($status === false) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    public function add_cart_item_data($cart_item_data, $product_id, $variation_id = false, $quantity = 1)
    {


        /**
         * Run only if fields are not set, setting fields and reading data already will be done at validation stage
         */

        if ($this->fields == false ||
            ($variation_id == false ? $this->product_id !== $product_id : $this->product_id !== $variation_id)
        ) {

            /** must pass $product-id, dont pass $variation id */
            $this->setFields($product_id);
            $this->set_product($product_id, $variation_id, $quantity);
            $this->read_form();
            $this->process_cl_logic();
        }

        if ($this->fields == false) {
            return $cart_item_data;
        }

        /*  removing this section from 3.0.2, as causing issues repeating added items to cart, in version 2 , it seems was set for order again data
            now order again has changed differently
        In pro version, this section is needed as it can use to process data when changes made from cart
              if (isset($cart_item_data[WCPA_CART_ITEM_KEY])) {
                  $this->form_data = $cart_item_data[WCPA_CART_ITEM_KEY];
              }
      */
        /**
         * remove  cl Status hidden fields
         */
        $_form_data = [];


        foreach ($this->form_data as $sectionKey => $section) {
            if ($section['extra']->clStatus !== 'visible') {
                continue;
            }
            $_form_data[$sectionKey]['extra'] = $section['extra'];
            if (!isset($section['fields'])) {
                $section['fields'] = []; // keep empty fields if no fields in this section
                $_form_data[$sectionKey]['fields'] = [];
            }
            foreach ($section['fields'] as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    if ($field['clStatus'] !== 'visible') {
                        continue;
                    }
                    if (!isset($field['type'])) {
                        continue;
                    }
                    $_form_data[$sectionKey]['fields'][$rowIndex][$colIndex] = $field;

                }
            }
            if (!isset($_form_data[$sectionKey]['fields'])) {
                /**  if all fields are clStatus hidden, 'field' can be not set*/
                $_form_data[$sectionKey]['fields'] = [];
            }
        }

       if(!is_array($cart_item_data)){
           $cart_item_data=[];// to avoid conflict with some plugins who retuns $cart_item_data as string
       }
        $cart_item_data[WCPA_CART_ITEM_KEY] = $_form_data;
        $cart_item_data['wcpa_cart_rules'] = [
            'quantity' => false,
        ];


        return $cart_item_data;
    }


    /**
     * Ajax Add to Cart
     * @since 5.0
     */
    public function ajax_add_to_cart()
    {
        if (!isset($_POST['add-to-cart'])) {
            return;
        }

        $product_id = intval($_POST['add-to-cart']);
        if (isset($_POST['quantity'])) {
            $quantity = intval($_POST['quantity']);
        } else {
            $quantity = 1;
        }

        if (empty(wc_get_notices('error'))) {
            // trigger action for added to cart in ajax
            do_action('woocommerce_ajax_added_to_cart', $product_id);

            if ('yes' === get_option('woocommerce_cart_redirect_after_add')) {
                wc_add_to_cart_message(array($product_id => $quantity), true);
            }

            wc_clear_notices();

            WC_AJAX::get_refreshed_fragments();
        } else {
            // If there was an error adding to the cart, redirect to the product page to show any errors.
            $data = array(
                'error' => true,
                'product_url' => apply_filters(
                    'woocommerce_cart_redirect_after_error',
                    get_permalink($product_id),
                    $product_id
                ),
            );

            wp_send_json($data);
        }
    }


    /**
     * Register API routes
     */

    public function register_routes()
    {

        $this->add_route('/upload/(?P<id>[0-9]+)/(?P<fname>[,a-zA-Z0-9_-]+)', 'ajax_upload', 'POST');
    }

    private function add_route($slug, $callBack, $method = 'GET')
    {
        register_rest_route(
            $this->token . '/front',
            $slug,
            array(
                'methods' => $method,
                'callback' => array($this, $callBack),
                'permission_callback' => '__return_true',
            )
        );
    }


    public function fieldValFromName($name)
    {
        if (is_array($name)) {
            /**  sectionKey,Index,Name */
            return $_POST[$name[0]][$name[1]][$name[2] . '_cl'];
        } else {
            return $_POST[$name . '_cl'];
        }
    }


    /**
     * @param $fieldId
     *
     * @return false
     */
    public function findFieldById($fieldId)
    {
        if ($this->fields == false) {
            return false;
        }
        foreach ($this->fields as $sectionKey => $section) {
            foreach ($section->fields as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    if ($field->elementId === $fieldId) {
                        return $field;
                    }
                }
            }
        }

        return false;
    }


}