<?php

namespace FluentPdf\Modules\FluentForms;

use FluentForm\App\Modules\Acl\Acl;
use FluentForm\App\Helpers\Protector;
use FluentForm\Framework\Helpers\ArrayHelper;
use FluentForm\Framework\Foundation\Application;
use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentPdf\Classes\Controller\AvailableOptions;

class FluentFormsIntegration
{
    protected $app = null;

    protected $optionKey = '_fluent_pdf_settings';

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function register()
    {
        // Always show notice if old plugin is active
        add_action('admin_notices', [$this, 'maybeShowOldPluginNotice']);

        // Run migration if not done yet (covers edge case where old plugin
        // was deactivated before fluent-pdf was activated)
        Migration::maybeRun();

        // If old fluentforms-pdf plugin already registered hooks, skip ours
        if (has_action('wp_ajax_fluentform_pdf_admin_ajax_actions')) {
            return;
        }

        $this->registerHooks();
    }

    protected function registerHooks()
    {
        add_action('fluentform_pdf_cleanup_tmp_dir', [$this, 'cleanupTempDir']);

        // Global settings register
        add_filter('fluentform/global_settings_components', [$this, 'globalSettingMenu']);
        add_filter('fluentform/form_settings_menu', [$this, 'formSettingsMenu']);

        // Single form pdf settings fields ajax
        add_action(
            'wp_ajax_fluentform_get_form_pdf_template_settings',
            [$this, 'getFormTemplateSettings']
        );

        add_action('wp_ajax_fluentform_pdf_admin_ajax_actions', [$this, 'ajaxRoutes']);

        add_filter('fluentform/submissions_widgets', [$this, 'pushPdfButtons'], 10, 3);
        add_filter('fluentform/email_attachments', [$this, 'maybePushToEmail'], 10, 5);

        add_action('fluentform/addons_page_render_fluentform_pdf_settings', [$this, 'renderGlobalPage']);

        add_action('admin_notices', function () {
            if (!get_option($this->optionKey) && Acl::hasAnyFormPermission()) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fluentform_sanitize_html() escapes output
                echo fluentform_sanitize_html(
                    '<div class="notice notice-warning"><p>'
                    . esc_html__('Fluent PDF requires fonts to be downloaded. Please ', 'fluent-pdf')
                    . '<a href="' . admin_url('admin.php?page=fluent_forms_add_ons&sub_page=fluentform_pdf') . '">'
                    . esc_html__('click here', 'fluent-pdf') . '</a>'
                    . esc_html__(' to download and configure the settings', 'fluent-pdf')
                    . '</p></div>'
                );
            }
        });

        add_filter('fluentform/pdf_body_parse', function ($content, $entryId, $formData, $form) {
            if (!defined('FLUENTFORMPRO')) {
                return $content;
            }
            $processor = new \FluentFormPro\classes\ConditionalContent();
            return $processor::initiate($content, $entryId, $formData, $form);
        }, 10, 4);

        add_filter('fluentform/will_return_html', function ($isHtml, $provider) {
            if ($provider == 'pdfFeed') {
                return true;
            }
            return $isHtml;
        }, 10, 2);

        add_filter('fluentform/all_editor_shortcodes', [$this, 'pushShortCode'], 10, 2);
        add_filter(
            'fluentform/shortcode_parser_callback_pdf.download_link',
            [$this, 'createLink'],
            10,
            2
        );

        add_filter(
            'fluentform/shortcode_parser_callback_pdf.download_link.public',
            [$this, 'createPublicLink'],
            10,
            2
        );

        add_action('wp_ajax_fluentform_pdf_download', [$this, 'download']);
        add_action('wp_ajax_fluentform_pdf_download_public', [$this, 'downloadPublic']);
        add_action('wp_ajax_nopriv_fluentform_pdf_download_public', [$this, 'downloadPublic']);

