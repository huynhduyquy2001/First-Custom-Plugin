<?php

namespace Acowebs\WCPA\Free;


function extractFormData($field)
{
    $attrs = [
        'name',
        'label',
        'description',
        'value',
        'active',
        'required',
        'elementId',
        'fee_label',
        'subtype',
        'maxlength',
        'allow_multiple',
        'minlength',
        'min',
        'max',
        'multiple'

    ];
    $_field = [];
    foreach ($attrs as $att) {
        if (isset($field->{$att})) {
            $_field[$att] = $field->{$att};
        }
    }

    return (object)$_field;
}


function fieldFromName($name, $action = 'value', $prefix = false)
{
    if ($action == 'isset') {
        return isset($_POST[$name]);
    }

    if ($action == 'value') {
        return $_POST[$name];
    }
}

function getDateFormat($field)
{

    if ($field->type == 'time') {
        $dateFormat = __(get_option('time_format'), 'woo-custom-product-addons');
    } elseif ($field->type == 'datetime-local') {
        $dateFormat = __(get_option('date_format'), 'woo-custom-product-addons') . ' ' . __(
                get_option('time_format'),
                'woo-custom-product-addons'
            );
    } else {
        $dateFormat = __(get_option('date_format'), 'woo-custom-product-addons');
    }

    return $dateFormat;
}

function isEmpty($var)
{
    if (is_array($var)) {
        return empty($var);
    } else {
        return ($var === null || $var === false || $var === '');
    }
}

function emptyObj($obj)
{
    foreach ($obj as $prop) {
        return false;
    }

    return true;
}

function priceToFloat($price)
{
    $locale = localeconv();
    $decimals = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point']);
    $price = str_replace($decimals, '.', $price);

    return (float)$price;
}

function getValueFromArrayValues($val)
{
    if (is_array($val)) {
        if (isset($val['value'])) {
            /** place selector */
            return $val['value'];
        } elseif (count($val)) {
            /**
             * For array of values, sum the values if the values are numeric
             * Otherwise return the first value
             */

            $p_temp = array_values($val)[0];
            $_i = -1;
            $valueSum = 0.0;
            foreach ($val as $_p) {
                $_i++;
                if (is_array($_p)) {
                    if (is_numeric($_p['value'])) {
                        $valueSum += (float)$_p['value'];
                    } elseif ($_i == 0) {
                        $valueSum = $_p['value'];
                        break;
                    }
                } else {
                    if (is_numeric($_p)) {
                        $valueSum += (float)$_p;
                    } elseif ($_i == 0) {
                        $valueSum = $_p;
                        break;
                    }
                }
            }

            return $valueSum;

        }

        return false;
    }

    return $val;
}


function getUNIDate($dateString, $type = 'date')
{
    /** using date_create_from_format as it will return dates in wrong format or invalid date as false. DateTime() will return value even for incorrect date value. so avoid using DateTime()  */

    if ($type == 'time') {
        return date_create_from_format('H:i', $dateString);
    } elseif ($type == 'datetime-local' || $type == 'datetime') {
        return date_create_from_format('Y-m-d H:i', $dateString);
    } else {
        return date_create_from_format('Y-m-d', $dateString);
    }
}


function confToCss($conf)
{
    $css = '';
    foreach ($conf as $k => $v) {
        if (strpos($v, '#') == 0 && strlen($v) == 9) {
            /** convert hex color with opacity to rgba */
            list($r, $g, $b, $a) = sscanf($v, "#%02x%02x%02x%02x");
            $a = round($a / 255, 2);
            $v = "rgba($r,$g,$b,$a)";
        }
        $css = $css . '  --wcpa' . $k . ':' . $v . '; ';
    }

    return ':root{' . $css . '}';
}

function metaToBoolean($v)
{
    if ($v === '' || $v === '0') {
        return false;
    } elseif ($v === '1') {
        return true;
    }

    return $v;
}

/**
 * convert wcpa 1 field structure to wcpa2
 *
 * @param $fields
 *
 * @return array
 */
function toRowCol($fields)
{
    $newArray = array();
    $row = 0;
    $col = 0;
    foreach ($fields as $i => $item) {
        $newItem = $item;
        $newItem->active = true;
        if (!isset($newItem->col)) {
            $newItem->col = 6;
        }
        if (($col + $newItem->col) > 6) {
            $row++;
            $col = $newItem->col;
        } else {
            $col += $newItem->col;
        }
        $newArray[$row][] = $newItem;
    }


    return $newArray;
}

