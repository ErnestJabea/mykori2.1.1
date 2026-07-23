<?php

namespace App\Support;

class PmgAlertRecipients
{
    public static function resolve(): array
    {
        $raw = null;
        $source = '.env : PMG_ALERT_EMAILS';
        $managedInVoyager = false;

        if (function_exists('setting')) {
            try {
                $raw = setting('site.anniversary_emails');
                $managedInVoyager = filled($raw);
            } catch (\Throwable $exception) {
                $raw = null;
            }
        }

        if (!$managedInVoyager) {
            $raw = config('notifications.pmg_alert_emails', '');
        } else {
            $source = 'Administration > Paramètres > Site > Emails pour notifications PMG';
        }

        $emails = collect(explode(',', (string) $raw))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values()
            ->all();

        return [
            'emails' => $emails,
            'source' => $source,
            'managed_in_voyager' => $managedInVoyager,
            'settings_url' => '/admin/settings',
        ];
    }
}
