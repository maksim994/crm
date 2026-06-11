<?php

return [
    'root' => base_path('docs/admin'),

    'groups' => [
        [
            'title' => 'Начало работы',
            'documents' => [
                [
                    'slug' => 'nachalo-raboty',
                    'path' => 'nachalo-raboty.md',
                    'title' => 'С чего начать',
                    'description' => 'Заказчик, проект, учётная запись для ЛК.',
                ],
                [
                    'slug' => 'nastrojka-proekta',
                    'path' => 'nastrojka-proekta.md',
                    'title' => 'Настройка проекта',
                    'description' => 'Поля карточки проекта, статусы, токен, проверка.',
                ],
                [
                    'slug' => 'lichnyj-kabinet',
                    'path' => 'lichnyj-kabinet.md',
                    'title' => 'Личный кабинет заказчика',
                    'description' => 'Доступ клиента, разделы ЛК, вход от имени заказчика.',
                ],
            ],
        ],
        [
            'title' => 'Ежедневная работа',
            'documents' => [
                [
                    'slug' => 'rabota-s-lidami',
                    'path' => 'rabota-s-lidami.md',
                    'title' => 'Работа с лидами',
                    'description' => 'Список, фильтры, карточка, статусы, ручное добавление.',
                ],
            ],
        ],
        [
            'title' => 'Интеграции',
            'documents' => [
                [
                    'slug' => 'integraciya-form',
                    'path' => 'integraciya-form.md',
                    'title' => 'Интеграция форм',
                    'description' => 'lead.php + нативный JS или jQuery.',
                ],
                [
                    'slug' => 'integraciya-pochty',
                    'path' => 'integraciya-pochty.md',
                    'title' => 'Интеграция почты',
                    'description' => 'mail@crm-lead.ru, пересылка, подменные почты.',
                ],
                [
                    'slug' => 'metrika-dlya-menedzhera',
                    'path' => 'metrika-dlya-menedzhera.md',
                    'title' => 'Яндекс.Метрика',
                    'description' => 'Счётчик на проекте, crm-lead, поля «Тип» и «Реклама».',
                ],
            ],
        ],
        [
            'title' => 'Справочник',
            'documents' => [
                [
                    'slug' => 'polya-lida',
                    'path' => 'polya-lida.md',
                    'title' => 'Поля лида',
                    'description' => 'Какие поля можно передать при приёме заявки.',
                ],
                [
                    'slug' => 'chastye-problemy',
                    'path' => 'chastye-problemy.md',
                    'title' => 'Если что-то не работает',
                    'description' => 'Частые проблемы с формами, почтой, Метрикой и ЛК.',
                ],
            ],
        ],
    ],
];
