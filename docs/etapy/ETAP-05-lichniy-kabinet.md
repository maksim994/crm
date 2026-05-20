# Этап 5. Личный кабинет заказчика

**Цель:** пользователь заказчика видит только свои лиды, фильтрует, экспортирует CSV.

**Зависимости:** [Этап 3](./ETAP-03-admin-panel.md), [Этап 4](./ETAP-04-priem-lidov.md)

---

## Задачи

- [ ] Filament Panel `client` на `/cabinet` (или отдельный guard)
- [ ] Роль `client_user` + `agency_client_id` на user
- [ ] Seeder: пользователь `client@demo.example.com` → заказчик «ООО Демо Кровля»
- [ ] **LeadResource (read-only)** с global scope `agency_client_id`
- [ ] Фильтры: период, сайт, channel, lead_status, utm_campaign
- [ ] Скрыть поля: token, visitor_ip, acc_*, ppc_*, manager_comment, inn, expected_amount
- [ ] Action «Экспорт CSV» (очередь или sync для MVP)
- [ ] Policy: запрет доступа к чужому `agency_client_id` (тест)

## Видимость полей

По [TZ.md](../TZ.md) раздел 10, колонка «ЛК = да».

## Готово когда

- [ ] `client@demo` видит лиды только Ruflex / Тест LP
- [ ] Admin не может войти в `/cabinet` без client role (или наоборот — разделение)
- [ ] CSV содержит только разрешённые колонки

## Следующий этап

[ETAP-06-integracii.md](./ETAP-06-integracii.md)
