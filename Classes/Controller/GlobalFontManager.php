<?php
namespace FluentPdf\Classes\Controller;

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
            'get_global_settings' => 'getGlobalSettingsAjax',
            'save_global_settings' => 'saveGlobalSettings',
            'download_pdf' => 'getPdf',
            'downloadFonts' => 'downloadFonts',
            
        ];

        $route = sanitize_text_field($_REQUEST['route']);

        // Acl::verify('fluent_forms_manager');

        if (isset($maps[$route])) {
            $this->{$maps[$route]}();
        }
    }

    public function getGlobalSettingsAjax()
    {
        wp_send_json_success([
            'settings' => $this->globalSettings(),
            'fields' => $this->getGlobalFields()
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
                'key' => 'paper_size',
                'label' => 'Paper size',
                'component' => 'dropdown',
                'tips' => 'All available templates are shown here, select a default template',
                'options' => AvailableOptions::getPaperSizes()
            ],
            [
                'key' => 'orientation',
                'label' => 'Orientation',
                'component' => 'dropdown',
                'options' => AvailableOptions::getOrientations()
            ],
            [
                'key' => 'font_family',
                'label' => 'Font Family',
                'component' => 'dropdown-group',
                'placeholder' => 'Select Font',
                'options' => AvailableOptions::getInstalledFonts()
            ],
            [
                'key' => 'font_size',
                'label' => 'Font size',
                'component' => 'number'
            ],
            [
                'key' => 'font_color',
                'label' => 'Font color',
                'component' => 'color_picker'
            ],
            [
                'key' => 'heading_color',
                'label' => 'Heading color',
                'tips' => 'The Color Form Headings',
                'component' => 'color_picker'
            ],
            [
                'key' => 'accent_color',
                'label' => 'Accent color',
                'tips' => 'The accent color is used for the borders, breaks etc.',
                'component' => 'color_picker'
            ],
            [
                'key' => 'language_direction',
                'label' => 'Language Direction',
                'tips' => 'Script like Arabic and Hebrew are written right to left. For Arabic/Hebrew please select RTL',
                'component' => 'radio_choice',
                'options' => [
                    'ltr' => 'LTR',
                    'rtl' => 'RTL'
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
                    'message' => 'Font Download failed. Please reload and try again'
                ], 423);
            }
        }

        wp_send_json_success([
            'downloaded_files' => $downloadedFiles
        ], 200);
    }

    public function renderGlobalPage()
    {        
        Vite::enqueueScript('fluent_pdf_admin', 'admin/FontManager/FontManager.js', array('jquery'), FLUENT_PDF_VERSION, true);

        $fontManager = new FontDownloader();
        $downloadableFiles = $fontManager->getDownloadableFonts();

        wp_localize_script('fluent_pdf_admin', 'fluent_pdf_admin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluent_pdf_admin_nonce'),
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
            'mbstring' => [
                'status' => $mbString,
                'label' => ($mbString) ? 'MBString is enabled' : 'The PHP Extension MB String could not be detected. Contact your web hosting provider to fix.'
            ],
            'mb_regex_encoding' => [
                'status' => $mbRegex,
                'label' => ($mbRegex) ? 'MBString Regex is enabled' : 'The PHP Extension MB String does not have MB Regex enabled. Contact your web hosting provider to fix.'
            ],
            'gd' => [
                'status' => $gd,
                'label' => ($gd) ? 'GD Library is enabled' : 'The PHP Extension GD Image Library could not be detected. Contact your web hosting provider to fix.'
            ],
            'dom' => [
                'status' => $dom,
                'label' => ($dom) ? 'PHP Dom is enabled' : 'The PHP DOM Extension was not found. Contact your web hosting provider to fix.'
            ],
            'libXml' => [
                'status' => $libXml,
                'label' => ($libXml) ? 'LibXml is OK' : 'The PHP Extension libxml could not be detected. Contact your web hosting provider to fix'
            ]
        ];

        $overAllStatus = $mbString && $mbRegex && $gd && $dom && $libXml;

        return [
            'status' => $overAllStatus,
            'extensions' => $extensions
        ];
    }
}
