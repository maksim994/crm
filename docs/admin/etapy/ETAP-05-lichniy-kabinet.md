# Этап 5. Личный кабинет заказчика

**Цель:** пользователь заказчика видит только свои лиды, фильтрует, экспортирует CSV; навигация по проектам.

**Зависимости:** [Этап 3](./ETAP-03-admin-panel.md), [Этап 4](./ETAP-04-priem-lidov.md)

**Реализация:** Vue 3 SPA (`/cabinet/`), не Filament. Подробнее: [lichnyj-kabinet.md](../lichnyj-kabinet.md).

---

## Задачи

- [x] SPA на `/cabinet/` + API `/api/client/*` (Sanctum session)
- [x] Роль `client_user` + `agency_client_id` на user
- [x] Seeder: пользователь `client@demo.example.com` → заказчик «ООО Демо Кровля»
- [x] Список лидов (read-only) с scope по заказчику / site_user
- [x] Фильтры: период, сайт, channel, lead_status, utm_campaign
- [x] Скрыть поля: token, visitor_ip, acc_*, ppc_*, manager_comment, inn, expected_amount
- [x] Экспорт CSV (sync)
- [x] Policy: запрет доступа к чужому `agency_client_id` (тест)
- [x] Доступ к отдельным проектам (`cabinet_all_sites`, pivot `site_user`)
- [x] Impersonation из админки
- [x] Навигация: «Лиды» (все проекты) + разделы по проекту:
  - **Лиды и продажи** — `/projects/{siteId}/leads`
  - **Трафик и лиды** — `/projects/{siteId}/traffic` (заглушка, таблица TZ §6.4)
  - **Аналитика** — `/projects/{siteId}/analytics` (графики TZ §6.5)
- [ ] Пагинация в UI списка лидов
- [ ] Magic link login (TZ §6.1, v2)

## Трафик и лиды (v2, см. TZ §6.4)

- [ ] UI таблицы на `/projects/{siteId}/traffic` (фильтр, пагинация, режимы день/месяц)
- [ ] API агрегации лидов из CRM (звонки, заявки, сумма)
- [ ] Reporting API Метрики: показы, клики, расход по `metrika_counter_id`
- [ ] Merge трафика и лидов + расчёт CTR, CV, CPA
- [ ] Кэш `traffic_daily_stats` + job синхронизации
- [ ] `GET /api/client/projects/{siteId}/traffic-stats`

## Аналитика — графики (v2, см. TZ §6.5)

- [x] Маршрут `/projects/{siteId}/analytics` + пункт sidebar «Аналитика»
- [x] `MetrikaAnalyticsService` + aggregate Reporting API
- [x] Endpoints analytics/* + кэш `metrika_report_cache`
- [x] UI: виджеты (donut, area, таблица) на ApexCharts
- [x] Виджеты: каналы, поисковики, география, устройства, branded/non-branded
- [ ] Тесты на prod с реальным OAuth Метрики

## Видимость полей

По [TZ.md](../TZ.md) раздел 10, колонка «ЛК = да».

## Готово когда

- [x] `client@demo` видит лиды только своих проектов
- [x] Admin не может войти в `/cabinet` без client role (или через impersonation)
- [x] CSV содержит только разрешённые колонки
- [x] Sidebar показывает доступные проекты и подразделы

## Следующий этап

[ETAP-06-integracii.md](./ETAP-06-integracii.md)
