# Архитектура приложения

Обычный монолит Laravel 13 (PHP 8.4): серверный рендер Blade для публичного сайта и Filament 5
(на Livewire 4) для админки. API нет, SPA нет, очередей фактически нет: письма уходят синхронно.

## Поток запроса

```
Браузер → Cloudflare (proxy, SSL, CF-IPCountry) → nginx :443 (Cloudflare Origin Cert)
       → статика из public/ напрямую | всё остальное → php8.4-fpm (unix socket) → public/index.php → Laravel
Laravel → PostgreSQL (контент, юзеры) · Redis (сессии db0, кеш db1) · R2 (загрузки) · Resend (почта)
```

Middleware группы `web` (порядок важен): стандартные Laravel → `SetLocale` → `EnsureUserIsActive`.
`trustProxies('*')`: реальный IP клиента берётся из `CF-Connecting-IP` (на уровне nginx, `set_real_ip_from` диапазоны CF).
Health-check Laravel: `GET /up`.

## Языки (i18n)

- Языки сайта: `en` (по умолчанию, без префикса), `de`, `fr` (префикс `/de/…`, `/fr/…`). Список зашит в
  `config/app.php` → `available_locales` (переменная `APP_AVAILABLE_LOCALES` в .env **не используется**).
- `SetLocale` берёт язык из первого сегмента URL; для `/admin` и Livewire-запросов админки — всегда `ru`.
- Переводимые поля моделей — JSON-колонки вида `{"en": "...", "de": "...", "fr": "..."}`
  (пакет `spatie/laravel-translatable`, свойство `$translatable` в модели). В PostgreSQL это тип `json`,
  поэтому сортировать по ним напрямую нельзя — в таблицах админки используется
  `App\Filament\Support\TranslatableSort::by('title')` (`ORDER BY lower(title->>'en')`).
- Тексты интерфейса — `lang/{en,de,fr}/{slot,post,content,author}.php`.
- Ссылки строить через `localized_url($locale, $path)` (`app/Support/helpers.php`), а не `url()`.

## URL-схема (`routes/web.php`)

Все публичные маршруты регистрируются дважды: без префикса (с именами) и под `{locale}` = `de|fr` (без имён).

| URL | Что | Контроллер |
|---|---|---|
| `/` | главная | `PageController@home` → `home.blade.php` |
| `/slots/{slug}/` | страница слота (главная сущность сайта) | `SlotController@show` |
| `/providers/{slug}` | провайдер | `ProviderController@show` |
| `/authors/{slug}` | автор | `AuthorController@show` |
| `/content/` | хаб публикаций | `ContentHubController@index` |
| `/content/{news,blogs,guides,streamers}` | раздел (пагинация) | `ContentHubController@section` |
| `/content/{slug}/` | публикация | `PostController@show` |
| `/news`, `/blogs`, `/guides`, `/streamers` | 301 → `/content/…` (легаси) | redirect |
| `/free-slots`, `/crash-games`, `/other-games`, `/providers`, `/by-feature[/{slug}]`, `/by-themes`, `/authors`, `/our-mission`, `/bonuses`, `/privacy`, `/terms`, `/cookies`, `/responsible-gaming` | **заглушки** (`pages/stub.blade.php`) | `PageController@stub` |
| `/profile` (GET/POST) | профиль, онбординг | `ProfileController` (auth) |
| `POST /slots/{slug}/reviews` | отзыв о слоте (1 на юзера на слот) | `SlotReviewController@store` (auth) |
| `POST /slot-reviews/{id}/like`, `/posts/{id}/like`, `/post-comments/{id}/like` | лайки (toggle) | auth, throttle 60/мин |
| `POST /posts/{id}/comments`, `DELETE /post-comments/{id}` | комментарии (текст ≤2000 и/или картинка → R2) | auth, throttle 30/мин |
| `POST /translate` | перевод пользовательского текста (UGC) | `TranslateController`, **без auth**, throttle 40/мин |
| `POST /auth/magic-link`, `GET /auth/magic/{user}` (signed) | вход по ссылке из письма | `MagicLinkController` |
| `GET /auth/google[/callback]` | вход через Google | `GoogleController` (Socialite) |
| `POST /logout` | выход | |
| `POST /newsletter/subscribe`, `GET /newsletter/unsubscribe/{token}` | рассылка | `NewsletterController` |
| `/admin/*` | админка Filament | `AdminPanelProvider` |

