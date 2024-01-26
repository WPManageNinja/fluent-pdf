<?php

namespace FluentPdf\Classes\Controller;

use FluentPdf\Support\Arr;

class AvailableOptions
{
    public static function getPaperSizes()
    {
        return [
            'A4' => 'A4 (210 x 297mm)',
            'Letter' => 'Letter (8.5 x 11in)',
            'Legal' => 'Legal (8.5 x 14in)',
            'ledger' => 'Ledger / Tabloid (11 x 17in)',
            'Executive' => 'Executive (7 x 10in)',
            'A0' => 'A0 (841 x 1189mm)',
            'A1' => 'A1 (594 x 841mm)',
            'A2' => 'A2 (420 x 594mm)',
            'A3' => 'A3 (297 x 420mm)',
            'A5' => 'A5 (148 x 210mm)',
            'A6' => 'A6 (105 x 148mm)',
            'A7' => 'A7 (74 x 105mm)',
            'A8' => 'A8 (52 x 74mm)',
            'A9' => 'A9 (37 x 52mm)',
            'A10' => 'A10 (26 x 37mm)',
            'B0' => 'B0 (1414 x 1000mm)',
            'B1' => 'B1 (1000 x 707mm)',
            'B2' => 'B2 (707 x 500mm)',
            'B3' => 'B3 (500 x 353mm)',
            'B4' => 'B4 (353 x 250mm)',
            'B5' => 'B5 (250 x 176mm)',
            'B6' => 'B6 (176 x 125mm)',
            'B7' => 'B7 (125 x 88mm)',
            'B8' => 'B8 (88 x 62mm)',
            'B9' => 'B9 (62 x 44mm)',
            'B10' => 'B10 (44 x 31mm)',
            'C0' => 'C0 (1297 x 917mm)',
            'C1' => 'C1 (917 x 648mm)',
            'C2' => 'C2 (648 x 458mm)',
            'C3' => 'C3 (458 x 324mm)',
            'C4' => 'C4 (324 x 229mm)',
            'C5' => 'C5 (229 x 162mm)',
            'C6' => 'C6 (162 x 114mm)',
            'C7' => 'C7 (114 x 81mm)',
            'C8' => 'C8 (81 x 57mm)',
            'C9' => 'C9 (57 x 40mm)',
            'C10' => 'C10 (40 x 28mm)',
            'RA0' => 'RA0 (860 x 1220mm)',
            'RA1' => 'RA1 (610 x 860mm)',
            'RA2' => 'RA2 (430 x 610mm)',
            'RA3' => 'RA3 (305 x 430mm)',
            'RA4' => 'RA4 (215 x 305mm)',
            'SRA0' => 'SRA0 (900 x 1280mm)',
            'SRA1' => 'SRA1 (640 x 900mm)',
            'SRA2' => 'SRA2 (450 x 640mm)',
            'SRA3' => 'SRA3 (320 x 450mm)',
            'SRA4' => 'SRA4 (225 x 320mm)',
            'B' => 'B (128 x 198mm)',
            'A' => 'B (111 x 178mm)',
            'DEMY' => 'DEMY (135 x 216mm)',
            'ROYAL' => 'ROYAL (135 x 216mm)'
        ];
    }

    public static function getOrientations()
    {
        return [
            'P' => "Portrait",
            'L' => 'Landscape'
        ];
    }

    public static function getFonts()
    {
        return [
            'default' => 'Default',
            'serif' => 'Serif',
            'monospace' => 'Monospace'
        ];
    }

    public static function getDefaultSettings()
    {
        return [
            'font_size' => '16',
            "paper_size" => 'A4',
            'template' => 'blank',
            'orientation' => 'P',
            'font' => 'default',
            'font_color' => '#000000',
            'entry_view' => 'I',
            'reverse_text' => 'no',
            'accent_color' => '#CCCCCC',
            'filename' => 'fluentpdf'
        ];
    }

