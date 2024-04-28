<?php


namespace Acowebs\WCPA\Free;

class FormValidation
{
    public $errorMessages = [];
    private $product;
    private $quantity = 1;

    public function __construct($product = false, $quantity = 1)
    {
        $this->product  = $product;
        $this->quantity = $quantity;

        /**
         * This messages are different from the messages configured in backend,
         * This message using when the user submit data , and some how it skipp the js validation. Also it uses in Free version of plugin where js validation doesnt provide
         * here it need to use the field label in message to identify the field (where as in js validation as it is showing against fields, it doesnt need to replace label)
         */
        $this->errorMessages = [
            'requiredError'  => __('Field %s is required', 'woo-custom-product-addons'),

            'minValueError' => __('Field %s is required', 'woo-custom-product-addons'),
            'maxValueError' => __('Field %s is required', 'woo-custom-product-addons'),




        ];
    }


    private function add_cart_error($message)
    {
        wc_add_notice($message, 'error');
    }

    public function validate($field, $dField)
    {
        /**
         * Required field validation
         */
        $status = true;
        if (isset($field->required) && $field->required) {
            if ($dField['value'] === false) {
                $status = false;
            }
            if (is_string($dField['value']) && trim($dField['value']) == "") {
                $status = false;
            }
            if ($status === false) {
                $this->add_cart_error(sprintf($this->errorMessages['requiredError'], $field->label));

                return false;
            }
        }




        /** if fields is empty, no further validations to be processed */
        /* TODO, need to verify
         */
        if ($dField['value'] === false) {
            return true;
        }
        if (is_string($dField['value']) && trim($dField['value']) == "") {
            return true;
        }



        if (in_array($field->type, ['number'])) {
            if (isset($field->max) && $field->max !== '') {
                if ($dField['value'] > $field->max) {
                    $status = false;
                    $this->add_cart_error(sprintf(__('Value must be less than or equal to %d for field %s ',
                        'woo-custom-product-addons'), $field->max, $field->label));
                }
            }
            if (isset($v->min) && $v->min !== '') {
                if ($dField < $field->min) {
                    $status = false;
                    $this->add_cart_error(sprintf(__('Value must be greater than or equal to %d for field %s ',
                        'woo-custom-product-addons'), $field->min, $field->label));
                }
            }
        }


        return $status;
    }



}