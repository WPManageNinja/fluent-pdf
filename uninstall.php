<?php

/**
 * Fired when the plugin is deleted (not just deactivated).
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

define('FLUENT_PDF_PATH', plugin_dir_path(__FILE__));

require_once FLUENT_PDF_PATH . 'vendor/autoload.php';

FluentPdf\Classes\Controller\Activator::uninstall();
