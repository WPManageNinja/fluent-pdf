<?php

/**
 * Plugin Name: Fluent PDF Generator
 * Plugin URI:  https://wpmanageninja.com/downloads/fluentform-pro-add-on/
 * Description: Download and Email entries as pdf with multiple template for all Fluent Products.
 * Author: WPManageNinja LLC
 * Author URI:  https://wpmanageninja.com
 * Version: 1.1.0
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
define('FLUENT_PDF_VERSION', '1.1.0');
define('FLUENT_PDF_PATH', plugin_dir_path(__FILE__));
define('FLUENT_PDF_URL', plugin_dir_url(__FILE__));
define('FLUENT_PDF_PRODUCTION', 'yes');

// Fluent Forms compatibility constants — enables FF core's PDF UI.
// VERSION defined early so FF core detects PDF support (hasPDF flag).
// PATH/URL deferred to plugins_loaded so the old fluentforms-pdf plugin
// can define them first with its own paths (load order is not guaranteed).
if (!defined('FLUENTFORM_PDF_VERSION')) {
    define('FLUENTFORM_PDF_VERSION', FLUENT_PDF_VERSION);
}
add_action('plugins_loaded', function() {
    // By now all plugin files are loaded — safe to set path/url
    // only if old plugin didn't already define them.
    if (!defined('FLUENTFORM_PDF_PATH')) {
        define('FLUENTFORM_PDF_PATH', FLUENT_PDF_PATH);
    }
    if (!defined('FLUENTFORM_PDF_URL')) {
        define('FLUENTFORM_PDF_URL', FLUENT_PDF_URL);
    }
}, 1);

require_once FLUENT_PDF_PATH . 'vendor/autoload.php';
require_once FLUENT_PDF_PATH . 'API/Pdf.php';

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

add_action('plugins_loaded', function() {
    (new FluentPdf())->boot();
});

// Load Fluent Forms integration at a later priority so we can detect
// if the old fluentforms-pdf plugin already registered its hooks
add_action('plugins_loaded', function() {
    if (defined('FLUENTFORM') && function_exists('wpFluentForm')) {
        (new FluentPdf\Modules\FluentForms\FluentFormsIntegration(wpFluentForm()))->register();
    }
}, 20);

add_action('init', function() {
    (new FluentPdf\Classes\Controller\GlobalFontManager())->registerAjax();
});

register_activation_hook(__FILE__, function() {
    FluentPdf\Classes\Controller\Activator::activate();
});

register_deactivation_hook(__FILE__, function() {
    FluentPdf\Classes\Controller\Activator::deactivate();
});
