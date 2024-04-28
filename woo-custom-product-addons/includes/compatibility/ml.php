<?php

namespace Acowebs\WCPA\Free;

if ( ! defined('ABSPATH')) {
    exit;
}

class ML
{

    /**
     * @var    object
     * @access  private
     * @since    1.0.0
     */
    private static $_instance = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;
    public $default_lang;
    public $current_lang;
    private $_active = false;

    public function __construct()
    {
        if (class_exists('SitePress')) {
            $this->_active      = 'wpml';
            $this->default_lang = apply_filters('wpml_default_language', null);
            $this->current_lang = apply_filters('wpml_current_language', null);
        } elseif (function_exists('pll_the_languages')) {
            $this->_active      = 'polylang';
            $this->default_lang = \pll_default_language();
            $this->current_lang = \pll_current_language();
        }
        if ($this->current_lang == null) {
            $this->current_lang = apply_filters('wcpa_set_default_lang', $this->current_lang, $this->_active);
        }
    }

    /**
     *
     *
     * Ensures only one instance of WCPA is loaded or can be loaded.
     *
     * @return Main WCPA instance
     * @see WordPress_Plugin_Template()
     * @since 1.0.0
     * @static
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function modify_lang_menu($languages_links)
    {
      //  unset($languages_links['all']);

        return $languages_links;
    }

    public function is_active()
    {
        return $this->_active !== false;
    }

    public function is_new_post($post_id)
    {
        if ($this->base_form($post_id) === 0) {
            if ($this->_active === 'wpml') {
                isset($_GET['trid']) ? false : true;
            } elseif ($this->_active === 'polylang') {
                return isset($_GET['from_post']) ? false : true;
            }
        }

        return false;
    }

    public function base_form($post_id)
    {
        if ($this->_active === 'wpml') {
            $base_id = $this->getTransById($post_id, $this->default_lang);
            $base_id = $base_id ? $base_id : $post_id;
//            $base_id = apply_filters('wpml_original_element_id', null, $post_id);
        } elseif ($this->_active === 'polylang') {
            $base_id = pll_get_post($post_id, pll_default_language());
        }

        return (int) $base_id;
    }

    public function getTransById($post_id, $lang)
    {
        $trid = apply_filters('wpml_element_trid', null, $post_id);
        $list = apply_filters('wpml_get_element_translations', null, $trid);
        foreach ($list as $code => $t) {
            if ($code == $lang) {
                return $t->element_id;
            }
        }

        return false;
    }

    public function langList()
    {
        $list = [];
        if ($this->_active === 'wpml') {
            $languages = apply_filters('wpml_active_languages', NULL, 'orderby=id&order=desc');
            if (!empty($languages)) {
                foreach ($languages as $l) {
                    $list[] = $l['language_code'];
                }
            }
        } elseif ($this->_active === 'polylang') {
            $languages = pll_languages_list(['fields' => '']);
            foreach ($languages as $l) {
                $list[] = $l->slug;
            }
        }
        return $list;

    }

    /** Using for translation attributes
     * @param $cl_val
     * @param $cl_field_sub
     * @return string
     */
    public function getAttribute($cl_val, $cl_field_sub)
    {
        if ($this->_active === 'wpml') {
            $att = get_term_by('slug', $cl_val, $cl_field_sub);
            if (isset($att->slug)) {
                return $att->slug;
            }
        }

        return $cl_val;
    }

    public function listArgs($args)
    {
        if ($this->_active === 'wpml') {
//            $args['suppress_filters'] = false;
            if ($this->current_lang == 'all') {
                $args['suppress_filters'] = true;
            }
        } else {
            if ($this->current_lang && $this->current_lang != '' && $this->current_lang !== 'false') {
                $args['lang'] = $this->current_lang;
            }
        }

        return $args;
    }

    public function is_default_lan()
    {
        return ($this->current_lang === $this->default_lang);
    }

    public function is_all_lan()
    {
        return ($this->current_lang === false);
    }

    public function lang_list()
    {
        $languages = [];
        if ($this->_active === 'wpml') {
            $list = apply_filters('wpml_active_languages', null, 'orderby=id&order=desc');
            if ( ! empty($list)) {
                foreach ($list as $v) {
                    $languages[] = [
                        'name' => $v['native_name'],
                        'code' => $v['language_code'],
                        'flag' => $v['country_flag_url']
                    ];
                }
            }
        } else {
            $list = pll_languages_list(['fields' => '']);

            foreach ($list as $v) {
                $languages[] = ['name' => $v->name, 'code' => $v->slug, 'flag' => $v->flag_url];
            }
        }


        return $languages;
    }

