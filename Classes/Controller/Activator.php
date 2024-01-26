<?php

namespace FluentPdf\Classes\Controller;

class Activator
{
    public static function activate()
    {
        self::maybeCreateFolderStructure();

        if (!wp_next_scheduled('fluent_pdf_cleanup_tmp_dir')) {
            wp_schedule_event(time(), 'daily', 'fluent_pdf_cleanup_tmp_dir');
        }
    }

    public static function maybeCreateFolderStructure()
    {
        $dirs = AvailableOptions::getDirStructure();

        /* add folders that need to be checked */
        $folders = [
            $dirs['workingDir'],
            $dirs['tempDir'],
            $dirs['pdfCacheDir'],
            $dirs['fontDir']
        ];

        /* create the required folder structure, or throw error */
        foreach ($folders as $dir) {
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
        }

        if (!is_file($dirs['workingDir'] . '/.htaccess')) {
            file_put_contents($dirs['workingDir'] . '/.htaccess', 'deny from all');
        }
    }


    public static function deactivate()
    {
        if (is_multisite()) {
            return;
        }

        wp_clear_scheduled_hook('fluent_pdf_cleanup_tmp_dir');

        $dirs = AvailableOptions::getDirStructure();

        /* delete folders that need to be checked */
        $folders = [
            $dirs['tempDir'],
            $dirs['pdfCacheDir'],
            $dirs['fontDir']
        ];

        if (!class_exists('\WP_Filesystem_Direct')) {
            $admin_path = ABSPATH . '/wp-admin/';
            if (!class_exists('\WP_Filesystem_Base')) {
                include_once $admin_path . 'includes/class-wp-filesystem-base.php';
            }
            include_once $admin_path . 'includes/class-wp-filesystem-direct.php';
        }

        $fileSystem = new \WP_Filesystem_Direct([]);

        foreach ($folders as $folder) {
            $fileSystem->delete($folder, true);
        }

        delete_option('_fluent_pdf_settings');
    }
}
