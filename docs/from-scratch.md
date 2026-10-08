# Разворот с нуля на новом сервере

Цель — поднять копию slots.tube на чистом Ubuntu 24.04 примерно за 10 минут. Скрипт
`infra/scripts/provision.sh` делает почти всё сам и идемпотентен (упал или остановился — запусти ещё раз).

## Что нужно иметь на руках

| Что | Откуда | Без этого |
|---|---|---|
| Доступ к GitHub `rusttrs/slots-tube` (чтобы добавить deploy key) | владелец репо | не склонировать |
| `.env` со старого сервера (или секреты из менеджера паролей) | `/var/www/slots.tube/.env` | соберётся из шаблона, секреты R2 / Resend / Google вписать руками ([env.md](env.md)) |
| Дамп БД `slotstube-*.dump` | `/var/backups/slotstube/` на старом сервере | будет пустая база (только схема) |
| Архив `storage-public-*.tgz` | там же | не критично: все медиа, включая аватарки, восстановятся из R2 (`media:mirror`), только дольше |
| Cloudflare Origin Certificate (`origin.pem` + `origin.key`) | `/etc/ssl/cloudflare/` старого сервера или выпустить новый в CF | nginx не поднимет HTTPS |
| Доступ к Cloudflare dashboard | владелец зоны | не переключить DNS |

> ⚠ Сейчас `.env`, бэкапы и сертификат лежат **только на сервере**. Если сервер умрёт целиком, их не будет.
> Положите `.env` в менеджер паролей и настройте внешнюю копию бэкапов ([known-issues.md](known-issues.md)).

## Шаг 0. Создать сервер

DigitalOcean → Create Droplet: Ubuntu 24.04 LTS x64, регион AMS3 (или любой), от 2 vCPU / 4 GB (текущий 4/16 — с
большим запасом), SSH-ключ — свой. Записать IP → дальше `NEW_IP`.

## Шаг 1. Собрать входные файлы

**Старый сервер жив** (переезд) — забрать прямо с него на свой компьютер:

```bash
OLD=188.166.21.66
ssh root@$OLD slots-backup-db                       # свежий бэкап прямо сейчас
mkdir -p ~/slots-move && cd ~/slots-move
scp root@$OLD:/var/www/slots.tube/.env ./slots.env
scp "root@$OLD:$(ssh root@$OLD 'ls -t /var/backups/slotstube/slotstube-*.dump | head -1')" ./slotstube.dump
scp "root@$OLD:$(ssh root@$OLD 'ls -t /var/backups/slotstube/storage-public-*.tgz | head -1')" ./storage-public.tgz
scp root@$OLD:/etc/ssl/cloudflare/origin.pem root@$OLD:/etc/ssl/cloudflare/origin.key ./
```

> Для переезда без потери данных: перед финальным бэкапом можно включить `ssh root@$OLD "cd /var/www/slots.tube && sudo -u deploy php artisan down"`,
> чтобы на старом никто ничего не писал, пока переключается DNS.

**Старого сервера нет** — взять то, что сохранено вне сервера (менеджер паролей, внешние бэкапы). Чего нет — пропустить:
скрипт работает и без дампа/архива/`.env`.

## Шаг 2. Залить файлы и скрипт на новый сервер

Репозиторий приватный, поэтому `provision.sh` кладём руками (из локального клона репо):

```bash
NEW=<NEW_IP>
scp infra/scripts/provision.sh slots.env slotstube.dump storage-public.tgz root@$NEW:/root/
ssh root@$NEW 'mkdir -p /etc/ssl/cloudflare && chmod 755 /etc/ssl/cloudflare'
scp origin.pem origin.key root@$NEW:/etc/ssl/cloudflare/
ssh root@$NEW 'chmod 644 /etc/ssl/cloudflare/origin.pem; chmod 600 /etc/ssl/cloudflare/origin.key'
```

## Шаг 3. Запустить provision

```bash
ssh root@$NEW
ENV_FILE=/root/slots.env DB_DUMP=/root/slotstube.dump MEDIA_TGZ=/root/storage-public.tgz bash /root/provision.sh
```

Первый запуск остановится на шаге 3 с кодом 2 и выведет **публичный ключ** — добавить его в GitHub →
`rusttrs/slots-tube` → Settings → Deploy keys (✔ Allow write access, если сервер должен уметь пушить) и запустить
ту же команду ещё раз. Старый ключ старого сервера из Deploy keys потом удалить.

Что делает скрипт (12 шагов, подробности в самом файле):

