<?php

namespace FluentFormPdf\Classes\Controller;

use FluentFormPdf\Classes\PdfBuilder;

class PdfManager
{
    public $pdfBuilder = null;

    public $pdfConfig = null;

    public function __construct()
    {
        $this->pdfConfig = new GlobalPdfConfig();
    }

    public function registerHooks()
    {
        add_action('fluent_pdf_cleanup_tmp_dir', array($this, 'cleanupTempDir'));
    }

    public function cleanupTempDir()
    {
        $max_file_age = time() - 6 * 3600; /* Max age is 6 hours old */
        $dirs = AvailableOptions::getDirStructure();
        $cleanUpDirs = [
            $dirs['tempDir'] . '/ttfontdata/',
            $dirs['pdfCacheDir'] . '/'
        ];

        foreach ($cleanUpDirs as $tmp_directory) {
            if (is_dir($tmp_directory)) {

                try {
                    $directory_list = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($tmp_directory, \RecursiveDirectoryIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::CHILD_FIRST
                    );

                    foreach ($directory_list as $file) {
                        if (in_array($file->getFilename(), ['.htaccess', 'index.html'], true)) {
                            continue;
                        }

                        if ($file->isReadable() && $file->getMTime() < $max_file_age) {
                            if (!$file->isDir()) {
                                unlink($file);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    //
                }
            }
        }
    }
}
