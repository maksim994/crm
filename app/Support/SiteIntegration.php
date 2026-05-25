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

    public static function inboundEmailWebhookUrl(): string
    {
        return rtrim(config('app.url'), '/').'/ingest/inbound-email';
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
            '',
            'Почта (IMAP): служебный ящик INBOUND_IMAP_* (напр. mail@mv-deploy.ru)',
            'На проекте — адрес пересылки (email_inbound_address), с него forward на служебный ящик',
            'Cron: php artisan schedule:run → mail:fetch-inbound каждые 5 мин',
            '',
            'Почта (webhook, опционально): '.self::inboundEmailWebhookUrl(),
            'POST JSON/form, X-Inbound-Webhook-Secret при INBOUND_WEBHOOK_SECRET',
        ];

        if ($site) {
            $lines[] = '';
            $lines[] = 'Сайт: '.$site->name;
            $lines[] = 'Домены: '.implode(', ', $site->domains ?? []);
            if ($site->metrika_counter_id) {
                $lines[] = 'Счётчик Метрики: '.$site->metrika_counter_id;
            }
            if ($site->email_inbound_address) {
                $lines[] = 'Почта проекта (пересылка с этого адреса): '.$site->email_inbound_address;
            } else {
                $lines[] = 'Почта проекта: не задана — укажите в настройках и настройте пересылку на служебный ящик';
            }
        }

        return implode("\n", $lines);
    }
}