**Слэш на конце:** страницы слотов канонически со слэшем — nginx делает 301 `/slots/x` → `/slots/x/`
(и для `/de|fr/slots/x`). Laravel-маршрут объявлен в обоих вариантах. Middleware `EnsureSlotTrailingSlash`
существует, но **не подключён** (дублирует nginx).

## Доменные модели (`app/Models`)

| Модель | Таблица | Суть | Переводимые поля |
|---|---|---|---|
| `Slot` | `slots` | обзор слота: ~60 полей (RTP, волатильность, символы, paytable, скриншоты, FAQ, методология, SEO…) | почти все текстовые |
| `Provider` | `providers` | студия-разработчик слотов | name, description |
| `Author` | `authors` | авторы/ревьюеры контента (это **не** юзеры сайта) | name, position, page_title, bio, traits (теги), started_at (Joined); подборки `favorite_slot_ids` / `red_flag_slot_ids` / `top_streamers` / `favorite_post_ids`; блоки `latest_slot_ids` / `latest_post_ids` (пусто — авто) + `show_latest_*` и `*_title`; SEO meta_*, noindex, social_links (sameAs). `role` — устаревшая должность, используется только как fallback |
| `Post` | `posts` | публикация; `type` ∈ news / blog / guide / streamer → раздел `/content/{news,blogs,guides,streamers}` | title, excerpt, body |
| `PostComment` | `post_comments` | комментарий к публикации, древовидный (`parent_id`), может иметь картинку | — |
| `SlotReview` | `slot_reviews` | отзыв юзера о слоте: рейтинг 1–5, demo/real, текст; unique(slot_id, user_id) | — |
| `Like` | `likes` | полиморфный лайк; `likeable_type` ∈ `post`, `post_comment`, `slot_review` (morphMap в `AppServiceProvider`) | — |
| `Bonus` | `bonuses` (+ `bonus_slot`) | карточка бонуса казино «Where to play», гео-таргетинг по странам | title, short_text, terms |
| `GamePromo` | `game_promos` | всплывающий бонус поверх демо-игры на странице слота (задержка `delay_seconds`) | offer_text, cta_label, legal_text |
| `Feature`, `Theme` | `features`, `themes` (+ pivot) | особенности/темы слотов (фильтры каталога — пока заглушки) | name, description |
| `Country` | `countries` | справочник стран | name |
| `PageSetting` | `page_settings` | SEO + FAQ для страниц-листингов; реестр ключей в `config/page_settings.php` | (JSON по локалям внутри) |
| `PageBlock`, `StaticPage` | `page_blocks`, `static_pages` | контент-блоки и статические страницы (пока почти не используются на фронте) | title, body |
| `NewsletterSubscriber` | `newsletter_subscribers` | подписчики, токен отписки | — |
| `User` | `users` | юзер сайта (и админки — см. «Доступ в админку») | — |

**Корзина (soft delete).** Почти все модели используют трейт `Concerns/Trashable` (= `SoftDeletes`).
Удалённое в админке попадает в «Система → Корзина» (`app/Filament/Pages/RecycleBin.php`), откуда можно восстановить.
`trash:purge` (ежедневно 03:15) удаляет окончательно записи старше `config('trash.retention_days')` = 30 дней.
Список моделей корзины — `config/trash.php`.

**Лайки.** `App\Services\LikeService` — единственный способ ставить/снимать лайк: в транзакции вставляет/удаляет
строку `likes` и двигает денормализованный счётчик `likes_count` у объекта (защита от двойного клика через
`insertOrIgnore`). При удалении юзера его лайки снимаются до каскадного удаления (`User::booted`).
`likes:sync` (ежедневно 03:45) удаляет «осиротевшие» лайки и пересчитывает все счётчики. Покрыто тестом
`tests/Feature/LikeServiceTest.php`.

**Публикация.** У большинства сущностей флаг `is_published`; публичные контроллеры показывают только опубликованное.
`Post`: при публикации проставляется `published_at`; `content_updated_on` + `updated_by_author_id` — плашка
«обновлено автором». Слаги: `Slot::ensureUniqueSlug`, `Bonus::uniqueSlug`; слаги постов не могут совпадать
с разделами (`Post::reservedSlugs()`).

**Гео-таргетинг бонусов.** `App\Support\VisitorCountry::code()` берёт страну из заголовка Cloudflare `CF-IPCountry`
(`XX`/`T1`/пусто → `ALL`). На не-production окружениях можно подменить: `?country=DE`.
`Bonus::forVisitorCountry()`: если есть бонусы именно для страны — показываются они, иначе — бонусы «ALL».
Список стран для выбора в админке — `config/bonus_countries.php`. Аналогично `GamePromo::resolveForVisitor()`.

