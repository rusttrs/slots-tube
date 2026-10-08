<?php

/**
 * Страны для карточек Where to play.
 * ALL = дефолт, если для страны посетителя нет отдельных бонусов.
 * Список временный — позже расширим.
 */
return [
    'all_code' => 'ALL',

    'options' => [
        'ALL' => 'All countries',
        'DE' => 'Germany',
        'FR' => 'France',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'CA' => 'Canada',
        'AU' => 'Australia',
        'BR' => 'Brazil',
        'ES' => 'Spain',
        'IT' => 'Italy',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'SE' => 'Sweden',
        'MX' => 'Mexico',
        'JP' => 'Japan',
    ],

    /**
     * Если Cloudflare не передал страну (локалка / XX / T1),
     * используем этот код для подбора. ALL → сразу дефолтные карточки.
     */
    'unknown_as' => 'ALL',
];
