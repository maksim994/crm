# Интеграция сайта с WBooster CRM (тестирование)

Пошаговая инструкция: как подключить форму на сайте к CRM и увидеть лид в админке.

---

## Что понадобится

| Что | Где |
|-----|-----|
| CRM (production) | https://crm.mv-deploy.ru |
| Админка | https://crm.mv-deploy.ru/admin/ |
| Логин админа | `admin@wbooster.local` / `password` (после `db:seed`) |
| Endpoint приёма заявок | `https://crm.mv-deploy.ru/ingest/seolead` |
| Токен проекта | Карточка **Проект** в админке (формат `uuid:секрет`) |

Локально (Docker): замените домен на `http://localhost:8080`.

---

## Шаг 1. Проект (сайт) в админке

1. Войдите в https://crm.mv-deploy.ru/admin/
2. Меню **Проекты** → **Добавить проект** (или откройте существующий).
3. Заполните:
   - **Заказчик** — к кому относится сайт
   - **Название** — например, «Мой лендинг»
   - **Домены** — домен сайта без `https://` (например `example.com`, для теста можно `localhost`)
   - **Статус** — **Активен** (иначе лиды не принимаются → 403)
   - **Счётчик Метрики** — опционально, если будете передавать `metrika_client_id`
4. Сохраните проект.

После создания откроется карточка с **токеном** — скопируйте его сразу.  
Если токен потерян: на карточке проекта → **Перевыпустить токен** (старый перестанет работать).

Формат токена:

```text
550e8400-e29b-41d4-a716-446655440000:aBcDeFgHiJkLmNoPqRsTuVwXyZ123456
         ↑ UUID проекта в CRM          ↑ секрет (32 символа)
```

На карточке проекта в блоке **Интеграция** уже указаны URL и подсказки по параметрам.

---

## Шаг 2. Проверка без сайта (curl)

Подставьте свой токен и выполните в терминале:

```bash
CRM=https://crm.mv-deploy.ru
TOKEN='ВАШ_UUID:секрет'

curl -sS -X POST "$CRM/ingest/seolead" \
  -H "Accept: application/json" \
  -d "token=$TOKEN" \
  -d "phone=+79001234567" \
  -d "name=Тест" \
  -d "description=Проверка интеграции" \
  -d "page_url=https://example.com/landing"
```

**Успех:** ответ `201` и JSON:

```json
{ "id": "286818" }
```

`id` — номер лида в CRM (передайте в Метрику: `yaCounter.params({ 'crm-lead': id })`).

**Ошибки:**

| Код | Причина |
|-----|---------|
| 401 | Неверный или устаревший `token` |
| 403 | Проект на паузе / в архиве |
| 422 | Нет ни `phone`, ни `email` |
| 429 | Больше 60 запросов в минуту на токен + IP |

Проверка в админке: **Лиды** → фильтр по проекту → новая строка с телефоном `+79001234567`.

---

## Шаг 3. Подключение к сайту

### Вариант A — Tilda (готовый сниппет)

1. В Tilda: **Настройки сайта** → **Ещё** → **HTML-код для вставки внутрь HEAD** или блок **HTML** на странице с формой.
2. Подключите jQuery (если ещё нет).
3. Возьмите код из [docs/snippets/tilda-seolead.md](./snippets/tilda-seolead.md).
4. Замените в сниппете:
   - `INGEST_URL` → `https://crm.mv-deploy.ru/ingest/seolead`
   - `SITE_TOKEN` → ваш токен из шага 1
   - `yaCounterNumber` → ID счётчика из карточки проекта
   - селектор формы `#formXXXXXX` — на ID вашей формы в Tilda

После отправки формы лид уходит в CRM; при наличии Метрики: `yaCounter.params({ 'crm-lead': id })` — см. [metrika-kanal-i-crm-lead.md](./metrika-kanal-i-crm-lead.md).

### Вариант B — любой сайт (JavaScript на странице)

Подходит для лендинга, WordPress (блок HTML), статики.

```html
<form id="lead-form">
  <input name="phone" type="tel" placeholder="+7..." required />
  <button type="submit">Отправить</button>
</form>

<script>
(function () {
  var INGEST_URL = 'https://crm.mv-deploy.ru/ingest/seolead';
  var SITE_TOKEN = 'ВАШ_UUID:секрет';

  document.getElementById('lead-form').addEventListener('submit', function (e) {
    e.preventDefault();
    var phone = this.phone.value.trim();
    if (!phone) return;

    var body = new URLSearchParams({
      token: SITE_TOKEN,
      phone: phone,
      description: 'Заявка с сайта',
      page_url: window.location.href,
      utm_source: new URLSearchParams(location.search).get('utm_source') || '',
      utm_medium: new URLSearchParams(location.search).get('utm_medium') || '',
      utm_campaign: new URLSearchParams(location.search).get('utm_campaign') || ''
    });

    fetch(INGEST_URL, { method: 'POST', body: body })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        console.log('Лид в CRM:', data.id);
        alert('Заявка принята, №' + data.id);
        // Метрика (если счётчик уже на странице):
        // window['yaCounterXXXX']?.params({ 'crm-lead': data.id });
      })
      .catch(function () { alert('Ошибка отправки'); });
  });
})();
</script>
```

