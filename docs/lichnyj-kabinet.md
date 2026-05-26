# Личный кабинет заказчика

Vue SPA по адресу `/cabinet/` для пользователей с ролью `client_user`. Заказчик видит лиды только по своим проектам (сайтам).

---

## Вход

| Окружение | URL |
|-----------|-----|
| Production | https://crm.mv-deploy.ru/cabinet/ |
| Local Docker | http://localhost:8080/cabinet/ |
| Dev (Vite) | http://localhost:5174/cabinet/ |

Демо-пользователь после seed: `client@demo.example.com` / `password`.

Администратор платформы (`platform_admin`) в кабинет **не входит** — только через «Войти в ЛК» (impersonation) с карточки заказчика в админке.

---

## Навигация

```text
Sidebar
├── Лиды                    → /cabinet/
└── Проекты
    ├── {Название проекта}
    │   ├── Лиды и продажи  → /cabinet/projects/{siteId}/leads
    │   ├── Трафик и лиды   → /cabinet/projects/{siteId}/traffic  (таблица TZ §6.4)
    │   └── Аналитика       → /cabinet/projects/{siteId}/analytics (графики TZ §6.5)
    └── ...
```

| Маршрут | Страница | Описание |
|---------|----------|----------|
| `/cabinet/` | Лиды | Все лиды по доступным проектам; фильтр «Проект» в форме |
| `/cabinet/projects/{siteId}/leads` | Лиды и продажи | Тот же список, проект зафиксирован |
| `/cabinet/projects/{siteId}/traffic` | Трафик и лиды | Заглушка; **v2:** сводная таблица (TZ §6.4) |
| `/cabinet/projects/{siteId}/analytics` | Аналитика | Графики трафика из Reporting API Метрики (TZ §6.5) |
| `/cabinet/leads/{id}` | Карточка лида | Read-only детали |

Прямой переход на `/projects/{чужой-uuid}/...` перенаправляет на главную — доступ проверяется по списку `/api/client/sites`.

---

## Доступ к проектам

На пользователе ЛК (`users`):

| Поле | Поведение |
|------|-----------|
| `agency_client_id` | Заказчик, чьи лиды видны |
| `cabinet_all_sites = true` | Все проекты заказчика |
| `cabinet_all_sites = false` | Только проекты из pivot `site_user` |

Настройка в админке: карточка заказчика → «Добавить доступ» → все проекты или выборочно.

В sidebar отображаются **только доступные** проекты (ответ `GET /api/client/sites`).

---

## API (backend)

Префикс: `/api/client/*`, сессия Sanctum + middleware `client.user`.

| Метод | Endpoint | Назначение |
|-------|----------|------------|
| GET | `/user` | Текущий пользователь и заказчик |
| GET | `/sites` | Список доступных проектов |
| GET | `/leads` | Список лидов (фильтры, пагинация 30) |
| GET | `/leads/{id}` | Карточка лида |
| GET | `/leads/export` | CSV с теми же фильтрами |

**Аналитика (v2):** `GET /api/client/projects/{siteId}/analytics/{report}` — отчёты `traffic-sources`, `search-engines`, `search-branded`, `search-non-branded`, `geography`, `devices`. Query: `date_from`, `date_to`, `group_by`, `refresh`.

**План v2:** `GET /api/client/projects/{siteId}/traffic-stats` — табличный отчёт «Трафик и лиды» (TZ §6.4).

Фильтры лидов: `site_id`, `channel`, `lead_status`, `date_from`, `date_to`, `utm_campaign`.

Внутренние поля (ACC/PPC, `manager_comment`, IP и т.д.) в API ЛК **не отдаются**.

---

## Сборка

```bash
make cabinet-build   # → public/cabinet/
make cabinet-dev     # hot reload на :5174
```

---

## Связанные документы

- [ETAP-05-lichniy-kabinet.md](./etapy/ETAP-05-lichniy-kabinet.md) — этап разработки
- [TZ.md](./TZ.md) §6 — требования к ЛК
- [integraciya-s-saytom.md](./integraciya-s-saytom.md) — откуда приходят лиды
