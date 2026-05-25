<?php

return [
    'inbound_local_prefix' => env('CRM_INBOUND_LOCAL_PREFIX', 'leads'),
    'inbound_domain' => env('CRM_INBOUND_DOMAIN', 'inbound.local'),

    /** Хранение лидов (мес.); job очистки — backlog после MVP */
    'lead_retention_months' => (int) env('LEAD_RETENTION_MONTHS', 24),

    /** Секрет для POST /ingest/inbound-email (Mailgun, curl). Пусто = без проверки (только dev). */
    'inbound_webhook_secret' => env('INBOUND_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Служебный IMAP-ящик (mail@mv-deploy.ru)
    |--------------------------------------------------------------------------
    |
    | На каждом проекте в админке указывается email_inbound_address (любой адрес).
    | С него настраивается пересылка на служебный ящик. CRM читает UNSEEN по IMAP
    | и сопоставляет проект по адресу в заголовках (To, X-Original-To, …).
    |
    */

    'inbound_imap' => [
        'enabled' => (bool) env('INBOUND_IMAP_ENABLED', false),
        'host' => env('INBOUND_IMAP_HOST', 'localhost'),
        'port' => (int) env('INBOUND_IMAP_PORT', 993),
        'encryption' => env('INBOUND_IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => (bool) env('INBOUND_IMAP_VALIDATE_CERT', true),
        'username' => env('INBOUND_IMAP_USERNAME'),
        'password' => env('INBOUND_IMAP_PASSWORD'),
        'folder' => env('INBOUND_IMAP_FOLDER', 'INBOX'),
        'mark_read' => (bool) env('INBOUND_IMAP_MARK_READ', true),
        'default_site_id' => env('INBOUND_IMAP_DEFAULT_SITE_ID'),
    ],
];
