# Этап 3. Админ-панель (Filament)

**Цель:** CRUD заказчиков и сайтов, выдача токена, просмотр лидов.

**Зависимости:** [Этап 2](./ETAP-02-model-dannyh.md)

---

## Задачи

### Filament Admin panel

- [x] Установить Filament 3, панель `/admin`
- [x] Пользователь `platform_admin` (seeder + factory)
- [x] **AgencyClientResource**: список, создание, редактирование, архив
- [x] **SiteResource** («Проекты» в меню + relation manager у заказчика):
  - поля: name, domains (tags), metrika_counter_id, timezone, status
  - при создании: генерация токена, показ **один раз** в modal (copy button)
  - действие «Перевыпустить токен»
- [x] **LeadResource**: таблица с фильтрами (заказчик, сайт, дата, channel, status)
  - карточка лида — все поля
  - редактирование: lead_status, manager_name, manager_comment, acc/ppc

### UX

- [x] Поиск заказчика по названию / ИНН
- [x] На карточке сайта — блок «Инструкция интеграции» с URL `http://localhost:8080/ingest/seolead` и примером token

## Готово когда

- [x] Можно создать заказчика и сайт, скопировать токен
- [x] Список лидов открывается (пока пустой)
- [x] Вход в `/admin` только под admin

## Не делать

- Публичный ingestion (этап 4)
- ЛК заказчика (этап 5)

## Следующий этап

[ETAP-04-priem-lidov.md](./ETAP-04-priem-lidov.md)
