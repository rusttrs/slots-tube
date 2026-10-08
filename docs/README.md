# slots.tube — документация

Обзорник слотов (игровых автоматов) на 3 языках (en / de / fr): обзоры слотов, провайдеры, бонусы казино
с гео-таргетингом, публикации (news / blogs / guides / streamers), авторы, отзывы, комментарии, лайки, рассылка.
Контент ведётся в админке `/admin` (Filament, интерфейс на русском).

> **Для AI-агента / нового разработчика:** читай этот файл целиком, дальше — по задаче:
> разворот с нуля → [from-scratch.md](from-scratch.md); выкатка изменений → [deploy.md](deploy.md);
> что-то сломалось → [operations.md](operations.md); как устроен код → [architecture.md](architecture.md).
> Секретов в репозитории нет — их список и источники в [env.md](env.md).

## Шпаргалка

| Что | Значение |
|---|---|
| Репозиторий | `git@github.com:rusttrs/slots-tube.git`, ветка `main` (единственная) |
| Сервер | DigitalOcean droplet, AMS3, 4 vCPU / 16 GB / 320 GB, Ubuntu 24.04, IP `188.166.21.66` |
| SSH | `ssh root@188.166.21.66` |
| Код на сервере | `/var/www/slots.tube` (владелец `deploy:www-data`) |
| Staging (этот сервер) | https://staging.slots.tube · админка https://staging.slots.tube/admin |
| Прод-домен `slots.tube` | пока **не** этот сервер: Cloudflare → GitHub Pages (заглушка Coming Soon) |
| Стек | Laravel 13 · PHP 8.4 · Filament 5 · Livewire 4 · PostgreSQL 16 · Redis 7 · Nginx 1.24 |
| Медиа | Cloudflare R2, бакет `slots-tube-media` + локальное зеркало `storage/app/public` |
| Почта | Resend, отправитель `noreply@slots.tube` |
| DNS / CDN / SSL | Cloudflare (proxied), на origin — Cloudflare Origin Certificate |
| Выкатка | `git push origin main` → `ssh root@188.166.21.66 deploy` |
| Бэкапы | ежедневно 03:00 UTC → `/var/backups/slotstube/` (БД + `storage/app/public`), 14 дней |

## Документы

| Файл | О чём |
|---|---|
| [architecture.md](architecture.md) | Как устроено приложение: модели, URL-схема, i18n, медиа-пайплайн, auth, админка, фронтенд, фоновые задачи |
| [infrastructure.md](infrastructure.md) | Сервер целиком: пакеты, nginx, PHP-FPM, PostgreSQL, Redis, UFW, fail2ban, cron, Cloudflare, DNS, внешние сервисы |
| [deploy.md](deploy.md) | Git-workflow, команда `deploy`, откат, правки прямо на сервере |
| [from-scratch.md](from-scratch.md) | Пошаговый разворот на новом сервере (≈10 минут) + перенос данных |
| [env.md](env.md) | Каждая переменная `.env`: смысл, значение, где взять секрет |
| [operations.md](operations.md) | Логи, крон, бэкапы и восстановление, частые команды, траблшутинг |
| [known-issues.md](known-issues.md) | Известные проблемы, техдолг и TODO по безопасности |
| [HANDOFF-publications.md](HANDOFF-publications.md) | Исторический handoff по разделу публикаций (ТЗ от 2026-10) |

## Карта репозитория

```
app/
  Console/Commands/     artisan-команды: trash:purge, likes:sync, media:mirror, media:push
  Filament/             админка: Resources/* (CRUD), Pages/RecycleBin (корзина), RichContent, Support
  Http/Controllers/     публичные страницы + Auth (magic link, Google)
  Http/Middleware/      SetLocale, EnsureUserIsActive, SetAdminLocale
  Mail/                 MagicLinkMail, NewsletterWelcomeMail
  Models/               Eloquent-модели (+ Concerns/HasLikes, Trashable)
  Services/             LikeService, GoogleTranslate
  Support/              helpers.php (media_url, localized_url…), MediaMirror, FilamentR2, PostBody, …
config/                 стандартные + свои: trash, page_settings, bonus_countries, game_promo
database/migrations/    схема БД (PostgreSQL); сидеры — только для локалки
docs/                   ← вы здесь
infra/                  всё серверное: nginx, cron, logrotate, скрипты deploy/backup/provision, шаблон .env
lang/{en,de,fr}/        переводы интерфейса публичного сайта
public/                 css/styles.css, js/*.js, assets/ — готовая статика (без сборки), index.php
resources/icons/        исходники SVG-иконок → php artisan icons:build → public/assets/icons/sprite.svg
resources/views/        Blade-шаблоны публичного сайта и писем
routes/web.php          все URL (см. architecture.md → «URL-схема»)
routes/console.php      расписание (scheduler)
tests/                  PHPUnit (sqlite in-memory, рабочую БД не трогают)
web.php, helpers.php    ⚠ устаревшие копии routes/web.php и app/Support/helpers.php в корне — не используются
```
