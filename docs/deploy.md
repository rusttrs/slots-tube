# Деплой

Автодеплоя нет — намеренно. Пушим в `main`, выкатываем руками одной командой, когда нужно.

## Обычный цикл

```bash
# локально
git push origin main

# выкатить
ssh root@188.166.21.66 deploy
```

Или попросить Claude на сервере: «спулль и задеплой».

## Что делает `deploy` (`infra/bin/deploy` → `/usr/local/bin/deploy`)

1. **Lock** (`/run/slots-deploy.lock`) — два деплоя одновременно не пойдут.
2. **Проверка рабочей копии**: если на сервере есть незакоммиченные правки в отслеживаемых файлах — стоп
   (чтобы не затереть). Неотслеживаемые файлы (`.env`, `storage/*`, `vendor/`) не мешают.
3. `git fetch origin main`. Если на сервере есть **незапушенные коммиты** — стоп («сначала git push»).
4. `git merge --ff-only origin/main` — только fast-forward. Если ветки разошлись — упадёт, разбираться руками.
5. Сравнивает HEAD с последним **успешно развёрнутым** коммитом (`/var/lib/slots-deploy/last-deployed`), а не с
   предыдущим HEAD — поэтому коммит, сделанный и запушенный прямо с сервера, тоже будет «доразвёрнут».
   Нечего выкатывать → выходит (`--force` — выкатить всё равно).
6. `composer install --no-dev --optimize-autoloader` (post-autoload-dump публикует ассеты Filament/Livewire).
7. `php artisan migrate --force`.
8. `optimize:clear --except=cache` → `optimize` (config, routes, views, events, blade-icons, filament) → `filament:optimize`.
   Кеш приложения в Redis (переводы и т.п.) при этом **не** сбрасывается.
9. `storage:link` (если нет), права `deploy:www-data ug+rwX` на `storage/` и `bootstrap/cache/`.
10. `systemctl reload php8.4-fpm` — сброс OPcache.
11. **Health-check**: `GET http://127.0.0.1/` с `Host: staging.slots.tube` должен вернуть 200. Иначе — ошибка с
    подсказкой отката. Успех → SHA пишется в `/var/lib/slots-deploy/last-deployed`.

Вывод каждого деплоя дописывается в `/var/log/slots-deploy.log`.

**Без даунтайма:** maintenance mode не включается; миграции идут на живой базе. Для тяжёлых/ломающих
миграций — вручную `php artisan down` до и `up` после (под `sudo -u deploy`).

## Важно помнить

- **Конфиг закеширован** (`config:cache`): после правки `.env` на сервере обязательно `deploy --force`
  (или `sudo -u deploy php artisan config:cache && systemctl reload php8.4-fpm`), иначе изменения не применятся.
- **`env()` вне `config/*.php` не работает** при закешированном конфиге — в коде использовать только `config()`.
- **Маршруты закешированы** — closures в `routes/web.php` допустимы (Laravel их сериализует), проверено.
- **CSS/JS** не собираются — правка `public/css/styles.css` / `public/js/*.js` + обновить `?v=` в шаблонах.
- Изменения в `infra/` (nginx, cron, logrotate, сами скрипты `deploy`/`slots-backup-db`) **не применяются деплоем** —
  после пуша выполнить `bash /var/www/slots.tube/infra/scripts/install-configs.sh` (идемпотентно).

## Откат

Предпочтительно — через git, чтобы история и сервер не расходились:

```bash
git revert <плохой-коммит>   # локально
git push origin main
ssh root@188.166.21.66 deploy
```

Если плохая миграция уже прошла — откатить её отдельно: `sudo -u deploy php artisan migrate:rollback --step=1`
(перед этим посмотреть `migrate:status`), либо восстановить БД из ночного бэкапа (operations.md).

## Правки прямо на сервере (Claude / срочный хотфикс)

```bash
cd /var/www/slots.tube
sudo -u deploy -H git add -A && sudo -u deploy -H git commit -m "..."
sudo -u deploy -H git push origin main
deploy
```

Все git-операции — **от `deploy`** (у него ключ GitHub; от root git ругается на «dubious ownership»).
Если `push` отклонён (кто-то запушил раньше): `sudo -u deploy -H git pull --rebase origin main`, затем push и `deploy`.

## Первичная настройка доступа (уже сделано)

Ключ сервера `/home/deploy/.ssh/id_ed25519.pub` добавлен в GitHub → `rusttrs/slots-tube` → Settings → Deploy keys
с **Allow write access** (сервер может пушить). На новом сервере ключ генерирует `provision.sh`.
