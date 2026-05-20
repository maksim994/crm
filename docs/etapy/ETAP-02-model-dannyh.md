# Этап 2. Модель данных

**Цель:** таблицы и Eloquent-модели по ТЗ; тестовый seed.

**Зависимости:** [Этап 1](./ETAP-01-infrastruktura.md)

---

## Таблицы

### `agency_clients`

| Поле | Тип |
|------|-----|
| id | uuid PK |
| name | string |
| inn | string nullable |
| contact_name | string nullable |
| contact_email | string nullable |
| contact_phone | string nullable |
| manager_comment | text nullable |
| status | enum: active, archived |
| timestamps | |

### `sites`

| Поле | Тип |
|------|-----|
| id | uuid PK |
| agency_client_id | uuid FK |
| name | string |
| domains | json (массив строк) |
| metrika_counter_id | string nullable |
| timezone | string default Europe/Moscow |
| token_hash | string |
| status | enum: active, paused, archived |
| email_inbound_address | string nullable |
| timestamps | |

### `leads`

| Поле | Тип |
|------|-----|
| id | uuid PK |
| site_id | uuid FK |
| channel | enum: form, call, email |
| phone, email | string nullable |
| contact_name | string nullable |
| form_description | string nullable |
| lead_status | enum (см. TZ) |
| manager_name | string nullable |
| metrika_client_id | string nullable |
| utm_* | string nullable |
| utm_campaign_first | string nullable |
| advertising_channel | string nullable |
| landing_domain | string nullable |
| visitor_ip | string nullable |
| call_recording_url | string nullable |
| call_duration_sec | int nullable |
| is_duplicate | boolean default false |
| acc_*, ppc_* | nullable (внутренние) |
| raw_payload | json nullable |
| created_at | timestamp |

### `users` + роли

- Расширить стандартную таблицу `users`: `role`, `agency_client_id` nullable.

## Задачи

- [x] Миграции для всех таблиц
- [x] Модели + relationships: `AgencyClient hasMany Site`, `Site hasMany Lead`
- [x] Enum / cast для статусов
- [x] `DatabaseSeeder`: данные из [PROEKT.md](../PROEKT.md) §8
- [x] Хелпер генерации токена: `Site::issueToken()` → `{uuid}:{plain}` + сохранение hash

## Готово когда

- [x] `make fresh` без ошибок
- [x] В tinker: `Site::first()->leads()` работает
- [x] Seed создаёт 1 заказчика, 2 сайта

## Следующий этап

[ETAP-03-admin-panel.md](./ETAP-03-admin-panel.md)
