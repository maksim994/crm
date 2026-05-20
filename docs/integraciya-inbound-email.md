# Интеграция входящей почты (реальный приём)

Письма принимаются через **HTTP webhook** — без queue worker и без `mail:test-inbound`.  
Обработка **синхронная**: ответ сразу с `id` лида.

```
Письмо → Mailgun / пересылка → POST https://crm.../ingest/inbound-email → лид в CRM
```

---

## 1. Адрес проекта

В админке на карточке **Проекта** поле **Email inbound**, например:

```text
leads+a1d2918cf1494de4bdc1c9a9a81c2c4e@inbound.wbooster.ru
```

Формат: `{prefix}+{site_uuid без дефисов}@{CRM_INBOUND_DOMAIN}`

Env:

```env
CRM_INBOUND_LOCAL_PREFIX=leads
CRM_INBOUND_DOMAIN=inbound.wbooster.ru
INBOUND_WEBHOOK_SECRET=длинный-случайный-секрет
```

После смены `CRM_INBOUND_DOMAIN` пересохраните проект или задайте `email_inbound_address` вручную.

---

## 2. Endpoint CRM

| | |
|---|---|
| **URL** | `POST https://crm.mv-deploy.ru/ingest/inbound-email` |
| **Auth** | заголовок `X-Inbound-Webhook-Secret: <INBOUND_WEBHOOK_SECRET>` |
| **Ответ** | `201` → `{"id":"...","channel":"email","is_duplicate":false}` |

### Поля (JSON или form-urlencoded)

| Поле CRM | Альтернативы (Mailgun) | Обязательно |
|----------|------------------------|-------------|
| `to` | `recipient` | да |
| `from` | `sender`, `From` | да |
| `subject` | `Subject` | нет |
| `body` | `body-plain`, `stripped-text`, `text` | нет (но нужен телефон или email в from/body) |

---

## 3. Тест без почтового сервера (curl = реальный HTTP)

Подставьте **to** с карточки проекта и секрет из Coolify:

```bash
curl -sS -X POST "https://crm.mv-deploy.ru/ingest/inbound-email" \
  -H "Content-Type: application/json" \
  -H "X-Inbound-Webhook-Secret: ВАШ_СЕКРЕТ" \
  -d '{
    "to": "leads+a1d2918cf1494de4bdc1c9a9a81c2c4e@inbound.wbooster.ru",
    "from": "real.client@gmail.com",
    "subject": "Заявка с сайта",
    "body": "Добрый день, перезвоните +79001234567"
  }'
```

Проверка: **Лиды** в админке → канал **Заявка на почту**, телефон и email из письма.

Имитация формата **Mailgun**:

```bash
curl -sS -X POST "https://crm.mv-deploy.ru/ingest/inbound-email" \
  -H "X-Inbound-Webhook-Secret: ВАШ_СЕКРЕТ" \
  -d "recipient=leads+...@inbound.wbooster.ru" \
  -d "sender=client@example.com" \
  -d "subject=Тест" \
  -d "body-plain=Телефон +79009876543"
```

---

## 4. Реальное письмо с Gmail / Outlook (через Mailgun)

### Шаг 1. Домен в Mailgun

1. Добавьте домен `inbound.wbooster.ru` (или поддомен) в [Mailgun](https://www.mailgun.com/).
2. Настройте DNS (MX, TXT) по инструкции Mailgun.
3. **Receiving** → Routes → Create Route:
   - **Expression:** `match_recipient(".*@inbound.wbooster.ru")` или точный адрес проекта
   - **Action:** Forward to URL  
     `https://crm.mv-deploy.ru/ingest/inbound-email`
   - Добавьте в Mailgun **HTTP header**:  
     `X-Inbound-Webhook-Secret: <ваш INBOUND_WEBHOOK_SECRET>`

### Шаг 2. Отправьте письмо

С личной почты отправьте на адрес с карточки проекта, например:

```text
leads+a1d2918cf1494de4bdc1c9a9a81c2c4e@inbound.wbooster.ru
```

Тема/тело: укажите телефон `+7...`

### Шаг 3. Проверка

- Mailgun → **Logs** → webhook delivery `200`
- CRM → **Лиды** → новый лид `channel=email`
- Логи CRM: `inbound_email.received`

---

## 5. Coolify env

```env
CRM_INBOUND_DOMAIN=inbound.wbooster.ru
INBOUND_WEBHOOK_SECRET=<случайная строка>
APP_URL=https://crm.mv-deploy.ru
```

**Runtime only**, redeploy web. Queue worker для почты **не нужен**.

---

## 6. Ошибки

| Код | Причина |
|-----|---------|
| 401 | Неверный или отсутствует `X-Inbound-Webhook-Secret` |
| 404 | `to` не совпадает ни с одним проектом |
| 422 | Нет `to`/`from` или не извлечён телефон/email |
| 403 | Проект на паузе / в архиве |

---

## 7. Отличие от `mail:test-inbound`

| | `mail:test-inbound` | `POST /ingest/inbound-email` |
|--|---------------------|------------------------------|
| Как вызывается | CLI в контейнере | HTTP (Mailgun, curl, Zapier) |
| Очередь | опционально | нет, сразу лид |
| Реальное письмо | нет | да, через Mailgun/MX |
