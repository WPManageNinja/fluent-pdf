<?php

namespace FluentPdf\Modules\FluentForms\Templates;

use FluentForm\App\Services\Emogrifier\Emogrifier;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;
use FluentPdf\Classes\Controller\AvailableOptions;

abstract class TemplateManager
{
    protected $app;

    public $workingDir = '';
    public $tempDir = '';
    public $pdfCacheDir = '';
    public $fontDir = '';

    public $headerHtml = '';

    public function __construct(Application $app)
    {
        $this->app = $app;

        $dirStructure = AvailableOptions::getDirStructure();

        $this->workingDir = $dirStructure['workingDir'];
        $this->tempDir = $dirStructure['tempDir'];
        $this->pdfCacheDir = $dirStructure['pdfCacheDir'];
        $this->fontDir = $dirStructure['fontDir'];
    }

    abstract public function getSettingsFields();

    abstract public function generatePdf($submissionId, $settings, $outPut, $fileName = '');

    public function viewPDF($submissionId, $settings)
    {
        $this->generatePdf($submissionId, $settings, 'I');
    }

    public function outputPDF($submissionId, $settings, $fileName = '', $forceClear = false)
    {
        $fileName = $this->pdfCacheDir . '/' . $fileName;
        if (!$forceClear && file_exists($fileName . '.pdf')) {
            return $fileName . '.pdf';
        }

        $this->generatePdf($submissionId, $settings, 'F', $fileName);

        if (file_exists($fileName . '.pdf')) {
            return $fileName . '.pdf';
        }

        return false;
    }

    public function downloadPDF($submissionId, $settings)
    {
        return $this->generatePdf($submissionId, $settings, 'D');
    }

    public function getGenerator($mpdfConfig)
    {
        $defaults = [
            'fontDir'                => [
                $this->fontDir,
                FLUENT_PDF_PATH . 'vendor/mpdf/mpdf/ttfonts',
            ],
            'tempDir'                => $this->tempDir,
            'curlCaCertificate'      => ABSPATH . WPINC . '/certificates/ca-bundle.crt',
            'curlFollowLocation'     => true,
            'allow_output_buffering' => true,
            'autoLangToFont'         => true,
            'autoScriptToLang'       => true,
            'useSubstitutions'       => true,
            'ignore_invalid_utf8'    => true,
            'setAutoTopMargin'       => 'stretch',
            'setAutoBottomMargin'    => 'stretch',
            'enableImports'          => true,
            'use_kwt'                => true,
            'keepColumns'            => true,
            'biDirectional'          => true,
            'showWatermarkText'      => true,
            'showWatermarkImage'     => true,
        ];

        $mpdfConfig = wp_parse_args($mpdfConfig, $defaults);

        $mpdfConfig = apply_filters_deprecated(
            'fluentform_mpdf_config',
            [$mpdfConfig],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/mpdf_config',
            'Use fluentform/mpdf_config instead of fluentform_mpdf_config.'
        );

        $mpdfConfig = apply_filters('fluentform/mpdf_config', $mpdfConfig);

        if (!class_exists('\Mpdf\Mpdf')) {
            require_once FLUENT_PDF_PATH . 'vendor/autoload.php';
        }

        try {
            return new \Mpdf\Mpdf($mpdfConfig);
        } catch (\Mpdf\MpdfException $e) {
            $settingsUrl = admin_url('admin.php?page=fluent_forms_add_ons&sub_page=fluentform_pdf');
            wp_die(
                '<h2>' . esc_html__('Fluent PDF - Font Error', 'fluent-pdf') . '</h2>'
                . '<p>' . esc_html($e->getMessage()) . '</p>'
                . '<p>' . esc_html__('Please download the required fonts from the ', 'fluent-pdf')
                . '<a href="' . esc_url($settingsUrl) . '">'
                . esc_html__('Fluent PDF Settings', 'fluent-pdf')
                . '</a>.</p>',
                esc_html__('Fluent PDF Error', 'fluent-pdf'),
                ['response' => 500, 'back_link' => true]
            );
        }
    }

