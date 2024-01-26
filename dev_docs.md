## use case form other plugins
if (isset($_GET['pdf_demo'])) {
    do_action('fluent_pdf_make', [
        'header' => '<h1>Hello header</h1>',
        'footer' => '<p>Hello footer</p>',
        'body' => '<p>Hello content</p>',
    ]);
    die();
}

echo '<a href="'. site_url() . '?pdf_demo'.'" download target="_blank" class="button button-primary">Download PDF</a>';
die();