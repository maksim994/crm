# Сниппет Tilda: отправка лида (seolead)

Подставьте **URL** и **token** из карточки проекта в админке WBooster.

| Параметр | Значение |
|----------|----------|
| Endpoint | `http://localhost:8080/ingest/seolead` (prod — ваш домен) |
| Метод | GET или POST |
| Ответ | JSON `{ "id": "uuid" }` или plain text `uuid` при `Accept: text/plain` |

## jQuery (форма Tilda)

```javascript
var INGEST_URL = 'http://localhost:8080/ingest/seolead';
var SITE_TOKEN = 'SITE_UUID:secret_from_admin'; // из админки → Проект → токен
var yaCounterNumber = 57691633; // metrika_counter_id из карточки сайта

$(document).ready(function () {
  var yaCounter = window['yaCounter' + yaCounterNumber] || null;

  $('#formXXXXXX .t447__submit').on('click', function () {
    var form = $(this).closest('form');
    $.post(INGEST_URL, {
      token: SITE_TOKEN,
      metrika_client_id: yaCounter ? yaCounter.getClientID() : null,
      name: '',
      phone: $('input.t447__input', form).val(),
      email: '',
      description: 'Связаться с нами',
      page_url: window.location.href,
      utm_source: '',
      utm_medium: '',
      utm_campaign: '',
      utm_term: '',
      utm_content: '',
      utm_campaign_first: ''
    }).done(function (res) {
      var id = res.id || res;
      if (yaCounter && id) {
        yaCounter.params({ 'crm-lead': id });
      }
    });
  });
});
```

## Проверка через curl

```bash
curl -X POST "http://localhost:8080/ingest/seolead" \
  -d "token=SITE_UUID:secret" \
  -d "phone=+79001112233" \
  -d "description=Тест" \
  -d "metrika_client_id=17791064241773632"
```

См. [PROEKT.md](../PROEKT.md) §5.
