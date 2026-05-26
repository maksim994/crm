<?php

return [
    'groups' => [
        [
            'title' => 'Интеграции',
            'documents' => [
                [
                    'slug' => 'integraciya-s-saytom',
                    'path' => 'integraciya-s-saytom.md',
                    'title' => 'Подключение сайта к CRM',
                    'description' => 'Формы, токен проекта, тест отправки, Tilda.',
                ],
                [
                    'slug' => 'metrika-kanal-i-crm-lead',
                    'path' => 'metrika-kanal-i-crm-lead.md',
                    'title' => 'Метрика: crm-lead и канал в ЛК',
                    'description' => 'Параметр crm-lead и рекламный канал для заказчика.',
                ],
                [
                    'slug' => 'integraciya-inbound-email',
                    'path' => 'integraciya-inbound-email.md',
                    'title' => 'Входящая почта',
                    'description' => 'Webhook Mailgun и приём лидов с email.',
                ],
                [
                    'slug' => 'tilda-seolead',
                    'path' => 'snippets/tilda-seolead.md',
                    'title' => 'Сниппет Tilda (seolead)',
                    'description' => 'Готовый код для формы Tilda.',
                ],
            ],
        ],
        [
            'title' => 'Проект',
            'documents' => [
                [
                    'slug' => 'lichnyj-kabinet',
                    'path' => 'lichnyj-kabinet.md',
                    'title' => 'Личный кабинет заказчика',
                    'description' => 'Маршруты, навигация по проектам, доступ и API.',
                ],
                [
                    'slug' => 'proekt',
                    'path' => 'PROEKT.md',
                    'title' => 'Стек и архитектура',
                    'description' => 'Технологии, решения, структура приложения.',
                ],
                [
                    'slug' => 'tz',
                    'path' => 'TZ.md',
                    'title' => 'Техническое задание',
                    'description' => 'Полное ТЗ платформы.',
                ],
                [
                    'slug' => 'mvp-checklist',
                    'path' => 'MVP-CHECKLIST.md',
                    'title' => 'Чеклист приёмки MVP',
                    'description' => 'Критерии готовности по ТЗ §14.',
                ],
                [
                    'slug' => 'etapy',
                    'path' => 'etapy/README.md',
                    'title' => 'Этапы разработки',
                    'description' => 'Обзор этапов 1–7.',
                ],
            ],
        ],
        [
            'title' => 'Этапы разработки',
            'documents' => [
                [
                    'slug' => 'etap-01-infrastruktura',
                    'path' => 'etapy/ETAP-01-infrastruktura.md',
                    'title' => 'Этап 1. Инфраструктура',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-02-model-dannyh',
                    'path' => 'etapy/ETAP-02-model-dannyh.md',
                    'title' => 'Этап 2. Модель данных',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-03-admin-panel',
                    'path' => 'etapy/ETAP-03-admin-panel.md',
                    'title' => 'Этап 3. Админ-панель',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-04-priem-lidov',
                    'path' => 'etapy/ETAP-04-priem-lidov.md',
                    'title' => 'Этап 4. Приём лидов',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-05-lichniy-kabinet',
                    'path' => 'etapy/ETAP-05-lichniy-kabinet.md',
                    'title' => 'Этап 5. Личный кабинет',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-06-integracii',
                    'path' => 'etapy/ETAP-06-integracii.md',
                    'title' => 'Этап 6. Интеграции',
                    'description' => null,
                ],
                [
                    'slug' => 'etap-07-kachestvo-i-deploy',
                    'path' => 'etapy/ETAP-07-kachestvo-i-deploy.md',
                    'title' => 'Этап 7. Качество и деплой',
                    'description' => null,
                ],
            ],
        ],
        [
            'title' => 'Эксплуатация',
            'documents' => [
                [
                    'slug' => 'deploy-coolify',
                    'path' => 'deploy-coolify.md',
                    'title' => 'Деплой (Coolify)',
                    'description' => 'Staging и production, переменные окружения.',
                ],
            ],
        ],
    ],
];
