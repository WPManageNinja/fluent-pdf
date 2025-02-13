<?php

namespace FluentPdf\Classes\Abstract;

use FluentPdf\Classes\PdfBuilder as ClassesPdfBuilder;

abstract class PdfBuilder
{
    protected $pdfBuilder;

    abstract public function header(): string;

    abstract public function body(): string;

    abstract public function footer(): string;

    public function __construct()
    {
        $this->pdfBuilder = new ClassesPdfBuilder;
    }

    public function getPdfContent()
    {
        return [
            'header' => $this->header(),
            'body'   => $this->body(),
            'footer' => $this->footer(),
        ];
    }


    public function generatePdf()
    {
        $content = $this->getPdfContent();
        return $this->pdfBuilder->generatePdf($content);
        // to-do we may write global method to generate pdf form hooks
    }
}