> **Важно:** токен в браузерном JS виден посетителю. Для production используйте **вариант C** (`lead.php` в корне сайта).

### Вариант C — `lead.php` в корне сайта (рекомендуется)

Токен CRM хранится только на сервере в `lead.php`. Браузер шлёт заявку на **тот же домен** (`/lead.php`) — нет CORS и токен не светится в исходниках страницы.

#### Структура на хостинге

```text
public_html/          ← корень сайта (document root)
  index.html          ← страница с формой
  lead.php            ← прокси в CRM (создаёте вы)
```

#### 1. Файл `lead.php` (в корне сайта)

Создайте `lead.php` рядом с `index.html`. Подставьте **свой токен** из админки CRM (или вынесите в переменную окружения на хостинге).

```php
<?php
/**
 * Прокси заявок с сайта в WBooster CRM.
 * URL на сайте: https://ваш-сайт.ru/lead.php
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Только POST с формы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

// Токен проекта из админки CRM (uuid:secret). Не отдавайте его в JS!
$siteToken = getenv('WBOOSTER_SITE_TOKEN') ?: 'ВАШ_UUID:секрет';

$crmIngestUrl = 'https://crm.mv-deploy.ru/ingest/seolead';

$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

if ($phone === '' && $email === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Укажите телефон или email']);
    exit;
}

// UTM с фронта (передаёт JS) или из page_url — как удобнее
$payload = [
    'token' => $siteToken,
    'phone' => $phone,
    'email' => $email,
    'name' => trim((string) ($_POST['name'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? 'Заявка с сайта')),
    'page_url' => trim((string) ($_POST['page_url'] ?? '')),
    'metrika_client_id' => trim((string) ($_POST['metrika_client_id'] ?? '')),
    'utm_source' => trim((string) ($_POST['utm_source'] ?? '')),
    'utm_medium' => trim((string) ($_POST['utm_medium'] ?? '')),
    'utm_campaign' => trim((string) ($_POST['utm_campaign'] ?? '')),
    'utm_term' => trim((string) ($_POST['utm_term'] ?? '')),
    'utm_content' => trim((string) ($_POST['utm_content'] ?? '')),
];

$ch = curl_init($crmIngestUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['message' => 'CRM unavailable', 'detail' => $curlError]);
    exit;
}

http_response_code($httpCode >= 400 ? 502 : 201);
echo $response;
```

Проверка `lead.php` без браузера:

```bash
curl -sS -X POST "https://ваш-сайт.ru/lead.php" \
  -d "phone=+79001234567" \
  -d "description=Тест lead.php" \
  -d "page_url=https://ваш-сайт.ru/"
```

Ожидается тот же JSON `{ "id": "..." }`, что и при прямом вызове CRM.

#### 2. Форма и JS на странице сайта

На `index.html` (или в шаблоне CMS) — форма и скрипт, который шлёт данные **на `/lead.php`**, без токена CRM:

```html
<form id="lead-form">
  <input name="name" type="text" placeholder="Имя" />
  <input name="phone" type="tel" placeholder="+7..." required />
  <button type="submit">Отправить</button>
</form>

<script>
(function () {
  var form = document.getElementById('lead-form');
  var METRIKA_ID = 57691633; // номер счётчика из карточки проекта в CRM (или null)

  function getUtm(name) {
    return new URLSearchParams(window.location.search).get(name) || '';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var yaCounter = METRIKA_ID ? window['yaCounter' + METRIKA_ID] : null;
    var body = new FormData(form);

    body.set('description', 'Заявка с главной');
    body.set('page_url', window.location.href);
    body.set('utm_source', getUtm('utm_source'));
    body.set('utm_medium', getUtm('utm_medium'));
    body.set('utm_campaign', getUtm('utm_campaign'));
    body.set('utm_term', getUtm('utm_term'));
    body.set('utm_content', getUtm('utm_content'));

    if (yaCounter && typeof yaCounter.getClientID === 'function') {
      body.set('metrika_client_id', yaCounter.getClientID());
    }

    fetch('/lead.php', {
      method: 'POST',
      body: body,
    })
      .then(function (res) {
        if (!res.ok) {
          return res.json().then(function (err) {
            throw new Error(err.message || 'Ошибка отправки');
          });
        }
        return res.json();
      })
      .then(function (data) {
        if (yaCounter && data.id) {
          yaCounter.params({ 'crm-lead': data.id });
        }
        alert('Заявка принята, №' + data.id);
        form.reset();
      })
      .catch(function (err) {
        console.error(err);
        alert(err.message || 'Не удалось отправить заявку');
      });
  });
})();
</script>
```

