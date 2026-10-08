# Переменные окружения (.env)

Шаблон для сервера — [`infra/env.server.example`](../infra/env.server.example); для локальной разработки — корневой
`.env.example` (sqlite, file-драйверы). Живой `.env` на сервере — `/var/www/slots.tube/.env` (`640 deploy:www-data`),
единственный источник истины по секретам. **После любой правки `.env` — `deploy --force`** (конфиг закеширован).

Легенда: 🔒 — секрет, в git не хранится.

## Приложение

| Переменная | Значение на staging | Смысл |
|---|---|---|
| `APP_NAME` | `"slots.tube"` | имя; из него же префиксы кеша и имя cookie сессии (`slotstube-session`) |
| `APP_ENV` | `staging` | `production` отключит подмену страны `?country=XX` (VisitorCountry) |
| `APP_KEY` 🔒 | `base64:…` | ключ шифрования cookies/сессий/подписанных ссылок. **При переезде переносить старый**, иначе всех разлогинит. Новый: `php artisan key:generate` |
| `APP_DEBUG` | `false` | на сервере всегда `false` (иначе наружу стектрейсы). Локально — `true` |
| `APP_URL` | `https://staging.slots.tube` | база для ссылок в письмах, `/storage` URL, Google redirect по умолчанию |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` / `en` | язык по умолчанию (без префикса в URL) |
| `APP_AVAILABLE_LOCALES` | `en,de,fr` | ⚠ **не используется** — список зашит в `config/app.php` |
| `APP_FAKER_LOCALE` | `en_US` | только для сидеров |
| `APP_MAINTENANCE_DRIVER` | `file` | `php artisan down` |
| `BCRYPT_ROUNDS` | `12` | |

## Логи

| Переменная | Значение | |
|---|---|---|
| `LOG_CHANNEL` | `stack` | |
| `LOG_STACK` | `single` | один файл `storage/logs/laravel.log` (ротирует logrotate, см. infrastructure.md) |
| `LOG_LEVEL` | `debug` | |

## База данных

| Переменная | Значение | |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | |
| `DB_HOST` / `DB_PORT` | `127.0.0.1` / `5432` | |
| `DB_DATABASE` / `DB_USERNAME` | `slotstube` / `slotstube` | |
| `DB_PASSWORD` 🔒 | | задаётся при создании роли; `provision.sh` сгенерирует, если пусто. Сменить: `ALTER ROLE slotstube PASSWORD '…'` + `.env` + `deploy --force` |

## Сессии, кеш, очередь, Redis

| Переменная | Значение | |
|---|---|---|
| `SESSION_DRIVER` | `redis` | db0 |
| `SESSION_LIFETIME` | `120` | минут |
| `SESSION_SECURE_COOKIE` | `true` | cookie только по HTTPS |
| `CACHE_STORE` | `redis` | db1 |
| `QUEUE_CONNECTION` | `redis` | не используется (задач нет) |
| `BROADCAST_CONNECTION` | `log` | не используется |
| `REDIS_CLIENT` | `phpredis` | расширение `php8.4-redis` |
| `REDIS_HOST` / `REDIS_PORT` | `127.0.0.1` / `6379` | |
| `REDIS_PASSWORD` | `null` | Redis без пароля (слушает только localhost) |
| `TRUSTED_PROXIES` | `*` | дублирует `trustProxies('*')` в `bootstrap/app.php` |

## Файлы / Cloudflare R2

| Переменная | Значение | Где взять |
|---|---|---|
| `FILESYSTEM_DISK` | `r2` | диск по умолчанию для загрузок |
| `AWS_ACCESS_KEY_ID` 🔒 | | Cloudflare → R2 → Manage R2 API tokens (права Object Read & Write на бакет) |
| `AWS_SECRET_ACCESS_KEY` 🔒 | | там же (показывается один раз) |
| `AWS_DEFAULT_REGION` | `auto` | для R2 всегда `auto` |
| `AWS_BUCKET` | `slots-tube-media` | |
| `AWS_ENDPOINT` | `https://<account-id>.r2.cloudflarestorage.com` | R2 → Overview → S3 API |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` | |
| `AWS_URL` | *(не задан)* | публичный домен бакета. Пока пуст → работает локальное зеркало (architecture.md → «Медиа»). Если подключить `cdn.slots.tube` — зеркало станет не нужно |
| `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK` | `local` | временные файлы загрузок админки — локально, не в R2 |

## Почта (Resend)

| Переменная | Значение | |
|---|---|---|
| `MAIL_MAILER` | `resend` | |
| `MAIL_FROM_ADDRESS` | `"noreply@slots.tube"` | домен верифицирован в Resend |
| `MAIL_FROM_NAME` | `"${APP_NAME}"` | |
| `RESEND_KEY` 🔒 | `re_…` | resend.com → API Keys. `config/services.php` читает `RESEND_KEY`, если пусто — `RESEND_API_KEY` |
| `RESEND_API_KEY` 🔒 | `re_…` | дубль (на сервере заданы оба одинаковые) |

## Google OAuth (вход через Google)

| Переменная | Значение | Где взять |
|---|---|---|
| `GOOGLE_CLIENT_ID` | `196155955612-ue4l9sp0e3ag9vcgrk629o1di69lhoe3.apps.googleusercontent.com` | Google Cloud Console → APIs & Services → Credentials |
| `GOOGLE_CLIENT_SECRET` 🔒 | | там же |
| `GOOGLE_REDIRECT_URI` | `https://staging.slots.tube/auth/google/callback` | **должен быть добавлен** в Authorized redirect URIs клиента; при смене домена — добавить новый |

## Прочее

| Переменная | |
|---|---|
| `VITE_APP_NAME` | не используется (Vite не используется) |
