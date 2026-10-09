<?php

/*
| Pages whose SEO and FAQ are edited in the admin ("Страницы: SEO и FAQ").
| To add a page: add an entry here, then in its controller/view use
| PageSetting::for('<key>') — the admin record is created automatically.
|
| key => [
|     'label' => admin title,
|     'group' => admin grouping,
|     'path'  => public path without locale prefix,
|     'faq'   => whether the page renders the FAQ block,
|     'blocks' => optional, page renders admin-built description blocks (partials/page-about),
|     'team_sections' => optional, page has admin-built author groups (team page),
|     'texts' => optional editable page texts, per locale:
|                field => ['label' => admin label, 'default' => lang key, 'multiline' => bool, 'help' => hint],
| ]
*/

return [
    'content' => ['label' => 'Хаб публикаций', 'group' => 'Публикации', 'path' => 'content/', 'faq' => true],
    'content.news' => ['label' => 'Раздел News', 'group' => 'Публикации', 'path' => 'content/news/', 'faq' => true],
    'content.blogs' => ['label' => 'Раздел Blogs', 'group' => 'Публикации', 'path' => 'content/blogs/', 'faq' => true],
    'content.guides' => ['label' => 'Раздел Guides', 'group' => 'Публикации', 'path' => 'content/guides/', 'faq' => true],
    'content.streamers' => ['label' => 'Раздел Streamers', 'group' => 'Публикации', 'path' => 'content/streamers/', 'faq' => true],
    'authors' => [
        'label' => 'Команда (список авторов)',
        'group' => 'About Us',
        'path' => 'authors/',
        'faq' => true,
        'team_sections' => true,
        'texts' => [
            'title' => ['label' => 'Заголовок страницы (H1)', 'default' => 'author.team_page.title', 'help' => 'Большой заголовок над вводным текстом.'],
            'lead' => ['label' => 'Вводный текст под H1', 'default' => 'author.team_page.lead', 'multiline' => true, 'help' => 'Ещё — meta description, если он пустой.'],
            'join_label' => ['label' => 'Карточка «?»: подпись', 'default' => 'author.team_page.join_label', 'help' => 'Серая карточка с вопросом. В какой группе она стоит — на вкладке «Группы авторов».'],
            'join_url' => ['label' => 'Карточка «?»: ссылка', 'default' => '', 'help' => 'Куда ведёт карточка: страница вакансий, форма или mailto:jobs@slots.tube. Пусто — карточка без ссылки.'],
        ],
    ],
    'bonuses' => [
        'label' => 'Бонусы',
        'group' => 'Rewards',
        'path' => 'bonuses/',
        'faq' => true,
        'blocks' => true,
        'texts' => [
            'title' => ['label' => 'Заголовок (H1): начало', 'default' => 'bonus.page.title', 'help' => 'Тёмная часть заголовка, например «Slots.tube».'],
            'title_accent' => ['label' => 'Заголовок (H1): оранжевая часть', 'default' => 'bonus.page.title_accent', 'help' => 'Выделенное слово после начала, например «Bonuses».'],
            'subtitle' => ['label' => 'Подзаголовок под H1', 'default' => 'bonus.page.subtitle'],
            'hero_title' => ['label' => 'Тёмный блок: заголовок', 'default' => 'bonus.page.hero_title', 'help' => 'Например «Top Online Casinos 2026».'],
            'hero_text' => ['label' => 'Тёмный блок: текст', 'default' => 'bonus.page.hero_text', 'multiline' => true, 'help' => 'Абзацы разделяйте пустой строкой. Первый абзац — ещё и meta description, если он пустой.'],
            'panel_title' => ['label' => 'Панель справа: заголовок', 'default' => 'bonus.page.panel_title'],
            'panel_1_title' => ['label' => 'Панель, пункт 1 (щит): заголовок', 'default' => 'bonus.page.panel_1_title'],
            'panel_1_text' => ['label' => 'Панель, пункт 1: текст', 'default' => 'bonus.page.panel_1_text'],
            'panel_2_title' => ['label' => 'Панель, пункт 2 (подарок): заголовок', 'default' => 'bonus.page.panel_2_title'],
            'panel_2_text' => ['label' => 'Панель, пункт 2: текст', 'default' => 'bonus.page.panel_2_text'],
            'panel_3_title' => ['label' => 'Панель, пункт 3 (замок): заголовок', 'default' => 'bonus.page.panel_3_title'],
            'panel_3_text' => ['label' => 'Панель, пункт 3: текст', 'default' => 'bonus.page.panel_3_text'],
        ],
    ],
];
