<?php


namespace Acowebs\WCPA\Free;


use stdClass;

class ReadForm
{
    private $processObject = null;

    /**
     * Using this when order again data processing
     * @var null
     */
    private $metaData = null;
    /**
     * To store the meta data is version 1 type or newer
     * @var null
     */
    private $isMetaV1 = null;


    public function __construct(Process $process = null)
    {
        $this->processObject = $process;
    }

    public function read_from_order_data($data, $field, $hide_empty, $zero_as_empty)
    {
        $fieldValue     = '';
        $this->metaData = $data;
        $this->isMetaV1 = is_array($data) && isset($data[0]);

        $dField = $this->find_meta($field);

        if ($dField === false) {
            return '';
        }

        if (isset($dField['value'])) {
            if (in_array($field->type, [
                'select',
                'radio-group',
                'checkbox-group',

            ])) {
                if (!is_array($dField['value']) && !isEmpty($dField['value'])) {
                    $dField['value'] = [['value'=>$dField['value']]];
                }
                if(is_array($dField['value'])){
                    $values     = array_map(function ($v) {
                        return $v['value'];
                    }, $dField['value']);
                    $fieldValue = $this->readOptionsField($field, $values);
                }

            } elseif (in_array($field->type, ['date'])) {
                $fieldValue = $dField['value'];
            } else {
                if (is_string($dField['value'])) {
                    $fieldValue = $dField['value'];
                }
            }
        }


        return $fieldValue;
    }

    public function find_meta($field)
    {
        if ($this->isMetaV1) {
            /** check with id */
            return $this->searchField($this->metaData, $field);
        } else {
            /** check with id */
            $elementId = $field->elementId;
            foreach ($this->metaData as $sectionKey => $section) {
                foreach ($section['fields'] as $rowIndex => $row) {
                    $value = $this->searchField($row, $field);
                    if ($value !== false) {
                        return $value;
                    }
                }
            }
        }

        return false;
    }

    public function searchField($data, $field)
    {
        $elementId = $field->elementId;
        $arr       = array_filter($data, function ($v) use ($elementId) {
            if(isset($v['elementId'])){
                return $v['elementId'] === $elementId;
            }
            if(isset($v['form_data']) && isset($v['form_data']->elementId)){
                return $v['form_data']->elementId === $elementId;
            }
        });

        if ($arr !== false && !isEmpty($arr)) {
            return reset($arr);
        } else {
            /** check with name */
            $name = $field->name;
            $arr  = array_filter($data, function ($v) use ($name) {
                return $v['name'] === $name;
            });
            if ($arr !== false) {
                return reset($arr);
            }

            return false;
        }
    }

    public function readOptionsField($field, $values = false)
    {

        if ($values === false) {
            $values = $this->fieldFromName($field->name);
        }

        $values_data = array();
        if ( ! is_array($values)) {
            $values = array($values);
        }
        foreach ($values as $l => $val) {
            $item = false;
            foreach ($field->values as $j => $_v) {
                if (isset($_v->options) && is_array($_v->options)) {
                    foreach ($_v->options as $k => $__v) {
                        if ($__v->value === $val || addslashes($__v->value) === $val) {
                            $item = $__v;
                            break;
                        }
                    }
                    if ($item !== false) {
                        break;
                    }
                } else {
                    if ($_v->value === $val || addslashes($_v->value) === $val) {
                        $item = $_v;
                        break;
                    }
                }
            }


            if($item===false){
               continue;
            }




            $values_data[$j] = array(
                'i'     => $j,
                'value' => $this->sanitize_values($val),
                'label' => isset($item->label) ? $this->sanitize_values($item->label) : false,

            );

            if (isset($field->multiple) && $field->multiple === false) {
                break;
            }
        }


        return $values_data;
    }

    public function fieldFromName($name, $action = 'value', $prefix = false)
    {
        return fieldFromName($name, $action, $prefix);

    }

    public function sanitize_values($value, $isMultiLine = false)
    {

                /**
         * sanitize functions removes %20 from urls, so it need to find url from string and escape it
         */
        $filtered = $value;
        while ( preg_match( '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $value, $match ) ) {
            $filtered = str_replace( $match[0], urldecode($match[0]), $filtered );
            $value = str_replace( $match[0], '', $value );
        }

        if ($isMultiLine) {
            return  sanitize_textarea_field(wp_unslash($filtered));
        }

        return sanitize_text_field(wp_unslash($filtered));


    }

    public function _read_form($field, $hide_empty, $zero_as_empty)
    {
        $fieldValue = false;
        if (in_array($field->type, array('content', 'header'))) {
            if (isset($field->show_in_checkout) && $field->show_in_checkout == true) {
                $fieldValue = (isset($field->value)) ? $field->value : '';
            }
        } elseif ($this->fieldFromName($field->name, 'isset')) {
            if (in_array($field->type, [
                'select',
                'radio-group',
                'checkbox-group',

            ])) {
                $fieldValue = $this->readOptionsField($field);
            } elseif (in_array($field->type, ['date'])) {
                $fieldValue = $this->readDateFields($field);
            } else {
                $fieldValue = $this->readTextFields($field);
            }
        }

        return $fieldValue;
    }



    public
    function readDateFields(
        $field,
        $value = false
    ) {
        if ($value == false) {
            $value = $this->fieldFromName($field->name);
        }

        $value = $this->sanitize_values($value);




        return $value;
    }

    public
    function readTextFields(
        $field
    ) {
        $value = $this->fieldFromName($field->name);


        $value = $this->sanitize_values($value,'textarea' == $field->type);


        return $value;
    }
}