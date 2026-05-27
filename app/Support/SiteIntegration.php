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

    public static function embedScriptUrl(): string
    {
        return rtrim(config('app.url'), '/').'/embed/wbooster.js';
    }

    /**
     * HTML-сниппет для подстановки почт с сайта (адреса из настроек проекта).
     */
    public static function embedScriptTag(?string $token = null): string
    {
        $url = self::embedScriptUrl();
        $tokenAttr = $token ? ' data-token="'.htmlspecialchars($token, ENT_QUOTES).'"' : ' data-token="SITE_TOKEN"';

        return '<script src="'.$url.'"'.$tokenAttr.' async></script>';
    }

    public static function instructions(?Site $site = null): string
    {
        $url = self::ingestUrl();

        $lines = [
            'Формы (seolead): '.$url,
            'Метрика (после ответа CRM): yaCounter.params({ \'crm-lead\': <id> })',
            'Метод: GET или POST',
            'Параметры: token, phone, email, name, description, product, comment, metrika_client_id, utm_*',
            '',
            'Звонки (Callibri и др.): '.self::callWebhookUrl(),
            'Метод: POST, token в query ?token=... или заголовок X-Site-Token',
            'Тело: phone или caller_phone, call_recording_url / record_url, call_duration_sec / duration',
            '',
            'Почта (IMAP): служебный ящик INBOUND_IMAP_* (напр. mail@mv-deploy.ru)',
            'На проекте — адреса пересылки (основной, SEO, остальные), с них forward на служебный ящик',
            'Cron: php artisan schedule:run → mail:fetch-inbound каждые 5 мин',
            '',
            'Почта (webhook, опционально): '.self::inboundEmailWebhookUrl(),
            'POST JSON/form, X-Inbound-Webhook-Secret при INBOUND_WEBHOOK_SECRET',
            '',
            'Почта на сайте (автоподстановка из настроек проекта): '.self::embedScriptUrl(),
            'Script определяет источник визита и подставляет почту:',
            '  реклама (UTM cpc, yclid, gclid, …) → основная почта проекта',
            '  поиск (Google/Yandex organic) → SEO-почта',
            '  прямой заход / закладка → почта «остальные»',
        ];

        if ($site) {
            $lines[] = '';
            $lines[] = 'Сайт: '.$site->name;
            $lines[] = 'Домены: '.implode(', ', $site->domains ?? []);
            if ($site->metrika_counter_id) {
                $lines[] = 'Счётчик Метрики: '.$site->metrika_counter_id;
            }
            if ($site->email_inbound_address) {
                $lines[] = 'Почта (реклама): '.$site->email_inbound_address;
            }
            if ($site->email_inbound_seo) {
                $lines[] = 'Почта (SEO / поиск): '.$site->email_inbound_seo;
            }
            if ($site->email_inbound_other) {
                $lines[] = 'Почта (прямые заходы): '.$site->email_inbound_other;
            }
            if ($site->inboundEmailAddresses() === []) {
                $lines[] = 'Почта проекта: не задана — укажите в настройках и настройте пересылку на служебный ящик';
            }
        }

        return implode("\n", $lines);
    }
}
