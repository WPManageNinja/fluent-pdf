<?php

/**
 * Plugin Name: Fluent PDF Generator
 * Plugin URI:  https://wpmanageninja.com/downloads/fluentform-pro-add-on/
 * Description: Download and Email entries as pdf with multiple template for all Fluent Products. 
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.2.0
 * Text Domain: fluentform-pdf
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
define('FLUENTFORM_PDF_VERSION', '1.2.0');
define('FLUENTFORM_PDF_PATH', plugin_dir_path(__FILE__));
define('FLUENT_PDF_PATH', plugin_dir_path(__FILE__)); // For backward compatibility
define('FLUENTFORM_PDF_URL', plugin_dir_url(__FILE__));
define('FLUENTPDF_FRAMEWORK_UPGRADE', '1.0.0'); //TO REMOVE

define('FLUENT_PDF_DEVELOPMENT', 'yes');

require_once FLUENTFORM_PDF_PATH . 'vendor/autoload.php';
require_once FLUENTFORM_PDF_PATH . 'API/Pdf.php';
class FluentFormPdf
{
    public function boot()
    {
        if (!apply_filters('fluent_pdf_hide_menu', __return_false())) {
            (new FluentFormPdf\Classes\AdminMenuHandler())->register();
        };

        (new FluentFormPdf\Classes\PdfBuilder())->register();
        
        do_action('fluent_pdf_loaded');
    }
}

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'fluentform-pdf',
        false,
        basename(dirname(__FILE__)) . 'assets/languages'
    );

    (new FluentFormPdf())->boot();
});

add_action('init', function () {
    (new FluentFormPdf\Classes\Controller\GlobalFontManager())->registerAjax();
});

register_activation_hook(__FILE__, function () {
    // require_once FLUENTFORM_PDF_PATH . '/Classes/Controller/Activator.php';
    FluentFormPdf\Classes\Controller\Activator::activate();
});

register_deactivation_hook(__FILE__, function () {
    // require_once FLUENTFORM_PDF_PATH . '/Classes/Controller/Activator.php';
    FluentFormPdf\Classes\Controller\Activator::deactivate();
});
