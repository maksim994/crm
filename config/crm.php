<?php

return [
    'inbound_local_prefix' => env('CRM_INBOUND_LOCAL_PREFIX', 'leads'),
    'inbound_domain' => env('CRM_INBOUND_DOMAIN', 'inbound.local'),

    /** Хранение лидов (мес.); job очистки — backlog после MVP */
    'lead_retention_months' => (int) env('LEAD_RETENTION_MONTHS', 24),
];
