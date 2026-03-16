<?php

namespace FluentPdf\Modules\FluentForms\Templates;

use FluentForm\App\Services\FormBuilder\ShortCodeParser;
use FluentForm\Framework\Foundation\Application;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;

class InvoiceTemplate extends TemplateManager
{
    public function __construct(Application $app)
    {
        parent::__construct($app);
    }

    public function getDefaultSettings($form)
    {
        return [
            'logo'               => '',
            'invoice_upper_text' => '',
            'invoice_thanks'     => 'Thank you for your order',
            'invoice_prefix'     => '',
            'customer_name'      => '',
            'customer_email'     => '',
            'customer_address'   => '',
        ];
    }

    public function getSettingsFields()
    {
        return [
            [
                'key'       => 'logo',
                'label'     => __('Business Logo', 'fluent-pdf'),
                'tips'      => __('Your Business Logo which will be shown in the invoice header', 'fluent-pdf'),
                'component' => 'image_widget',
            ],
            [
                'key'       => 'customer_name',
                'label'     => __('Customer Name', 'fluent-pdf'),
                'tips'      => __('Please select the customer name field from the smartcode dropdown', 'fluent-pdf'),
                'component' => 'value_text',
            ],
            [
                'key'       => 'customer_email',
                'label'     => __('Customer Email', 'fluent-pdf'),
                'tips'      => __('Please select the customer email field from the smartcode dropdown', 'fluent-pdf'),
                'component' => 'value_text',
            ],
            [
                'key'       => 'customer_address',
                'label'     => __('Customer Address', 'fluent-pdf'),
                'tips'      => __('Please select the customer address field from the smartcode dropdown', 'fluent-pdf'),
                'component' => 'value_text',
            ],
            [
                'key'       => 'invoice_prefix',
                'label'     => __('Invoice Prefix', 'fluent-pdf'),
                'tips'      => __('Add your invoice prefix which will be prepended with the invoice number', 'fluent-pdf'),
                'component' => 'value_text',
            ],
            [
                'key'       => 'invoice_upper_text',
                'label'     => __('Invoice Body Text', 'fluent-pdf'),
                'tips'      => __('Write Invoice body text. This will show before the invoice items', 'fluent-pdf'),
                'component' => 'wp-editor',
            ],
            [
                'key'       => 'invoice_thanks',
                'label'     => __('Invoice Footer Text', 'fluent-pdf'),
                'tips'      => __('Write Invoice Footer Text. This will show at the end of the invoice', 'fluent-pdf'),
                'component' => 'value_textarea',
            ],
        ];
    }

    public function generatePdf($submissionId, $feed, $outPut, $fileName = '')
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

        $settings['invoice_lines'] = '{payment.order_items}';
        $invoiceUpperText = Arr::get($settings, 'invoice_upper_text', '');
        if (
            false !== strpos($invoiceUpperText, '{payment.receipt}') ||
            false !== strpos($invoiceUpperText, '{payment.order_items}')
        ) {
            $settings['invoice_lines'] = '';
        }
        $settings['payment_summary'] = '{payment.summary_list}';
        $settings = ShortCodeParser::parse($settings, $submissionId, $formData, null, false, 'pdfFeed');

        $htmlBody = $this->generateInvoiceHTML($submission, $settings, $feed);

        $htmlBody = str_replace('{page_break}', '<page_break />', $htmlBody);

        $form = wpFluent()->table('fluentform_forms')->find($submission->form_id);

        $htmlBody = apply_filters_deprecated(
            'ff_pdf_body_parse',
            [$htmlBody, $submissionId, $formData, $form],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/pdf_body_parse',
            'Use fluentform/pdf_body_parse instead of ff_pdf_body_parse.'
        );

        $htmlBody = apply_filters('fluentform/pdf_body_parse', $htmlBody, $submissionId, $formData, $form);

        if (!$fileName) {
            $fileName = ShortCodeParser::parse($feed['name'], $submissionId, $formData);
            $fileName = sanitize_title($fileName, 'pdf-file', 'display');
        }

