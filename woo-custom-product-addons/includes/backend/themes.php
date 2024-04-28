<?php

namespace Acowebs\WCPA\Free;

class Themes
{
    /**
     * [
     * 'css' => [
     * 'LabelColor' => '',
     * 'LabelSize' => '',
     * 'LineColor' => '',
     *
     * 'DescColor' => '',
     * 'DescSize' => '',
     *
     * 'BorderColor' => '',
     * 'BorderWidth' => '',
     *
     * 'InputBgColor' => '',
     * 'CheckLabelColor' => '',
     * 'CheckLabelSize' => '',
     * 'CheckBgColor' => '',
     * 'CheckTickColor' => '',
     * ],
     * 'conf' => [
     * 'LabelPosition' => '',
     * 'DescPosition' => '',
     * ]
     * ];
     */

    public function getThemes()
    {
        $common = [
            'conf' => [
                'LabelPosition' => 'above',
                'DescPosition' => 'above',

            ],
            'css' => [
            ]

        ];

        $style0 = [
            'name' => 'No Custom Styles',
            'key' => 'style_0',
            'conf' => [

            ],
            'css' => [


            ]
        ];

        $style1 = [
            'name' => 'Custom Styles',
            'key' => 'style_1',
            'conf' => [

            ],
            'css' => [
                'SectionTitleSize' => '14px',

                'LabelSize' => '14px',
                'DescSize' => '13px',
                'ErrorSize' => '13px',

                'LabelWeight' => 'normal',
                'DescWeight' => 'normal',

                'BorderWidth' => "1px",
                'BorderRadius' => "6px",
                'InputHeight' => '45px',

                'CheckLabelSize' => '14px',
                'CheckBorderWidth' => '1px',
                'CheckWidth' => '20px',
                'CheckHeight' => '20px',
                'CheckBorderRadius' => '4px',

                'CheckButtonRadius' => '5px',
                'CheckButtonBorder' => '2px',
            ]
        ];


        /**
         * Grey
         */
        $color1 = [
            'name' => 'Color 1',
            'key' => 'color_1',
            'conf' => [

            ],
            'css' => [


                'ButtonColor' => '#3340d3',
                'LabelColor' => '#424242',
                'DescColor' => '#797979',

                'BorderColor' => "#c6d0e9",
                'BorderColorFocus' => "#3561f3",
                'InputBgColor' => '#FFFFFF',
                'InputColor' => '#5d5d5d',

                'CheckLabelColor' => '#4a4a4a',

                'CheckBgColor' => '#3340d3',
                'CheckBorderColor' => '#B9CBE3',
                'CheckTickColor' => '#ffffff',

                'RadioBgColor' => '#3340d3',
                'RadioBorderColor' => '#B9CBE3',
                'RadioTickColor' => '#ffffff',

                'ButtonTextColor' => '#ffffff',

                'ErrorColor' => '#F55050',


            ]
        ];


        return [
            'common' => $common,
            'styles' => [$style0, $style1],
            'colors' => [$color1]
        ];

    }
}
