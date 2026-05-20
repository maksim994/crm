<?php

namespace App\Filament\Support;

use App\Models\Site;

class SiteIntegration
{
    public static function ingestUrl(): string
    {
        return rtrim(config('app.url'), '/').'/ingest/seolead';
    }

    public static function instructions(?Site $site = null): string
    {
        $url = self::ingestUrl();

        $lines = [
            'Endpoint: '.$url,
            'Метод: GET или POST',
            'Параметры: token, phone, email, name, description, metrika_client_id, utm_*',
        ];

        if ($site) {
            $lines[] = '';
            $lines[] = 'Сайт: '.$site->name;
            $lines[] = 'Домены: '.implode(', ', $site->domains ?? []);
            if ($site->metrika_counter_id) {
                $lines[] = 'Счётчик Метрики: '.$site->metrika_counter_id;
            }
        }

        return implode("\n", $lines);
    }
}
