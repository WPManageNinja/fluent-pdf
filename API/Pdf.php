<?php
namespace FluentPdf\API;

use FluentPdf\Classes\PdfBuilder;
use FluentPdf\Classes\Helper\Helper;
use FluentPdf\Classes\Controller\GlobalPdfConfig;

class Pdf
{

    protected $mpdf;

    public function __construct($config = [])
    {
        // $this->mpdf = new \Mpdf\Mpdf($config);
    }

    /**
     * @return array, Global configs
     */
    public static function getGlobalConfig()
    {
        return (new GlobalPdfConfig())->globalSettings();
    }

    /**
     * @param string $header, HTML string
     * @return Mpdf object with custom config
     */
    public function setHeader($header)
    {
        $this->mpdf->SetHTMLHeader($header ? $header : '');
        return $this->mpdf;
    }

    public function setFooter($footer)
    {
        $this->mpdf->SetHTMLFooter($footer ? $footer : '');
        return $this->mpdf;
    }

    public function setBody($body)
    {
        $this->mpdf->WriteHTML($body, \Mpdf\HTMLParserMode::HTML_BODY);
        return $this->mpdf;
    }

    /**
     * @param array $otherConfig, 
     * Extra config allows 
     * array (
     *      password => 'password',
     *      direction   => 'rtl',
     *      htmlHeader => 'html string', 
     *      watermark_image => 'image url', 
     *      watermark_text => 'watermark text'
     * )
     * @return Mpdf object with custom configs
     */
    public function setOtherConfig($otherConfig)
    {
        return (new Helper())->reflectConfig($this->mpdf, $otherConfig);
    }

    
    /**
     * @param string $filename, 'document.pdf'
     * @param null $outputType, 'S', 'I', 'D', 'F'
     * @return string   and generate PDF as output type
     */
    public function output($filename = 'document.pdf', $outputType = null)
    {
        return $this->mpdf->Output($filename, $outputType);
    }


     /**
     * @param string $filename, 'document.pdf'
     */
    public function download($filename = 'document.pdf')
    {
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
    }

    /**
     * @param string $filename, 'document.pdf'
     */
    public function show($filename = 'document.pdf')
    {
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
    }


    /**
     * @param string $filename, 'document.pdf'
    */
    public function stream($filename = 'document.pdf')
    {
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::FILE);
    }

        /**
     * @param string $filename, 'document.pdf'
    */
    public function asString($filename = 'document.pdf')
    {
        return $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);
    }


    public function getInstance()
    {
        return $this->mpdf;
    }

    public function generateAll($content, $filename, $outputType, $config, $extraConfig)
    {
        return (new PdfBuilder())->generatePdf(
            $content,       // ['header' => '', 'body' => '', 'footer' => '']
            $filename,      // $filename, 'document.pdf'
            $outputType,    // 'S', 'I', 'D', 'F'
            $config,        // General settings config
            $extraConfig    //[password, direction, htmlHeader, watermark_image, watermark_text]
        );
    }

}