**Авто-ссылки на фичи.** `App\Support\FeaturePhraseLinker` в тексте слота превращает фразы
(«free spins», «bonus buy», «scatter symbol»…) в ссылки на `/by-feature/{slug}`.

## Медиа: R2 + локальное зеркало (важно!)

- Все загрузки из админки (обложки, логотипы, скриншоты, символы, картинки в тексте постов) и картинки
  в комментариях пишутся в **Cloudflare R2**, бакет `slots-tube-media` (диск `r2`, `FILESYSTEM_DISK=r2`).
  В БД хранится относительный путь, например `slots/covers/xxx.png`.
- Бакет **приватный**, публичного CDN-домена нет (`AWS_URL` пуст). Поэтому `App\Support\MediaMirror`
  копирует каждый файл из R2 в локальный диск `public` (`storage/app/public`, отдаётся nginx как `/storage/...`).
  Зеркалирование срабатывает при сохранении модели (`Slot/Post/GamePromo/User/Author::saved`), при открытии формы в админке
  (`App\Support\FilamentR2` — превью FilePond берётся из зеркала, т.к. signed URL R2 без CORS ломают FilePond)
  и при загрузке картинки в комментарий.
- Вывод URL — хелпер `media_url($path)`, порядок поиска:
  1. абсолютный URL → как есть; путь начинается с `assets/` → `asset()`;
  2. `public/assets/images/{path}` (картинки, лежащие в git) → `/assets/images/...`;
  3. локальное зеркало `storage/app/public/{path}` → `/storage/...`;
  4. иначе URL диска R2 (при приватном бакете не откроется — значит, зеркало не синхронизировано).
- **Аватарки юзеров** (профиль `/profile`, форма юзера в админке) и **фото авторов** тоже пишутся в R2
  (`avatars/…`, `authors/…`); зеркалятся при сохранении модели (`User/Author::saved`). Аватарки, загруженные
  до переезда в R2, выгружаются командой `php artisan media:push avatars` (только недостающие; идемпотентна).
  У юзеров из Google в `avatar_path` лежит внешний URL — он отдаётся как есть.
  Демо-аватарки `avatars/user-0N.jpg` лежат в git (`public/assets/images/avatars/`).
- После переезда на новый сервер зеркало восстанавливается командой **`php artisan media:mirror`**
  (скачивает все объекты R2 в `storage/app/public`; идемпотентна).
- Временные загрузки Livewire — диск `local` (`storage/app/private/livewire-tmp`).

## Аутентификация

- **Magic link:** `POST /auth/magic-link` (email, intent login|signup, для signup — подтверждение 18+) →
  `firstOrCreate` юзера → письмо `MagicLinkMail` со ссылкой `URL::temporarySignedRoute` на 30 минут →
  `GET /auth/magic/{user}` логинит (remember=true), подтверждает email. Пароли у юзеров сайта = `null`.
- **Google:** Socialite, `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`; при входе связывает по `google_id`/email.
- После первого входа — онбординг (`/profile`: ник, аватар), см. `User::needsOnboarding()`.
- Деактивация: `users.deactivated_at`; `EnsureUserIsActive` разлогинивает деактивированных на любом запросе.
- Возврат на исходную страницу после входа — хелперы `remember_auth_return()` / `pull_auth_return()`.
- Гости, попавшие на auth-страницу, редиректятся на главную с `?auth=1` (открывает модалку входа).

## Админка (Filament 5, `/admin`)

- Панель: `app/Providers/Filament/AdminPanelProvider.php`; ресурсы автообнаруживаются в `app/Filament/Resources`.
  Структура ресурса: `XResource.php` + `Pages/` + `Schemas/XForm.php` + `Tables/XTable.php` (+ `RelationManagers/`).
- Группы меню: **Каталог** (Слоты, Провайдеры, Бонусы, Попапы в игре, Особенности, Темы),
  **Контент** (Публикации, Авторы, Страницы: SEO и FAQ, Блоки страниц, Статические страницы),
  **Монетизация** (Страны), **Пользователи** (Пользователи, Подписки), **Система** (Корзина).
- Вход: Filament-логин по email+паролю (юзер с паролем создаётся `php artisan make:filament-user`).
- ⚠ **Доступ в админку:** `User::canAccessPanel()` возвращает `isActive()` — ролей нет, в админку пускает
  **любого активного юзера** (в т.ч. зарегистрированного через magic link: сессия общая, guard `web`).
  См. [known-issues.md](known-issues.md).
