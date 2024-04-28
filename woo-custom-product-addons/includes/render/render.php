<?php

namespace Acowebs\WCPA\Free;


class Render
{
    public $token;
    public $action_hook_tag = false;
    public $product = false;
    /**
     * @var bool
     */
    private $cartError = false;
    /**
     * @var int
     */
    private $product_id;

    public function __construct()
    {
        $this->token = WCPA_TOKEN;

        $this->init();
    }

    public function init()
    {
        if ($this->action_hook_tag !== false) {
            remove_action($this->action_hook_tag[0], array($this, 'render_form'), $this->action_hook_tag[1]);
        }


        add_action('woocommerce_before_single_product', array($this, 'render_init'), 10); // added this after rnb plugin not showed the form in some sites
        add_action('woocommerce_before_add_to_cart_form', array($this, 'render_init'), 10);
        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_init'), 1);


//        add_action('woocommerce_before_add_to_cart_button', array($this, 'render_form'), 10);

        add_action('wp_footer', array($this, 'wp_footer'));

    }



    public function wp_footer(){

        if (Config::get_config('enqueue_cs_js_all_pages') ) {
            wp_enqueue_script($this->token . '-front'); // to ensure front script loaded after all depends scripts loaded
        }

    }
    public function render_init()
    {



        remove_action('woocommerce_before_add_to_cart_form', array($this, 'render_init'), 10);
        remove_action('woocommerce_before_add_to_cart_button', array($this, 'render_init'), 1);
        global $product;
        $hook_simple = [
            'simple' => [Config::get_config('render_hook'), Config::get_config('render_hook_priority')],
            'variable' => [
                Config::get_config('render_hook_variable'), Config::get_config('render_hook_variable_priority')
            ]
        ];
        $this->action_hook_tag = apply_filters('wcpa_form_render_hook', $hook_simple, $product);
        if ($this->action_hook_tag['simple'][0] == $this->action_hook_tag['variable'][0]
            && $this->action_hook_tag['simple'][1] == $this->action_hook_tag['variable'][1]) {
            add_action($this->action_hook_tag['simple'][0], array($this, 'render_form'),
                $this->action_hook_tag['simple'][1]);
        } else {
            if ($product->is_type('variable')) {
                add_action($this->action_hook_tag['variable'][0], array($this, 'render_form'),
                    $this->action_hook_tag['variable'][1]);
            } else {
                add_action($this->action_hook_tag['simple'][0], array($this, 'render_form'),
                    $this->action_hook_tag['simple'][1]);
            }
        }
    }

    public function render_form()
    {


        global $product;
        if (!is_a($product, 'WC_Product')) {
            return;
        }
        $product_id = $product->get_id();
        $this->product = $product;
        $this->product_id = $product_id;

//
//        if (!$this->product) {
//            global $product;
//            $product_id = $product->get_id();
//            $this->product = $product;
//            $this->product_id = $product_id;
//        }

        $wcpaProduct = new Product();

        $data = $wcpaProduct->get_fields($this->product_id);


        if ($data['fields'] && !emptyObj($data['fields'])) {
            /** checking fields not empty */
            $fields = $data['fields'];


            if (isset(Main::$cartError[$this->product_id]) && Main::$cartError[$this->product_id]) {
                $this->cartError = true;
            }


            $this->processFields($fields);

            if ($data['scripts']) {
                foreach ($data['scripts'] as $tag => $status) {
                    if ($status) {
                        wp_enqueue_script($this->token . '-' . $tag);
                    }
                }
            }

            wp_enqueue_script($this->token . '-front');



            $design = Config::get_config('active_design', false);

            $wcpaData = [
                'product' => $this->getProductData(),
                'fields' => $data['fields'],
                'config' => $data['config'],

                'design' => $design['common'],


            ];


            $fieldsCount = min(5,isset($data['fieldsCount'])?$data['fieldsCount']:3);
            echo '<div class="wcpa_form_outer" 
				 data-wcpa=\'' . htmlspecialchars(wp_json_encode($wcpaData), ENT_QUOTES) . '\' >
			 <div class="wcpat_skeleton_loader_area">' . str_repeat(
                    '<div class="wcpa_skeleton_loader">
				 <div class="wcpa_skeleton_label"></div>
				 <div class="wcpa_skeleton_field"></div>
			 </div>',
                    $fieldsCount
                ) . '
			</div>
			</div>';
        }
    }

    /**
     * Prefill if the form validation got wrong
     * Prefill data passed in URL
     * prefill data for cart edit
     * @param $fields
     */

    public function processFields(
        &$fields
    )
    {

        foreach ($fields as $sectionKey => $section) {

            foreach ($section->fields as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {

                    $this->setDefaultValue($field);
                    $field = apply_filters('wcpa_form_field', $field, $this->product_id);
                }
            }
        }


    }

    public function setDefaultValue(&$field, $default = false)
    {
        if (!isset($field->name)) {
            return;
        }
        if ($this->cartError && fieldFromName(
                $field->name,
                'isset'
            )) { // if there is a validation error, it has persist the user entered values,

            $default_value = fieldFromName($field->name, 'value');
        } elseif ((isset($field->name)) && isset($_GET[$field->name])) { // using get if there is any value passed using url/get method
            $default_value = $_GET[$field->name];
        } else {
            return;
        }


        $field->preSetValue = $this->sanitizeValue($field->type, $default_value);
    }

    public function sanitizeValue($type, $value)
    {
        switch ($type) {
            case 'text':
            case 'date':
            case 'number':
            case 'color':
            case 'hidden':
            case 'time':

                return sanitize_text_field(wp_unslash($value));

            case 'textarea':
                return sanitize_textarea_field(wp_unslash($value));



            case 'select':
            case 'checkbox-group':
            case 'radio-group':

                if (is_array($value)) {
                    $_values = $value;
                    $_values = array_map(
                        function ($v) {
                            return sanitize_text_field(wp_unslash($v));
                        },
                        $_values
                    );

                    /* some plugins/themes send checkbox/radio field data even if they are not checked, it can filter using null*/
                    $_values = array_filter(
                        $_values,
                        function ($value) {
                            return ($value !== null && $value !== false && $value !== '');
                        }
                    );
                    $value = array_values($_values);// in front end , to treat it as array, set the index from 0
                } else {
                    $value = sanitize_text_field($value);
                }

                return $value;
                break;
            //TODO reset of field types
        }
    }



    public function getProductData()
    {
        $product_data = array();

        $product_data['product_id'] = ['parent' => $this->product->get_id(), 'variation' => false];
        $product_data['is_variable'] = $this->product->is_type('variable') ? true : false;

        return $product_data;
    }


}