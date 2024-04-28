<?php

namespace Acowebs\WCPA\Free;

use WP_Post;

class Product
{
    private $data;
    private $formConf;
    private $cart_error;
    private $has_custom_fields;
    private $relations = array();
    private $form;
    private $ml;
    private $options;
    private $price_dependency = array();

    public function __construct()
    {

        $this->ml = new ML();
    }



    public function get_fields($product_id = false)
    {
        if (($this->data !== null && !empty($this->data))) {
            return ['fields' => $this->data, 'config' => $this->formConf];
        }

        $cacheKey = $product_id;
        if($this->ml->is_active()){
            $cacheKey = $cacheKey.'_'.$this->ml->current_language();
        }
        if (false !== ($data = $this->getCache($cacheKey))) {
            return $data;
        }


        $this->form = new Form();
        $this->form->init(); // as it depends wpml


        $this->data = array();
        //      $this->cart_error = WCPA_Front_End::get_cart_error($product_id); // need to recheck

        $post_ids = $this->get_form_ids($product_id);

        $prod = wc_get_product($product_id);



        $scripts = [
            'datepicker' => false,

        ];

        $formulas = [];


//		if ( Config::get_config( 'form_loading_order_by_date' ) === true ) {
        $post_ids = array_filter($post_ids);//remove null/empty elements
        if (is_array($post_ids) && count($post_ids)) {
            $post_ids = get_posts(
                array(
                    'include' => $post_ids,
                    'fields' => 'ids',
                    'post_type' => Form::$CPT,
                    'lang' => '', // deactivates the Polylang filter
                    'posts_per_page' => -1,
                    'orderby' => 'post__in'
                )
            );
        }
//		}

//        $post_ids = $this->re_order_forms($post_ids, $product_id);

        foreach ($post_ids as $id) {
            if (get_post_status(
                    $id
                ) == 'publish') {  // need to check if this check needed as post_ids will be published posts only
                $json_encoded = $this->form->get_form_meta_data($id);

            




                $form_rules = [

                ];


                /**
                 * @var keep track of connected global forms, remove if already imported to avoide infinite loop
                 */

                $rowsToResetIndex = [];
                if ($json_encoded && is_object($json_encoded)) {

                    foreach ($json_encoded as $sectionKey => $section) {
                        if (!isset($rowsToResetIndex[$sectionKey])) {
                            $rowsToResetIndex[$sectionKey] = [];
                        }

                        /**
                         * Form rules&form_id will be taken from the parent form only, will not be considering form rules from other global form fields added in this form,
                         */
                        $section->extra->form_id = $id;
                        $section->extra->form_rules = $form_rules;


                        $this->process_cl($section->extra, $prod);

                        foreach ($section->fields as $rowIndex => $row) {
                            foreach ($row as $colIndex => $field) {
                                if (isset($field->active) && $field->active === false) {
                                    //TODO remove empty row, or section
                                    unset($section->fields[$rowIndex][$colIndex]);
                                    $rowsToResetIndex[$sectionKey][] = $rowIndex;
                                    continue;
                                }


                                $this->process_cl($field, $prod);
                                $this->processFields($field, $section->extra->form_id);

                                $this->findScriptsRequired($field, $scripts);


                            }

                        }


                    }

                    // check for external forms


                    //   $json_encoded = $this->appendGlobalForm($json_encoded);
                    /**
                     * resetting array index when an column removed from row
                     * @var  $rowIndexes
                     */
                    foreach ($rowsToResetIndex as $sec => $rowIndexes) {
                        $resetSecFieldsIndex = false;
                        foreach ($rowIndexes as $rowIndex) {
                            if (isset($json_encoded->{$sec}->fields[$rowIndex])) {
                                $json_encoded->{$sec}->fields[$rowIndex] = array_values(
                                    $json_encoded->{$sec}->fields[$rowIndex]
                                );
                                if (count($json_encoded->{$sec}->fields[$rowIndex]) == 0) {
                                    unset($json_encoded->{$sec}->fields[$rowIndex]);
                                    $resetSecFieldsIndex = true;
                                }
                            }
                        }
                        if ($resetSecFieldsIndex) {
                            $json_encoded->{$sec}->fields = array_values($json_encoded->{$sec}->fields);
                        }
                    }
                    $this->data = array_merge($this->data, (array)$json_encoded);
                }
            }
        }

        $totalFieldsCount=0;
        if ($this->data !== null) {
            $this->data = (object)$this->data;
            $totalFieldsCount =    $this->map_dependencies();
        }


        $data = [
            'fields' => $this->data,
            'config' => $this->formConf,
            'scripts' => $scripts,
            'fieldsCount' => $totalFieldsCount

        ];
        $this->setCache($cacheKey, $data);

        return $data;

    }

