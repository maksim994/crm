# Метрика: параметр `crm-lead` и рекламный канал в CRM

## 1. Параметр визита `crm-lead` (зафиксировано)

После успешной отправки заявки в CRM на сайте вызывают:

```javascript
yaCounter.params({ 'crm-lead': data.id });
```

| Было (устарело) | Сейчас |
|-----------------|--------|
| `yaCounter.params({ wbooster: id })` | `yaCounter.params({ 'crm-lead': id })` |

Имя параметра задаётся в `config/metrika.php` → `lead_visit_param` (по умолчанию `crm-lead`).

### Как это выглядит в Метрике

Отчёт **Параметры визита** (как на вашем скрине):

```text
Параметр визита, ур. 1    →  crm-lead
Параметр визита, ур. 2    →  uuid лида из CRM (например a1d29496-2c01-4411-8637-...)
```

Это **не** «рекламный канал» и не тип заявки. Это **сквозная метка**: визит в Метрике связан с конкретным лидом в CRM. По ней строят сегменты и сверяют конверсии.

---

## 2. Два разных поля в личном кабинете

| Поле в ЛК | Что это | Откуда сейчас |
|-----------|---------|----------------|
| **Канал** (`channel`) | Тип поступления: Заявка / Звонок / Почта | CRM по endpoint (`/ingest/seolead`, `/api/v1/leads/call`, почта) |
| **Реклама** (`advertising_channel`) | Источник трафика: «Переходы по рекламе» / «Нет данных» | **v1:** UTM с формы; **v2:** Reporting API по `metrika_client_id` (job, см. ниже) |

Параметр `crm-lead` в отчёте Метрики **не подставляется** в колонку «Реклама» автоматически.

---

## 3. Почему «канал» нужно брать из Метрики

В Метрике достовернее, чем в произвольных UTM с формы:

- атрибуция Директа / органики / рефералов;
- склейка с `ClientID`;
- отчёты **Источники**, **Директ**, UTM в интерфейсе Метрики.

В CRM **сейчас** для «Рекламы» используется упрощённое правило (см. `AdvertisingChannelResolver`):

- `utm_medium=cpc` → «Переходы по рекламе»;
- `utm_source` содержит `yandex` или `google` → то же;
- иначе → «Нет данных».

**Интеграция Reporting API Метрики** реализована опционально (`METRIKA_REPORTING_ENABLED` + OAuth). Без токена остаётся правило по UTM (v1).

Пока для теста передавайте с сайта те же UTM, что видите в Метрике на визите (в `lead.php` / JS они уже есть в [integraciya-s-saytom.md](./integraciya-s-saytom.md)).

---

## 4. Что сделать на сайте сегодня (чтобы ЛК и Метрика совпадали)

1. **Обязательно** при отправке формы:
   - `metrika_client_id` — `yaCounter.getClientID()`;
   - `utm_source`, `utm_medium`, `utm_campaign`, … — из URL посадочной.
2. **После ответа CRM** — `yaCounter.params({ 'crm-lead': data.id })`.
3. В карточке **проекта** в админке CRM указан тот же **ID счётчика**, что на сайте.

Тогда:

- в Метрике появится ветка `crm-lead` → id лида;
- в ЛК «Реклама» заполнится по UTM (если был `cpc` / yandex / google);
- «Канал» для формы останется **Заявка**.

---

## 5. Проверка end-to-end

1. Отправить тестовую заявку с рекламной меткой в URL, например  
   `?utm_source=yandex&utm_medium=cpc`.
2. В CRM ЛК → лид → **Реклама** = «Переходы по рекламе».
3. В Метрике → **Параметры визита** → `crm-lead` → uuid этого лида.
4. В Метрике → **Источники** / UTM на том же визите — сверить с полями лида.

---

## 6. Дальше (v2)

- [x] Reporting API: job `EnrichLeadFromMetrikaJob` после ingest (если `METRIKA_REPORTING_ENABLED=true`)
- [x] `php artisan leads:prune` — удаление лидов старше `LEAD_RETENTION_MONTHS` (cron 03:00)
- OAuth: `METRIKA_OAUTH_TOKEN` в Coolify (**Runtime only**)

### Включение обогащения из Метрики

```env
METRIKA_REPORTING_ENABLED=true
METRIKA_OAUTH_TOKEN=y0_AgAAAA...   # OAuth, право metrika:read
```

На карточке **проекта** должен быть указан **ID счётчика**; в заявке — `metrika_client_id`.

После отправки формы job запрашивает `ym:s:trafficSource` по Client ID за день лида и обновляет **Рекламу** в ЛК (источник `ad` → «Переходы по рекламе»). При пустом `utm_campaign_first` подставляется кампания из Метрики.

Требуется **queue worker** (`php artisan queue:work redis`) на том же окружении, что и web.

---

## 7. Отладка запросов в Reporting API

### Включить лог

```env
METRIKA_REPORTING_ENABLED=true
METRIKA_OAUTH_TOKEN=...
METRIKA_REPORTING_LOG=true
```

Лог пишется в **`storage/logs/metrika.log`** (OAuth-токен в лог **не** попадает, в URL его тоже нет).

События:

| Ключ в логе | Что значит |
|-------------|------------|
| `metrika.job.start` | Job взял лид в работу |
| `metrika.reporting.request` | URL и параметры запроса (filters, dimensions, date) |
| `metrika.reporting.response` | HTTP-статус, `total_rows`, число строк |
| `metrika.reporting.parsed` | Распознанный источник трафика и UTM |
| `metrika.reporting.empty_attribution` | Метрика ответила, но визит с Client ID не найден |
| `metrika.reporting.failed` | Ошибка API (401, 403, 400…) — смотрите `body` |
| `metrika.enrich.applied` | Поля лида обновлены |

Просмотр на сервере:

```bash
# локально
docker compose exec app tail -f storage/logs/metrika.log

# production (в контейнере Coolify)
tail -f /var/www/html/storage/logs/metrika.log
```

В Coolify можно смотреть **логи queue worker** — там же stderr, если `LOG_CHANNEL=stderr` (предупреждения дублируются в `laravel.log`).

### Команда проверки по лиду (без очереди)

```bash
php artisan metrika:test-lead <UUID_лида>
```

Покажет URL запроса, выполнит enrich синхронно и выведет «Реклама до/после».

Только URL без вызова API:

```bash
php artisan metrika:test-lead <UUID> --show-url
```

Проверка вручную через curl (подставьте токен и URL из `--show-url`):

```bash
curl -sS -H "Authorization: OAuth ВАШ_ТОКЕН" \
  "https://api-metrika.yandex.net/stat/v1/data?ids=СЧЁТЧИК&..."
```

### Частые проблемы

| Симптом | Причина |
|---------|---------|
| Нет строк в логе `metrika.*` | `METRIKA_REPORTING_LOG=false` или не запущен worker |
| `401` в `metrika.reporting.failed` | Неверный или просроченный OAuth |
| `empty_attribution` | Client ID не совпал с визитом **за дату лида** (другой день, другой счётчик) |
| Job не стартует | Нет `metrika_client_id` на лиде или счётчика на проекте |

**Важно:** CRM ходит в **Reporting API** (чтение статистики), а не отправляет данные *в* Метрику. На сайт данные уходят через `yaCounter.params({ 'crm-lead': id })` — это проверяется в интерфейсе Метрики (отчёт «Параметры визита»).
