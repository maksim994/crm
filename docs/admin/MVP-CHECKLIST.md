# Чеклист приёмки MVP (ТЗ §14)

Используйте после деплоя staging и перед сдачей заказчику.  
Автотесты: `make test` (PHPUnit).

| # | Критерий | Авто | Как проверить |
|---|----------|------|----------------|
| 1 | Создание заказчика с обязательным «название» | частично | Админка → Заказчики → Создать; `AdminPanelTest` |
| 2 | Сайт + токен, статус «активен» | частично | Карточка заказчика → проект; `AdminPanelTest` |
| 3 | POST ingest с валидным токеном → лид + `id` | да | `SeoLeadIngestTest` |
| 4 | Неверный токен → 401 | да | `SeoLeadIngestTest` |
| 5 | Архивный сайт → 403 | да | `SeoLeadIngestTest` |
| 6 | Лид в админке, channel=form, UTM | да | `SeoLeadIngestTest` + UI |
| 7 | ЛК видит свой лид, чужой — нет | да | `ClientCabinetTest` |
| 8 | Фильтр по сайту в ЛК | да | `ClientCabinetTest` |
| 9 | CSV без ACC/PPC | да | `ClientCabinetTest` (export) |
| 10 | Webhook звонка → `channel=call` | да | `CallLeadIngestTest` |
| 11 | Inbound email → `channel=email` | да | `InboundEmailTest` |
| 12 | Дубль по телефону 30 дней → `is_duplicate` | да | `SeoLeadIngestTest`, `DuplicateLeadDetectorTest` |
| 13 | `metrika_client_id` без искажений | да | `SeoLeadIngestTest` |
| 14 | Токен не в исходнике Tilda | **manual** | Просмотр опубликованной страницы / Network |
| 15 | ACC/PPC не в ЛК | да | `ClientCabinetTest` |

## NFR (этап 7)

| Требование | Статус | Примечание |
|------------|--------|------------|
| Лид в UI ≤ 5 сек | ok | Ingest синхронный, без тяжёлых jobs |
| Rate limit 60/min на token | да | `IngestRateLimitTest`, `throttle:ingest` |
| Хранение 24 мес | config + job | `LEAD_RETENTION_MONTHS`, `php artisan leads:prune` (cron 03:00) |
| Healthcheck | да | `/health`, Docker `HEALTHCHECK` |
| Prod Docker | да | `Dockerfile.prod`, [deploy-coolify.md](./deploy-coolify.md) |

## Ручной smoke (5 мин)

1. `make fresh` (локально) или staging seed.
2. Админка: заказчик → сайт → скопировать токен / ingest URL.
3. `curl` POST на `/ingest/seolead` — лид в списке.
4. ЛК `client@demo.example.com` — лид виден.
5. «Войти в ЛК» с карточки заказчика — impersonation.
