<?php

/**
 * Корзина админки: soft delete + автоочистка.
 */
return [
    /** Сколько дней хранить удалённые записи перед окончательным удалением. */
    'retention_days' => 30,

    /**
     * Модели, которые попадают в корзину.
     * key — код вкладки, value — Eloquent-класс.
     *
     * @var array<string, class-string<\Illuminate\Database\Eloquent\Model>>
     */
    'models' => [
        'slots' => App\Models\Slot::class,
        'bonuses' => App\Models\Bonus::class,
        'game_promos' => App\Models\GamePromo::class,
        'posts' => App\Models\Post::class,
        'providers' => App\Models\Provider::class,
        'authors' => App\Models\Author::class,
        'themes' => App\Models\Theme::class,
        'features' => App\Models\Feature::class,
        'countries' => App\Models\Country::class,
        'newsletter' => App\Models\NewsletterSubscriber::class,
        'reviews' => App\Models\SlotReview::class,
        'post_comments' => App\Models\PostComment::class,
    ],

    /** Подписи вкладок в админке. */
    'labels' => [
        'slots' => 'Слоты',
        'bonuses' => 'Бонусы',
        'game_promos' => 'Попапы в игре',
        'posts' => 'Посты',
        'providers' => 'Провайдеры',
        'authors' => 'Авторы',
        'themes' => 'Темы',
        'features' => 'Фичи',
        'countries' => 'Страны',
        'newsletter' => 'Подписки',
        'reviews' => 'Отзывы',
        'post_comments' => 'Комментарии',
    ],
];
