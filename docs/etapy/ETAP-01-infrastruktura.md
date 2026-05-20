# Этап 1. Инфраструктура

**Цель:** локально поднимается Laravel в Docker, есть health-check, готова база для миграций.

**Зависимости:** нет.

---

## Задачи

- [x] Инициализировать Laravel 11 в корне `crm/`
- [x] `Dockerfile` (PHP 8.3-fpm + extensions: pdo_pgsql, redis, intl, bcmath)
- [x] `docker-compose.yml`:
  - `nginx` → порт `8080:80` (localhost)
  - `app` (php-fpm)
  - `postgres` → `5432`, БД `wbooster_crm`
  - `redis` → `6379`
- [x] `docker/nginx/default.conf` — root `public/`
- [x] `.env.example` с переменными для Docker
- [x] `README.md` + `Makefile`: `make setup`
- [x] Route `GET /health` → JSON со статусом app/database/redis
- [x] Подключение к PostgreSQL и Redis из контейнера `app`

## Команды (ориентир)

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
curl http://localhost:8080/health
```

## Готово когда

- [x] `http://localhost:8080/health` возвращает 200 *(после `docker compose down && up` и ключа в кавычках в `.env`)*
- [ ] `php artisan` выполняется внутри контейнера
- [x] В README описан запуск с нуля за ≤ 10 минут

## Не делать на этом этапе

- Filament, модели Lead, ingestion
- Coolify (этап 7)

## Следующий этап

[ETAP-02-model-dannyh.md](./ETAP-02-model-dannyh.md)
