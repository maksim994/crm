# Деплой в Coolify (staging / production)

Один контейнер **web** (`Dockerfile.prod`): Nginx + PHP-FPM, собранные `/admin/` и `/cabinet/`, Laravel без dev-зависимостей.

## 1. Репозиторий

- **Dockerfile:** `Dockerfile.prod`
- **Порт контейнера:** `80`
- **Healthcheck:** `GET /health` → JSON `status: ok`

## 2. Переменные окружения (обязательные)

| Переменная | Пример | Комментарий |
|------------|--------|-------------|
| `APP_KEY` | `base64:...` | `php artisan key:generate --show` локально |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | |
| `APP_URL` | `https://crm.example.com` | С протоколом, без слэша в конце |
| `DB_HOST` | managed Postgres host | |
| `DB_PORT` | `5432` | |
| `DB_DATABASE` | `wbooster_crm` | |
| `DB_USERNAME` | | |
| `DB_PASSWORD` | | |
| `REDIS_HOST` | managed Redis | Rate limit ingest, сессии, очереди |
| `CACHE_STORE` | `redis` | |
| `SESSION_DRIVER` | `redis` | |
| `QUEUE_CONNECTION` | `redis` | Worker — отдельный процесс (см. ниже) |
| `SANCTUM_STATEFUL_DOMAINS` | `crm.example.com` | Домен SPA |
| `SESSION_DOMAIN` | `.example.com` | При необходимости |

## 3. Рекомендуемые

| Переменная | По умолчанию | Назначение |
|------------|--------------|------------|
| `LEAD_RETENTION_MONTHS` | `24` | NFR хранения (job удаления — backlog) |
| `CRM_INBOUND_DOMAIN` | `inbound.local` | Домен inbound-почты в prod |
| `RUN_MIGRATIONS` | `true` | `false`, если миграции в Pre-deploy команде |
| `LOG_CHANNEL` | `stack` | В prod можно `stderr` |
| `LOG_LEVEL` | `warning` | |
| `SENTRY_LARAVEL_DSN` | — | Опционально, пакет Sentry — отдельно |

## 4. Миграции

**Вариант A (по умолчанию):** `RUN_MIGRATIONS=true` — entrypoint выполняет `php artisan migrate --force` при старте.

**Вариант B:** Pre-deployment в Coolify:

```bash
php artisan migrate --force --no-interaction
```

и `RUN_MIGRATIONS=false`.

## 5. Queue worker

Inbound email и фоновые задачи требуют воркера:

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

Отдельный сервис в Coolify с тем же образом и env, команда выше.

## 6. Проверка после деплоя

```bash
curl -sf https://crm.example.com/health
```

Приём лида (токен с карточки сайта в админке):

```bash
curl -X POST "https://crm.example.com/ingest/seolead" \
  -H "Content-Type: application/json" \
  -d '{"token":"SITE_TOKEN","phone":"+79001234567","description":"staging test"}'
```

Ожидается `201` и `{"id":"..."}`.

## 7. Локальная проверка prod-образа

```bash
cp .env.example .env.prod.local
# Заполните APP_KEY, при необходимости APP_URL=http://localhost:8080

export $(grep -v '^#' .env.prod.local | xargs)
docker compose -f docker-compose.prod.yml build web
docker compose -f docker-compose.prod.yml up -d
curl -sf http://localhost:8080/health
```

## 8. Отличия от dev (`docker-compose.yml`)

| | Dev | Prod (`Dockerfile.prod`) |
|---|-----|--------------------------|
| Код | volume mount | В образе |
| Frontend | `make admin-build` на хосте | `npm run build` в образе |
| Nginx + PHP | 2 контейнера | 1 контейнер (supervisor) |
| Composer | с dev | `--no-dev` |

## 9. Чеклист staging

- [ ] `/health` → 200
- [ ] `/admin/` открывается, логин админа
- [ ] `/cabinet/` открывается
- [ ] POST `/ingest/seolead` создаёт лид
- [ ] POST `/api/v1/leads/call?token=...` создаёт лид `call`
- [ ] Redis доступен (rate limit, сессии)
- [ ] Queue worker запущен (если используется почта)

См. также [MVP-CHECKLIST.md](./MVP-CHECKLIST.md) (критерии ТЗ §14).
