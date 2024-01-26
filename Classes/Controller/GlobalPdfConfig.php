<?php

namespace FluentFormPdf\Classes\Controller;


class GlobalPdfConfig
{
    const OPTIONKEY = '_fluentform_pdf_settings';

    public function globalSettings()
    {
        $defaults = [
            'paper_size'         => 'A4',
            'orientation'        => 'P',
            'font'               => 'default',
            'font_size'          => 14,
            'font_color'         => '#323232',
            'accent_color'       => '#989797',
            'heading_color'      => '#000000',
            'language_direction' => 'ltr'
        ];

        $option = get_option(self::OPTIONKEY);
        if (!$option || !is_array($option)) {
            return $defaults;
        }

        if (isset($option['font_size'])) {
            $option['font_size'] = intval($option['font_size']);
        }

        return wp_parse_args($option, $defaults);
    }
}
