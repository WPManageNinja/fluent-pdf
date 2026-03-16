<?php

namespace FluentPdf\Classes\Controller;

class FontDownloader
{

    private $github_repo = 'https://raw.githubusercontent.com/WPManageNinja/mpdf-core-fonts/master/';

    public function getCoreFonts()
    {
        $json = file_get_contents(FLUENT_PDF_PATH . '/core-fonts.json');
        return json_decode($json, true);
    }

    public function getDownloadableFonts($limit = 0)
    {
        $fontDir = $this->getFontDir();
        if (!function_exists('\list_files')) {
            $admin_path = ABSPATH . '/wp-admin/';
            include_once $admin_path . 'includes/file.php';
        }
        $downloadedFiles = \list_files($fontDir, 1);

        $fileNames = [];
        foreach ($downloadedFiles as $file) {
            $fileNames[] = str_replace($fontDir, '', $file);
        }
        $coreFonts = $this->getCoreFonts();
        $downloadableFonts = [];
        foreach ($coreFonts as $coreFont) {
            if ($limit && count($downloadableFonts) == $limit) {
                return $downloadableFonts;
            }
            if (!in_array($coreFont['name'], $fileNames)) {
                $downloadableFonts[] = $coreFont;
            }
        }
        return $downloadableFonts;
    }

    public function download($fontName)
    {
        $destination = $this->getFontDir();
        $res = wp_remote_get(
            $this->github_repo . $fontName,
            [
                'timeout'  => 60,
                'stream'   => true,
                'filename' => $destination . $fontName,
            ]
        );

        /* Check for errors and log them to file */
        if (is_wp_error($res)) {
            return $res;
        }

        $res_code = wp_remote_retrieve_response_code($res);
        if ($res_code !== 200) {
            return new \WP_Error('failed', __('Core Font API Response Failed', 'fluent-pdf'));
        }
        return true;
    }

    private function getFontDir()
    {
        $dirStructure = AvailableOptions::getDirStructure();
        return $dirStructure['fontDir'] . '/';
    }
}
