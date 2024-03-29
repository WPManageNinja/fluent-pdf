<?php

namespace FluentPdf\Classes;

use FluentPdf\Classes\Controller\GlobalFontManager;


class AdminMenuHandler
{
    public function register()
    {
        add_action('admin_menu',  function () {
            $this->addMenu();
        });
    }

    public function addMenu()
    {
        $title = __('Fluent PDF', 'fluent-pdf');

        add_menu_page(
            $title,
            $title,
            'manage_options',
            'fluent_pdf.php',
            array($this, 'render'),
            'dashicons-media-document',
            99
        );

        add_submenu_page(
            'fluent_pdf.php',
            __('Dashboard', 'fluent-pdf'),
            __('Dashboard', 'fluent-pdf'),
            'manage_options',
            'fluent_pdf.php',
            array($this, 'render')
        );

        add_submenu_page(
            'fluent_pdf.php',
            __('Global Settings', 'fluent-pdf'),
            __('Global Settings', 'fluent-pdf'),
            'manage_options',
            'fluent_pdf.php/settings',
            array($this, 'renderSettings')
        );
    }

    public function renderSettings()
    {
        Vite::enqueueScript('fluent-pdf-script-boot', 'admin/start.js', array('jquery'), FLUENT_PDF_VERSION, true);
        Vite::enqueueStyle('fluent-pdf-style-boot', 'scss/admin/app.scss', array(), FLUENT_PDF_VERSION);

        $fluentPdfVars = apply_filters('fluent-pdf/admin_app_vars', array(
            'assets_url' => FLUENT_PDF_URL . 'assets/',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fluent_pdf_nonce'),
        ));

        wp_localize_script('fluent-pdf-script-boot', 'fluent_pdf_admin', $fluentPdfVars);

        echo '<div class="fluent-pdf-admin-page" id="fluent-pdf_app">
                <router-view></router-view>
            </div>';
    }

    public function render()
    {
        (new GlobalFontManager())->renderGlobalPage();
    }
}
