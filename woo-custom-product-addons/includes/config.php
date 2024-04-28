<?php

namespace Acowebs\WCPA\Free;

class Config
{

    static $key;
    static $values = false;


    public function __construct()
    {
        self::$key = Settings::$key;
    }

    static function getValidationMessage($field, $key)
    {
        if (isset($field->{requiredError}) && ! empty($field->{requiredError})) {
            return $field->{requiredError};
        }
        $validationMessages = self::get_config('wcpa_validation_strings');
        if (isset($validationMessages['validation_'.$key]) && ! empty($validationMessages['validation_'.$key])) {
            return $validationMessages['validation_'.$key];
        }

        return '';
    }

    static function get_config($option, $default = false, $translate = false)
    {
        if (self::$values == false) {
            $cacheKey = 'wcpa_settings_'.WCPA_VERSION;
            $ml = new ML();
            if($ml->is_active()){
                $cacheKey = $cacheKey.'_'.$ml->current_language();
            }
            $values = get_transient($cacheKey);
            if (false === $values) {
                $settings                = new Settings();
                $design                  = new Designs();
                $values                  = $settings->get_settings();
                $values['active_design'] = $design->get_active_design();
                set_transient($cacheKey, $values);
            }
            self::$values = $values;
        }
        $values = self::$values;

        $values   = apply_filters('wcpa_configurations', $values);

        $response = isset($values[$option]) ? $values[$option] : $default;
        if ($translate) {
            if (function_exists('pll__')) {
                return pll__($response);
            } else {
                return __($response, 'woo-custom-product-addons');
            }
        }

        return $response;
    }


}
