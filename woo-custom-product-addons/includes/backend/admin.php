<?php

namespace Acowebs\WCPA\Free;

class Admin
{
    private $assets_url;
    private $version;
    private $token;
    private $isWooActive;
    private $hook_suffix=[];

    /**
     * Admin constructor.
     *
     * @param $isWooActive
     */
    public function __construct($isWooActive)
    {
        $this->isWooActive = $isWooActive;
        add_action('admin_menu', [$this, 'add_menu'], 10);

        $this->assets_url = WCPA_ASSETS_URL;
        $this->version = WCPA_VERSION;
        $this->token = WCPA_TOKEN;

        $form = new Form();
        $form->init();



        if ($isWooActive) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
            add_action('current_screen', array($this, 'this_screen'));

            add_action('save_post', array($this, 'delete_transient'), 1);
            add_action('edited_term', array($this, 'delete_transient'));
            add_action('delete_term', array($this, 'delete_transient'));
            add_action('created_term', array($this, 'delete_transient'));
            Product_Meta::instance();
        } else {
            add_action('admin_notices', array($this, 'notice_need_woocommerce'));
        }
        register_deactivation_hook(WCPA_FILE, array($this, 'deactivation'));

        $plugin = plugin_basename(WCPA_FILE);
        add_filter("plugin_action_links_$plugin", array($this, 'add_settings_link'));

