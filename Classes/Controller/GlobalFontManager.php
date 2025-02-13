<?php

namespace FluentPdf\Classes\Controller;

use FluentFormPdf\Classes\Controller\AvailableOptions;
use FluentPdf\Classes\Vite;
use FluentPdf\Classes\PdfBuilder;

class GlobalFontManager
{
    protected $optionKey = '_fluent_pdf_settings';

    public function registerAjax()
    {
        add_action('wp_ajax_fluent_pdf_admin_ajax_actions', [$this, 'ajaxRoutes']);
    }

    public function ajaxRoutes()
    {
        $maps = [
            'get_global_settings'  => 'getGlobalSettingsAjax',
            'save_global_settings' => 'saveGlobalSettings',
            'download_pdf'         => 'getPdf',
            'downloadFonts'        => 'downloadFonts',
        ];

        $route = sanitize_text_field($_REQUEST['route']);

        if (isset($maps[$route])) {
            $this->{$maps[$route]}();
        }
    }

    public function getGlobalSettingsAjax()
    {
        wp_send_json_success([
            'settings' => $this->globalSettings(),
            'fields'   => $this->getGlobalFields()
        ]);
    }

    public function saveGlobalSettings()
    {
        $settings = wp_unslash($_REQUEST['settings']);

        update_option($this->optionKey, $settings);

        wp_send_json_success([
            'message' => __('Settings successfully updated', 'fluent-pdf')
        ], 200);
    }

    /*
    * @return [ key name]
    * global pdf setting fields
    */
    public function getGlobalFields()
    {
        return [
            [
                'key'       => 'paper_size',
                'label'     => __('Paper size', 'fluent-pdf'),
                'component' => 'dropdown',
                'tips'      => __('All available templates are shown here, select a default template', 'fluent-pdf'),
                'options'   => AvailableOptions::getPaperSizes()
            ],
            [
                'key'       => 'orientation',
                'label'     => __('Orientation', 'fluent-pdf'),
                'component' => 'dropdown',
                'options'   => AvailableOptions::getOrientations()
            ],
            [
                'key'         => 'font_family',
                'label'       => __('Font Family', 'fluent-pdf'),
                'component'   => 'dropdown-group',
                'placeholder' => __('Select Font', 'fluent-pdf'),
                'options'     => AvailableOptions::getInstalledFonts()
            ],
            [
                'key'       => 'font_size',
                'label'     => __('Font size', 'fluent-pdf'),
                'component' => 'number'
            ],
            [
                'key'       => 'font_color',
                'label'     => __('Font color', 'fluent-pdf'),
                'component' => 'color_picker'
            ],
            [
                'key'       => 'heading_color',
                'label'     => __('Heading color', 'fluent-pdf'),
                'tips'      => __('Select Heading Color', 'fluent-pdf'),
                'component' => 'color_picker'
            ],
            [
                'key'       => 'accent_color',
                'label'     => __('Accent color', 'fluent-pdf'),
                'tips'      => __('The accent color is used for the borders, breaks etc.', 'fluent-pdf'),
                'component' => 'color_picker'
            ],
            [
                'key'       => 'language_direction',
                'label'     => __('Language Direction', 'fluent-pdf'),
                'tips'      => __('Script like Arabic and Hebrew are written right to left. For Arabic/Hebrew please select RTL',
                    'fluent-pdf'),
                'component' => 'radio_choice',
                'options'   => [
                    'ltr' => __('LTR', 'fluent-pdf'),
                    'rtl' => __('RTL', 'fluent-pdf')
                ]
            ]
        ];
    }

    public function downloadFonts()
    {
        Activator::maybeCreateFolderStructure();

        $fontManager = new FontDownloader();
        $downloadableFiles = $fontManager->getDownloadableFonts(3);

        $downloadedFiles = [];
        foreach ($downloadableFiles as $downloadableFile) {
            $fontName = $downloadableFile['name'];
            $res = $fontManager->download($fontName);
            $downloadedFiles[] = $fontName;
            if (is_wp_error($res)) {
                wp_send_json_error([
                    'message' => __('Font Download failed. Please reload and try again', 'fluent-pdf')
                ], 423);
            }
        }

        wp_send_json_success([
            'downloaded_files' => $downloadedFiles
        ], 200);
    }

    public function renderGlobalPage()
    {
        Vite::enqueueScript('fluent_pdf_admin', 'admin/FontManager/FontManager.js', array('jquery'), FLUENT_PDF_VERSION,
            true);

        $fontManager = new FontDownloader();
        $downloadableFiles = $fontManager->getDownloadableFonts();

        wp_localize_script('fluent_pdf_admin', 'fluent_pdf_admin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('fluent_pdf_admin_nonce'),
        ]);

        $statuses = [];
        $globalSettingsUrl = '#';
        if (!$downloadableFiles) {
            $statuses = $this->getSystemStatuses();
            $globalSettingsUrl = admin_url('admin.php?page=fluent_pdf.php/settings');

            if (!get_option($this->optionKey)) {
                update_option($this->optionKey, $this->globalSettings(), 'no');
            }
        }

        include FLUENT_PDF_PATH . 'views/admin_screen.php';
    }

    private function globalSettings()
    {
        return (new PdfBuilder())->getConfig();
    }

    private function getSystemStatuses()
    {
        $mbString = extension_loaded('mbstring');
        $mbRegex = extension_loaded('mbstring') && function_exists('mb_regex_encoding');
        $gd = extension_loaded('gd');
        $dom = extension_loaded('dom') || class_exists('DOMDocument');
        $libXml = extension_loaded('libxml');
        $extensions = [
            'mbstring'          => [
                'status' => $mbString,
                'label'  => ($mbString) ? __('MBString is enabled',
                    'fluent-pdf') : __('The PHP Extension MB String could not be detected. Contact your web hosting provider to fix.',
                    'fluent-pdf')
            ],
            'mb_regex_encoding' => [
                'status' => $mbRegex,
                'label'  => ($mbRegex) ? __('MBString Regex is enabled',
                    'fluent-pdf') : __('The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix.',
                    'fluent-pdf')
            ],
            'gd'                => [
                'status' => $gd,
                'label'  => ($gd) ? __('GD Library is enabled',
                    'fluent-pdf') : __('The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix.',
                    'fluent-pdf')
            ],
            'dom'               => [
                'status' => $dom,
                'label'  => ($dom) ? __('PHP Dom is enabled',
                    'fluent-pdf') : __('The PHP DOM Extension was not found. Contact your web hosting provider to fix.',
                    'fluent-pdf')
            ],
            'libXml'            => [
                'status' => $libXml,
                'label'  => ($libXml) ? __('LibXml is OK',
                    'fluent-pdf') : __('The PHP Extension libxml could not be detected. Contact your web hosting provider to fix',
                    'fluent-pdf')
            ]
        ];

        $overAllStatus = $mbString && $mbRegex && $gd && $dom && $libXml;

        return [
            'status'     => $overAllStatus,
            'extensions' => $extensions
        ];
    }
}