    /**
     * Get the from post id , for wpml it can get from trid
     *
     * @return bool
     */
    public function from_post_id()
    {
        if ($this->_active === 'wpml' && isset($_GET['trid'])) {
            $my_duplications = apply_filters('wpml_get_element_translations', null, $_GET['trid']);
            if (isset($my_duplications[$this->default_lang]->element_id)) {
                return $my_duplications[$this->default_lang]->element_id;
            } elseif (is_array($my_duplications)) {
                return array_values($my_duplications)[0]->element_id;
            }
        } elseif ($this->_active === 'polylang' && isset($_GET['from_post'])) {
            return $_GET['from_post'];
        }

        return false;
    }

    public function is_duplicating()
    {
        if ($this->_active === 'wpml' && isset($_GET['trid'])) {
            return true;
        } elseif ($this->_active === 'polylang' && isset($_GET['from_post'])) {
            return true;
        }

        return false;
    }

    /**
     * change/set post language
     *
     * @param $postId
     * @param $lang
     * @param  false  $base_lang_id
     */
    public function set_post_lang($postId, $lang, $base_lang_id = false, $post_type = false)
    {
        $post_type       = $post_type ? $post_type : Form::$CPT;
        $postCurrentLang = $this->get_post_language($postId);
//		if ( $lang == $postCurrentLang ) { // removed this code as it need to re-sycn even if the post has set lang already
//			/**
//			 * If same language, no change need to done
//			 */
//			return;
//		}
        if ($this->_active == 'polylang') {
            if ($base_lang_id == false) {
                $base_lang_id = $this->base_form($postId);
            }

//			$transList = pll_get_post_translations( $postId );
            pll_set_post_language($postId, $lang);
            /**
             * swap language if the post has a translation with same language already
             */
//			if ( count( $transList ) ) {
//				foreach ( $transList as $code => $id ) {
//					if ( $code == $lang && $id != $postId ) {
//						pll_set_post_language( $id, $postCurrentLang );
//						break;
//					}
//				}
//			}

            if ($base_lang_id) {
                $transList        = pll_get_post_translations($base_lang_id);
                $swap             = isset($transList[$lang]) ? $transList[$lang] : false;
                $transList[$lang] = $postId;
                if ($swap) {
                    pll_set_post_language($swap, $postCurrentLang);
                    $transList[$postCurrentLang] = $swap;
                }
                pll_save_post_translations($transList);
            }
        } else {
            if ($base_lang_id) {
                $trid = apply_filters('wpml_element_trid', null, $base_lang_id);
            } else {
                $trid = apply_filters('wpml_element_trid', null, $postId);
            }

            $swapId = $this->getTransById($postId, $lang); // get the post which have already set this lang

            $source_language_code = $this->default_language();
            $set_language_args    = array(
                'element_id'           => $postId,
                'element_type'         => 'post_'.$post_type,
                'trid'                 => $trid,
                'language_code'        => $lang,
                'source_language_code' => ($source_language_code == $lang) ? null : $lang
            );
            do_action('wpml_set_element_language_details', $set_language_args);
            if ($swapId) {
                $set_language_args = array(
                    'element_id'           => $swapId,
                    'element_type'         => 'post_'.$post_type,
                    'trid'                 => $trid,
                    'language_code'        => $postCurrentLang,
                    'source_language_code' => ($source_language_code == $postCurrentLang) ? null : $postCurrentLang
                );
                do_action('wpml_set_element_language_details', $set_language_args);
            }
        }
    }

    /**
     * Get posts language
     *
     * @param $postId
     *
     * @return false|string
     *
     */
    public function get_post_language($postId)
    {
        if ($this->_active == 'polylang') {
            if (function_exists('pll_get_post_language')) {
                return pll_get_post_language($postId);
            }
        } elseif ($this->_active == 'wpml') {
            $lang = apply_filters('wpml_post_language_details', null, $postId);
            if ($lang) {
                return $lang['language_code'];
            }
        }

        return false;
    }

    public function default_language()
    {
        return $this->default_lang;
    }

    public function setCurrentLang($lang_code)
    {
        if ($this->_active == 'wpml') {
            global $sitepress;
            $sitepress->switch_lang($lang_code);
        } else if ($this->_active == 'polylang') {

        }


    }

    /**
     * get form edit links in all languages
     *
     * @param $postId
     *
     * @return array
     */
    public function get_post_translations_links($postId)
    {
        $langs = [];
        if ($this->_active == 'polylang') {
            $list = pll_languages_list(['fields' => '']);
            foreach ($list as $v) {
                $tra_post = pll_get_post($postId, $v->slug);
                if ($tra_post) {
                    $post    = get_post($tra_post);
                    $langs[] = [
                        'code'    => $v->slug,
                        'title'   => $post->post_title,
                        'post_id' => $tra_post,
                        'status'  => $post->post_status
                    ];
                }
            }
        } elseif ($this->_active === 'wpml') {
            $trid = apply_filters('wpml_element_trid', null, $postId);
            $list = apply_filters('wpml_get_element_translations', null, $trid);
            if ($list && is_array($list)) {
                foreach ($list as $v) {
                    if ($postId != $v->element_id) {
                        $langs[] = [
                            'code'    => $v->language_code,
                            'title'   => $v->post_title,
                            'post_id' => $v->element_id,
                            'status'  => $v->post_status// TODO to check
                        ];
                    }
                }
            }
        }

        return $langs;
    }

