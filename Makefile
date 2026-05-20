.PHONY: setup up down build install key migrate health test shell admin-install admin-dev admin-build prod-build prod-up

setup: .env
	$(MAKE) build
	$(MAKE) up
	$(MAKE) install
	$(MAKE) key
	$(MAKE) migrate
	@echo ""
	@echo "Ready: http://localhost:8080/health"

.env:
	cp .env.example .env

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

install:
	docker compose exec app composer install --no-interaction

key:
	docker compose exec app php artisan key:generate --force
	@# Ключ в .env должен быть в кавычках (в значении бывает символ +)
	@grep -q '^APP_KEY="base64:' .env || (echo 'Подсказка: оберните APP_KEY в двойные кавычки в .env' && true)
	docker compose exec app php artisan config:clear
	docker compose up -d --force-recreate app

migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

fresh:
	docker compose exec app php artisan migrate:fresh --seed --force

health:
	@curl -sf http://localhost:8080/health | python3 -m json.tool || \
		(echo "Ошибка: endpoint не вернул JSON. Попробуйте: docker compose restart app && make health" && exit 1)

test:
	docker compose exec app php artisan test

shell:
	docker compose exec app sh

admin-install:
	cd frontend && npm install

admin-dev:
	cd frontend && npm run dev

admin-build:
	cd frontend && npm run build-only

cabinet-install:
	cd frontend && npm install

cabinet-dev:
	cd frontend && npm run cabinet-dev

cabinet-build:
	cd frontend && npm run cabinet-build

prod-build:
	docker compose -f docker-compose.prod.yml build

prod-up:
	docker compose -f docker-compose.prod.yml up -d
