# Интеграция входящей почты

Два способа принять письмо в CRM как лид (`channel=email`):

1. **IMAP (рекомендуется)** — один служебный ящик, пересылка с почты каждого проекта.
2. **HTTP webhook** — Mailgun или curl на `POST /ingest/inbound-email`.

---

## 1. Схема IMAP (пересылка → служебный ящик)

```
[zayavki@client.ru]  ──пересылка──►  [mail@mv-deploy.ru]
                                              │
                                    CRM: IMAP UNSEEN
                                    сопоставление по адресу проекта
```

### Шаг 1. Служебный ящик

Создайте почту, например `mail@mv-deploy.ru`, с доступом по **IMAP** (SSL, порт 993).

### Шаг 2. Проект в CRM

В админке → **Проект** → поле **«Почта проекта (для пересылки)»** — любой реальный адрес, с которого клиенты пишут или который указан на сайте, например:

```text
zayavki@ruflex.ru
```

### Шаг 3. Пересылка на служебный ящик

В панели хостинга / Яндекс / Gmail для **почты проекта** настройте правило:

- все входящие → переслать на `mail@mv-deploy.ru`

CRM при чтении письма ищет в заголовках и теле пересылки оригинальный адрес (`To`, `X-Original-To`, `Delivered-To`, блок «Кому:» в теле) и находит проект по `sites.email_inbound_address`.

### Шаг 4. Env в Coolify

```env
INBOUND_IMAP_ENABLED=true
INBOUND_IMAP_HOST=imap.your-mail-host.ru
INBOUND_IMAP_PORT=993
INBOUND_IMAP_ENCRYPTION=ssl
INBOUND_IMAP_USERNAME=mail@mv-deploy.ru
INBOUND_IMAP_PASSWORD=***
INBOUND_IMAP_FOLDER=INBOX
INBOUND_IMAP_MARK_READ=true
# Опционально: если адрес не распознан — все такие письма в один проект
# INBOUND_IMAP_DEFAULT_SITE_ID=uuid-проекта
```

Пересоберите образ (нужно PHP **ext-imap** в `Dockerfile.prod`).

### Шаг 5. Cron (Scheduled Task в Coolify)

В Coolify откройте приложение CRM → **Scheduled Tasks** → **New Scheduled Task** и заполните форму:

| Поле | Значение | Комментарий |
|------|----------|-------------|
| **Name** | `Laravel scheduler` | Любое понятное имя |
| **Command** | `php artisan schedule:run` | Рабочая директория в образе уже `/var/www/html` |
| **Frequency** | `* * * * *` | **Каждую минуту** — так требует Laravel |
| **Timeout (seconds)** | `300` | Достаточно для IMAP-опроса |
| **Container name** | имя **web**-контейнера CRM | Основной контейнер приложения (Nginx + PHP), не queue worker |

> **Важно:** в Coolify в поле **Frequency** указывается только cron-выражение (`* * * * *`), без команды.  
> Команда — отдельно в поле **Command**. Не пишите туда строку вида `* * * * * cd /var/www/html && php artisan schedule:run`.

Laravel сам решает, что запускать:

- каждые **5 минут** — `mail:fetch-inbound` (если `INBOUND_IMAP_ENABLED=true`);
- ежедневно в **03:00** — `leads:prune`.

Эквивалент классического crontab на сервере:

```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

В Coolify перенаправление в `/dev/null` не нужно — его добавляет платформа.

Ручная проверка:

```bash
php artisan mail:fetch-inbound
```

Логи: `inbound_imap.*` в `storage/logs/laravel.log`.

### Шаг 6. Тест

1. Отправьте письмо на `zayavki@ruflex.ru` (или сразу на проектный адрес с пересылкой).
2. Убедитесь, что оно попало в `mail@mv-deploy.ru`.
3. Запустите `mail:fetch-inbound` или дождитесь cron.
4. В CRM → **Лиды** → канал **Заявка на почту**.

---

## 2. HTTP webhook (альтернатива)

Без IMAP: Mailgun принимает MX на поддомен и шлёт POST в CRM.

| | |
|---|---|
| **URL** | `POST https://crm.mv-deploy.ru/ingest/inbound-email` |
| **Auth** | `X-Inbound-Webhook-Secret: <INBOUND_WEBHOOK_SECRET>` |
| **Ответ** | `201` → `{"id":"...","channel":"email"}` |

Поле `to` / `recipient` должно совпадать с **почтой проекта** в CRM (как при IMAP).

### curl (без почтового сервера)

```bash
curl -sS -X POST "https://crm.mv-deploy.ru/ingest/inbound-email" \
  -H "Content-Type: application/json" \
  -H "X-Inbound-Webhook-Secret: ВАШ_СЕКРЕТ" \
  -d '{
    "to": "zayavki@client.ru",
    "from": "client@gmail.com",
    "subject": "Заявка",
    "body": "Перезвоните +79001234567"
  }'
```

Env:

```env
INBOUND_WEBHOOK_SECRET=длинный-случайный-секрет
```

Queue worker для webhook **не нужен** (обработка синхронная).

---

## 3. Старый формат leads+uuid@domain

Автогенерация `leads+{uuid}@CRM_INBOUND_DOMAIN` при создании проекта **отключена**.  
Можно по-прежнему указать такой адрес вручную в поле проекта, если используете Mailgun на поддомен.

```env
CRM_INBOUND_LOCAL_PREFIX=leads
CRM_INBOUND_DOMAIN=inbound.example.com
```

---

## 4. Ошибки

| Ситуация | Что проверить |
|----------|----------------|
| Письмо в ящике, лида нет | `email_inbound_address` в проекте, пересылка, заголовки в логе `inbound_imap.message` |
| `inbound_imap.connect_failed` | host/port/login/password, SSL |
| `extension_missing` | пересборка Docker с `imap` |
| Webhook 404 | `to` не совпадает с почтой проекта |
| Webhook 401 | `INBOUND_WEBHOOK_SECRET` |

---

## 5. CLI для отладки

| Команда | Назначение |
|---------|------------|
| `php artisan mail:fetch-inbound` | забрать UNSEEN по IMAP |
| `php artisan mail:test-inbound` | имитация job (очередь), для dev |
