jQuery(document).ready(function ($) {
    let progress = 0;
    const pdfDownloader = {
        
        initDownloadFonts() {
            $('#ff_download_fonts').addClass('is-loading').attr('disabled', true);
            $('.ff_download_fonts_text').text('Downloading...');
            $('.ff_download_loading').html('Please do not close this window when downloading the fonts, After downloading page will auto reload.');

            this.ajaxLoadFonts();
        },

        $post(data) {
            let url = window.fluent_pdf_admin.ajaxUrl;
            return $.post(url, data);
        },

        ajaxLoadFonts() {

            if (progress < 95){
                progress += 5;
            }

            $(".ff_download_fonts_bar").animate({
                width: progress + '%'
            }, 1000);

            this.$post({
                action: 'fluent_pdf_admin_ajax_actions',
                route: 'downloadFonts'
            })
                .then(response => {
                    if(response.data.downloaded_files && response.data.downloaded_files.length) {
                        $('.ff_download_logs').prepend(response.data.downloaded_files.join('<br />')).show();
                        $('.ff_downlaod_logs').removeClass('hidden');
                        this.ajaxLoadFonts();
                    } else {
                        $(".ff_download_fonts_bar").animate({
                            width: '100%'
                        }, 1000);

                        // All Done
                        window.location.reload();
                    }
                })
                .fail(error => {
                    // window.location.reload();
                });
        },

        init() {
            $('#ff_download_fonts').on('click', (e) => {
                e.preventDefault();
                this.initDownloadFonts();
            });
        }
    };

    pdfDownloader.init();
});