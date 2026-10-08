# Инфраструктура

Снято с живого сервера 2026-10-08. Все конфиги, которые отличаются от дефолтов Ubuntu, лежат в `infra/`
и раскладываются по системе скриптом `infra/scripts/install-configs.sh`.

## Сервер

| | |
|---|---|
| Провайдер | DigitalOcean, регион AMS3, droplet `ubuntu-s-4vcpu-16gb-320gb-intel-ams3` |
| Ресурсы | 4 vCPU, 16 GB RAM, 320 GB SSD, **swap нет**. Загрузка: ~1 GB RAM, ~4 GB диска — запас огромный |
| ОС | Ubuntu 24.04 LTS, timezone **UTC** (NTP on), `unattended-upgrades` включён (security-обновления сами) |
| IP | `188.166.21.66` (публичный; при этом сайт должен открываться через Cloudflare) |
| Агенты DO | `do-agent` (мониторинг в панели DO), `droplet-agent` (веб-консоль) |

### Пользователи и права

| Кто | Зачем |
|---|---|
| `root` | вход по SSH (ключ `slots-tube` в `/root/.ssh/authorized_keys`; ⚠ пароль тоже разрешён, см. known-issues) |
| `deploy` (uid 1000, группы `deploy`, `www-data`) | владелец кода, под ним git/composer/artisan/scheduler. SSH-входа нет. Ключ `/home/deploy/.ssh/id_ed25519` = **deploy key GitHub с правом записи** |
| `www-data` | nginx и php-fpm |

Код: `/var/www/slots.tube`, владелец `deploy:www-data`; `storage/` и `bootstrap/cache/` — `ug+rwX`
(чтобы писать могли и `deploy`, и `www-data`). `.env` — `640 deploy:www-data`.
Git-автор коммитов с сервера: `Claude (slots.tube server) <deploy@slots.tube>`.

### Пакеты и источники

| Компонент | Версия | Источник |
|---|---|---|
| nginx | 1.24 | Ubuntu |
| PHP-FPM + CLI | 8.4 | PPA `ondrej/php` |
| PHP-расширения | bcmath, curl, gd, intl, mbstring, opcache, pgsql, redis (+igbinary), xml, zip, readline | PPA |
| Composer | 2.x | `/usr/local/bin/composer` (getcomposer.org) |
| PostgreSQL | 16 | Ubuntu |
| Redis | 7.0 | Ubuntu |
| Node.js | 22 | NodeSource — **не нужен** (сборки фронта нет), стоит от первоначального bootstrap |
| ufw, fail2ban, git, unzip, tmux | | Ubuntu |

## Сеть и безопасность

- **UFW:** deny incoming по умолчанию; открыты `22/tcp`, `80/tcp`, `443/tcp` (v4+v6).
- **Слушают только localhost:** PostgreSQL `127.0.0.1:5432`, Redis `127.0.0.1:6379`.
- **fail2ban:** один jail `sshd` (дефолтные настройки Debian). Банит активно (за неделю ~1500 IP).
- ⚠ Порты 80/443 открыты всему интернету, а не только Cloudflare — сайт доступен напрямую по IP.

## Nginx — `infra/nginx/slots.tube.conf` → `/etc/nginx/sites-available/slots.tube`

`nginx.conf` — дефолтный Ubuntu. `sites-enabled/` содержит только `slots.tube` (default удалён).

- Два server-блока с одинаковым содержимым: `:80` (`default_server`, server_name `staging.slots.tube slots.tube www.slots.tube _`)
  и `:443 ssl http2` (те же имена). Редиректа http→https на origin нет — его делает (или должен делать) Cloudflare.
- SSL: `/etc/ssl/cloudflare/origin.pem` + `origin.key` — **Cloudflare Origin Certificate** на `*.slots.tube, slots.tube`,
  выпущен 2026-10-02, действует до **2041-09-28**. Браузеру не доверен — работает только за Cloudflare (режим Full (strict)).
  Рядом лежат `origin-fullchain.pem` и `origin_ca_rsa_root.pem` (не используются в конфиге). В git ключа нет.
