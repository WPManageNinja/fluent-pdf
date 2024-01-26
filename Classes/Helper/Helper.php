<?php
namespace FluentFormPdf\Classes\Helper;

use FluentFormPdf\Support\Arr;

class Helper
{
    public function reflectConfig(&$pdfBuilder, $pdfConfig)
    {
        if ($value = Arr::get($pdfConfig, 'password')) {
            $pdfBuilder->SetProtection(array(), $value, $value);
        };

        if ($value = Arr::get($pdfConfig, 'direction')) {
            $pdfBuilder->SetDirectionality($value);
        };

        if ($value = Arr::get($pdfConfig, 'htmlHeader')) {
            $pdfBuilder->SetHTMLHeader($value);
        };

        if ($value = Arr::get($pdfConfig, 'watermark_image')) {
            $pdfBuilder->SetWatermarkImage($value['image'], $value['alpha']);
            //Todo check
            if (Arr::get($value, 'behind') !== false) {
                $pdfBuilder->watermarkImgBehind = true;
            }
            $pdfBuilder->showWatermarkImage = true;
        };

        if ($value = Arr::get($pdfConfig, 'watermark_text')) {
            $pdfBuilder->SetWatermarkText($value['text'], $value['alpha']);
            $pdfBuilder->showWatermarkText = true;
        };
    }
}