        return $this->pdfBuilder($fileName, $feed, $htmlBody, '', $outPut);
    }

    private function generateInvoiceHTML($submission, $settings, $feed)
    {
        $paymentSettings = false;
        $logo = Arr::get($settings, 'logo');
        if (class_exists('\FluentFormPro\Payments\PaymentHelper')) {
            $paymentSettings = \FluentFormPro\Payments\PaymentHelper::getPaymentSettings();
            if (!$logo) {
                $logo = Arr::get($paymentSettings, 'business_logo');
            }
        }

        ob_start();
        ?>
        <table style="width: 100%; border: 0px solid transparent;">
            <tr>
                <td style="width: 40%" class="business_details">
                    <?php if ($logo): ?>
                    <div class="business_logo">
                        <img src="<?php echo esc_url($logo); ?>" alt="Business logo"/>
                    </div>
                    <?php endif; ?>
                    <?php if ($paymentSettings): ?>
                    <div class="business_address">
                        <div class="business_name"><?php echo fluentform_sanitize_html(Arr::get($paymentSettings, 'business_name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                        <div class="business_address"><?php echo fluentform_sanitize_html(Arr::get($paymentSettings, 'business_address')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="width: 20%"></td>
                <td style="width: 40%" class="customer_row">
                    <?php if (Arr::get($settings, 'invoice_prefix')): ?>
                        <h2 style="padding-bottom: 30px" class="invoice_title"><?php esc_html_e('RECEIPT:', 'fluent-pdf'); ?> <?php echo fluentform_sanitize_html(Arr::get($settings, 'invoice_prefix')) . '-' . esc_html($submission->serial_number); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
                        <br/>
                    <?php endif; ?>

                    <div class="heading_items">
                        <div class="order_number"><b><?php esc_html_e('Order Number:', 'fluent-pdf'); ?></b> <?php echo fluentform_sanitize_html($submission->id); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                        <div class="payment_date"><b><?php esc_html_e('Payment Date:', 'fluent-pdf'); ?></b> <?php echo fluentform_sanitize_html(date(get_option('date_format'), strtotime($submission->created_at))); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date, WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
                        <br />
                        <div class="customer_details">
                            <?php if (Arr::get($settings, 'customer_name') || Arr::get($settings, 'customer_address') || Arr::get($settings, 'customer_email')): ?>
                                <p style="font-weight: bold; margin-bottom:10px;" class="customer_heading"><?php esc_html_e('Customer Details', 'fluent-pdf'); ?></p>
                                <p class="customer_name"><?php echo fluentform_sanitize_html(Arr::get($settings, 'customer_name')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                <p class="customer_address"><?php echo fluentform_sanitize_html(Arr::get($settings, 'customer_address')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                                <p class="customer_email"><?php echo fluentform_sanitize_html(Arr::get($settings, 'customer_email')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <hr />
        <div class="receipt_upper_text"><?php echo fluentform_sanitize_html(Arr::get($settings, 'invoice_upper_text')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

        <div class="invoice_lines"><?php echo fluentform_sanitize_html(Arr::get($settings, 'invoice_lines')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>

        <?php if (strpos(Arr::get($settings, 'payment_summary'), 'class="ffp_payment_info_table"') !== false): ?>
            <div class="invoice_summary">
                <h3><?php esc_html_e('Payment Details', 'fluent-pdf'); ?></h3>
                <?php echo fluentform_sanitize_html(Arr::get($settings, 'payment_summary')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </div>
        <?php endif; ?>

        <div class="invoice_thanks">
            <?php echo fluentform_sanitize_html(Arr::get($settings, 'invoice_thanks')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <style>
            .business_logo {
                max-width: 200px;
                margin-bottom: 20px;
            }
            .business_logo img {
                margin-bottom: 20px;
                max-width: 200px;
                max-height: 100px;
            }
            .business_name {
                font-weight: bold;
                margin-bottom: 10px;
            }
            td.customer_row {
                text-align: right;
            }
            td.customer_row ul, td.customer_row ul li {
                list-style: none;
            }
            td.customer_row ul li {
                padding-bottom: 7px;
            }
        </style>
        <?php
        return ob_get_clean();
    }
}
