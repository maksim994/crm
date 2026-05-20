<?php

return [
    'inbound_local_prefix' => env('CRM_INBOUND_LOCAL_PREFIX', 'leads'),
    'inbound_domain' => env('CRM_INBOUND_DOMAIN', 'inbound.local'),

    /** Хранение лидов (мес.); job очистки — backlog после MVP */
    'lead_retention_months' => (int) env('LEAD_RETENTION_MONTHS', 24),

    /** Секрет для POST /ingest/inbound-email (Mailgun, пересылка). Пусто = без проверки (только dev). */
    'inbound_webhook_secret' => env('INBOUND_WEBHOOK_SECRET'),
];
