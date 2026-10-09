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
        'countries' => App\Models\Country::class,
        'posts' => App\Models\Post::class,
        'providers' => App\Models\Provider::class,
        'authors' => App\Models\Author::class,
        'features' => App\Models\Feature::class,
        'newsletter' => App\Models\NewsletterSubscriber::class,
        'reviews' => App\Models\SlotReview::class,
        'post_comments' => App\Models\PostComment::class,
    ],

    /** Подписи вкладок в админке. */
    'labels' => [
        'slots' => 'Слоты',
        'bonuses' => 'Бонусы',
        'game_promos' => 'Попапы в игре',
        'countries' => 'Страны',
        'posts' => 'Посты',
        'providers' => 'Провайдеры',
        'authors' => 'Авторы',
        'features' => 'Фичи',
        'newsletter' => 'Подписки',
        'reviews' => 'Отзывы',
        'post_comments' => 'Комментарии',
    ],
];
