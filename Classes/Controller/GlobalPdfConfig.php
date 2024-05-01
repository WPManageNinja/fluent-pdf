<?php

namespace FluentPdf\Classes\Controller;


class GlobalPdfConfig
{
    const OPTIONKEY = '_fluent_pdf_settings';

    public function globalSettings()
    {
        $defaults = [
            'paper_size'         => 'A4',
            'orientation'        => 'P',
            'font'               => 'default',
            'font_size'          => 14,
            'font_color'         => '#323232',
            'accent_color'       => '#989797',
            'heading_color'      => '#000000',
            'language_direction' => 'ltr'
        ];

        $option = get_option(self::OPTIONKEY);
        if (!$option || !is_array($option)) {
            return $defaults;
        }

        if (isset($option['font_size'])) {
            $option['font_size'] = intval($option['font_size']);
        }

        return wp_parse_args($option, $defaults);
    }

    public static function checkForUpdate($slug)
    {
        $githubApi = "https://api.github.com/repos/WPManageNinja/{$slug}/releases";
        $result = array(
            'available' => 'no',
            'url' => '',
            'slug' => 'fluent-pdf'
        );

        $response = wp_remote_get($githubApi);


        if(is_wp_error($response)){
            return $result;
        }
        
        $releases = json_decode($response['body']);

        if (isset($releases->documentation_url)) {
            return $result;
        }

        $latestRelease = $releases[0];
        $latestVersion = $latestRelease->tag_name;
        $zipUrl = $latestRelease->zipball_url;

        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
    
        $plugins = get_plugins();
        $currentVersion = '';

        // Check if the plugin is present
        foreach ($plugins as $plugin_file => $plugin_data) {
            // Check if the plugin slug or name matches
            if ($slug === $plugin_data['TextDomain'] || $slug === $plugin_data['Name']) {
                $currentVersion = $plugin_data['Version'];
            }
        }

        if (version_compare( $latestVersion, $currentVersion, '>')) {
            $result['available'] = 'yes';
            $result['url'] = $zipUrl;
        }

        return $result;
    }
}