    public static function commonSettings()
    {
        return [
            [
                'key' => 'paper_size',
                'label' => 'Paper size',
                'component' => 'dropdown',
                'tab' => 'tab2',
                'tips' => 'select a pdf paper size',
                'options' => self::getPaperSizes()
            ],
            [
                'key' => 'orientation',
                'label' => 'Orientation',
                'tab' => 'tab2',
                'component' => 'dropdown',
                'options' => self::getOrientations()
            ],
            [
                'key' => 'font',
                'label' => 'Font family',
                'component' => 'dropdown',
                'tab' => 'tab2',
                'options' => self::getFonts()
            ],
            [
                'key' => 'font_size',
                'label' => 'Font size',
                'tab' => 'tab2',
                'component' => 'number'
            ],
            [
                'key' => 'font_color',
                'label' => 'Font color',
                'tab' => 'tab2',
                'tips' => 'The font color will use in the PDF.',
                'component' => 'color_picker'
            ],
            [
                'key' => 'accent_color',
                'label' => 'Accent color',
                'tab' => 'tab2',
                'tips' => 'The accent color is used for the page, section titles and the border.',
                'component' => 'color_picker'
            ],
            [
                'key' => 'entry_view',
                'label' => 'Entry view',
                'tab' => 'tab2',
                'component' => 'radio_choice',
                'options' => [
                    'I' => 'View',
                    'D' => 'Download'
                ]
            ],
            [
                'key' => 'empty_fields',
                'label' => 'Show empty fields',
                'tab' => 'tab2',
                'component' => 'radio_choice',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]
            ],
            [
                'key' => 'reverse_text',
                'label' => 'Reverse text',
                'tab' => 'tab2',
                'tips' => 'Script like Arabic and Hebrew are written right to left.',
                'component' => 'radio_choice',
                'options' => [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]
            ]
        ];
    }

    public static function slugify($string)
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    }

    public static function getPreferences($settings, $default)
    {
        $color = Arr::get($settings, 'font_color');
        $accent = Arr::get($settings, 'accent_color');
        if ($color == '' || null) {
            $color = Arr::get($default, 'font_color');
        }
        if ($accent == '' || null) {
            $accent = Arr::get($default, 'accent_color');
        }

        return [
            'color' => $color,
            'accent' => $accent,
            'font' => Arr::get($settings, 'font', Arr::get($default, 'font')),
            'fontSize' => Arr::get($settings, 'font_size', Arr::get($default, 'font_size'))
        ];
    }

    public static function getDirStructure()
    {
        /*
         * Todo: Need a fix for multi-site network
         */
        $workingPath = wp_upload_dir()['basedir'];

        $workingDir = apply_filters_deprecated(
            'fluent_pdf_working_dir',
            [
                $workingPath . '/FLUENT_PDF_TEMPLATES'
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluent/pdf_working_dir',
            'Use fluent/pdf_working_dir instead of fluent_pdf_working_dir.'
        );

        $workingDir = apply_filters('fluent/pdf_working_dir', $workingPath . '/FLUENT_PDF_TEMPLATES');

        $tmpDir = apply_filters_deprecated(
            'fluent_pdf_temp_dir',
            [
                $workingDir . '/temp'
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluent/pdf_temp_dir',
            'Use fluent/pdf_temp_dir instead of fluent_pdf_temp_dir.'
        );

        $tmpDir = apply_filters('fluent/pdf_temp_dir', $workingDir . '/temp');

        $cacheDir = apply_filters_deprecated(
            'fluent_pdf_cache_dir',
            [
                $workingDir . '/pdfCache'
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluent/pdf_cache_dir',
            'Use fluent/pdf_cache_dir instead of fluent_pdf_cache_dir.'
        );

        $cacheDir = apply_filters('fluent/pdf_cache_dir', $workingDir . '/pdfCache');

        $fontDir = apply_filters_deprecated(
            'fluent_pdf_font_dir',
            [
                $workingDir . '/fonts'
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluent/pdf_font_dir',
            'Use fluent/pdf_font_dir instead of fluent_pdf_font_dir.'
        );

        $fontDir = apply_filters('fluent/pdf_font_dir', $workingDir . '/fonts');


        return [
            'workingDir' => $workingDir,
            'tempDir' => apply_filters('fluent/pdf_temp_dir', $workingDir . '/temp'),
            'pdfCacheDir' => apply_filters('fluent/pdf_cache_dir', $workingDir . '/pdfCache'),
            'fontDir' => apply_filters('fluent/pdf_font_dir', $workingDir . '/fonts')
        ];
    }

    public static function getInstalledFonts()
    {
        $fonts = [
            'Unicode' => [
                'dejavusanscondensed' => 'Dejavu Sans Condensed',
                'dejavusans' => 'Dejavu Sans',
                'dejavuserifcondensed' => 'Dejavu Serif Condensed',
                'dejavuserif' => 'Dejavu Serif',
                'dejavusansmono' => 'Dejavu Sans Mono',

                'freesans' => 'Free Sans',
                'freeserif' => 'Free Serif',
                'freemono' => 'Free Mono',

                'mph2bdamase' => 'MPH 2B Damase',
            ],

            'Indic' => [
                'lohitkannada' => 'Lohit Kannada',
                'pothana2000' => 'Pothana2000',
            ],

            'Arabic' => [
                'xbriyaz' => 'XB Riyaz',
                'lateef' => 'Lateef',
                'kfgqpcuthmantahanaskh' => 'Bahif Uthman Taha',
            ],

            'Chinese, Japanese, Korean' => [
                'sun-exta' => 'Sun Ext',
                'unbatang' => 'Un Batang (Korean)',
            ],

            'Other' => [
                'estrangeloedessa' => 'Estrangelo Edessa (Syriac)',
                'kaputaunicode' => 'Kaputa (Sinhala)',
                'abyssinicasil' => 'Abyssinica SIL (Ethiopic)',
                'aboriginalsans' => 'Aboriginal Sans (Cherokee / Canadian)',
                'jomolhari' => 'Jomolhari (Tibetan)',
                'sundaneseunicode' => 'Sundanese (Sundanese)',
                'taiheritagepro' => 'Tai Heritage Pro (Tai Viet)',
                'aegyptus' => 'Aegyptus (Egyptian Hieroglyphs)',
                'akkadian' => 'Akkadian (Cuneiform)',
                'aegean' => 'Aegean (Greek)',
                'quivira' => 'Quivira (Greek)',
                'eeyekunicode' => 'Eeyek (Meetei Mayek)',
                'lannaalif' => 'Lanna Alif (Tai Tham)',
                'daibannasilbook' => 'Dai Banna SIL (New Tai Lue)',
                'garuda' => 'Garuda (Thai)',
                'khmeros' => 'Khmer OS (Khmer)',
                'dhyana' => 'Dhyana (Lao)',
                'tharlon' => 'TharLon (Myanmar / Burmese)',
                'padaukbook' => 'Padauk Book (Myanmar / Burmese)',
                'zawgyi-one' => 'Zawgyi One (Myanmar / Burmese)',
                'ayar' => 'Ayar Myanmar (Myanmar / Burmese)',
                'taameydavidclm' => 'Taamey David CLM (Hebrew)',
            ],
        ];

        $fontList = apply_filters_deprecated(
            'fluent_pdf_font_list',
            [
                $fonts
            ],
            FLUENTPDF_FRAMEWORK_UPGRADE,
            'fluent/pdf_font_list',
            'Use fluent/pdf_font_list instead of fluent_pdf_font_list.'
        );

        $fontList = apply_filters('fluent/pdf_font_list', $fonts);

        return $fontList;
    }
}
