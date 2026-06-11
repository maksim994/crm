# Этап 4. Приём лидов (формы / seolead)

**Цель:** заявки с Tilda попадают в БД на правильный сайт; ответ с `id` для Метрики.

**Зависимости:** [Этап 3](./ETAP-03-admin-panel.md)

---

## Endpoint

```
GET|POST  /ingest/seolead
```

Без CSRF (отдельная группа routes `api` middleware: throttle).

### Параметры

См. [PROEKT.md](../PROEKT.md) §5.1.

### Логика `LeadIngestionService`

1. Разобрать `token` → `site_id` + verify hash
2. Проверить `site.status === active`
3. Валидировать phone/email
4. Заполнить UTM, `metrika_client_id`, `landing_domain` из `page_url`
5. `AdvertisingChannelResolver` → «Переходы по рекламе» / «Нет данных»
6. `DuplicateLeadDetector` → `is_duplicate`
7. Сохранить Lead, `lead_status = not_processed`
8. Вернуть `{ "id": "..." }` JSON; при `Accept: text/plain` — только uuid

## Задачи

- [ ] `SeoLeadController@store`
- [ ] Rate limit: `throttle:60,1` по token + IP
- [ ] Поддержка GET (query) и POST (form/json)
- [ ] Логирование неуспешных token (без ПДн в логах)
- [ ] Файл `docs/snippets/tilda-seolead.md` с готовым сниппетом
- [ ] Feature test: валидный token → 201 + lead в БД
- [ ] Feature test: неверный token → 401
- [ ] Feature test: paused site → 403

## Ручная проверка

```bash
curl -X POST "http://localhost:8080/ingest/seolead" \
  -d "token=UUID:secret" \
  -d "phone=+79001112233" \
  -d "description=Тест" \
  -d "metrika_client_id=17791064241773632"
```

## Готово когда

- [ ] Лид виден в Filament LeadResource
- [ ] Сниппет jQuery с localhost создаёт лид
- [ ] Дубль по телефону на том же сайте за 30 дней помечается

## Следующий этап

[ETAP-05-lichniy-kabinet.md](./ETAP-05-lichniy-kabinet.md)