- Корзина, лайки (просмотр/удаление «кто лайкнул»), комментарии и отзывы — через RelationManagers в Посты/Слоты.

## Фронтенд публичного сайта

- **Сборки нет.** `public/css/styles.css`, `public/js/{main,slot-page,post-page,auth-newsletter,ugc-translate}.js`,
  `public/assets/` — готовые файлы, перенесённые из HTML-прототипа (отдельный репо `slots-tube-verstka`), и правятся
  прямо в `public/`. Кеш-бастинг — вручную через `?v=...` в `resources/views/layouts/app.blade.php`
  и в шаблонах страниц: **поменял CSS/JS — поменяй `?v=`**.
- **Иконки — SVG-спрайт.** Исходники: `resources/icons/<имя>.svg` (по файлу на иконку, имя файла = имя иконки).
  `php artisan icons:build` собирает их в `public/assets/icons/sprite.svg` (файл **коммитится** — на сервере сборки нет;
  внутренние id иконок префиксуются, неиспользуемые выкидываются). В шаблоне: `<x-site-icon name="search" width="20" height="20" />`
  → `<svg class="icon"><use href="/assets/icons/sprite.svg?v=<хеш>#search"></svg>` — версия считается от содержимого
  спрайта, `?v=` руками не трогать. CSS-стили иконок пишутся через `.icon`, а не `img`. Цвет из CSS работает только там, где
  в исходнике стоит `currentColor`; заливку можно включать переменной (`--icon-fill`, см. `topic-heart`).
  `tests/Feature/IconSpriteTest` падает, если спрайт не пересобран или шаблон ссылается на несуществующую иконку.
  Логотипы, водяные знаки, бейджи GamCare/GamStop и заглушки аватаров остаются обычными `<img>`.
- Внешнее: Google Fonts (Inter, Montserrat), Swiper 11 с `cdn.jsdelivr.net`.
- `vite.config.js`, `resources/css/app.css`, `resources/js/app.js`, `package.json` — заготовка Laravel, **не используются**
  (Node на сервере не нужен). Ассеты Filament/Livewire публикуются `php artisan filament:upgrade`
  (composer post-autoload-dump) в `public/{css,js,fonts}/filament` — они в `.gitignore`.
- Шаблоны: `resources/views/layouts/app.blade.php` (основной layout), `partials/` (header, footer, auth-modal,
  bottom-nav, page-seo), `components/site-icon.blade.php`, `slots/show.blade.php` (≈1500 строк — самая сложная страница), `posts/`, `content/`,
  `providers/`, `authors/`, `pages/`, `emails/`. `welcome*.blade.php`, `app.blade.php` — старые, не используются роутами.
- **Перевод UGC:** кнопка «перевести» у отзывов/комментариев → `POST /translate` → `App\Services\GoogleTranslate`
  ходит в **неофициальный** endpoint `translate.googleapis.com/translate_a/single?client=gtx` (без ключа),
  результат кешируется в Redis на 30 дней.
- SEO: `partials/page-seo.blade.php`, мета/FAQ для листингов — `PageSetting::for('<key>')`.
  Пока весь сайт отдаётся с `X-Robots-Tag: noindex, nofollow` (заголовок в nginx) — это staging.

## Почта

Синхронно (без очереди) через Resend (`MAIL_MAILER=resend`): `MagicLinkMail` (вход/регистрация),
`NewsletterWelcomeMail` (подписка, со ссылкой отписки). Шаблоны — `resources/views/emails/`.
DNS домена для Resend: DKIM `resend._domainkey.slots.tube`, `send.slots.tube` (MX + SPF).

## Фоновые задачи

`routes/console.php` (запускается cron'ом `schedule:run` каждую минуту, см. infrastructure.md):

| Когда (UTC) | Команда | Что делает |
|---|---|---|
| 03:15 | `trash:purge` | окончательно удаляет из корзины записи старше 30 дней (`--days=N` переопределить) |
| 03:45 | `likes:sync` | чистит осиротевшие лайки, пересчитывает `likes_count` |

Вне расписания: `media:mirror` (восстановление зеркала R2). Очередь (`QUEUE_CONNECTION=redis`) настроена,
но задач в очередь код не ставит, воркер не запущен и не нужен.

## Тесты

`php artisan test` (dev-зависимости нужны: `composer install` без `--no-dev`). БД тестов — sqlite in-memory
(`phpunit.xml`), рабочую базу не трогают. Есть: `LikeServiceTest`, `PostPublicationTest`, примеры.
Сидеры (`database/seeders`) используют Faker → **только для локалки**, на сервере (`--no-dev`) упадут.