    public function default_fb_meta()
    {
        $value = null;
        if ($this->_active === 'wpml') {
            $my_duplications = apply_filters('wpml_get_element_translations', null, $_GET['trid']);
            if (isset($my_duplications[$this->default_lang]->element_id)) {
                $value = get_post_meta($my_duplications[$this->default_lang]->element_id, WCPA_FORM_META_KEY, true);
            } elseif (is_array($my_duplications)) {
                $value = get_post_meta(array_values($my_duplications)[0]->element_id, WCPA_FORM_META_KEY, true);
            }
        } elseif ($this->_active === 'polylang') {
            $base_form = $this->base_form($_GET['from_post']);
            $value     = get_post_meta($base_form, WCPA_FORM_META_KEY, true);

            return $value;
        }

        return $value;
    }

    public function current_language()
    {
        return $this->current_lang;
    }

    public function get_new_language()
    {
        if ($this->_active === 'wpml') {
        } elseif ($this->_active === 'polylang') {
            if (isset($_GET['new_lang'])) {
                return $_GET['new_lang'];
            }

            return $this->current_lang;
        }
    }

    public function get_original_forms()
    {
        if ($this->_active === 'wpml') {
            $forms          = get_posts(array('post_type' => Form::$CPT, 'posts_per_page' => -1));
            $forms_original = array();
            foreach ($forms as $p) {
                $trid = apply_filters('wpml_element_trid', null, $p->ID);
                if ($this->base_form($p->ID) === (int) $p->ID) {
                    $forms_original[] = $p;
                }
            }

            return $forms_original;
        } elseif ($this->_active === 'polylang') {
            $forms = get_posts(array(
                'post_type'      => Form::$CPT,
                'lang'           => pll_default_language(),
                'posts_per_page' => -1
            ));

            return $forms;
        }
    }

    public function lang_object_ids($object_id, $type, $fromDefault = false)
    {
        if (is_array($object_id)) {
            $translated_object_ids = array();
            foreach ($object_id as $id) {
//
                if ($this->_active === 'wpml') {
                    $translated_object_ids[] = apply_filters('wpml_object_id', $id, $type, true, $fromDefault ? $this->default_lang : null);
                } elseif ($this->_active === 'polylang') {
                    if ($type == 'product_cat') {
                        $p_id = pll_get_term($id, $fromDefault ? $this->default_lang : null);
                    } else {
                        $p_id = pll_get_post($id, $fromDefault ? $this->default_lang : null);
                    }

                    if ($p_id) {
                        $translated_object_ids[] = $p_id;
                    } else {
                        $translated_object_ids[] = $id;
                    }
                }
            }

            return array_unique($translated_object_ids);
        } else {
            if ($this->_active === 'wpml') {
                return apply_filters('wpml_object_id', $object_id, $type, true, $fromDefault ? $this->default_lang : null);
            } elseif ($this->_active === 'polylang') {
                $p_id = pll_get_post($object_id, $fromDefault ? $this->default_lang : null);
                if ($p_id) {
                    return $p_id;
                } else {
                    return $object_id;
                }
            }
        }
    }

    public function sync_data($post_id)
    {
        $base_id = $this->base_form($post_id);

        $form         = new Form();


        if ($base_id === (int) $post_id) {
            /** checks if base for is editing */
            /**
             * update all other forms if base form is updating
             */

            if ($this->_active === 'wpml') {
                $trid            = apply_filters('wpml_element_trid', null, $post_id);
                $my_duplications = apply_filters('wpml_get_element_translations', null, $trid);
                if (is_array($my_duplications)) {
                    foreach ($my_duplications as $item) {
                        if ((int) $item->element_id !== (int) $base_id) {
                            $form->merge_meta($base_id, $item->element_id);

                        }
                    }
                }
            } elseif ($this->_active === 'polylang') {
                $langs = pll_languages_list();
                foreach ($langs as $v) {
                    $p = pll_get_post($base_id, $v);
                    if ($p && $p !== $base_id) {
                        $form->merge_meta($base_id, $p);

                    }
                }
            }
        } elseif ($post_id !== 0) {
            /**
             * re-sync fields&settings date with  base form, to ensure it doesnt have modified non translatable fields
             */
            $form->merge_meta($base_id, $post_id);

        }
    }


    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?'), $this->_version);
    }

}