        add_action('admin_footer', array($this, 'wcpa_deactivation_form'));
        add_action('admin_footer', array($this, 'popup_container'));
    }

    public function popup_container()
    {
        echo '<div id="wcpa_order_popup"></div>';
    }

    /*
    * Deactivation form
    */
    public function wcpa_deactivation_form() {
        $currentScreen = get_current_screen();
        $screenID = $currentScreen->id;
        if ( $screenID == 'plugins' ) {
            $view = '<div id="wcpa-aco-survey-form-wrap"><div id="wcpa-aco-survey-form">
            <p>If you have a moment, please let us know why you are deactivating this plugin. All submissions are anonymous and we only use this feedback for improving our plugin.</p>
            <form method="POST">
                <input name="Plugin" type="hidden" placeholder="Plugin" value="'.WCPA_TOKEN.'" required>
                <input name="Date" type="hidden" placeholder="Date" value="'.date("m/d/Y").'" required>
                <input name="Website" type="hidden" placeholder="Website" value="'.get_site_url().'" required>
                <input name="Title" type="hidden" placeholder="Title" value="'.get_bloginfo( 'name' ).'" required>
                <input name="Version" type="hidden" placeholder="Version" value="'.WCPA_VERSION.'" required>
                <input type="radio" id="wcpa-better" name="Reason" value="I found a better plugin">
                <label for="wcpa-better">I found a better plugin</label><br>
                <input type="radio" id="wcpa-temporarily" name="Reason" value="I\'m only deactivating temporarily">
                <label for="wcpa-temporarily">I\'m only deactivating temporarily</label><br>
                <input type="radio" id="wcpa-notneeded" name="Reason" value="I no longer need the plugin">
                <label for="wcpa-notneeded">I no longer need the plugin</label><br>
                <input type="radio" id="wcpa-short" name="Reason" value="I only needed the plugin for a short period">
                <label for="wcpa-short">I only needed the plugin for a short period</label><br>
                <input type="radio" id="wcpa-upgrade" name="Reason" value="Upgrading to PRO version">
                <label for="wcpa-upgrade">Upgrading to PRO version</label><br>
                <input type="radio" id="wcpa-requirement" name="Reason" value="Plugin doesn\'t meets my requirement">
                <label for="wcpa-requirement">Plugin doesn\'t meets my requirement</label><br>
                <input type="radio" id="wcpa-broke" name="Reason" value="Plugin broke my site">
                <label for="wcpa-broke">Plugin broke my site</label><br>
                <input type="radio" id="wcpa-stopped" name="Reason" value="Plugin suddenly stopped working">
                <label for="wcpa-stopped">Plugin suddenly stopped working</label><br>
                <input type="radio" id="wcpa-bug" name="Reason" value="I found a bug">
                <label for="wcpa-bug">I found a bug</label><br>
                <input type="radio" id="wcpa-other" name="Reason" value="Other">
                <label for="wcpa-other">Other</label><br>
                <p id="wcpa-aco-error"></p>
                <div class="wcpa-aco-comments" style="display:none;">
                    <textarea type="text" name="Comments" placeholder="Please specify" rows="2"></textarea>
                    <p>For support queries <a href="https://support.acowebs.com/portal/en/newticket?departmentId=361181000000006907&layoutId=361181000000074011" target="_blank">Submit Ticket</a></p>
                </div>
                <button type="submit" class="aco_button" id="wcpa-aco_deactivate">Submit & Deactivate</button>
                <a href="#" class="aco_button" id="wcpa-aco_cancel">Cancel</a>
                <a href="#" class="aco_button" id="wcpa-aco_skip">Skip & Deactivate</a>
            </form></div></div>';
            echo $view;
        } ?>
        <style>
            #wcpa-aco-survey-form-wrap{ display: none;position: absolute;top: 0px;bottom: 0px;left: 0px;right: 0px;z-index: 10000;background: rgb(0 0 0 / 63%); } #wcpa-aco-survey-form{ display:none;margin-top: 15px;position: fixed;text-align: left;width: 40%;max-width: 600px;min-width:350px;z-index: 100;top: 50%;left: 50%;transform: translate(-50%, -50%);background: rgba(255,255,255,1);padding: 35px;border-radius: 6px;border: 2px solid #fff;font-size: 14px;line-height: 24px;outline: none;}#wcpa-aco-survey-form p{font-size: 14px;line-height: 24px;padding-bottom:20px;margin: 0;} #wcpa-aco-survey-form .aco_button { margin: 25px 5px 10px 0px; height: 42px;border-radius: 6px;background-color: #1eb5ff;border: none;padding: 0 36px;color: #fff;outline: none;cursor: pointer;font-size: 15px;font-weight: 600;letter-spacing: 0.1px;color: #ffffff;margin-left: 0 !important;position: relative;display: inline-block;text-decoration: none;line-height: 42px;} #wcpa-aco-survey-form .aco_button#wcpa-aco_deactivate{background: #fff;border: solid 1px rgba(88,115,149,0.5);color: #a3b2c5;} #wcpa-aco-survey-form .aco_button#wcpa-aco_skip{background: #fff;border: none;color: #a3b2c5;padding: 0px 15px;float:right;}#wcpa-aco-survey-form .wcpa-aco-comments{position: relative;}#wcpa-aco-survey-form .wcpa-aco-comments p{ position: absolute; top: -24px; right: 0px; font-size: 14px; padding: 0px; margin: 0px;} #wcpa-aco-survey-form .wcpa-aco-comments p a{text-decoration:none;}#wcpa-aco-survey-form .wcpa-aco-comments textarea{background: #fff;border: solid 1px rgba(88,115,149,0.5);width: 100%;line-height: 30px;resize:none;margin: 10px 0 0 0;} #wcpa-aco-survey-form p#wcpa-aco-error{margin-top: 10px;padding: 0px;font-size: 13px;color: #ea6464;}
        </style>
    <?php }

    public function add_settings_link($links)
    {
        $products = '<a href="' . admin_url('/admin.php?page=wcpa-admin-ui#') . '">' . __('Create Forms', 'woo-custom-product-addons') . '</a>';
        $support = '<a href="https://support.acowebs.com/portal/en/newticket?departmentId=361181000000006907&layoutId=361181000000074011" target="_blank">' . __('Contact Us', 'woo-custom-product-addons') . '</a>';

        array_push($links, $products);
        array_push($links, $support);
        return $links;

    }



    public function delete_transient($arg = false)
    {
        if ($arg) {
            in_array(get_post_type($arg), ['product', Form::$CPT]) && delete_transient(WCPA_PRODUCTS_TRANSIENT_KEY);
        } else {
            delete_transient(WCPA_PRODUCTS_TRANSIENT_KEY);
        }
    }

    public function deactivation()
    {
        Cron::clear();
    }

    /**
     * Load admin Javascript.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function admin_enqueue_scripts($hook = '')
    {
        wp_enqueue_script('jquery');

        wp_enqueue_media();


        if (!isset($this->hook_suffix) || empty($this->hook_suffix)) {
            return;
        }


        $screen = get_current_screen();

        if ( $screen->id == 'plugins' ) {
            wp_enqueue_script($this->token . '-survey',
                esc_url($this->assets_url) . 'deactivation-survey.js', array('jquery'),
                $this->version, true);

        }
        $ml = new ML();
        if (in_array($screen->id, $this->hook_suffix)) {

            $scripts = [
                'datepicker',
            ];
            foreach ($scripts as $tag) {
                wp_enqueue_script($this->token . '-' . $tag);
            }

            wp_enqueue_script($this->token . '-front');

            $form = new Form();
            $settings = new Settings;
            $formsList = $form->forms_list();
            add_filter('wpml_admin_language_switcher_items', [$ml, 'modify_lang_menu']);

            wp_enqueue_script('wp-i18n');

            $globalVars = array(

                'forms_url' => admin_url('/admin.php?page=wcpa-admin-ui'),
                'api_nonce' => wp_create_nonce('wp_rest'),
                'root' => ($ml->is_active() &&  $ml->current_language()=='all')?str_replace('/all','',rest_url($this->token . '/admin/')):rest_url($this->token . '/admin/'),
                'assets_url' => $this->assets_url,
                'default_image_url' => $this->assets_url . 'images/default-image.jpg',
                'screen_options' => $settings->get_screen_options(),

                'prod_cats' => $this->get_taxonomy_hierarchy('product_cat'),
                'isMlActive' => $ml->is_active(),
                'formsList' => $formsList,
                'date_format' => __(get_option('date_format'), 'woo-custom-product-addons'),
                'time_format' => __(get_option('time_format'), 'woo-custom-product-addons'),
                'strings' => array(

                ),
                'ml' => $ml->is_active() ? [
                    'langList' => $ml->lang_list(),
                    'currentLang' => $ml->current_language(),
                    'defaultLang' => $ml->default_language(),
                    'isDefault' => $ml->is_default_lan() ? $ml->is_default_lan() : (($ml->current_language() === 'all') ? true : false)
                ] : false
            );


            wp_enqueue_script($this->token . '-backend',
                esc_url($this->assets_url) . 'js/backend/main.js', array('wp-i18n'),
                $this->version, true);


            wp_set_script_translations($this->token . '-backend', 'woo-custom-product-addons',
                plugin_dir_path(WCPA_FILE) . '/languages/');

            wp_localize_script($this->token . '-backend', $this->token . '_object', $globalVars);
        }
        if ($screen->id == 'product') {
            wp_enqueue_script($this->token . '-product', esc_url($this->assets_url) . 'js/backend/product.js',
                array('wp-i18n'), $this->version, true);
            wp_set_script_translations($this->token . '-product', 'woo-custom-product-addons',
                plugin_dir_path(WCPA_FILE) . '/languages/');

            wp_localize_script($this->token . '-product', $this->token . '_object', array(
                    'api_nonce' => wp_create_nonce('wp_rest'),
                    'root' => rest_url($this->token . '/admin/'),
                    'assets_url' => $this->assets_url,
                    'isMlActive' => $ml->is_active(),
                    'ml' => $ml->is_active() ? [
                        'langList' => $ml->lang_list(),
                        'currentLang' => $ml->current_language(),
                        'defaultLang' => $ml->default_language(),
                        'isDefault' => $ml->is_default_lan() ? $ml->is_default_lan() : (($ml->current_language() === 'all') ? true : false)
                    ] : false
                )
            );
        }
        if (!in_array($screen->id, $this->hook_suffix) && $screen->id!=='product') {
//        if ($screen->id == 'shop_order' || $screen->id == 'shop_subscription') {
            wp_enqueue_script($this->token . '-order', esc_url($this->assets_url) . 'js/backend/order.js',
                array('wp-i18n'), $this->version, true);
            wp_set_script_translations($this->token . '-product', 'woo-custom-product-addons',
                plugin_dir_path(WCPA_FILE) . '/languages/');



            wp_localize_script($this->token . '-order', $this->token . '_object', array(
                    'api_nonce' => wp_create_nonce('wp_rest'),
                    'root' => rest_url($this->token . '/admin/'),
                    'assets_url' => $this->assets_url
                )
            );
        }
    }

    public function get_taxonomy_hierarchy($taxonomy, $parent = 0)
    {
        // only 1 taxonomy
        $taxonomy = is_array($taxonomy) ? array_shift($taxonomy) : $taxonomy;
        // get all direct decendants of the $parent
        $terms = get_terms($taxonomy, array('parent' => $parent, 'hide_empty' => false));
        // prepare a new array.  these are the children of $parent
        // we'll ultimately copy all the $terms into this new array, but only after they
        // find their own children
        $children = array();
        // go through all the direct decendants of $parent, and gather their children
        foreach ($terms as $term) {
            // recurse to get the direct decendants of "this" term
            $term->children = $this->get_taxonomy_hierarchy($taxonomy, $term->term_id);
            // add the term to our new array
            $children[] = $term;
//            $children[ $term->term_id ] = $term;
        }

        // send the results back to the caller
        return $children;
    }

    function this_screen()
    {
        $current_screen = get_current_screen();

        if ($current_screen->post_type === Form::$CPT) {
            $ml = new ML();

            if ($ml->is_active()) {
                if ($ml->is_default_lan() || $ml->is_all_lan()) {
                    if (isset($_GET['post'])) {
                        $post_id = $_GET['post'];

                        return $this->redirectMl($post_id);
                    }
                } else {
                    if (isset($_GET['post'])) {
                        $post_id = $_GET['post'];
                        $lang_code = $ml->current_language();
                        $this->redirectMl($post_id, $lang_code);
                    } elseif ($ml->is_duplicating()) {
                        $from_post_id = $ml->from_post_id();
                        $new_lang_code = $ml->get_new_language();
                        // Create post object
                        $my_post = array(
                            'post_title' => '',
                            'post_type' => Form::$CPT,
                            'post_status' => 'draft',
                        );
// Insert the post into the database
                        $new_post_id = wp_insert_post($my_post);
                        $fb_json_value = $ml->default_fb_meta();
                        update_post_meta($new_post_id, Form::$META_KEY_2, $fb_json_value);
//                        pll_set_post_language($new_post_id, $lang_code);
                        $fromPostCode = \pll_get_post_language($from_post_id, 'slug');

                        $translations = \pll_get_post_translations($from_post_id);
                        $translations[$new_lang_code] = $new_post_id;
                        \pll_save_post_translations($translations);

                        if ($from_post_id) {
                            return $this->redirectMl($new_post_id, $new_lang_code);
                        }
                    }
                }
            }
                echo '<p style="text-align: center;
    font-size: 18px;
    color: #000000;
    margin-top: 10%;" >' . sprintf(__('This menu has been changed in new version of Product addon plugin,
                 Please go to menu <a href="%s" />Product Addons</a> on left sidebar', 'woo-custom-product-addons'), admin_url('admin.php?page=wcpa-admin-ui#/')) . '</p>';

                exit();



        }
    }

    function redirectMl($post_id, $lang = false)
    {
        if ($lang) {
            $url = 'admin.php?lang=' . $lang . '&page=wcpa-admin-ui#/form/' . $post_id . '/';
        } else {
            $url = 'admin.php?page=wcpa-admin-ui#/form/' . $post_id . '/';
        }
        $url = admin_url($url);
        wp_redirect($url);
    }

    /**
     * Load admin CSS.
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function admin_enqueue_styles($hook = '')
    {
        if (!isset($this->hook_suffix) || empty($this->hook_suffix)) {
            return;
        }
        $screen = get_current_screen();
        wp_register_style($this->token . '-common',
            esc_url($this->assets_url) . 'css/backend/common.css', array(), $this->version);
        wp_enqueue_style($this->token . '-common');

        if (in_array($screen->id, $this->hook_suffix)) {
            wp_register_style($this->token . '-admin',
                esc_url($this->assets_url) . 'css/backend/main.css', array(), $this->version);
            wp_enqueue_style($this->token . '-admin');
        }
        if ($screen->id == 'product') {
            wp_register_style($this->token . '-product', esc_url($this->assets_url) . 'css/backend/product.css', array(),
                $this->version);
            wp_enqueue_style($this->token . '-product');
        }
//        if ($screen->id == 'shop_order' || $screen->id == 'shop_subscription') {
        if (!in_array($screen->id, $this->hook_suffix) && $screen->id!=='product') {
            wp_register_style($this->token . '-order', esc_url($this->assets_url) . 'css/backend/order.css', array(),
                $this->version);
            wp_enqueue_style($this->token . '-order');
        }
    }

    public function add_menu()
    {
        $this->hook_suffix[] = add_menu_page(
            __('Custom Product Addons', 'woo-custom-product-addons'),
            __('Product Addons', 'woo-custom-product-addons'),
            'manage_woocommerce',
            $this->token . '-admin-ui',
            array($this, 'adminUi'),
            'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGcgY2xpcC1wYXRoPSJ1cmwoI2NsaXAwXzEzODJfMTg1NSkiPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTguNzYxNzIgM0g0Ljc2MTcyQzQuNzYxNzIgMS44OTcgNS42NTg3MiAxIDYuNzYxNzIgMUM3Ljg2NDcyIDEgOC43NjE3MiAxLjg5NyA4Ljc2MTcyIDNNMy43NjE3MiAzSDEuMjYxNzJDMC45ODUyMTkgMyAwLjc2MTcxOSAzLjIyNCAwLjc2MTcxOSAzLjVWMTUuNUMwLjc2MTcxOSAxNS43NzYgMC45ODUyMTkgMTYgMS4yNjE3MiAxNkgxMi4yNjE3QzEyLjUzODIgMTYgMTIuNzYxNyAxNS43NzYgMTIuNzYxNyAxNS41VjEzLjEyMUwxMi4zMjIyIDEzLjU2MDVDMTIuMDM5MiAxMy44NDQgMTEuNjYyMiAxNCAxMS4yNjE3IDE0SDkuNzYxNzJDOC45MzQ3MiAxNCA4LjI2MTcyIDEzLjMyNyA4LjI2MTcyIDEyLjVWMTFDOC4yNjE3MiAxMC41OTk1IDguNDE4MjIgMTAuMjIyNSA4LjcwMTIyIDkuOTM5NUwxMi43MDEyIDUuOTM5NUMxMi43MTk3IDUuOTIwNSAxMi43NDIyIDUuOTA3NSAxMi43NjE3IDUuODg5NVYzLjVDMTIuNzYxNyAzLjIyNCAxMi41MzgyIDMgMTIuMjYxNyAzSDkuNzYxNzJDOS43NjE3MiAxLjM0NTUgOC40MTYyMiAwIDYuNzYxNzIgMEM1LjEwNzIyIDAgMy43NjE3MiAxLjM0NTUgMy43NjE3MiAzWiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMy4zMzE4IDYuODE3MDdMOS4yMjkzNiAxMC45MTk2QzkuMTMyOTUgMTEuMDE2IDkuMDc5MSAxMS4xNDYyIDkuMDc5MSAxMS4yODIxVjEyLjgyMDVDOS4wNzkxIDEzLjEwMzYgOS4zMDgzMyAxMy4zMzM0IDkuNTkxOTEgMTMuMzMzNEgxMS4xMzAzQzExLjI2NjggMTMuMzMzNCAxMS4zOTcgMTMuMjc5NSAxMS40OTI5IDEzLjE4MzFMMTUuNTk1NCA5LjA4MDYxQzE1Ljc5NTkgOC44ODAxMSAxNS43OTU5IDguNTU2MDEgMTUuNTk1NCA4LjM1NTVMMTQuMDU3IDYuODE3MDdDMTMuODU2NCA2LjYxNjU2IDEzLjUzMjMgNi42MTY1NiAxMy4zMzE4IDYuODE3MDciIGZpbGw9IndoaXRlIi8+CjwvZz4KPGRlZnM+CjxjbGlwUGF0aCBpZD0iY2xpcDBfMTM4Ml8xODU1Ij4KPHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjE2IiBmaWxsPSJ3aGl0ZSIvPgo8L2NsaXBQYXRoPgo8L2RlZnM+Cjwvc3ZnPgo=',
            25
        );
        $this->hook_suffix[] = add_submenu_page(
            $this->token . '-admin-ui/',
            __('Forms', 'woo-custom-product-addons'),
            __('Forms', 'woo-custom-product-addons'),
            'manage_woocommerce',
            $this->token . '-admin-ui',
            array($this, 'adminUi')
        );
        $this->hook_suffix[] = add_submenu_page(
            $this->token . '-admin-ui/',
            __('Designs', 'woo-custom-product-addons'),
            __('Designs', 'woo-custom-product-addons'),
            'manage_woocommerce',
            $this->token . '-admin-ui#/designs',
            array($this, 'adminUi')
        );
//        $this->hook_suffix[] = add_submenu_page(
//            $this->token . '-admin-ui/',
//            __('Options Lists', 'woo-custom-product-addons'),
//            __('Options Lists', 'woo-custom-product-addons'),
//            'manage_woocommerce',
//            $this->token . '-admin-ui#/options',
//            array($this, 'adminUi')
//        );
        $this->hook_suffix[] = add_submenu_page(
            $this->token . '-admin-ui/',
            __('Settings', 'woo-custom-product-addons'),
            __('Settings', 'woo-custom-product-addons'),
            'manage_woocommerce',
            $this->token . '-admin-ui#/settings',
            array($this, 'adminUi')
        );
        $this->hook_suffix[] = add_submenu_page(
            $this->token . '-admin-ui/',
            __('Support', 'woo-custom-product-addons'),
            __('Support', 'woo-custom-product-addons'),
            'manage_woocommerce',
            $this->token . '-write-us',
            array($this, 'writeUs')
        );
    }
    public function writeUs()
    {
        wp_redirect('https://support.acowebs.com/portal/en/newticket?departmentId=361181000000006907&layoutId=361181000000074011');
        exit;
    }
    /**
     * Calling view function for admin page components
     */
    public function adminUi()
    {
        if ($this->isWooActive) {
            echo(
                '<div id="' . $this->token . '_ui_root">
            <div class="' . $this->token . '_loader"><h1>' . __('Acowebs Custom Product Addon',
                    'woo-custom-product-addons') . '</h1><p>' . __('Plugin is loading Please wait for a while..',
                    'woo-custom-product-addons') . '</p></div>
            </div>'
            );
        } else {
            echo(
                '<div id="' . $this->token . '_ui_root">Product addon need WooCommerce to function</div>'
            );
        }
    }

    /**
     * WooCommerce not active notice.
     * @access  public
     * @return string Fallack notice.
     */
    public function notice_need_woocommerce()
    {
        $error = sprintf(__(WCPA_PLUGIN_NAME . ' requires %sWooCommerce%s to be installed & activated!',
            'woo-custom-product-addons'), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>');
        $message = '<div class="error"><p>' . $error . '</p></div>';
        echo $message;
    }
}
