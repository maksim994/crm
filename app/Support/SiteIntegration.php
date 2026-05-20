<?php

namespace App\Support;

use App\Models\Site;

class SiteIntegration
{
    public static function ingestUrl(): string
    {
        return rtrim(config('app.url'), '/').'/ingest/seolead';
    }

    public static function callWebhookUrl(): string
    {
        return rtrim(config('app.url'), '/').'/api/v1/leads/call';
    }

    public static function instructions(?Site $site = null): string
    {
        $url = self::ingestUrl();

        $lines = [
            'Формы (seolead): '.$url,
            'Метрика (после ответа CRM): yaCounter.params({ \'crm-lead\': <id> })',
            'Метод: GET или POST',
            'Параметры: token, phone, email, name, description, metrika_client_id, utm_*',
            '',
            'Звонки (Callibri и др.): '.self::callWebhookUrl(),
            'Метод: POST, token в query ?token=... или заголовок X-Site-Token',
            'Тело: phone или caller_phone, call_recording_url / record_url, call_duration_sec / duration',
        ];

        if ($site) {
            $lines[] = '';
            $lines[] = 'Сайт: '.$site->name;
            $lines[] = 'Домены: '.implode(', ', $site->domains ?? []);
            if ($site->metrika_counter_id) {
                $lines[] = 'Счётчик Метрики: '.$site->metrika_counter_id;
            }
            $inbound = $site->email_inbound_address ?? InboundEmailAddress::forSite($site);
            $lines[] = 'Входящая почта: '.$inbound;
        }

        return implode("\n", $lines);
    }
}