    public function pdfBuilder($fileName, $feed, $body = '', $footer = '', $outPut = 'I')
    {
        $body = str_replace('{page_break}', '<page_break />', $body);

        $appearance = Arr::get($feed, 'appearance');
        $mpdfConfig = [
            'mode'          => 'utf-8',
            'format'        => Arr::get($appearance, 'paper_size'),
            'margin_header' => 10,
            'margin_footer' => 10,
            'orientation'   => Arr::get($appearance, 'orientation'),
        ];

        if ($fontFamily = ArrayHelper::get($appearance, 'font_family')) {
            $mpdfConfig['default_font'] = $fontFamily;
        }

        if (!defined('FLUENTFORMPRO')) {
            $footer .= '<p style="text-align: center;">Powered By <a target="_blank" href="https://wpmanageninja.com/downloads/fluentform-pro-add-on/">Fluent Forms</a></p>';
        }

        $pdfGenerator = $this->getGenerator($mpdfConfig);
        if (ArrayHelper::get($appearance, 'security_pass')) {
            $password = ArrayHelper::get($appearance, 'security_pass');
            $pdfGenerator->SetProtection([], $password, $password);
        }

        if (ArrayHelper::get($appearance, 'language_direction') == 'rtl') {
            $pdfGenerator->SetDirectionality('rtl');
            $body = '<div class="ff_rtl">' . $body . '</div>';
            if ($footer) {
                $footer = '<div class="ff_rtl">' . $footer . '</div>';
            }
            if ($this->headerHtml) {
                $this->headerHtml = '<div class="ff_rtl">' . $this->headerHtml . '</div>';
            }
        }
        if ($this->headerHtml) {
            $this->headerHtml = $this->applyInlineCssStyles($this->headerHtml, $appearance);
            $pdfGenerator->SetHTMLHeader($this->headerHtml);
        }

        $body = $this->applyInlineCssStyles($body, $appearance, true);
        if (!empty($appearance['watermark_text']) || !empty($appearance['watermark_image'])) {
            $alpha = Arr::get($appearance, 'watermark_opacity');
            if (!$alpha || $alpha > 100) {
                $alpha = 5;
            }
            $alpha = $alpha / 100;

            if (!empty($appearance['watermark_image'])) {
                $feedId = ArrayHelper::get($feed, 'id');
                $watermarkImageSize = apply_filters('fluentform/pdf_watermark_image_size', 'D', $feedId);
                $watermarkImagePosition = apply_filters('fluentform/pdf_watermark_image_position', 'F', $feedId);
                $pdfGenerator->SetWatermarkImage($appearance['watermark_image'], $alpha, $watermarkImageSize, $watermarkImagePosition);

                if (Arr::isTrue($appearance, 'watermark_img_behind')) {
                    $pdfGenerator->watermarkImgBehind = true;
                }

                $pdfGenerator->showWatermarkImage = true;
            } else {
                $pdfGenerator->SetWatermarkText($appearance['watermark_text'], $alpha);
                $pdfGenerator->showWatermarkText = true;
            }
        }
        $footer = $this->applyInlineCssStyles($footer, $appearance);

        $pdfGenerator->SetHTMLFooter($footer);
        $pdfGenerator->WriteHTML('<div class="ff_pdf_wrapper">' . $body . '</div>', \Mpdf\HTMLParserMode::HTML_BODY);

        if ($outPut == 'S') {
            return $pdfGenerator->Output($fileName . '.pdf', $outPut);
        }

        $pdfGenerator->Output($fileName . '.pdf', $outPut);
    }