    public function getCache($product_id)
    {
        return get_transient('wcpa_fields_' . $product_id);
        //TODO clear cache one upgrading to pro
    }

    /**
     * get forms assigned to product by product id
     *
     * @param $product_id
     *
     * @return array|int|int[]|mixed|void|WP_Post[]
     */
    public function get_form_ids($product_id)
    {
        if ($this->ml->is_active()) {
            $product_id = $this->ml->lang_object_ids($product_id, 'post', true);
        }
        $key_1_value = get_post_meta($product_id, 'wcpa_exclude_global_forms', true);
        $form_ids = array();

        if (empty($key_1_value)) {
            $terms = wp_get_object_terms(
                $product_id,
                'product_cat',
                array(
                    'orderby' => 'name',
                    'order' => 'ASC',
                    'fields' => 'ids',
                )
            );
//            if ($this->ml->is_active()) {
//                $terms = $this->ml->lang_object_ids($terms, 'product_cat', true);
//                $currentLag = $this->ml->current_language();
//                $this->ml->setCurrentLang($this->ml->default_language());
//                $form_ids = get_posts(
//                    array(
//                        'tax_query' => array(
//                            array(
//                                'taxonomy' => 'product_cat',
//                                'field' => 'ids',
//                                'include_children' => false,
//                                'terms' => $terms,
//                            ),
//                        ),
//                        'fields' => 'ids',
//                        'post_type' => Form::$CPT,
//                        'posts_per_page' => -1,
//                        'lang' => $this->ml->default_language()
//
//                    )
//                );
//                $this->ml->setCurrentLang($currentLag);
//            } else {
            if ($this->ml->is_active()) {
                $currentLag = $this->ml->current_language();
                $this->ml->setCurrentLang($this->ml->default_language());
                $form_ids = get_posts(
                array(
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field' => 'ids',
                            'include_children' => false,
                                'terms' => $terms,
                            ),
                        ),
                                    'fields' => 'ids',
                        'post_type' => Form::$CPT,
                        'posts_per_page' => -1

                                )
                );
                $this->ml->setCurrentLang($currentLag);
            } else {
                $form_ids = get_posts(
                    array(
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'product_cat',
                                'field' => 'ids',
                                'include_children' => false,
                                'terms' => $terms,
                        ),
                    ),
                    'fields' => 'ids',
                    'post_type' => Form::$CPT,
                        'posts_per_page' => -1

                )
            );
        }

//            }
        }
        $form_ids_set2 = maybe_unserialize(get_post_meta($product_id, WCPA_PRODUCT_META_KEY, true));
        if($form_ids_set2 && is_array($form_ids_set2)){
            /** reorder ids based on form created date */
            /** @since 5.0.14 **/
            /* Earlier form was loaded based on the order the user checks the form in backend */
            if ($this->ml->is_active()) {
                $currentLag = $this->ml->current_language();
                $this->ml->setCurrentLang($this->ml->default_language());
                $form_ids_set2 = get_posts(
                    array(
                        'include' => $form_ids_set2,
                        'fields' => 'ids',
                        'post_type' => Form::$CPT,
                        'lang' => '', // deactivates the Polylang filter
                        'posts_per_page' => -1,
                    )
                );
                $this->ml->setCurrentLang($currentLag);

            }else{
                $form_ids_set2 = get_posts(
                    array(
                        'include' => $form_ids_set2,
                        'fields' => 'ids',
                        'post_type' => Form::$CPT,
                        'lang' => '', // deactivates the Polylang filter
                        'posts_per_page' => -1,
                    )
                );
            }

        }


        $form_ids = $this->re_order_and_merge_forms($form_ids, $form_ids_set2, $product_id);
