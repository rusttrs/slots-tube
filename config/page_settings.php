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
| ]
*/

return [
    'content' => ['label' => 'Хаб публикаций', 'group' => 'Публикации', 'path' => 'content/', 'faq' => true],
    'content.news' => ['label' => 'Раздел News', 'group' => 'Публикации', 'path' => 'content/news/', 'faq' => true],
    'content.blogs' => ['label' => 'Раздел Blogs', 'group' => 'Публикации', 'path' => 'content/blogs/', 'faq' => true],
    'content.guides' => ['label' => 'Раздел Guides', 'group' => 'Публикации', 'path' => 'content/guides/', 'faq' => true],
    'content.streamers' => ['label' => 'Раздел Streamers', 'group' => 'Публикации', 'path' => 'content/streamers/', 'faq' => true],
];
