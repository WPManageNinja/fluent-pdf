<?php

namespace FluentPdf\Classes;

use FluentPdf\Support\Arr;
use FluentPdf\API\Pdf;

class PdfBuilder
{
    protected $pdf;
    protected static $instance;
    protected $config;

    public function register()
    {
        add_action('fluent_pdf_make', [$this, 'generatePdf'], 10, 5);
    }

    public function getConfig()
    {
        return (new Pdf())->getGlobalConfig();
    }

    /**
     * @param $content, array, ['header' => '', 'body' => '', 'footer' => '']
     * @param string $filename, 'document.pdf'
     * @param null $outputType, 'S', 'I', 'D', 'F'
     * @param array $config General settings config
     * @param array $extraConfig, Extra config allows password, direction, htmlHeader, watermark_image, watermark_text
     * @return string   
     */
    public function generatePdf($content, $filename = 'document.pdf', $outputType = null, $config = [], $extraConfig = [])
    {
        $pdfInstance = (new Pdf($config));
        $pdfInstance->setOtherConfig($extraConfig);
        $pdfInstance->setHeader(Arr::get($content, 'header', ''));
        $pdfInstance->setFooter(Arr::get($content, 'footer', ''));
        $pdfInstance->setBody(Arr::get($content, 'body', ''));
        return $pdfInstance->output($filename, $outputType);
    }
}
