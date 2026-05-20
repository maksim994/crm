# WBooster CRM

Платформа учёта лидов и сквозной аналитики для digital-агентства.

## Документация

| Документ | Описание |
|----------|----------|
| [docs/PROEKT.md](docs/PROEKT.md) | Стек, решения, архитектура |
| [docs/TZ.md](docs/TZ.md) | Техническое задание |
| [docs/etapy/README.md](docs/etapy/README.md) | Этапы разработки (1–7) |
| [docs/deploy-coolify.md](docs/deploy-coolify.md) | Деплой staging/production (Coolify) |
| [docs/MVP-CHECKLIST.md](docs/MVP-CHECKLIST.md) | Приёмка MVP (ТЗ §14) |
| [openapi.yaml](openapi.yaml) | OpenAPI 3.1 |

## Требования

- Docker Desktop (или Docker Engine + Compose v2)
- Make (опционально)

## Быстрый старт

```bash
# 1. Запустите Docker Desktop

# 2. Первичная настройка (копирует .env, собирает образы, миграции)
make setup

# 3. Проверка
make health
# или
curl http://localhost:8080/health
```

Вручную без Make:

```bash
cp .env.example .env
docker compose build
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
curl http://localhost:8080/health
```

Ожидаемый ответ:

```json
{
  "status": "ok",
  "checks": {
    "app": "ok",
    "database": "ok",
    "redis": "ok"
  }
}
```

## URL

| Сервис | URL |
|--------|-----|
| Приложение | http://localhost:8080 |
| **Админка (prod build)** | http://localhost:8080/admin/ |
| **Админка (dev, hot reload)** | http://localhost:5173/admin/ |
| **ЛК заказчика (prod build)** | http://localhost:8080/cabinet/ |
| **ЛК заказчика (dev)** | http://localhost:5174/cabinet/ |
| Health | http://localhost:8080/health |
| Laravel `/up` | http://localhost:8080/up |
| PostgreSQL | `localhost:5432` (user `wbooster`, db `wbooster_crm`) |
| Redis | `localhost:6379` |

### Кто куда входит

| Роль | URL | API | Лиды |
|------|-----|-----|------|
| `platform_admin` | `/admin/` | `/api/admin/*` | Все заказчики (фильтр по заказчику в списке лидов) |
| `client_user` | `/cabinet/` | `/api/client/*` | Только проекты своего заказчика |

**Важно:** вход `admin@wbooster.local` в `/admin/` — это режим агентства, не личный кабинет клиента. Заказчик использует `/cabinet/` и `client@demo.example.com`.

## Стек

Laravel 11 · PHP 8.3 · PostgreSQL 16 · Redis 7 · Nginx · Docker · Vue 3 (TailAdmin)

## Статус разработки

| Этап | Статус |
|------|--------|
| [1. Инфраструктура](docs/etapy/ETAP-01-infrastruktura.md) | готово |
| [2. Модель данных](docs/etapy/ETAP-02-model-dannyh.md) | готово |
| [3. Админ-панель](docs/etapy/ETAP-03-admin-panel.md) | TailAdmin Vue |
| [4. Приём лидов](docs/etapy/ETAP-04-priem-lidov.md) | готово |
| [5. Личный кабинет](docs/etapy/ETAP-05-lichniy-kabinet.md) | готово |
| [6. Интеграции](docs/etapy/ETAP-06-integracii.md) | Callibri + inbound email |
| [7. Качество и деплой](docs/etapy/ETAP-07-kachestvo-i-deploy.md) | Prod Docker, тесты, Coolify |

## Если `make health` падает с «Expecting value»

Обычно это **HTTP 500** вместо JSON. Частые причины:

1. **Пустой `APP_KEY` в контейнере** — после `key:generate` пересоздайте app:
   ```bash
   docker compose down
   docker compose up -d
   docker compose exec app php artisan key:generate --force
   ```
2. **`APP_KEY` с символом `+`** — в `.env` значение должно быть в кавычках:
   ```env
   APP_KEY="base64:...."
   ```
3. Логи: `docker compose exec app tail -30 storage/logs/laravel.log`

## Полезные команды

```bash
make fresh         # migrate:fresh + seed (демо-данные)
make seed          # только seed
make down          # остановить контейнеры
make shell         # shell в контейнере app
make test          # phpunit
make admin-install # npm install в frontend/
make admin-dev     # Vite :5173 → API :8080
make admin-build   # сборка в public/admin/
make cabinet-build # сборка в public/cabinet/
docker compose logs -f app
```

### Админка

```bash
make fresh          # демо-данные
make admin-install
make admin-dev      # http://localhost:5173/admin/
# или production UI:
make admin-build    # http://localhost:8080/admin
```

Вход: `admin@wbooster.local` / `password` — видны **все** лиды; для просмотра одного заказчика используйте фильтр «Заказчик» на странице лидов.

После `make fresh` в выводе seed будут **токены сайтов** для интеграции (этап 4).

### Личный кабинет заказчика

```bash
make cabinet-build   # http://localhost:8080/cabinet/
# или dev:
make cabinet-dev     # http://localhost:5174/cabinet/
```

Вход: `client@demo.example.com` / `password` (только лиды заказчика «ООО Демо Кровля»).

**Доступы в ЛК:** на карточке заказчика (`/admin/clients/{id}`) → «Добавить доступ». Можно выдать все проекты или выбрать конкретные сайты. Кнопка **«Войти в ЛК»** открывает кабинет под первым активным пользователем (или «Войти» в строке таблицы).

### Интеграции (этап 6)

- **Звонки:** `POST /api/v1/leads/call?token={site_token}` или заголовок `X-Site-Token` (формат Callibri поддерживается).
- **Почта (dev):** `php artisan mail:test-inbound {site_id} --sync` — адрес вида `leads+{uuid}@inbound.local` на карточке проекта.

## Деплой (Coolify / production)

Локальная разработка — `docker-compose.yml` + `make setup`.  
Production-образ — **`Dockerfile.prod`** (Nginx + PHP-FPM + собранные SPA в одном контейнере).

```bash
# Проверка prod-стека локально (нужен APP_KEY в env)
docker compose -f docker-compose.prod.yml up -d --build
curl -sf http://localhost:8080/health
```

Подробно: [docs/deploy-coolify.md](docs/deploy-coolify.md).  
Чеклист приёмки MVP: [docs/MVP-CHECKLIST.md](docs/MVP-CHECKLIST.md).

| NFR | Реализация |
|-----|------------|
| Rate limit ingest | 60 req/min на token + IP (`throttle:ingest`) |
| Хранение лидов | `LEAD_RETENTION_MONTHS=24` (job очистки — backlog) |
| Health | `GET /health` |
