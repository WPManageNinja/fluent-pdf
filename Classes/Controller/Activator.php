<?php

namespace FluentPdf\Classes\Controller;

use FluentPdf\Modules\FluentForms\Migration;

class Activator
{
    public static function activate()
    {
        self::maybeCreateFolderStructure();

        if (!wp_next_scheduled('fluent_pdf_cleanup_tmp_dir')) {
            wp_schedule_event(time(), 'daily', 'fluent_pdf_cleanup_tmp_dir');
        }

        // Migrate settings from old fluentforms-pdf plugin if Fluent Forms is active
        if (defined('FLUENTFORM')) {
            Migration::maybeRun();
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

        // Only clean up temp/cache dirs — preserve fonts and settings
        // so users don't lose data on deactivate/reactivate cycles.
        // Full cleanup (fonts, settings) happens in uninstall.php.
        $dirs = AvailableOptions::getDirStructure();

        $folders = [
            $dirs['tempDir'],
            $dirs['pdfCacheDir'],
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
    }

    /**
     * Full cleanup — called from uninstall.php only.
     * Removes fonts, settings, and migration flags.
     */
    public static function uninstall()
    {
        if (is_multisite()) {
            return;
        }

        $dirs = AvailableOptions::getDirStructure();

        $folders = [
            $dirs['tempDir'],
            $dirs['pdfCacheDir'],
            $dirs['fontDir'],
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
        delete_option('_fluent_pdf_migration_completed');
    }
}
