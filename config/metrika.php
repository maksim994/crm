<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Параметр визита для связки лида CRM ↔ отчёты Метрики
    |--------------------------------------------------------------------------
    |
    | После создания лида на сайте вызывают:
    |   yaCounter.params({ 'crm-lead': '<uuid лида>' })
    |
    | В отчёте Метрики «Параметры визита»:
    |   ур. 1 — crm-lead, ур. 2 — id лида.
    |
    */

    'lead_visit_param' => env('METRIKA_LEAD_VISIT_PARAM', 'crm-lead'),

    /*
    |--------------------------------------------------------------------------
    | Reporting API (обогащение рекламного канала — v2, опционально)
    |--------------------------------------------------------------------------
    |
    | OAuth-токен и счётчик для запроса источника трафика по metrika_client_id.
    | Пока не подключено в ingest — см. docs/metrika-kanal-i-crm-lead.md
    |
    */

    'oauth_token' => env('METRIKA_OAUTH_TOKEN'),
    'reporting_enabled' => (bool) env('METRIKA_REPORTING_ENABLED', false),
    'reporting_base_url' => env('METRIKA_REPORTING_BASE_URL', 'https://api-metrika.yandex.net/stat/v1/data'),
    'reporting_timeout' => (int) env('METRIKA_REPORTING_TIMEOUT', 15),
    'reporting_lang' => env('METRIKA_REPORTING_LANG', 'ru'),

    /*
    | Подробные логи запросов/ответов Reporting API → storage/logs/metrika.log
    | На время отладки: METRIKA_REPORTING_LOG=true
    */
    'reporting_log' => (bool) env('METRIKA_REPORTING_LOG', false),

];