        // Report PDF download — guard since ReportPdfGenerator is not yet ported
        add_action('wp_ajax_fluentform_report_download_pdf', [$this, 'handleReportPdfDownload']);
    }

    public function maybeShowOldPluginNotice()
    {
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if (!is_plugin_active('fluentforms-pdf/fluentforms-pdf.php')) {
            return;
        }

        $deactivateUrl = wp_nonce_url(
            admin_url('plugins.php?action=deactivate&plugin=fluentforms-pdf/fluentforms-pdf.php'),
            'deactivate-plugin_fluentforms-pdf/fluentforms-pdf.php'
        );

        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo esc_html__('Fluent Forms PDF (fluentforms-pdf) is no longer needed. All your PDF feeds and settings have been preserved in Fluent PDF. You can safely deactivate it.', 'fluent-pdf');
        echo ' <a href="' . esc_url($deactivateUrl) . '" class="button button-small">';
        echo esc_html__('Deactivate Now', 'fluent-pdf');
        echo '</a></p></div>';
    }

    public function globalSettingMenu($setting)
    {
        $setting['pdf_settings'] = [
            'hash'  => 'pdf_settings',
            'title' => __('PDF Settings', 'fluent-pdf'),
        ];

        return $setting;
    }

    public function formSettingsMenu($settingsMenus)
    {
        $settingsMenus['pdf'] = [
            'title' => __('PDF Feeds', 'fluent-pdf'),
            'slug'  => 'pdf-feeds',
            'hash'  => 'pdf',
            'route' => '/pdf-feeds',
        ];

        return $settingsMenus;
    }

    public function getFormTemplateSettings()
    {
        // placeholder for form template settings AJAX
    }

    public function ajaxRoutes()
    {
        $maps = [
            'get_global_settings' => 'getGlobalSettingsAjax',
            'save_global_settings' => 'saveGlobalSettings',
            'get_feeds'           => 'getFeedsAjax',
            'feed_lists'          => 'getFeedListAjax',
            'create_feed'         => 'createFeedAjax',
            'get_feed'            => 'getFeedAjax',
            'save_feed'           => 'saveFeedAjax',
            'delete_feed'         => 'deleteFeedAjax',
            'download_pdf'        => 'getPdf',
            'downloadFonts'       => 'downloadFonts',
        ];

        Acl::verify('fluentform_forms_manager');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified in Acl::verify
        $route = isset($_REQUEST['route']) ? sanitize_text_field(wp_unslash($_REQUEST['route'])) : '';

        if ($route && isset($maps[$route])) {
            $this->{$maps[$route]}();
        }
    }

    public function getGlobalSettingsAjax()
    {
        wp_send_json_success([
            'settings' => $this->globalSettings(),
            'fields'   => $this->getGlobalFields(),
        ]);
    }

    private function globalSettings()
    {
        $defaults = [
            'paper_size'         => 'A4',
            'orientation'        => 'P',
            'font'               => 'default',
            'font_size'          => '14',
            'font_color'         => '#323232',
            'accent_color'       => '#989797',
            'heading_color'      => '#000000',
            'language_direction' => 'ltr',
        ];

        $option = get_option($this->optionKey);
        if (!$option || !is_array($option)) {
            return $defaults;
        }

        return wp_parse_args($option, $defaults);
    }

    public function saveGlobalSettings()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified in previous function
        $settings = isset($_REQUEST['settings']) ? wp_unslash($_REQUEST['settings']) : [];

        $sanitizerMap = [
            'accent_color'       => 'sanitize_text_field',
            'font'               => 'sanitize_text_field',
            'font_color'         => 'sanitize_text_field',
            'font_size'          => 'intval',
            'heading_color'      => 'sanitize_text_field',
            'language_direction' => 'sanitize_text_field',
            'orientation'        => 'sanitize_text_field',
            'font_family'        => 'fluentform_sanitize_html',
        ];
        $settings = $this->sanitizeData($settings, $sanitizerMap);

        update_option($this->optionKey, $settings);
        wp_send_json_success([
            'message' => __('Settings successfully updated', 'fluent-pdf'),
        ], 200);
    }

    public function getFeedsAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $formId = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;

        if (!$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No form found!', 'fluent-pdf'),
            ], 423);
        }

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $feeds = $this->getFeeds($form->id);

        wp_send_json_success([
            'pdf_feeds' => $feeds,
            'templates' => $this->getAvailableTemplates($form),
        ], 200);
    }

    public function getFeedListAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $formId = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;
        if (!$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No form found!', 'fluent-pdf'),
            ], 423);
        }

        $feeds = $this->getFeeds($formId);

        $formattedFeeds = [];
        foreach ($feeds as $feed) {
            $formattedFeeds[] = [
                'label' => $feed['name'],
                'id'    => $feed['id'],
            ];
        }

        wp_send_json_success([
            'pdf_feeds' => $formattedFeeds,
        ], 200);
    }

    public function createFeedAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $templateName = sanitize_text_field(isset($_REQUEST['template']) ? wp_unslash($_REQUEST['template']) : '');
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $formId = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;

        if (!$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No form found!', 'fluent-pdf'),
            ], 423);
        }

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName]) || !$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No template found!', 'fluent-pdf'),
            ], 423);
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            wp_send_json_error([
                'message' => __('Sorry! No template Class found!', 'fluent-pdf'),
            ], 423);
        }
        $instance = new $class($this->app);

        $defaultSettings = $instance->getDefaultSettings($form);

        $sanitizerMap = [
            'header' => 'fluentform_sanitize_html',
            'footer' => 'fluentform_sanitize_html',
            'body'   => 'fluentform_sanitize_html',
        ];
        $defaultSettings = $this->sanitizeData($defaultSettings, $sanitizerMap);

        $data = [
            'name'         => $template['name'],
            'template_key' => $templateName,
            'settings'     => $defaultSettings,
            'appearance'   => $this->globalSettings(),
        ];

        $insertId = wpFluent()->table('fluentform_form_meta')
            ->insertGetId([
                'meta_key' => '_pdf_feeds', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'form_id'  => $formId,
                'value'    => wp_json_encode($data),
            ]);

        wp_send_json_success([
            'feed_id' => $insertId,
            'message' => esc_html__('Feed has been created, edit the feed now', 'fluent-pdf'),
        ], 200);
    }

    private function getFeeds($formId)
    {
        $feeds = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formId)
            ->where('meta_key', '_pdf_feeds')
            ->get();

        $formattedFeeds = [];
        foreach ($feeds as $feed) {
            $settings = json_decode($feed->value, true);
            $settings['id'] = $feed->id;
            $formattedFeeds[] = $settings;
        }

        return $formattedFeeds;
    }

    public function getFeedAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $formId = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;
        if (!$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No form found!', 'fluent-pdf'),
            ], 423);
        }

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $feedId = isset($_REQUEST['feed_id']) ? intval($_REQUEST['feed_id']) : 0;
        if (!$feedId) {
            wp_send_json_error([
                'message' => __('Sorry! No feed found!', 'fluent-pdf'),
            ], 423);
        }

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        $settings = json_decode($feed->value, true);

        $settings['appearance']['watermark_img_behind'] = ArrayHelper::isTrue($settings, 'appearance.watermark_img_behind');

        $templateName = ArrayHelper::get($settings, 'template_key');

        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName]) || !$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No template found!', 'fluent-pdf'),
            ], 423);
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            wp_send_json_error([
                'message' => __('Sorry! No template Class found!', 'fluent-pdf'),
            ], 423);
        }
        $instance = new $class($this->app);

        $globalFields = $this->getGlobalFields();

        $globalFields['watermark_image'] = [
            'key'       => 'watermark_image',
            'label'     => __('Watermark Image', 'fluent-pdf'),
            'component' => 'image_widget',
        ];

        $globalFields['watermark_text'] = [
            'key'         => 'watermark_text',
            'label'       => __('Watermark Text', 'fluent-pdf'),
            'component'   => 'text',
            'placeholder' => __('Watermark text', 'fluent-pdf'),
        ];

        $globalFields['watermark_opacity'] = [
            'key'        => 'watermark_opacity',
            'label'      => __('Watermark Opacity', 'fluent-pdf'),
            'component'  => 'number',
            'inline_tip' => __('Value should be between 1 to 100', 'fluent-pdf'),
        ];
        $globalFields['watermark_img_behind'] = [
            'key'        => 'watermark_img_behind',
            'label'      => __('Watermark Position', 'fluent-pdf'),
            'component'  => 'checkbox-single',
            'inline_tip' => __('Set as background', 'fluent-pdf'),
        ];

        $globalFields['security_pass'] = [
            'key'        => 'security_pass',
            'label'      => 'PDF Password',
            'component'  => 'text',
            'inline_tip' => __('If you want to set password please enter password otherwise leave it empty', 'fluent-pdf'),
        ];

        $settingsFields = $instance->getSettingsFields();

        $settingsFields[] = [
            'key'       => 'allow_download',
            'label'     => __('Allow Download', 'fluent-pdf'),
            'tips'      => __('Allow this feed to be downloaded on form submission. Only logged in users will be able to download.', 'fluent-pdf'),
            'component' => 'radio_choice',
            'options'   => [
                true  => __('Yes', 'fluent-pdf'),
                false => __('No', 'fluent-pdf'),
            ],
        ];

        $settingsFields[] = [
            'key'       => 'shortcode',
            'label'     => __('Shortcode', 'fluent-pdf'),
            'tips'      => __('Use this shortcode on submission message to generate PDF link.', 'fluent-pdf'),
            'component' => 'text',
            'readonly'  => true,
        ];

        $settings['settings']['shortcode'] = '{pdf.download_link.' . $feedId . '}';

        wp_send_json_success([
            'feed'              => $settings,
            'settings_fields'   => $settingsFields,
            'appearance_fields' => $globalFields,
        ], 200);
    }

    public function saveFeedAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $formId = isset($_REQUEST['form_id']) ? intval($_REQUEST['form_id']) : 0;
        if (!$formId) {
            wp_send_json_error([
                'message' => __('Sorry! No form found!', 'fluent-pdf'),
            ], 423);
        }

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $formId)
            ->first();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $feedId = isset($_REQUEST['feed_id']) ? intval($_REQUEST['feed_id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce verified previously
        $feed = isset($_REQUEST['feed']) ? wp_unslash($_REQUEST['feed']) : [];

        if (empty($feed['name'])) {
            wp_send_json_error([
                'message' => __('Feed name is required', 'fluent-pdf'),
            ], 423);
        }

        $sanitizerMap = [
            'name'                 => 'sanitize_text_field',
            'header'               => 'fluentform_sanitize_html',
            'footer'               => 'fluentform_sanitize_html',
            'body'                 => 'fluentform_sanitize_html',
            'shortcode'            => 'sanitize_text_field',
            'allow_download'       => 'rest_sanitize_boolean',
            'logo'                 => 'sanitize_url',
            'invoice_upper_text'   => 'sanitize_text_field',
            'invoice_thanks'       => 'sanitize_text_field',
            'invoice_prefix'       => 'sanitize_text_field',
            'customer_name'        => 'sanitize_text_field',
            'customer_email'       => 'sanitize_email',
            'watermark_img_behind' => 'rest_sanitize_boolean',
            'customer_address'     => 'sanitize_text_field',
        ];
        $feed = $this->sanitizeData($feed, $sanitizerMap);

        wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->update([
                'value' => wp_json_encode($feed),
            ]);

        wp_send_json_success([
            'message' => __('Settings successfully updated', 'fluent-pdf'),
        ], 200);
    }

    public function deleteFeedAjax()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $feedId = isset($_REQUEST['feed_id']) ? intval($_REQUEST['feed_id']) : 0;
        if (!$feedId) {
            wp_send_json_error([
                'message' => __('Sorry! No feed found!', 'fluent-pdf'),
            ], 423);
        }

        wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->delete();

        wp_send_json_success([
            'message' => __('Feed successfully deleted', 'fluent-pdf'),
        ], 200);
    }

    public function getAvailableTemplates($form)
    {
        $templates = [
            'general' => [
                'name'    => 'General',
                'class'   => '\FluentPdf\Modules\FluentForms\Templates\GeneralTemplate',
                'key'     => 'general',
                'preview' => FLUENT_PDF_URL . 'assets/images/basic_template.png',
            ],
        ];

        if ($form->has_payment) {
            $templates['invoice'] = [
                'name'    => 'Invoice',
                'class'   => '\FluentPdf\Modules\FluentForms\Templates\InvoiceTemplate',
                'key'     => 'invoice',
                'preview' => FLUENT_PDF_URL . 'assets/images/tabular.png',
            ];
        }

        // Support old filter name for backwards compatibility with custom templates
        $templates = apply_filters_deprecated(
            'fluentform_pdf_templates',
            [$templates, $form],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/pdf_templates',
            'Use fluentform/pdf_templates instead of fluentform_pdf_templates.'
        );

        return apply_filters('fluentform/pdf_templates', $templates, $form);
    }

    public function getGlobalFields()
    {
        return [
            [
                'key'       => 'paper_size',
                'label'     => __('Paper size', 'fluent-pdf'),
                'component' => 'dropdown',
                'tips'      => __('All available templates are shown here, select a default template', 'fluent-pdf'),
                'options'   => AvailableOptions::getPaperSizes(),
            ],
            [
                'key'       => 'orientation',
                'label'     => __('Orientation', 'fluent-pdf'),
                'component' => 'dropdown',
                'options'   => AvailableOptions::getOrientations(),
            ],
            [
                'key'         => 'font_family',
                'label'       => __('Font Family', 'fluent-pdf'),
                'component'   => 'dropdown-group',
                'placeholder' => __('Select Font', 'fluent-pdf'),
                'options'     => AvailableOptions::getInstalledFonts(),
            ],
            [
                'key'       => 'font_size',
                'label'     => __('Font size', 'fluent-pdf'),
                'component' => 'number',
            ],
            [
                'key'       => 'font_color',
                'label'     => __('Font color', 'fluent-pdf'),
                'component' => 'color_picker',
            ],
            [
                'key'       => 'heading_color',
                'label'     => __('Heading color', 'fluent-pdf'),
                'tips'      => __('Select Heading Color', 'fluent-pdf'),
                'component' => 'color_picker',
            ],
            [
                'key'       => 'accent_color',
                'label'     => __('Accent color', 'fluent-pdf'),
                'tips'      => __('The accent color is used for the borders, breaks etc.', 'fluent-pdf'),
                'component' => 'color_picker',
            ],
            [
                'key'       => 'language_direction',
                'label'     => __('Language Direction', 'fluent-pdf'),
                'tips'      => __('Script like Arabic and Hebrew are written right to left. For Arabic/Hebrew please select RTL', 'fluent-pdf'),
                'component' => 'radio_choice',
                'options'   => [
                    'ltr' => __('LTR', 'fluent-pdf'),
                    'rtl' => __('RTL', 'fluent-pdf'),
                ],
            ],
        ];
    }

    public function pushPdfButtons($widgets, $data, $submission)
    {
        $formId = $submission->form_id;
        if (!$formId) {
            return $widgets;
        }

        if (
            isset($submission->type) &&
            (
                $submission->type === 'step_data' ||
                $submission->type === 'saved_state_data'
            )
        ) {
            return $widgets;
        }

        if (!isset($submission->serial_number)) {
            return $widgets;
        }

        $feeds = $this->getFeeds($formId);
        if (!$feeds) {
            return $widgets;
        }

        $widgetData = [
            'title' => __('PDF Downloads', 'fluent-pdf'),
            'type'  => 'html_content',
        ];

        $fluent_forms_admin_nonce = wp_create_nonce('fluent_forms_admin_nonce');

        $contents = '<ul class="ff_list_items">';
        foreach ($feeds as $feed) {
            $fileName = ShortCodeParser::parse(
                $feed['name'],
                $submission->id,
                json_decode($submission->response, true)
            );

            $contents .= '<li><a href="' . admin_url('admin-ajax.php?action=fluentform_pdf_admin_ajax_actions&fluent_forms_admin_nonce=' . $fluent_forms_admin_nonce . '&route=download_pdf&submission_id=' . $submission->id . '&id=' . $feed['id']) . '" target="_blank"><span style="font-size: 12px;" class="dashicons dashicons-arrow-down-alt"></span>' . esc_html($fileName) . '</a></li>';
        }
        $contents .= '</ul>';
        $widgetData['content'] = $contents;

        $widgets['pdf_feeds'] = $widgetData;
        return $widgets;
    }

    public function getPdfConfig($settings, $default)
    {
        return [
            'mode'        => 'utf-8',
            'format'      => ArrayHelper::get($settings, 'paper_size', ArrayHelper::get($default, 'paper_size')),
            'orientation' => ArrayHelper::get($settings, 'orientation', ArrayHelper::get($default, 'orientation')),
        ];
    }

    public function getPdf()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $feedId = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
        $submissionId = isset($_REQUEST['submission_id']) ? intval($_REQUEST['submission_id']) : 0;

        if (!$feedId || !$submissionId) {
            die(esc_html__('Sorry! No feed found', 'fluent-pdf'));
        }

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('id', $feedId)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        $settings = json_decode($feed->value, true);
        $settings['id'] = $feed->id;

        $form = wpFluent()->table('fluentform_forms')
            ->where('id', $feed->form_id)
            ->first();

        $templateName = ArrayHelper::get($settings, 'template_key');
        $templates = $this->getAvailableTemplates($form);

        if (!isset($templates[$templateName])) {
            die(esc_html__('Sorry! No template found', 'fluent-pdf'));
        }

        $template = $templates[$templateName];

        $class = $template['class'];
        if (!class_exists($class)) {
            die(esc_html__('Sorry! No template class found', 'fluent-pdf'));
        }

        $instance = new $class($this->app);
        $instance->viewPDF($submissionId, $settings);
    }

    public function maybePushToEmail($emailAttachments, $emailData, $formData, $entry, $form)
    {
        if (!ArrayHelper::get($emailData, 'pdf_attachments')) {
            return $emailAttachments;
        }

        $pdfFeedIds = ArrayHelper::get($emailData, 'pdf_attachments');

        $feeds = wpFluent()->table('fluentform_form_meta')
            ->whereIn('id', $pdfFeedIds)
            ->where('meta_key', '_pdf_feeds')
            ->where('form_id', $form->id)
            ->get();

        $templates = $this->getAvailableTemplates($form);

        foreach ($feeds as $feed) {
            $settings = json_decode($feed->value, true);
            $settings['id'] = $feed->id;
            $templateName = ArrayHelper::get($settings, 'template_key');

            if (!isset($templates[$templateName])) {
                continue;
            }
            $template = $templates[$templateName];
            $class = $template['class'];
            if (!class_exists($class)) {
                continue;
            }
            $instance = new $class($this->app);

            $fileName = $settings['name'] . '_' . $entry->id . '_' . $feed->id;
            $fileName = ShortCodeParser::parse($fileName, $entry->id, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');

            if (is_multisite()) {
                $fileName .= '_' . get_current_blog_id();
            }

            $file = $instance->outputPDF($entry->id, $settings, $fileName, false);
            if ($file) {
                $emailAttachments[] = $file;
            }
        }

        return $emailAttachments;
    }

    public function renderGlobalPage()
    {
        // Delegate to fluent-pdf's existing font manager UI
        (new \FluentPdf\Classes\Controller\GlobalFontManager())->renderGlobalPage();
    }

    public function downloadFonts()
    {
        // Delegate to fluent-pdf's existing font download handler
        (new \FluentPdf\Classes\Controller\GlobalFontManager())->downloadFonts();
    }

    public function cleanupTempDir()
    {
        $max_file_age = time() - 6 * 3600;
        $dirs = AvailableOptions::getDirStructure();
        $cleanUpDirs = [
            $dirs['tempDir'] . '/ttfontdata/',
            $dirs['pdfCacheDir'] . '/',
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
                                wp_delete_file($file);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    //
                }
            }
        }
    }

    public function pushShortCode($shortCodes, $formID)
    {
        $feeds = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $formID)
            ->where('meta_key', '_pdf_feeds')
            ->get();

        $feedShortCodes = [
            '{pdf.download_link}' => 'Submission PDF link',
        ];

        foreach ($feeds as $feed) {
            $feedSettings = json_decode($feed->value);
            $key = '{pdf.download_link.' . $feed->id . '}';
            $feedShortCodes[$key] = $feedSettings->name . ' feed PDF link';
        }

        $shortCodes[] = [
            'title'      => __('PDF', 'fluent-pdf'),
            'shortcodes' => $feedShortCodes,
        ];

        return $shortCodes;
    }

    public function createLink($shortCode, $parser)
    {
        $form = $parser->getForm();
        $entry = $parser->getEntry();

        $feed = wpFluent()->table('fluentform_form_meta')
            ->where('form_id', $form->id)
            ->where('meta_key', '_pdf_feeds')
            ->first();

        if ($feed) {
            $feedSettings = json_decode($feed->value, true);

            if (ArrayHelper::get($feedSettings, 'settings.allow_download')) {
                $nonce = wp_create_nonce('fluent_forms_admin_nonce');

                $url = admin_url('admin-ajax.php?action=fluentform_pdf_download&fluent_forms_admin_nonce=' . $nonce . '&submission_id=' . $entry->id . '&id=' . $feed->id);

                return $url;
            }
        }
    }

    public function download()
    {
        Acl::verifyNonce();

        if (!is_user_logged_in()) {
            wp_send_json_error([
                'message' => __('Sorry! You have to login first.', 'fluent-pdf'),
            ], 422);
        }

        $hasPermission = Acl::hasPermission('fluentform_entries_viewer');

        if (!$hasPermission) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verified previously
            $submissionId = isset($_REQUEST['submission_id']) ? intval($_REQUEST['submission_id']) : 0;
            $submission = null;
            if ($submissionId) {
                $submission = wpFluent()->table('fluentform_submissions')
                    ->where('id', $submissionId)
                    ->where('user_id', get_current_user_id())
                    ->first();
            }

            if (!$submission) {
                wp_send_json_error([
                    'message' => __("You don't have permission to download the PDF.", 'fluent-pdf'),
                ], 422);
            }
        }

        return $this->getPdf();
    }

    public function createPublicLink($shortCode, $parser)
    {
        $feedID = str_replace('pdf.download_link.', '', $shortCode);

        if ($feedID) {
            $feed = wpFluent()->table('fluentform_form_meta')
                ->where('id', $feedID)
                ->first();

            if ($feed) {
                $entry = $parser->getEntry();
                $hashedEntryID = base64_encode(Protector::encrypt($entry->id));
                $hashedFeedID = base64_encode(Protector::encrypt($feedID));

                return admin_url('admin-ajax.php?action=fluentform_pdf_download_public&submission_id=' . $hashedEntryID . '&id=' . $hashedFeedID);
            }
        }
    }

    public function downloadPublic()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $feedId = isset($_REQUEST['id']) ? intval(Protector::decrypt(base64_decode($_REQUEST['id']))) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $submissionId = isset($_REQUEST['submission_id']) ? intval(Protector::decrypt(base64_decode($_REQUEST['submission_id']))) : 0;

        $_REQUEST['id'] = $feedId;
        $_REQUEST['submission_id'] = $submissionId;

        return $this->getPdf();
    }

    /**
     * Handle report PDF download. Guards against missing ReportPdfGenerator
     * since it has not been ported yet.
     */
    public function handleReportPdfDownload()
    {
        Acl::verify('fluentform_entries_viewer');

        // Try the old plugin's class first (if still installed but deactivated files remain)
        if (class_exists('\FluentFormPdf\Classes\Report\ReportPdfGenerator')) {
            try {
                $pdfGenerator = new \FluentFormPdf\Classes\Report\ReportPdfGenerator();
                $pdfGenerator->generatePdf($_REQUEST); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            } catch (\Exception $e) {
                // fall through to error
            }
        }

        wp_send_json_error([
            'message' => __('Report PDF generation is not available yet. Please check for plugin updates.', 'fluent-pdf'),
        ], 422);
    }

    private function sanitizeData($settings, $sanitizerMap)
    {
        if (fluentformCanUnfilteredHTML()) {
            return $settings;
        }

        return fluentform_backend_sanitizer($settings, $sanitizerMap);
    }
}
