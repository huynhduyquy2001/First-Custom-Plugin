<?php

namespace Acowebs\WCPA\Free;


class Product_Meta
{

    static $fieldKey = '_wcpa_product_meta';

    private static $_instance = null;

    public function __construct()
    {
        add_filter('woocommerce_product_data_tabs', array($this, 'add_my_custom_product_data_tab'), 101, 1);
        add_action('woocommerce_product_data_panels', array($this, 'add_my_custom_product_data_fields'));
        add_action('woocommerce_process_product_meta', array(
            $this,
            'woocommerce_process_product_meta_fields_save'
        ));

        /** show forms assigned to a product in the product list (backend) */
        add_filter('manage_product_posts_columns', array($this, 'manage_products_columns'), 20, 1);
        add_action('manage_product_posts_custom_column', array($this, 'manage_products_column'), 10, 2);


    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }







    public function manage_products_columns($columns)
    {
        return array_merge(array_slice($columns, 0, -2, true),
            ['wcpa_forms' => __('Product Forms', 'woo-custom-product-addons')], array_slice($columns, -2, null, true));
    }

    public function manage_products_column($column_name, $post_id)
    {
        if ($column_name == 'wcpa_forms') {
            $forms = get_post_meta($post_id, WCPA_PRODUCT_META_KEY, true);
            $link  = '';
            if (is_array($forms)) {
                foreach ($forms as $v) {
                    $link .= '<a href="'.getFormEditUrl($v).'" target="_blank">A'.get_the_title($v).'</a>, ';
                }
            }
            echo trim($link, ', ');
        }
    }

    public function woocommerce_process_product_meta_fields_save($post_id)
    {
        if (isset($_POST['wcpa_product_meta'])) {
            $jsonData = json_decode(html_entity_decode(stripslashes($_POST['wcpa_product_meta'])));
            if ($jsonData) {
                $this->save_meta($post_id,
                    [
                        'active' => (array) $jsonData->active,
                        'conf'   => (array) $jsonData->conf,
                    ]);
            }
        }
    }

    public function save_meta($post_id, $data)
    {
        $active     = $data['active'];
        $conf       = $data['conf'];
        $meta_field = [];
        if (is_array($active)) {
            foreach ($active as $v) {
                $form_id = (int) sanitize_text_field($v);
                if ( ! in_array(get_post_status($form_id), ['publish', 'draft'])) {
                    continue;
                }
                $meta_field[] = $form_id;

            }
        }
        update_post_meta($post_id, self::$fieldKey, $meta_field);

        if (isset($conf['wcpa_exclude_global_forms']) && $conf['wcpa_exclude_global_forms']) {
            update_post_meta($post_id, 'wcpa_exclude_global_forms', true);
        } else {
            update_post_meta($post_id, 'wcpa_exclude_global_forms', false);
        }


        refreshCaches(false,$post_id);
        return true;
    }

    public function get_forms($post_id)
    {
        $form       = new Form();
        $forms_list = $form->forms_list();
        $conf       = [];
        $meta_field = get_post_meta($post_id, self::$fieldKey, true);


        $conf['wcpa_exclude_global_forms'] = metaToBoolean(get_post_meta($post_id, 'wcpa_exclude_global_forms', true));

        return [
            'forms'  => $forms_list,
            'active' => $meta_field?array_values($meta_field):[],// ensure it has index starting from 0
            'conf'   => $conf,
            'order'  =>  []
        ];


    }

    public function add_my_custom_product_data_tab($product_data_tabs)
    {
        $product_data_tabs['wcpa_product-meta-tab'] = array(
            'label'    => __('Product Addons', 'my_text_domain'),
            'target'   => 'wcpa_product-meta-tab',
            'priority' => 90
        );

        return $product_data_tabs;
    }


    public function add_my_custom_product_data_fields()
    {
        global $post;
        $ml          = new ML();
        $meta_class  = '';
        $preventEdit = false;
        if ($ml->is_active() && $ml->current_language() !== false && ! $ml->is_default_lan()) {
            $meta_class  = 'wcpa_wpml_pro_meta';
            $preventEdit = true;
        }

        ?>

        <div id="wcpa_product-meta-tab" class="panel woocommerce_options_panel <?php
        echo $meta_class; ?>">
            <?php
            if ($ml->is_active() && $ml->current_language() !== false && ! $ml->is_default_lan()) {
                echo '<p class="wcpa_editor_message" >'.sprintf(__('You cannot manage form fields from this language. You can manage fields from base language only.
                All changes in base language will be synced with all translated version of product')).'</p>';
            }
            ?>
            <div id="wcpa_product_meta" class="<?php
            echo $preventEdit ? 'wcpa_ml_prevent' : '' ?>"
                 data-postId="<?php
                 echo $post->ID ?>"></div>

        </div>
        <?php
    }

    public function save_products_meta($form_id, $products_ids)
    {
        $response = ['status' => true];

        if (is_array($products_ids)) {
            foreach ($products_ids as $v) {
                $product_id = (int) sanitize_text_field($v);
                $meta_field = get_post_meta($product_id, self::$fieldKey, true);

                if (is_array($meta_field)) {
                    array_push($meta_field, $form_id);
                }
                else {
                    $meta_field = [$form_id];
                }
                array_unique($meta_field);
                update_post_meta($product_id, self::$fieldKey, $meta_field);
            }
        }
        return $response;
    }

    public function remove_products_meta($form_id, $product_id)
    {
        $response = ['status' => true];
        $meta_field = get_post_meta($product_id, self::$fieldKey, true);


        $meta_field_new = array_diff($meta_field, array($form_id));
        update_post_meta($product_id, self::$fieldKey, $meta_field_new);

        return $response;
    }

}
