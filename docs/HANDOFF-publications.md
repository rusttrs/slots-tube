# Handoff: страница публикации + админка

## Задача
Сделать публичную страницу публикации по Figma и довести Filament-админку, чтобы контент-менеджер мог уже создавать и публиковать материалы.

Публикации живут в **разных подразделах** (один шаблон страницы, разный раздел / URL / type):

| Подраздел | `Post.type` | Листинг (сейчас stub) | Страница материала |
|-----------|-------------|------------------------|--------------------|
| **News** | `news` | `/news` | `/news/{slug}` |
| **Blogs** | `blog` | `/blogs` | `/blogs/{slug}` |
| **Guides** | `guide` | `/guides` | `/guides/{slug}` |
| **Streamers** | `streamer` | `/streamers` | `/streamers/{slug}` |

- Одна модель `Post`, поле `type` выбирает подраздел.
- Один UI страницы публикации (Figma), «Back to …» ведёт в листинг своего подраздела (не всегда Community).
- В админке CM выбирает тип → материал попадает в нужный раздел.
- (Опционально позже: `industry` / `/industry-news` — уже намечен в `publicUrl()`, не блокер для старта.)

## Figma
- Файл: https://www.figma.com/design/xaj13PgGYZyWXBmAPBNEzc/Slots.tube--save-?node-id=310-7136
- `fileKey`: `xaj13PgGYZyWXBmAPBNEzc`
- `nodeId`: `310:7136`
- Доступа к другой фигме нет — опираться на этот файл + скриншоты пользователя.
- Макет один на все подразделы; отличаются URL, back-link и листинг.

## Визуальная структура (из макета)
1. **Back to {раздел}** (стрелка + ссылка на news/blogs/guides/streamers)
2. **Hero-картинка** full-width, скругления
3. **H1** заголовок
4. **Автор**: аватар + nickname + относительная дата («7 days ago»)
5. **Rich body**: абзацы, H2/H3, картинки с подписью/источником
6. **Engagement**:
   - liked by {user} and N others (+ стек аватаров)
   - Like / Comment
   - счётчик comments
7. **Комментарии**:
   - список (аватар, имя, дата, текст, Like, Reply, счётчик likes)
   - вложенные replies
   - composer «What are your thoughts?» + attach image + Post
8. Общий layout сайта: header / footer как на остальном slots.tube

## Что уже есть в коде
- Модель `App\Models\Post` (translatable title/excerpt/body, cover, author, type, SoftDeletes/Trashable)
- В форме типы: `news`, `guide`, `blog`, `streamer`, `industry`
- Filament: `PostResource` в группе «Контент» — форма пока без RichEditor body, только title/slug/cover/excerpt/type
- Фронт: `resources/views/posts/show.blade.php` — черновой одноколоночный вид, без лайков/комментов
- Роуты-листинки stub: `/news`, `/blogs`, `/guides`, `/streamers`
- Show сейчас заведён только для `/guides/{slug}`; `publicUrl()` уже мапит news→`/news/`, blog→`/blogs/`, guide→`/guides/` — **дописать streamer→`/streamers/{slug}`** и show-роуты для всех подразделов

## Что сделать (порядок)
1. Расширить админку Post:
   - RichEditor body EN/DE/FR (как у слотов)
   - cover = hero
   - author (уже есть)
   - **type обязателен**: News / Blogs / Guides / Streamers (и при необходимости Industry)
   - published / featured
   - фильтр списка постов по типу в Filament
   - при необходимости caption для inline-images в rich text
2. Роутинг: show для `/news/{slug}`, `/blogs/{slug}`, `/guides/{slug}`, `/streamers/{slug}` (+ trailing slash), единый `PostController@show` с проверкой `type`
3. Сверстать `posts/show` по Figma (CSS в `public/css/styles.css`, токены сайта); back-link и canonical от `type`
4. Модели/API для likes + comments (+ replies), модерация в Filament
5. i18n EN/DE/FR для UI-строк страницы
6. Soft delete уже есть → удалённые посты попадают в «Корзина»
7. Листинги подразделов (карточки) — следующим этапом, если не запрошены сразу; для CM-старта важнее show + админка

## Ограничения проекта
- Staging: `https://staging.slots.tube`, деплой rsync+ssh как раньше
- Медиа в R2 через `FilamentR2::prepare`, зеркало через `MediaMirror` / `media_url`
- Админка на русском
- Не коммитить, пока пользователь не попросит
- Не трогать plan-файлы

## Скриншоты от пользователя
- `/Users/rustemimamov/.cursor/projects/Users-rustemimamov-Desktop-slots-tube/assets/______________2026-10-08___09.10.56-224804d1-7f0d-44db-aa73-0b6d5d1d8796.jpg` — статья
- `/Users/rustemimamov/.cursor/projects/Users-rustemimamov-Desktop-slots-tube/assets/______________2026-10-08___09.11.05-844f8e33-6fb3-4f80-9362-9adba4d39a46.png` — комментарии / like / reply
