# Этап 6. Интеграции (Callibri + почта)

**Цель:** лиды из звонков и email создаются с `channel=call|email`.

**Зависимости:** [Этап 4](./ETAP-04-priem-lidov.md)

---

## 6.1. Callibri (webhook)

### Endpoint

```
POST /api/v1/leads/call?token={site_token}
```

Или заголовок `X-Site-Token` — см. [openapi.yaml](../../openapi.yaml).

### Задачи

- [ ] `CallibriWebhookController`
- [ ] `Integrations\Callibri\CallibriPayloadMapper` — маппинг полей (уточнить по доке Callibri)
- [ ] `channel = call`, запись `call_recording_url`, `call_duration_sec`
- [ ] Feature test с fixture JSON

## 6.2. Inbound email

### MVP-схема

Уникальный адрес на сайт: `leads+{site_uuid_short}@inbound.local` (dev) / реальный домен в prod.

### Задачи

- [ ] Job `ProcessInboundEmailJob` (from, subject, body, to-address)
- [ ] Парсинг site по local-part
- [ ] Извлечение phone/email regex из тела
- [ ] `channel = email`, `advertising_channel = Нет данных`
- [ ] Для локальной отладки: `php artisan mail:test-inbound` с fixture

## 6.3. Опционально

- [ ] Привести `/ingest/seolead` и `/api/v1/leads` к одному `LeadIngestionService`

## Готово когда

- [ ] Webhook создаёт лид-звонок на правильном сайте
- [ ] Тестовое письмо создаёт лид-email
- [ ] Оба лида видны в админке и ЛК

## Следующий этап

[ETAP-07-kachestvo-i-deploy.md](./ETAP-07-kachestvo-i-deploy.md)
