<?php

namespace Acowebs\WCPA\Free;


class Settings
{

    static $key;
    static $screen_options_key;
    private $confKeys = [];
    private $values = false;

    public function __construct()
    {
        self::$screen_options_key = 'wcpa_screen_options';

        self::$key = 'wcpa_settings_key';
        $this->confKeys = [

            'add_to_cart_text' => ['text', __('Select options', 'woo-custom-product-addons')],
            'show_meta_in_cart'     => ['boolean', true],
            'show_meta_in_checkout' => ['boolean', true],
            'show_meta_in_order'    => ['boolean', true],
            'wcpa_validation_strings' => [
                'array',
                [

                    'formError' => [
                        'text',
                        __('Fix the errors shown above', 'woo-custom-product-addons')
                    ],

                    'validation_requiredError' => [
                        'text', __('Field is required', 'woo-custom-product-addons')
                    ],
                    'validation_maxlengthError'    => [
                        'text',
                        __('Maximum %s characters allowed', 'woo-custom-product-addons'), '{maxLength}'
                    ],

                    'validation_minValueError' => [
                        'text', __('Minimum value required: %s', 'woo-custom-product-addons'), '{minValue}'
                    ],
                    'validation_maxValueError' => [
                        'text', __('Maximum value allowed: %s', 'woo-custom-product-addons'), '{maxValue}'
                    ],


                    'validEmailError' => [
                        'text',
                        __('Provide a valid email address', 'woo-custom-product-addons')
                    ],
                    'validUrlError' => [
                        'text', __('Provide a valid URL', 'woo-custom-product-addons')
                    ],


                ]
            ],
            'render_hook'                   => ['text', 'woocommerce_before_add_to_cart_button'],
            'render_hook_priority'          => ['text', '10'],
            'render_hook_variable'          => ['text', 'woocommerce_before_add_to_cart_button'],
            'render_hook_variable_priority' => ['text', '10'],

            'hide_empty_data' => ['boolean', false],
            'add_to_cart_button_class' => ['text', 'wcpa_add_to_cart_button'],
            'consider_product_tax_conf' => ['boolean', true],
            'override_cart_meta_template' => ['boolean', true],
            'ajax_add_to_cart' => ['boolean', false],
            'append_global_form' => ['text', 'at_start'],// at_end
            'enqueue_cs_js_all_pages' =>  ['boolean', true],
            'item_meta_format' =>  ['text', '{label} | {value}'] ,// {label} {value}
            'meta_custom_date_format' =>  ['boolean', false] ,// whether to store the date in custom format or  the default Y-m-d H:i
        ];
    }

    /**
     * Get Screen Options
     * @since 3.0.0
     */
    public static function get_screen_options()
    {
        return get_option(self::$screen_options_key, false);
    }

    /**
     * Update Screen Options
     * @since 3.0.0
     */
    public function update_screen_options($options)
    {
        $settings = get_option(self::$screen_options_key, false);

        if ($settings === $options) {
            return true;
        }

        return update_option(self::$screen_options_key, $options, true);
    }

    /**
     * Get Gloval Seetings
     * @param bool $isBackend
     * @return array|bool
     * @since 3.0.0
     */
    public function get_settings($isBackend = false)
    {
        if ($this->values !== false) {
            return $this->values;
        }
        $this->values = [];
        $settings = get_option(self::$key);
        foreach ($this->confKeys as $key => $val) {
            list($type, $default) = $val;
            if ($type == 'array') {

                if (empty($default)) {
                    $value = isset($settings[$key]) ? $settings[$key] : $default;
                    $this->values[$key] = $value;
                } else {
                    foreach ($default as $_key => $_val) {
                        list($_type, $_default) = $_val;
                        $value = isset($settings[$key][$_key]) ? $settings[$key][$_key] : $_default;
                        if (isset($_val[2]) && $isBackend) {
                            $value = str_replace("%s", $_val[2], $value);
                        }
                        if ($_type == 'boolean') {
                            $value = metaToBoolean($value);
                        }
                        $this->values[$key][$_key] = $value;
                    }
                }
            } else {
                $value = isset($settings[$key]) ? $settings[$key] : $default;
                if ($type == 'boolean') {
                    $value = metaToBoolean($value);
                }
                $this->values[$key] = $value;
            }
        }

        return $this->values;
    }


    public function save_settings($data)
    {
        if ($data) {
            $settings = get_option(self::$key);
            if (!is_array($settings)) {
                $settings = [];
            }
            foreach ($this->confKeys as $key => $val) {
                if (in_array($key,['item_meta_format'])) {
                    if ((strpos($data[$key], '{label}') === false) && (strpos($data[$key], '{value}') === false)) {
                        /** ensure this field has included price tag , otherwise it can cause issues, so omit*/
                        continue;
                    }
                }
                list($type, $default) = $val;
                if ($type == 'array') {
                    if (empty($default)) {
                        $settings[$key] = array_map(function ($v) {
                            return $this->sanitize_settings('text', $v);
                        }, $data[$key]);
                    } else {
                        foreach ($default as $_key => $_val) {
                            list($_type, $_default) = $_val;
                            if (isset($data[$key][$_key])) {
                                $settings[$key][$_key] = $this->sanitize_settings($_type, $data[$key][$_key]);
                            } else {
                                $settings[$key][$_key] = $_default;
                            }
                        }
                    }
                } else {
                    if (isset($data[$key])) {
                        $settings[$key] = $this->sanitize_settings($type, $data[$key]);
                    } else {
                        $settings[$key] = $default;
                    }
                }
            }


            update_option(self::$key, $settings);
        }

        delete_transient('wcpa_settings_' . WCPA_VERSION);
        refreshCaches();

        return true;
    }

    public function sanitize_settings($type, $val)
    {
        if ($type == 'text') {
            $val = str_ireplace(
                [

                    '{minLength}',
                    '{maxLength}',
                    '{minValue}',
                    '{maxValue}',


                ],
                '%s', $val);

            return stripslashes(sanitize_text_field($val));
        } elseif ($type == 'boolean') {
            return metaToBoolean($val);
        }
    }



}