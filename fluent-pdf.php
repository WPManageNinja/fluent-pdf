<?php

/**
 * Plugin Name: Fluent PDF Generator
 * Plugin URI:  https://wpmanageninja.com/downloads/fluentform-pro-add-on/
 * Description: Download and Email entries as pdf with multiple template for all Fluent Products. 
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.0.2
 * Text Domain: fluent-pdf
 * Domain Path: /assets/languages
 */

/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright 2019 WPManageNinja LLC. All rights reserved.
 */

defined('ABSPATH') or die;

define('FLUENT_PDF', true);
define('FLUENT_PDF_VERSION', '1.0.2');
define('FLUENT_PDF_PATH', plugin_dir_path(__FILE__)); // For backward compatibility
define('FLUENT_PDF_URL', plugin_dir_url(__FILE__));
define('FLUENTPDF_FRAMEWORK_UPGRADE', '1.0.0'); //TO REMOVE

define('FLUENT_PDF_PRODUCTION', 'yes');

require_once FLUENT_PDF_PATH . 'vendor/autoload.php';
require_once FLUENT_PDF_PATH . 'API/Pdf.php';
require_once FLUENT_PDF_PATH . 'PluginManager/Updater.php';

class FluentPdf
{
    public function boot()
    {
        if (!apply_filters('fluent_pdf_hide_menu', __return_false())) {
            (new FluentPdf\Classes\AdminMenuHandler())->register();
        };

        (new FluentPdf\Classes\PdfBuilder())->register();
        
        do_action('fluent_pdf_loaded');
    }
}

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'fluent-pdf',
        false,
        basename(dirname(__FILE__)) . 'assets/languages'
    );

    (new FluentPdf())->boot();

    /**
     * Plugin Updater
     */
    $apiUrl = 'https://api.fluentcart.com/wp-admin/admin-ajax.php?action=fluent_pdf_update&time=' . time();
    new \FluentPdf\PluginManager\Updater($apiUrl, __FILE__, array(
        'version'   => FLUENT_PDF_VERSION,
        'license'   => '12345',
        'item_name' => 'fluent-pdf',
        'item_id'   => 'fluent-pdf',
        'author'    => 'wpmanageninja'
    ),
        array(
            'license_status' => 'valid',
            'admin_page_url' => admin_url('admin.php?page=fluent-pdf'),
            'purchase_url'   => 'https://wpmanageninja.com',
            'plugin_title'   => 'Fluent PDF Generator'
        )
    );

    add_filter('plugin_row_meta', function ($links, $pluginFile) {
        if (plugin_basename(__FILE__) !== $pluginFile) {
            return $links;
        }

        $checkUpdateUrl = esc_url(admin_url('plugins.php?fluent-pdf-check-update=' . time()));

        $row_meta = array(
            'check_update' => '<a style="color: #583fad;font-weight: 600;" href="' . $checkUpdateUrl . '" aria-label="' . esc_attr__('Check Update', 'fluent-pdf') . '">' . esc_html__('Check Update', 'fluent-pdf') . '</a>',
        );

        return array_merge($links, $row_meta);
    }, 10, 2);
});

add_action('init', function () {
    (new FluentPdf\Classes\Controller\GlobalFontManager())->registerAjax();
});

register_activation_hook(__FILE__, function () {
    // require_once FLUENT_PDF_PATH . '/Classes/Controller/Activator.php';
    FluentPdf\Classes\Controller\Activator::activate();
});

register_deactivation_hook(__FILE__, function () {
    // require_once FLUENT_PDF_PATH . '/Classes/Controller/Activator.php';
    FluentPdf\Classes\Controller\Activator::deactivate();
});