1. apt: nginx, PHP 8.4 (PPA ondrej) + расширения, PostgreSQL 16, Redis, ufw, fail2ban, git, composer.
2. UFW: открыты 22, 80, 443.
3. Пользователь `deploy` (+ группа `www-data`), SSH-ключ, known_hosts GitHub, git-автор.
4. `git clone` → `/var/www/slots.tube`.
5. `.env` из `ENV_FILE` (или из `infra/env.server.example`); генерирует `DB_PASSWORD`, если пуст.
6. PostgreSQL: роль `slotstube` с паролем из `.env`, база `slotstube`.
7. `composer install --no-dev`; `key:generate`, если `APP_KEY` пуст.
8. `pg_restore` дампа — **только если база пустая** (ничего не затирает), затем `migrate --force`.
9. Распаковка `MEDIA_TGZ` в `storage/app/public` + `php artisan media:mirror` (докачивает всё из R2).
10. Проверка наличия SSL-сертификата.
11. `infra/scripts/install-configs.sh`: nginx-сайт, `/usr/local/bin/{deploy,slots-backup-db}`, cron (scheduler + бэкап), logrotate.
12. `deploy --force`: кеши, права, reload php-fpm, health-check.

## Шаг 4. Проверить до переключения DNS

С сервера:

```bash
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: staging.slots.tube" http://127.0.0.1/          # 200
curl -s -o /dev/null -w "%{http_code}\n" -H "Host: staging.slots.tube" http://127.0.0.1/admin/login # 200
cd /var/www/slots.tube && sudo -u deploy php artisan migrate:status | tail -3                     # всё Ran
sudo -u deploy php artisan about | grep -E "Environment|Debug|URL"                                 # staging, OFF
ls storage/app/public | head                                                                        # avatars, slots, providers…
```

Со своего компьютера, в обход DNS:

```bash
curl -sk -o /dev/null -w "%{http_code}\n" --resolve staging.slots.tube:443:$NEW https://staging.slots.tube/
```

## Шаг 5. Переключить DNS

Cloudflare → `slots.tube` → DNS: запись `staging` (A) → `NEW_IP`, Proxied (оранжевое облако).
SSL/TLS режим — Full (strict). Через 1–5 минут проверить в браузере: главная, страница слота с картинками,
`/admin`, вход по magic link (придёт письмо), вход через Google.

Если меняется домен (например, запуск прода на `slots.tube`): `.env` → `APP_URL`, `GOOGLE_REDIRECT_URI`,
`APP_ENV=production`; Google Cloud Console → добавить redirect URI; nginx — убрать `X-Robots-Tag`;
`deploy --force` (подробнее — infrastructure.md → «Переключение прода»).

## Шаг 6. После

- Пустая база (без дампа) → создать админа: `cd /var/www/slots.tube && sudo -u deploy php artisan make:filament-user`.
- Удалить с сервера входные файлы: `rm /root/{slots.env,slotstube.dump,storage-public.tgz,provision.sh}`.
- Убедиться, что крон работает: через минуту `tail storage/logs/schedule.log`.
- Сделать первый бэкап: `slots-backup-db`.
- Старый сервер — выключить, но не удалять неделю-другую.

## Если скрипт падает — ручной эквивалент

Каждый шаг `provision.sh` — несколько обычных команд, их можно выполнить вручную, читая скрипт сверху вниз.
Частые причины:

| Симптом | Причина / решение |
|---|---|
| выход с кодом 2 на шаге 3 | не добавлен deploy key в GitHub — добавить и перезапустить |
| `Please provide a valid cache path` при composer | нет `storage/framework/*` — скрипт создаёт их сам; при ручной установке: `mkdir -p storage/framework/{cache/data,sessions,views}` |
| `pg_restore` ругается на owner/role | использовать `--no-owner --role=slotstube` (как в скрипте) |
| `nginx: cannot load certificate` | нет `/etc/ssl/cloudflare/origin.{pem,key}` — положить и `bash infra/scripts/install-configs.sh` |
| картинки слотов битые | не отработал `media:mirror` — проверить `AWS_*` в `.env`, запустить `sudo -u deploy php artisan media:mirror` |
| 500 сразу после смены `.env` | конфиг закеширован — `deploy --force` |
| `db:seed` падает с `fake()` | сидеры только для локалки (Faker в dev-зависимостях) — на сервере не запускать |

## Промпт для AI-агента

> Разверни slots.tube на этом сервере с нуля. Репозиторий `git@github.com:rusttrs/slots-tube.git`. Прочитай
> `docs/README.md` и `docs/from-scratch.md`, затем выполни `infra/scripts/provision.sh` с файлами в `/root`
> (`slots.env`, `slotstube.dump`, `storage-public.tgz`, если есть). Когда скрипт выведет deploy key — покажи его мне и жди.
> После — пройди чек-лист «Шаг 4» и доложи результат. DNS не трогай.

Скрипт `provision.sh` проверен по частям на текущем сервере (восстановление дампа с `--no-owner --role`, миграции на
пустой базе, `media:mirror` на пустом зеркале, `install-configs.sh`), но целиком на чистой машине ещё не прогонялся.
