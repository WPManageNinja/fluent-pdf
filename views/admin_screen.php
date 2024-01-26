<?php defined('ABSPATH') or die; ?>

<?php if(count($downloadableFiles)): ?>
<div  class="font_downloader_wrapper text-center" style="max-width: 600px;margin: 10px auto;">
    <img class="mb-3" src="<?= FLUENTFORM_PDF_URL . 'assets/images/pdf-img.png'; ?>" alt="">
    <h3 class="mb-2">Fonts are required for PDF Generation</h3>
    <p class="mb-4">This module requires to download fonts for PDF generation. Please click on the bellow button and it will download the required font files. This is one time job</p>
    <button id="ff_download_fonts" class="el-button el-button--primary">
        <span class="ff_download_fonts_bar"></span>
        <span class="ff_download_fonts_text">Install Fonts</span>
    </button>
    <div class="ff_download_loading mt-3"></div>
    <div class="ff_download_logs mt-3 hidden"></div>
</div>
<?php else: ?>

<div class="ff_pdf_system_status" style="<?php echo isset($inheritStyle) && $inheritStyle ? '' :  'max-width: 600px; margin: 0 auto; padding-top: 32px;'?>">
    <h3 class="mb-3">Fluent PDF Module is now active <?php if(!$statuses['status']): ?><span style="color: red;">But Few Server Extensions are missing</span><?php endif; ?></h3>
    <ul>
        <?php foreach ($statuses['extensions'] as $status): ?>
        <li>
            <?php if($status['status']): ?><span class="dashicons dashicons-yes"></span>
            <?php else: ?><span class="dashicons dashicons-no-alt"></span><?php endif; ?>
            <?php echo $status['label']; ?>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if($statuses['status']): ?>
    <p>All Looks good! You can now use Fluent PDF Addon. <a href="<?php echo $globalSettingsUrl; ?>">Click Here</a> to check your global PDF feed settings</p>
    <?php endif; ?>
</div>
<?php endif; ?>