#### Схема запроса

```text
Браузер  --POST /lead.php-->  ваш хостинг (lead.php)
                                    |
                                    +--POST /ingest/seolead + token-->  CRM
```

| Кто видит токен | Где |
|-----------------|-----|
| Нет в браузере | Только в `lead.php` на сервере |
| Админ CRM | Карточка проекта |

#### Замечания по `lead.php`

- На **shared-хостинге** обычно уже есть `curl` — иначе включите расширение или используйте `file_get_contents` с `stream_context_create`.
- Путь `fetch('/lead.php')` — от корня домена; если сайт в подпапке, укажите `/папка/lead.php`.
- Не коммитьте `lead.php` с реальным токеном в публичный Git — лучше `WBOOSTER_SITE_TOKEN` в env хостинга.

### Вариант D — произвольный backend (не только PHP)

Если прокси не в корне, а на другом пути (например `/api/send-lead`), логика та же: сервер добавляет `token` и вызывает `https://crm.mv-deploy.ru/ingest/seolead`. Пример для Node/Express — по запросу в репозиторий можно добавить отдельно.

---

## Параметры запроса к `/ingest/seolead`

Метод: **GET** или **POST** (form-urlencoded, query string или JSON в теле).

| Параметр | Обязательный | Описание |
|----------|--------------|----------|
| `token` | да | `uuid:secret` из админки |
| `phone` | да* | Телефон |
| `email` | да* | Email (*хотя бы одно из phone/email) |
| `name` | нет | Имя |
| `description` | нет | Текст заявки / название формы |
| `metrika_client_id` | нет | `yaCounter.getClientID()` |
| `page_url` | нет | URL страницы отправки |
| `utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content`, `utm_campaign_first` | нет | UTM из URL |
| `ip` | нет | IP посетителя (если прокси знает реальный IP) |

Ответ:

- JSON: `{ "id": "..." }` (код 201)
- или plain text `id` при заголовке `Accept: text/plain`

Спецификация: [openapi.yaml](../openapi.yaml) (раздел ingest; основной рабочий путь — **seolead**).

---

## Шаг 4. Яндекс.Метрика

Подробно: [metrika-kanal-i-crm-lead.md](./metrika-kanal-i-crm-lead.md).

1. В проекте в CRM укажите **ID счётчика** (как на сайте).
2. На сайте после успешного ответа CRM:

```javascript
var yaCounter = window['yaCounter' + 57691633]; // ваш номер счётчика
if (yaCounter && leadId) {
  yaCounter.params({ 'crm-lead': leadId });
}
```

В отчёте **Параметры визита**: ур. 1 — `crm-lead`, ур. 2 — id лида (как на вашем скрине, раньше мог быть `wbooster`).

**Рекламный канал** в ЛК: сначала UTM (v1), затем job из Метрики перезаписывает поле, если включён Reporting API.

---

## Шаг 5. Демо-данные на production

Если в админке пусто, один раз на сервере:

```bash
php artisan db:seed --force
```

В логах seed появятся токены демо-проектов (`Ruflex Pro`, `Тест LP`).  
Для **своего** сайта лучше создать отдельный проект (шаг 1), а не использовать демо-токены.

---

## Чеклист теста end-to-end

- [ ] Проект в статусе **Активен**
- [ ] Токен скопирован / перевыпущен
- [ ] `curl` возвращает `201` и `id`
- [ ] Лид виден в **Лиды** админки
- [ ] Отправка с реальной формы сайта создаёт второй лид (через `lead.php` или напрямую)
- [ ] (опционально) в Метрике: `crm-lead` → id лида; UTM на визите совпадают с полями лида

---

## Другие каналы (не форма)

| Канал | URL | Как передать токен |
|-------|-----|-------------------|
| Звонок (Callibri и др.) | `POST /api/v1/leads/call?token=...` | query `token` или заголовок `X-Site-Token` |
| Почта (inbound) | адрес на карточке проекта | парсинг письма на стороне CRM |

Подробнее: [ETAP-06-integracii.md](./etapy/ETAP-06-integracii.md).

---

## Частые проблемы

**Лид не появляется в админке**

- Проверьте фильтр «Проект» / «Заказчик» на странице лидов
- Убедитесь, что статус проекта **Активен**
- Повторите `curl` и смотрите код ответа

**CORS при вызове с другого домена**

Прямой `fetch` на `crm.mv-deploy.ru` с чужого домена может блокироваться браузером. Используйте **`lead.php` на том же домене**, что и сайт (вариант C).

**Дубликаты**

Повторный телефон/email на том же проекте за 30 дней помечается как дубликат (`is_duplicate`), но лид всё равно создаётся.

---

## Ссылки

- [Сниппет Tilda](./snippets/tilda-seolead.md)
- [PROEKT.md §5](./PROEKT.md) — архитектура приёма лидов
- [deploy-coolify.md](./deploy-coolify.md) — production URL и env
