<?php

namespace Acowebs\WCPA\Free;

class Designs
{
    /**
     *  ['custom'=>[],'active'=>['type'=>'custom','key'=>'active theme key','conf'=>['key'=>conf]]
     * @var string
     *
     */
    static $keyDesigns = 'wcpa_designs_key';
    static $keyActiveDesign = 'wcpa_active_design';

    static $design_css = 'wcpa_css_';
    public $designs = [];


    public function __construct()
    {
        $theme = new Themes();


        $this->designs = $theme->getThemes();
    }

    public function save_theme_conf($data)
    {
        $active  = $data['designs']['active'];
        $designs = $data['designs']['designs'];



        update_option(self::$keyDesigns, $designs);
        update_option(self::$keyActiveDesign, $active);

        delete_transient('wcpa_settings_'.WCPA_VERSION);

        return true;
    }

    public function activate_theme($theme)
    {
        $designs           = get_option(self::$key, ['active' => false, 'designs' => false]);
        $designs['active'] = ['type' => 'default', 'key' => $theme];
        $this->updateOption($designs);

        return $designs['active'];
    }

    public function updateOption($option)
    {
        update_option(self::$key, $option);
        wp_cache_delete('wcpa_settings_'.WCPA_VERSION);
    }

    /** return css and conf for admin api
     *
     * @param $style
     *
     * @return array|false
     */
    public function get_style($style)
    {
        $cssCode = file_get_contents(__DIR__.'/../../assets/css/'.$style.'.css');
        if ($cssCode) {
            $cssCode  = str_replace('.wcpa_form_outer', '', $cssCode);
            $themeCss = file_get_contents(get_template_directory().'/style.css');
            if ($themeCss) {
                $cssCode = $themeCss.$cssCode;
            }

            return ['designs' => $this->designs, 'cssCode' => $cssCode];
        } else {
            return false;
        }
    }

//    public function get_design($theme, $returnValueOnly = true)
//    {
//        $designs = $this->get_designs($returnValueOnly);
//
//        return current(array_filter($designs['designs'], function ($v) use ($theme) {
//            return $v['key'] === $theme;
//        }));
//    }

    /**
     * Return active theme key,  and  also its corresponding configration  as css code
     * @return array
     */
    public function get_active_design()
    {
        $designs = $this->get_designs();

        $style = $designs['active']['style'];
        $color = $designs['active']['color'];

        $activeStyle = current(array_filter($designs['designs']['styles'], function ($v) use ($style) {
            return $v['key'] === $style;
        }));
        $activeColor = current(array_filter($designs['designs']['colors'], function ($v) use ($color) {
            return $v['key'] === $color;
        }));
        /**
         * generate vars with $conf
         */
        $cssCode = '';
        if ($activeStyle && isset($activeStyle['css'])) {
            $cssCode = confToCss($activeStyle['css']);
        }
        if ($activeStyle['key']!=='style_0' && $activeColor && isset($activeColor['css'])) {
            $cssCode .= confToCss($activeColor['css']);
        }

        $cssCode .= confToCss($designs['designs']['common']['css']);

        return [
            'active'  => $designs['active'],
            'cssCode' => $cssCode,
            'common'  => $designs['designs']['common']
        ];
    }

    /**
     * get designs, compaigned with default value and user updated value from db
     *
     * @param  bool  $returnValueOnly  theme conf,can be with attributes other than value, for front end users, it needs to return value only,
     * where as for backend, it need config as well
     *
     * @return array
     */

    public function get_designs($returnValueOnly = true)
    {
        /**
         * Data structure for design at self::$key
         *  [
         * 'active'=>active theme key,
         * 'designs'=>  all themes configurations customized ( arrya format)
         *       [
         *      'key'=>'theme_key'
         *      'conf'=>[conf values']
         *         'css'=>['css values']
         * ]
         */

        $designs = get_option(self::$keyDesigns, false);
        $default = $this->designs;
        if ($designs) {
            /**
             * merge designs in db with the default configuration, in case it has any missing elements or unsupported
             */
//            foreach ($this->designs['common'] as $dKey => $values) {
//                $this->designs['common'][$dKey] = $this->mergeDesigns($designs['common'][$dKey], $values);
//            }
//            foreach ($this->designs['styles'] as $dKey => $values) {
//                $this->designs['styles'][$dKey] = $this->mergeDesigns($designs['styles'][$dKey], $values);
//            }
            foreach ($this->designs as $dKey => $configs) {
                /** common,styles, colors */
                if (isset($designs[$dKey])) {
                    foreach ($configs as $key => $values) {
                        /* conf, css,name,key */
                        if (isset($designs[$dKey][$key])) {
                            $this->designs[$dKey][$key] = $this->mergeDesigns($designs[$dKey][$key], $values);
                        }
                    }
                }
            }

        }

        $active = get_option(self::$keyActiveDesign, ['style' => 'style_1', 'color' => 'color_1']);

        $validActive = false;
        foreach ($this->designs['styles'] as $style) {
            if ($style['key'] == $active['style']) {
                $validActive = true;
                break;
            }
        }
        if ( ! $validActive) {
            $active['style'] = 'style_1';
        }
        $validActive = false;
        foreach ($this->designs['colors'] as $color) {
            if ($color['key'] == $active['color']) {
                $validActive = true;
                break;
            }
        }
        if ( ! $validActive) {
            $active['color'] = 'color_1';
        }

        return [
            'designs' => $this->designs,
            'default' => $default,
            'active'  => $active,
        ];
    }

    public function mergeDesigns($fromDb, $default)
    {
        $out = [];
        foreach ($default as $key => $val) {
            if (in_array($key, ['name', 'key'])) {
                $out[$key] = $val;
                continue;
            }
            if ( ! isset($fromDb[$key])) {
                $out[$key] = $val;
                continue;
            }
            if (is_array($val) && ! isEmpty($val)) {
                foreach ($val as $p => $v) {
                    if ( ! isset($fromDb[$key][$p])) {
                        $out[$key][$p] = $v;
                        continue;
                    }
                    $out[$key][$p] = $fromDb[$key][$p];
                }
            } else {
                $out[$key] = $fromDb[$key];
            }
        }

        return $out;
    }
}
