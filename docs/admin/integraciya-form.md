# Интеграция форм

Рекомендуемый способ подключения заявок с сайта — **`lead.php` на хостинге клиента**.  
Токен CRM хранится только на сервере; браузер отправляет форму на **тот же домен**, что и сайт.

Перед подключением: [С чего начать](nachalo-raboty.md) (заказчик, проект, токен) · [Поля лида](polya-lida.md).

---

## Схема

```text
Браузер  --POST /lead.php-->  хостинг сайта (lead.php)
                                    |
                                    +--POST /ingest/seolead + token-->  Lead CRM
```

| | |
|---|---|
| **CRM (ingest)** | `https://lk.crm-lead.ru/ingest/seolead` |
| **URL на сайте** | `https://ваш-сайт.ru/lead.php` |
| **Токен** | Только в `lead.php` (не в JS) |

---

## Шаг 1. Файл `lead.php`

Положите `lead.php` в **корень сайта** (рядом с `index.html` или в document root CMS).

```php
<?php
/**
 * Прокси заявок с сайта в Lead CRM.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
    exit;
}

$siteToken = getenv('LEAD_CRM_SITE_TOKEN') ?: 'ВАШ_UUID:секрет';
$crmIngestUrl = 'https://lk.crm-lead.ru/ingest/seolead';

$phone = trim((string) ($_POST['phone'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

if ($phone === '' && $email === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Укажите телефон или email']);
    exit;
}

$payload = [
    'token' => $siteToken,
    'phone' => $phone,
    'email' => $email,
    'name' => trim((string) ($_POST['name'] ?? '')),
    'description' => trim((string) ($_POST['description'] ?? 'Заявка с сайта')),
    'product' => trim((string) ($_POST['product'] ?? '')),
    'comment' => trim((string) ($_POST['comment'] ?? '')),
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
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['message' => 'CRM unavailable']);
    exit;
}

http_response_code($httpCode >= 400 ? 502 : 201);
echo $response;
```

**Токен** — из карточки **Проект** в админке. Лучше задать через env хостинга (`LEAD_CRM_SITE_TOKEN`), не хранить в Git.

### Проверка `lead.php`

```bash
curl -sS -X POST "https://ваш-сайт.ru/lead.php" \
  -d "phone=+79001234567" \
  -d "description=Тест lead.php" \
  -d "page_url=https://ваш-сайт.ru/"
```

Ожидается `{ "id": "..." }` и лид в **Лиды** админки.

---

## Вариант 1 — нативный JavaScript

Форма и скрипт на странице. Запрос идёт на **`/lead.php`**, токен CRM в JS **не передаётся**.

```html
<form id="lead-form">
  <input name="name" type="text" placeholder="Имя" />
  <input name="phone" type="tel" placeholder="+7..." required />
  <input name="email" type="email" placeholder="Email" />
  <button type="submit">Отправить</button>
</form>

<script>
(function () {
  var form = document.getElementById('lead-form');
  var METRIKA_ID = 57691633; // ID счётчика из карточки проекта (или null)

  function getUtm(name) {
    return new URLSearchParams(window.location.search).get(name) || '';
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var yaCounter = METRIKA_ID ? window['yaCounter' + METRIKA_ID] : null;
    var body = new FormData(form);

    body.set('description', 'Заявка с сайта');
    body.set('page_url', window.location.href);
    body.set('utm_source', getUtm('utm_source'));
    body.set('utm_medium', getUtm('utm_medium'));
    body.set('utm_campaign', getUtm('utm_campaign'));
    body.set('utm_term', getUtm('utm_term'));
    body.set('utm_content', getUtm('utm_content'));

    if (yaCounter && typeof yaCounter.getClientID === 'function') {
      body.set('metrika_client_id', yaCounter.getClientID());
    }

    fetch('/lead.php', { method: 'POST', body: body })
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
        alert(err.message || 'Не удалось отправить заявку');
      });
  });
})();
</script>
```

Подходит для: статического HTML, WordPress (блок HTML), любого сайта **без jQuery**.

---

## Вариант 2 — jQuery

Тот же `lead.php`, но отправка через **jQuery** — удобно для Tilda и legacy-вёрстки.

Подключите jQuery, если его ещё нет на странице.

```html
<form id="form1234567">
  <input class="t-input" name="phone" type="tel" placeholder="+7..." required />
  <button type="submit" class="t-submit">Отправить</button>
</form>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {
  var METRIKA_ID = 57691633; // ID счётчика из карточки проекта (или null)
  var yaCounter = METRIKA_ID ? window['yaCounter' + METRIKA_ID] : null;

  function getUtm(name) {
    var m = window.location.search.match(new RegExp('[?&]' + name + '=([^&]*)'));
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  $('#form1234567').on('submit', function (e) {
    e.preventDefault();

    var $form = $(this);
    var phone = $.trim($form.find('[name=phone]').val() || '');

    if (!phone) {
      return;
    }

    $.post('/lead.php', {
      phone: phone,
      name: $.trim($form.find('[name=name]').val() || ''),
      email: $.trim($form.find('[name=email]').val() || ''),
      description: 'Заявка с сайта',
      product: $.trim($form.find('[name=product]').val() || ''),
      comment: $.trim($form.find('[name=comment]').val() || ''),
      page_url: window.location.href,
      metrika_client_id: yaCounter ? yaCounter.getClientID() : '',
      utm_source: getUtm('utm_source'),
      utm_medium: getUtm('utm_medium'),
      utm_campaign: getUtm('utm_campaign'),
      utm_term: getUtm('utm_term'),
      utm_content: getUtm('utm_content'),
    })
      .done(function (data) {
        var id = data.id || data;
        if (yaCounter && id) {
          yaCounter.params({ 'crm-lead': id });
        }
        alert('Заявка принята, №' + id);
        $form[0].reset();
      })
      .fail(function () {
        alert('Не удалось отправить заявку');
      });
  });
});
</script>
```

**Tilda:** замените `#form1234567` на ID вашей формы (в Zero Block смотрите `#formXXXXXX`).

---

## Метрика

После успешного ответа CRM передайте id лида в Метрику:

```javascript
yaCounter.params({ 'crm-lead': leadId });
```

Подробнее: [Яндекс.Метрика](metrika-dlya-menedzhera.md).

---

## Чеклист

- [ ] Проект в статусе **Активен**, токен в `lead.php`
- [ ] `curl` на `/lead.php` → `{ "id": "..." }`
- [ ] Отправка с формы на сайте создаёт лид в админке
- [ ] (опционально) в Метрике параметр `crm-lead` → id лида

---

## Связанные документы

- [Поля лида](polya-lida.md)
- [Яндекс.Метрика](metrika-dlya-menedzhera.md)
- [Настройка проекта](nastrojka-proekta.md)
- [Если что-то не работает](chastye-problemy.md)