//
//        if ($form_ids_set2 && is_array($form_ids_set2)) {
//            $form_ids_set2 = $this->re_order_forms($form_ids_set2, $product_id);
//            if (Config::get_config('append_global_form') == 'at_end') {
//                $form_ids = array_unique(array_merge($form_ids_set2, $form_ids));
//            } else {
//                $form_ids = array_unique(array_merge($form_ids, $form_ids_set2));
//            }
//
//        }
//        $form_ids = $this->re_order_forms($form_ids, $product_id);

        if ($this->ml->is_active()) {
            $form_ids = $this->ml->lang_object_ids($form_ids, 'post');
        }

        return $form_ids;
    }

    /**
     * @param $ids
     * @param $p_id
     *
     * @return array
     */
    public function re_order_and_merge_forms($idsByCats, $directIds, $p_id)
    {

        $form_order = get_post_meta($p_id, 'wcpa_product_meta_order', true);
        if (!is_array($directIds)) {
            $directIds = [];
        }
        if ($idsByCats && is_array($idsByCats)) {
            if (Config::get_config('append_global_form') == 'at_end') {
                $directIds = array_unique(array_merge($directIds, $idsByCats));
            } else {
                $directIds = array_unique(array_merge($idsByCats, $directIds));
            }

        }

        $bigNum = 99999;
        if ($form_order && is_array($form_order)) {
            $ids_new = array();
            $form_order_new = array();
            foreach ($directIds as $id) {
                if (isset($form_order[$id])) {
                    $form_order_new[$id] = $form_order[$id];
                } else if (in_array($id, $idsByCats) && isset($form_order[0])) {// order for forms assigned by category
                    $form_order_new[$id] = $form_order[0];
                } else {
                    $form_order_new[$id] = $bigNum++;
                }
            }
//            arsort($form_order_new);
//            $directIds = array_keys($form_order_new);
            $directIds = array_keys($form_order_new);
            // Sort the keys based on the values
            usort($directIds, function ($a, $b) use ($form_order_new) {
                if ($form_order_new[$a] == $form_order_new[$b]) {
                    return ($a < $b) ? 1 : -1;
                }
                return ($form_order_new[$a] > $form_order_new[$b]) ? 1 : -1;
            });


//            array_multisort($order_new_values, SORT_ASC,SORT_NUMERIC , $directIds);
//
//            foreach ($form_order_new as $form_id => $order) {
//                $index = array_search($form_id, $directIds);
//                if ($index !== false) {
//                    unset($directIds[$index]); // remove item at index 0
//                    $directIds = array_values($directIds); // 'reindex' array
//                    $length = count($directIds);
//                    if ($order <= 0) {
//                        $pos = 0;
//                    } elseif ($order > $length) {
//                        $pos = $length;
//                    } else {
//                        $pos = $order - 1;
//                    }
//
//                    array_splice($directIds, $pos, 0, $form_id);
//                }
//            }
        }


        return $directIds;


    }

    public function process_cl($v, $prod)
    {
        if (isset($v->enableCl) && $v->enableCl && isset($v->relations) && is_array($v->relations)) {
            foreach ($v->relations as $val) {
                foreach ($val->rules as $k) {
                    if (!empty($k->rules->cl_field)) {

                        if (!isset($this->relations[$k->rules->cl_field])) {
                            $this->relations[$k->rules->cl_field] = array();
                        }


                        $this->relations[$k->rules->cl_field][] = (isset($v->elementId) ? $v->elementId : false);
                    }
                }
            }
        }
    }


    public function processFields($field, $form_id)
    {

        if (isset($field->description)) {
            $field->description = nl2br(trim($field->description));
        }


    }

    public function findScriptsRequired($field, &$scripts)
    {

        if (!$scripts['datepicker']
            && (in_array($field->type, ['date']))
            && isset($field->picker_type) && $field->picker_type !== 'basic') {
            $scripts['datepicker'] = true;
        }

    }

    public function map_dependencies()
    {
        $totalFieldsCount=0;
        if ($this->data && $this->data !== null) {
            foreach ($this->data as $sectionKey => $section) {
                foreach ($section->fields as $rowIndex => $row) {
                    foreach ($row as $colIndex => $field) {


                        if (isset($this->relations[$field->elementId])) {
                            $field->cl_dependency = $this->relations[$field->elementId];
                        } else {
                            $field->cl_dependency = false;
                        }
                        $totalFieldsCount++;
                    }
                }
            }
        }
        return  $totalFieldsCount;
    }

    public function setCache($product_id, $data)
    {
        set_transient('wcpa_fields_' . $product_id, $data, 24 * HOUR_IN_SECONDS);
    }
}
