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
        'texts' => [
            'title' => ['label' => 'Заголовок страницы (H1)', 'default' => 'author.team_page.title', 'help' => 'Большой заголовок над вводным текстом.'],
            'lead' => ['label' => 'Вводный текст под H1', 'default' => 'author.team_page.lead', 'multiline' => true, 'help' => 'Ещё — meta description, если он пустой.'],
            'editors_title' => ['label' => 'Группа «Our Editors»: заголовок', 'default' => 'author.team_page.editors_title'],
            'editors_text' => ['label' => 'Группа «Our Editors»: текст', 'default' => 'author.team_page.editors_text', 'multiline' => true],
            'team_title' => ['label' => 'Группа «Other Team Members»: заголовок', 'default' => 'author.team_page.team_title'],
            'team_text' => ['label' => 'Группа «Other Team Members»: текст', 'default' => 'author.team_page.team_text', 'multiline' => true],
            'join_label' => ['label' => 'Карточка «?»: подпись', 'default' => 'author.team_page.join_label', 'help' => 'Серая карточка с вопросом в конце редакции.'],
            'join_url' => ['label' => 'Карточка «?»: ссылка', 'default' => '', 'help' => 'Куда ведёт карточка: страница вакансий, форма или mailto:jobs@slots.tube. Пусто — карточка без ссылки.'],
        ],
    ],
];