- Реальный IP: `real_ip_header CF-Connecting-IP` + `set_real_ip_from` для всех диапазонов Cloudflare
  (если CF добавит новые диапазоны — обновить список, https://www.cloudflare.com/ips/).
- `root /var/www/slots.tube/public`, `try_files $uri $uri/ /index.php?$query_string`, PHP через
  `unix:/run/php/php8.4-fpm.sock`, `fastcgi_param SCRIPT_FILENAME $realpath_root…` (важно для атомарных подмен папки).
- 301 `/slots/x` → `/slots/x/` и `/(de|fr)/slots/x` → `…/x/` (канонический слэш).
- Запрет dot-файлов (`/.env`, `/.git` → 403), кроме `/.well-known`.
- Заголовки: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`,
  **`X-Robots-Tag: noindex, nofollow`** (staging не индексируется; при запуске прода — убрать).
- Логи: `/var/log/nginx/{access,error}.log` (ротация — системный logrotate nginx).
- Остатки: `/var/www/coming-soon/` (старая заглушка из bootstrap), `/var/www/html/` (дефолт nginx) — не используются.

## PHP-FPM

Конфиги дефолтные (`/etc/php/8.4/fpm/php.ini`, `pool.d/www.conf` не менялись):
`pm = dynamic`, `max_children = 5`, `memory_limit = 128M`, `upload_max_filesize = 2M`, `post_max_size = 8M`,
`max_execution_time = 30`, OPcache включён (`validate_timestamps = On`, 128 MB), JIT off.
После деплоя делается `systemctl reload php8.4-fpm` (сброс OPcache).
⚠ `upload_max_filesize = 2M` ограничивает загрузки в админке (обложки/скриншоты > 2 МБ не загрузятся), а
`max_children = 5` — мало для 16 GB; см. known-issues.

## PostgreSQL 16

- Кластер `16/main`, конфиг дефолтный (`shared_buffers` 128 MB, `max_connections` 100, timezone UTC).
- Роль `slotstube` (не суперюзер, пароль в `.env` → `DB_PASSWORD`), база `slotstube` (владелец `slotstube`).
- `pg_hba`: локально peer, по TCP с 127.0.0.1/::1 — scram-sha-256. Приложение ходит по TCP `127.0.0.1:5432`.
- Расширений, кроме `plpgsql`, нет. Размер БД ~10 MB (2026-10-08).
- Консоль: `sudo -u postgres psql slotstube`.

## Redis 7

Дефолтный конфиг Ubuntu: без пароля (только localhost), `maxmemory 0` (без лимита), `noeviction`,
RDB-снапшоты (`save 3600 1 300 100 60 10000`), без AOF.
- db0 — **сессии** (`SESSION_DRIVER=redis`, cookie `slotstube-session`, 120 мин).
- db1 — **кеш** (`CACHE_STORE=redis`): переводы Google Translate (30 дней) и прочее.
- Очередь (`QUEUE_CONNECTION=redis`) — настроена, не используется.
Перезапуск Redis теряет максимум последние минуты сессий — некритично.

## Cron и фоновые задачи

| Где | Расписание | Команда |
|---|---|---|
| crontab `deploy` (`infra/cron/deploy.crontab`) | каждую минуту | `php artisan schedule:run >> storage/logs/schedule.log` → `trash:purge` 03:15, `likes:sync` 03:45 |
| `/etc/cron.d/slots-tube-backup` (`infra/cron/slots-tube-backup`) | 03:00 UTC, root | `/usr/local/bin/slots-backup-db >> /var/log/slots-backup.log` |

Воркеров очереди, supervisor, Horizon — нет.

## Скрипты (`infra/bin` → `/usr/local/bin`)

| Команда | Что делает | Документ |
|---|---|---|
| `deploy [--force]` | git pull main → composer → migrate → кеши → права → reload fpm → health-check | [deploy.md](deploy.md) |
| `slots-backup-db` | pg_dump + tar `storage/app/public` → `/var/backups/slotstube`, хранит 14 дней | [operations.md](operations.md) |

Состояние деплоя: `/var/lib/slots-deploy/last-deployed` (SHA последнего успешного деплоя), lock `/run/slots-deploy.lock`.

## Логи и ротация (`infra/logrotate/slots-tube`)

| Лог | Ротация |
|---|---|
| `storage/logs/laravel.log` (канал `single`, уровень debug) | weekly ×8 или >50 MB, сжатие, copytruncate |
| `storage/logs/schedule.log` | так же |
| `/var/log/slots-deploy.log`, `/var/log/slots-backup.log` | monthly ×12 |

## Cloudflare и DNS

Зона `slots.tube` на Cloudflare (NS `desiree.ns.cloudflare.com`, `matias.ns.cloudflare.com`).
Доступа к панели Cloudflare с сервера нет — ниже то, что видно снаружи; проверять в дашборде CF.

| Запись | Куда | Proxied |
|---|---|---|
| `staging.slots.tube` | A → `188.166.21.66` (этот сервер) | да |
| `slots.tube`, `www.slots.tube` | **GitHub Pages** (заглушка Coming Soon; виден заголовок `x-github-request-id`) | да |
| `resend._domainkey` | TXT DKIM Resend | — |
| `send.slots.tube` | CNAME `send.forge.rmta.net` (отдаёт MX `feedback.forge.rmta.net` и SPF) — bounce-домен Resend | — |

Что должно быть включено в Cloudflare для корректной работы:
- **SSL/TLS → Full (strict)** (origin-сертификат CF).
- **IP Geolocation** (заголовок `CF-IPCountry`) — на нём держится гео-таргетинг бонусов. Включено по умолчанию.
- Желательно: «Always Use HTTPS» (сейчас `http://staging.slots.tube` отдаёт 200 без редиректа).

**Переключение прода на этот сервер (когда придёт время):** A-записи `slots.tube`/`www` → IP сервера (proxied),
`APP_URL`/`GOOGLE_REDIRECT_URI` в `.env` → `https://slots.tube`, `APP_ENV=production`,
убрать `X-Robots-Tag` из nginx, добавить redirect URI в Google OAuth, `deploy --force`.

## Внешние сервисы (аккаунты)

| Сервис | Для чего | Что в .env | Где управлять |
|---|---|---|---|
| Cloudflare | DNS, прокси, SSL, R2 | `AWS_*` (R2) | dash.cloudflare.com → зона slots.tube; R2 → бакет `slots-tube-media` |
| Cloudflare R2 | хранилище загрузок (приватный бакет, ~40 объектов) | `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT`, `AWS_BUCKET` | R2 → Manage R2 API tokens |
| Resend | отправка писем | `RESEND_KEY` / `RESEND_API_KEY` | resend.com → API Keys, Domains |
| Google Cloud | вход через Google (OAuth client `196155955612-…apps.googleusercontent.com`) | `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI` | console.cloud.google.com → Credentials |
| Google Translate | перевод UGC, неофициальный endpoint без ключа | — | — |
| GitHub | репозиторий `rusttrs/slots-tube`, deploy key сервера | — | Settings → Deploy keys |
| GitHub Pages | заглушка на `slots.tube` | — | (репо заглушки — не этот) |
| DigitalOcean | сервер | — | cloud.digitalocean.com |
| jsDelivr, Google Fonts | Swiper, шрифты на фронте | — | — |

## Секреты на сервере (не в git)

| Путь | Что |
|---|---|
| `/var/www/slots.tube/.env` | **все** секреты приложения (источник истины) |
| `/etc/ssl/cloudflare/origin.{pem,key}` | origin-сертификат Cloudflare |
| `/home/deploy/.ssh/id_ed25519` | deploy key GitHub (запись в репо) |
| `/root/slots-tube-credentials.txt` | пароль Postgres от первоначального bootstrap (дубль `.env`; скрипт bootstrap советовал удалить) |
| `/root/slots-tube-secrets/{r2,resend}.env` | дубли R2/Resend-ключей из `.env` |
| `/root/backups/` | ручные бэкапы переезда на git (2026-10-08): архив старой папки и дамп БД |
