<?php

namespace FluentPdf\Modules\FluentForms;

class Migration
{
    const MIGRATION_KEY = '_fluent_pdf_migration_completed';
    const OLD_SETTINGS  = '_fluentform_pdf_settings';
    const NEW_SETTINGS  = '_fluent_pdf_settings';

    /**
     * Run migration if old plugin settings exist and migration hasn't been done yet.
     */
    public static function maybeRun()
    {
        if (get_option(self::MIGRATION_KEY)) {
            return;
        }

        $oldSettings = get_option(self::OLD_SETTINGS);

        if (!$oldSettings) {
            // No old settings to migrate
            return;
        }

        self::migrateGlobalSettings($oldSettings);
        self::cleanupOldCron();

        update_option(self::MIGRATION_KEY, [
            'migrated_at' => current_time('mysql'),
            'from_plugin' => 'fluentforms-pdf',
        ]);
    }

    /**
     * Copy global settings from old option key to new one.
     * Only copies if new settings don't already exist.
     */
    private static function migrateGlobalSettings($oldSettings)
    {
        $existingSettings = get_option(self::NEW_SETTINGS);

        if ($existingSettings) {
            // New settings already exist — don't overwrite
            return;
        }

        update_option(self::NEW_SETTINGS, $oldSettings, 'no');
    }

    /**
     * Clear old plugin's cron and ensure new one is registered.
     */
    private static function cleanupOldCron()
    {
        wp_clear_scheduled_hook('fluentform_pdf_cleanup_tmp_dir');

        if (!wp_next_scheduled('fluent_pdf_cleanup_tmp_dir')) {
            wp_schedule_event(time(), 'daily', 'fluent_pdf_cleanup_tmp_dir');
        }
    }
}