    public function getPdfCss($appearance)
    {
        $mainColor = Arr::get($appearance, 'font_color');
        if (!$mainColor) {
            $mainColor = '#4F4F4F';
        }
        $secondaryColor = Arr::get($appearance, 'accent_color');
        if (!$secondaryColor) {
            $secondaryColor = '#EAEAEA';
        }
        $headingColor = Arr::get($appearance, 'heading_color');

        $fontSize = Arr::get($appearance, 'font_size', 14);

        ob_start();
        ?>
        .ff_pdf_wrapper, p, li, td, th {
        color: <?php echo esc_attr($mainColor); ?>;
        font-size: <?php echo esc_attr($fontSize); ?>px;
        }

        .ff_all_data, table {
        empty-cells: show;
        border-collapse: collapse;
        border: 1px solid <?php echo esc_attr($secondaryColor); ?>;
        width: 100%;
        color: <?php echo esc_attr($mainColor); ?>;
        }
        hr {
        color: <?php echo esc_attr($secondaryColor); ?>;
        background-color: <?php echo esc_attr($secondaryColor); ?>;
        }
        h1, h2, h3, h4, h5, h6 {
        color: <?php echo esc_attr($headingColor); ?>;
        }
        .ff_all_data th {
        border-bottom: 1px solid <?php echo esc_attr($secondaryColor); ?>;
        border-top: 1px solid <?php echo esc_attr($secondaryColor); ?>;
        padding-bottom: 10px !important;
        }
        .ff_all_data tr td {
        padding-left: 30px !important;
        padding-top: 15px !important;
        padding-bottom: 15px !important;
        }

        .ff_all_data tr td, .ff_all_data tr th {
        border: 1px solid <?php echo esc_attr($secondaryColor); ?>;
        text-align: left;
        }

        table, .ff_all_data {width: 100%; overflow:wrap;} img.alignright { float: right; margin: 0 0 1em 1em; }
        img.alignleft { float: left; margin: 0 10px 10px 0; }
        .center-image-wrapper {text-align:center;}
        .center-image-wrapper img.aligncenter {display: initial; margin: 0; text-align: center;}
        .alignright { float: right; }
        .alignleft { float: left; }
        .aligncenter { display: block; margin-left: auto; margin-right: auto; text-align: center; }

        .invoice_title {
        padding-bottom: 10px;
        display: block;
        }
        .ffp_table thead th {
        background-color: #e3e8ee;
        color: #000;
        text-align: left;
        vertical-align: bottom;
        }
        table th {
        padding: 5px 10px;
        }
        .ff_rtl table th, .ff_rtl table td {
            text-align: right !important;
        }
        <?php
        $css = ob_get_clean();

        $pdfGeneratorCss = apply_filters_deprecated(
            'fluentform_pdf_generator_css',
            [$css, $appearance],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/pdf_generator_css',
            'Use fluentform/pdf_generator_css instead of fluentform_pdf_generator_css.'
        );
        return apply_filters('fluentform/pdf_generator_css', $pdfGeneratorCss, $appearance);
    }

    private function applyInlineCssStyles($html, $appearance, $keepBodyTag = false)
    {
        $html = $this->resolveCenteredImage($html);
        try {
            $emogrifier = new Emogrifier($html, $this->getPdfCss($appearance));
            if ($keepBodyTag) {
                $html = $emogrifier->emogrify();
            } else {
                $html = $emogrifier->emogrifyBodyContent();
            }
        } catch (\Exception $e) {
        }
        return $html;
    }

    private function resolveCenteredImage($html)
    {
        if (strpos($html, '<img') === false) {
            return $html;
        }
        preg_match_all('/(<img[^>]+>)/i', $html, $matches);
        foreach ($matches[0] as $image) {
            if (strpos($image, 'aligncenter') !== false) {
                $newImg = "<div class='center-image-wrapper'> $image </div>";
                $newHtml = str_replace($image, $newImg, $html);
                if ($newHtml) {
                    $html = $newHtml;
                }
            }
        }
        return $html;
    }
}