function fix_cols($data)
{
    $newArray = array();

    $colCount = 0;
    $rowCount = 0;
    $newArray = array();

    foreach ($data as $row) {
        if (is_array($row)) {
            foreach ($row as $field) {
                if (($colCount + $field->col) > 6) {
                    $rowCount++;
                    $colCount = $field->col;
                } else {
                    $colCount += $field->col;
                }
                $newArray[$rowCount][] = $field;
            }
        } else {
            if (($colCount + $row->col) > 6) {
                $rowCount++;
                $colCount = $row->col;
            } else {
                $colCount += $row->col;
            }
            $newArray[$rowCount][] = $row;
        }
    }

    return $newArray;
}


/**
 * Check if the fields are in old wcpa structure or new
 *
 * @param $data
 */
function checkFieldStructure($data)
{
    $value = reset($data); // get first value
    if (isset($value->fields)) {
        return 2;
    } else {
        return 1;
    }
}

function generateSectionFields($fields = [])
{
    $new_arr = (object)[];
    $sectionKey = 'sec_' . uniqSectionId();
    $new_arr->{$sectionKey} = (object)array(
        "extra" => (object)[
            'key' => $sectionKey,
            'section_id' => $sectionKey,
            'name' => __('Default', 'woo-custom-product-addons'),
            'status' => 1,

            "toggle" => true,


        ],
        "fields" => $fields
    );

    return $new_arr;
}

function uniqSectionId()
{
    return uniqid(rand(0, 10), false);
}

function sanitizeFields(&$formBuilderData, $allowed_html)
{
    foreach ($formBuilderData as $sectionKey => $section) {
        foreach ($section['fields'] as $rowIndex => $row) {
            foreach ($row as $colIndex => $field) {
                $_field = &$formBuilderData[$sectionKey]['fields'][$rowIndex][$colIndex];
                if (isset($field['label']) && ($field['type'] == 'content' || $field['type'] == 'header')) {
                    $_field['label'] = html_entity_decode(wp_kses($field['label'], 'post'));
                } elseif (isset($field['label'])) {
                    $_field['label'] = html_entity_decode(wp_kses($field['label'], array()));
                }
                if (isset($field['description'])) {
                    $_field['description'] = html_entity_decode(wp_kses($field['description'], $allowed_html));
                }

                if (isset($field['values'])) {
                    foreach ($field['values'] as $k => $v) {
                        if (isset($v['label'])) {
                            $_field['values'][$k]['label'] = html_entity_decode(
                                wp_kses($field['values'][$k]['label'], array())
                            );
                        }


                        if (isset($v['value'])) {
                            $_field['values'][$k]['value'] = trim($field['values'][$k]['value']);
                        }
                    }
                }
            }
        }
    }
}


/**
 * Using to check if the date contains from to value ( 2011-1-20 to 2022-02-30)
 */
function processDateValueForCl($val)
{
    $res = [];
    $d = getUNIDate($val);
    if ($d) {
        $res[] = $d->getTimestamp();
    } else {
        $res[] = $val;
    }
    return $res;
}


function getFormEditUrl($post_id)
{
    return admin_url('admin.php?page=wcpa-admin-ui#/form/' . $post_id);
}


function formattedDate($value, $dateFormat = false)
{
    return ($dateFormat ? (function_exists('wp_date') ? wp_date($dateFormat,
        strtotime($value), new \DateTimeZone('UTC')) : date($dateFormat, strtotime($value))) : $value);
}


/**
 * find field by elementID,
 *
 * @param $formData
 * @param $element_id
 * @param false $returnIndex whether to return section=>row>col indexes only or return field itself
 *
 * @param bool $isObject
 *
 * @return array|false|mixed
 * @since 5.0
 */

function findFieldById($formData, $element_id, $returnIndex = false, $isObject = false)
{
    $resp = false;
    foreach ($formData as $sectionKey => $section) {
        if ($isObject) {
            if (!isset($section->fields)) {
                continue;
            }
            foreach ($section->fields as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    if ($field->elementId === $element_id) {
                        $resp = [
                            'sectionKey' => $sectionKey,
                            'rowIndex' => $rowIndex,
                            'colIndex' => $colIndex,
                        ];
                        break;
                    }
                }
                if ($resp !== false) {
                    break;
                }
            }
        } else {
            if (!isset($section['fields'])) {
                continue;
            }
            foreach ($section['fields'] as $rowIndex => $row) {
                foreach ($row as $colIndex => $field) {
                    if (!isset($field['elementId'])) {
                        continue;
                    }
                    if ($field['elementId'] === $element_id) {
                        $resp = [
                            'sectionKey' => $sectionKey,
                            'rowIndex' => $rowIndex,
                            'colIndex' => $colIndex,
                        ];
                        break;
                    }
                }
                if ($resp !== false) {
                    break;
                }
            }
        }

        if ($resp !== false) {
            break;
        }
    }
    if ($returnIndex) {
        return $resp;
    }
    if ($resp == false) {
        return $resp;
    }

    if ($isObject) {
        return $formData->{$resp['sectionKey']}->fields[$resp['rowIndex']][$resp['colIndex']];
    }

    return $formData[$resp['sectionKey']]['fields'][$resp['rowIndex']][$resp['colIndex']];
}

