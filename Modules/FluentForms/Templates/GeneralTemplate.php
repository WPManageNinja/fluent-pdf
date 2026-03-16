<?php

namespace FluentPdf\Modules\FluentForms\Templates;

use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\Framework\Foundation\Application;

class GeneralTemplate extends TemplateManager
{
    public $headerHtml = '';

    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function getDefaultSettings($form)
    {
        return [
            'header' => '<h2>PDF Title</h2>',
            'footer' => '<table width="100%"><tr><td width="50%">{DATE j-m-Y}</td><td width="50%"  style="text-align: right;" align="right">{PAGENO}/{nbpg}</td></tr></table>',
            'body'   => '{all_data}',
        ];
    }

    public function getSettingsFields()
    {
        return [
            [
                'key'       => 'header',
                'label'     => __('Header Content', 'fluent-pdf'),
                'tips'      => __('Write your header content which will be shown every page of the PDF', 'fluent-pdf'),
                'component' => 'wp-editor',
            ],
            [
                'key'        => 'body',
                'label'      => __('PDF Body Content', 'fluent-pdf'),
                'tips'       => __('Write your Body content for actual PDF body', 'fluent-pdf'),
                'component'  => 'wp-editor',
                'inline_tip' => defined('FLUENTFORMPRO') ?
                    sprintf(
                        '%1$s %2$s.',
                        __('You can use Conditional Content in PDF body, for details please check this', 'fluent-pdf'),
                        '<a target="_blank" href="https://wpmanageninja.com/docs/fluent-form/advanced-features-functionalities-in-wp-fluent-form/conditional-shortcodes-in-email-notifications-form-confirmation/">Documentation</a>'
                    )
                    : __('Conditional PDF Body Content is supported in Fluent Forms Pro Version', 'fluent-pdf'),
            ],
            [
                'key'        => 'footer',
                'label'      => __('Footer Content', 'fluent-pdf'),
                'tips'       => __('Write your Footer content which will be shown every page of the PDF', 'fluent-pdf'),
                'component'  => 'wp-editor',
                'inline_tip' => __('Write your Footer content which will be shown every page of the PDF', 'fluent-pdf'),
            ],
        ];
    }

    public function generatePdf($submissionId, $feed, $outPut = 'I', $fileName = '')
    {
        $settings = $feed['settings'];
        $submission = wpFluent()->table('fluentform_submissions')
            ->where('id', $submissionId)
            ->where('status', '!=', 'trashed')
            ->first();
        if (!$submission) {
            return '';
        }

        $formData = json_decode($submission->response, true);

        $settings = ShortCodeParser::parse($settings, $submissionId, $formData, null, false, 'pdfFeed');

        if (!empty($settings['header'])) {
            $this->headerHtml = $settings['header'];
        }

        $htmlBody = $settings['body'];

        $form = wpFluent()->table('fluentform_forms')->find($submission->form_id);

        $htmlBody = apply_filters_deprecated(
            'ff_pdf_body_parse',
            [$htmlBody, $submissionId, $formData, $form],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/pdf_body_parse',
            'Use fluentform/pdf_body_parse instead of ff_pdf_body_parse.'
        );

        $htmlBody = apply_filters('fluentform/pdf_body_parse', $htmlBody, $submissionId, $formData, $form);

        $footer = $settings['footer'];

        if (!$fileName) {
            $fileName = ShortCodeParser::parse($feed['name'], $submissionId, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');
        }

        return $this->pdfBuilder($fileName, $feed, $htmlBody, $footer, $outPut);
    }
}
