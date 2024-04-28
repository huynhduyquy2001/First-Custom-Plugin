<?php


namespace Acowebs\WCPA\Free;

class MetaDisplay
{
    public $config;

    private $isCart;

    public function __construct(
        $isCart
    )
    {

        $this->isCart = $isCart;
    }

    public function display($field, $formRules = false, $value = false)
    {

        $value = $value == false ? $field['value'] : $value;
        $display = $value;
        switch ($field['type']) {
            case 'date':
            case 'datetime-local':
                if ($value !== '') {
                    $format = isset($field['dateFormat']) ? $field['dateFormat'] : false;
                    if (is_array($value)) {
                        if (isset($value['start'])) {
                            $display = formattedDate($value['start'], $format) .
                                __(' to ', 'woo-custom-product-addons') .
                                formattedDate($value['end'], $format);
                        } else {
                            $display = '';
                            foreach ($value as $dt) {
                                $display .= formattedDate($dt, $format) . ', ';
                            }
                            $display = trim($display, ',');
                        }
                    } else {
                        $display = formattedDate($value, $format);
                    }

                }
                break;
            case 'content':
                $display = do_shortcode(nl2br($value));
                break;
            case 'textarea':
                $display = nl2br($value);
                break;
            case 'color':
                $display = '<span  style="color:' . $value . ';font-size: 20px; padding: 0;line-height: 0;">&#9632;</span>' . $value;
                break;
            case 'select':
            case 'checkbox-group':
            case 'radio-group':
                $display = $this->group($field);
                break;

        }

        if ($display == '') {
            $display = '&nbsp;';
        }

        return $display;

    }


    public function group($value)
    {
        $display = '';

        if (is_array($value['value'])) {
            foreach ($value['value'] as $k => $v) {
                //Label no need to apply i18n.
                if (is_string($v)) {
                    /** free version data */
                    $display .= '<span>' . $v . ' </span>';
                } else {
                    $display .= '<span>' . $v['label'] . ' </span>';
                }
                $display .= '<br />';
            }
        } else {
            $display = $value['value'];
        }
        return $display;
    }
}