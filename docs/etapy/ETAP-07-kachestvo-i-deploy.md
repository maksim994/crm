# Этап 7. Качество, деплой, NFR

**Цель:** стабильный деплой через Coolify, базовые тесты, соответствие NFR из ТЗ.

**Зависимости:** этапы 1–6.

---

## Тестирование

- [x] PHPUnit: ingestion, policies, duplicate detector, advertising channel resolver
- [x] Чеклист 15 сценариев ТЗ §14 — [MVP-CHECKLIST.md](../MVP-CHECKLIST.md)
- [ ] Laravel Pint / PHPStan level 5 (опционально)

## NFR

| Требование | Реализация |
|------------|------------|
| Задержка лида в UI | ≤ 5 сек (ingest синхронный) |
| Rate limit | 60/min на token (`throttle:ingest`, `IngestToken`) |
| Ретенция | `LEAD_RETENTION_MONTHS=24` в config (job позже) |
| Логи | channel `stack` + при prod Sentry (опц., DSN в env) |

## Docker / Coolify

- [x] Production `Dockerfile.prod` multi-stage (composer --no-dev, Vue build)
- [x] `docker-compose.prod.yml` + [deploy-coolify.md](../deploy-coolify.md)
- [x] Переменные: `APP_URL`, `DB_*`, `REDIS_*`
- [x] Migrate on deploy: entrypoint `RUN_MIGRATIONS` или Pre-deploy
- [x] Healthcheck → `/health`

## Документация

- [x] README: local vs Coolify
- [ ] Обновить [PROEKT.md](../PROEKT.md) при смене URL prod
- [x] Чеклист MVP в [MVP-CHECKLIST.md](../MVP-CHECKLIST.md)

## Готово когда

- [ ] Проект развёрнут на Coolify (staging)
- [ ] С тестового Tilda/Postman лид доходит до staging
- [ ] Все пункты MVP из PROEKT §10 отмечены

## После MVP (backlog)

- Metrika Reporting API (v2)
- Idempotency-Key на ingest
- Horizon dashboard
- Ротация токена с grace period
- Job удаления лидов старше `lead_retention_months`