function orderMetaValueForDb($label,$value){

    $format = Config::get_config('item_meta_format');
    return str_replace(['{label}','{value}'], [$label,$value], $format);
}
function refreshCaches($form_id = false, $product_id = false)
{
    delete_transient(WCPA_PRODUCTS_TRANSIENT_KEY);
    $ml = new ML();
    if($ml->is_active()){
        $langs = $ml->langList();
        foreach ($langs as $l) {
            delete_transient(WCPA_PRODUCTS_TRANSIENT_KEY . '_' . $l);
        }
    }
    if ($product_id) {
        delete_transient('wcpa_fields_' . $product_id);
        $status = delete_transients_with_prefix('wcpa_fields_'.$product_id);
        if(!$status && $ml->is_active()){
            foreach ($langs as $l){
                delete_transient('wcpa_fields_' . $product_id.'_'.$l);
                delete_transients_with_prefix('wcpa_fields_'.$product_id.'_'.$l);
            }
        }
    } elseif ($form_id) {
        $status = delete_transients_with_prefix('wcpa_fields_');
        if (!$status) {
            /** some servers stores transients differently, in  that case this bulk action doesnt work,
             * so refresh cache individually findig all products connected with this form id
             */

            $form = new Form();
            $ids = $form->products_listing($form_id, true);
            global $wpdb;
            $query = "SELECT
distinct object_id from $wpdb->term_relationships
 where term_taxonomy_id"
                . " in (select tr.term_taxonomy_id from $wpdb->term_relationships as tr left join $wpdb->term_taxonomy as tt on(tt.term_taxonomy_id=tr.term_taxonomy_id) where tr.object_id in (" . implode(',',
                    [$form_id]) . ")"
                . "and  tt.taxonomy = 'product_cat')";

            $pro_ids = $wpdb->get_col($query);
            $ids = array_unique(array_merge($pro_ids, $ids));
            foreach ($ids as $id) {
                delete_transient('wcpa_fields_' . $id);

                if( $ml->is_active()){
                    $status = delete_transients_with_prefix('wcpa_fields_'.$id);
                    if (!$status) {
                        foreach ($langs as $l){
                            delete_transient('wcpa_fields_' . $id.'_'.$l);
                        }
                    }

                }
            }
        }
    } else {
        $status = delete_transients_with_prefix('wcpa_fields_');
        if (!$status) {
            /** some servers stores transients differently, in  that case this bulk action doesnt work, so refresh cache individually */
            $form = new Form();
            $ids = $form->get_wcpaProducts();
            if (isset($ids['full'])) {
                foreach ($ids['full'] as $id) {
                    delete_transient('wcpa_fields_' . $id);
                    if( $ml->is_active()){

                        foreach ($langs as $l){
                            delete_transient('wcpa_fields_' . $id.'_'.$l);
                        }
                    }
                }
            }

        }
    }

    $status =  delete_transients_with_prefix('wcpa_settings_');
    if (!$status && $ml->is_active()) {

        foreach ($langs as $l){
            delete_transient( 'wcpa_settings_'.WCPA_VERSION.'_'.$l);
        }
    }
}

function get_transient_keys_with_prefix($prefix)
{
    global $wpdb;

    $prefix = $wpdb->esc_like('_transient_' . $prefix);
    $sql = "SELECT `option_name` FROM $wpdb->options WHERE `option_name` LIKE '%s'";
    $keys = $wpdb->get_results($wpdb->prepare($sql, $prefix . '%'), ARRAY_A);

    if (is_wp_error($keys)) {
        return [];
    }

    return array_map(function ($key) {
        // Remove '_transient_' from the option name.
        return ltrim($key['option_name'], '_transient_');
    }, $keys);
}

function delete_transients_with_prefix($prefix)
{
    $status = false;
    foreach (get_transient_keys_with_prefix($prefix) as $key) {
        $status = true;
        delete_transient($key);
    }

    return $status;
}


/**
 *  function to check a product has product form assigned
 * It can call Acowebs\WCPA\Free\has_form()
 *
 * @param $product_id
 *
 * @return string
 */
function has_form($product_id)
{
    $form = new Form();
    $wcpaProducts = $form->get_wcpaProducts();

    return in_array($product_id, $wcpaProducts['full']);
}

/**
 * Polyfill for `array_key_last()` function added in PHP 7.3.
 *
 * @param array $array An array.
 *
 * @return string|int|null The last key of array if the array
 *.                        is not empty; `null` otherwise.
 */
function array_key_last($array)
{

    if (empty($array)) {
        return null;
    }

    end($array);

    return key($array);
}

function array_key_first(array $array)
{

    foreach ($array as $key => $value) {
        return $key;
    }
}