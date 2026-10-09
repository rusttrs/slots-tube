# Эксплуатация

Все команды — на сервере (`ssh root@188.166.21.66`), из `/var/www/slots.tube`.
artisan/composer/git — **всегда от `deploy`**: `sudo -u deploy php artisan …`, `sudo -u deploy -H git …`
(от root создадутся файлы, которые потом не сможет перезаписать `www-data` → ошибки вида `touch(): Utime failed`).

## Где смотреть, если что-то не так

| Что | Где |
|---|---|
| Ошибки приложения | `tail -f storage/logs/laravel.log` (или `grep ERROR storage/logs/laravel.log \| tail`) |
| Планировщик | `storage/logs/schedule.log` |
| Деплои | `/var/log/slots-deploy.log` |
| Бэкапы | `/var/log/slots-backup.log` |
| nginx | `/var/log/nginx/error.log`, `access.log` |
| PHP-FPM | `journalctl -u php8.4-fpm`, `/var/log/php8.4-fpm.log` |
| PostgreSQL | `/var/log/postgresql/postgresql-16-main.log` |
| Сервисы | `systemctl status nginx php8.4-fpm postgresql redis-server` |
| Кто забанен | `fail2ban-client status sshd` |

`APP_DEBUG=false` — пользователь видит обычную страницу 500, подробности только в `laravel.log`.

## Частые команды

```bash
deploy                                                  # выкатить main
deploy --force                                          # переразвернуть (после правки .env)
sudo -u deploy php artisan about                        # сводка: окружение, драйверы, кеши
sudo -u deploy php artisan migrate:status
sudo -u deploy php artisan route:list --except-vendor
sudo -u deploy php artisan tinker                       # REPL с моделями
sudo -u deploy php artisan make:filament-user           # создать юзера с паролем (доступ в /admin ещё не даёт)
sudo -u deploy php artisan user:admin                   # список админов
sudo -u deploy php artisan user:admin me@x.com [--revoke] # выдать / забрать доступ в /admin
sudo -u deploy php artisan media:mirror                 # перекачать все медиа из R2 в локальное зеркало
sudo -u deploy php artisan media:push avatars           # выгрузить в R2 локальные файлы папки, которых там нет
sudo -u deploy php artisan trash:purge [--days=N]       # очистить корзину вручную
sudo -u deploy php artisan likes:sync                   # пересчитать лайки
sudo -u deploy php artisan cache:clear                  # сбросить кеш приложения (Redis db1; сессии не трогает)
sudo -u deploy php artisan down / up                    # режим обслуживания
sudo -u postgres psql slotstube                         # консоль БД
redis-cli -n 0 dbsize ; redis-cli -n 1 dbsize           # сессии / кеш
```

## Планировщик

crontab пользователя `deploy` (`crontab -u deploy -l`) — каждую минуту `schedule:run`.
Расписание — `routes/console.php`; посмотреть: `sudo -u deploy php artisan schedule:list`.
Проверка, что крон жив: `tail -3 storage/logs/schedule.log` — строки каждую минуту.

## Бэкапы

`/usr/local/bin/slots-backup-db` (исходник `infra/bin/slots-backup-db`), cron `/etc/cron.d/slots-tube-backup`,
ежедневно **03:00 UTC**, до `trash:purge`. Хранится 14 дней в `/var/backups/slotstube/` (`700 postgres`, файлы `600 root`):

- `slotstube-YYYYMMDD-HHMM.dump` — БД, `pg_dump -Fc`; после дампа проверяется `pg_restore --list`.
- `storage-public-YYYYMMDD-HHMM.tgz` — `storage/app/public` (зеркало R2; все загрузки, включая аватарки, есть и в R2).

Сделать бэкап вручную: `slots-backup-db`. Лог: `/var/log/slots-backup.log`.

⚠ Бэкапы лежат на том же сервере — от гибели droplet не спасают ([known-issues.md](known-issues.md)).

### Восстановление БД

```bash
F=/var/backups/slotstube/slotstube-YYYYMMDD-HHMM.dump

# 1) проверить дамп во временной базе (безопасно)
sudo -u postgres createdb -O slotstube slotstube_check
sudo -u postgres pg_restore --no-owner --role=slotstube -d slotstube_check < $F
sudo -u postgres psql -d slotstube_check -c "select count(*) from users"
sudo -u postgres dropdb slotstube_check

# 2) восстановить в рабочую (затирает текущие данные!)
cd /var/www/slots.tube && sudo -u deploy php artisan down
slots-backup-db                                   # на всякий случай — снимок текущего состояния
sudo -u postgres pg_restore --clean --if-exists --no-owner --role=slotstube -d slotstube < $F
sudo -u deploy php artisan migrate --force        # если дамп старее кода
sudo -u deploy php artisan up
```

### Восстановление файлов

```bash
tar -xzf /var/backups/slotstube/storage-public-YYYYMMDD-HHMM.tgz -C /var/www/slots.tube/storage/app/public
chown -R deploy:www-data /var/www/slots.tube/storage/app/public
sudo -u deploy php artisan media:mirror   # докачать из R2 то, чего нет в архиве
```

## Медиа

Если картинки на сайте битые (ведут на `*.r2.cloudflarestorage.com` или 404 на `/storage/...`) — локальное зеркало
не синхронизировано: `sudo -u deploy php artisan media:mirror`. Проверить ключи R2:
`sudo -u deploy php artisan tinker --execute='echo count(Storage::disk("r2")->allFiles());'`.

## Траблшутинг

| Симптом | Что делать |
|---|---|
| 500 на всём сайте после правки `.env` | конфиг закеширован → `deploy --force` |
| 500 после деплоя | `tail -50 storage/logs/laravel.log`; откат — `git revert` + push + `deploy` (deploy.md) |
| `touch(): Utime failed` / `Permission denied` в storage | artisan запускали от root → `chown -R deploy:www-data storage bootstrap/cache && chmod -R ug+rwX storage bootstrap/cache` |
| `could not identify an ordering operator for type json` | сортировка по переводимой JSON-колонке → в таблице Filament использовать `->sortable(query: TranslatableSort::by('поле'))` |
| Письма не приходят | `grep -i resend storage/logs/laravel.log`; ключ `RESEND_KEY`; домен в Resend → Domains (DKIM/SPF зелёные) |
| Вход через Google — `redirect_uri_mismatch` | добавить `GOOGLE_REDIRECT_URI` в Authorized redirect URIs клиента в Google Cloud Console |
| Бонусы не те для страны | заголовок `CF-IPCountry` (Cloudflare → IP Geolocation); на staging проверить `?country=DE` |
| `deploy`: «незакоммиченные правки» | кто-то правил файлы на сервере: `sudo -u deploy -H git status` → закоммитить/запушить или `git checkout -- <файл>` |
| `deploy`: «незапушенные коммиты» | `sudo -u deploy -H git push origin main` (или `pull --rebase`, если main ушёл вперёд) |
| Плановые задачи не выполняются | `crontab -u deploy -l`, `tail storage/logs/schedule.log`, `grep CRON /var/log/syslog \| tail` |
| Диск / память | `df -h /`, `free -h`, `du -sh /var/www/slots.tube/storage/* /var/backups/slotstube` |

## Обновления системы

Security-патчи Ubuntu ставятся сами (`unattended-upgrades`). Иногда:
`apt update && apt upgrade` (PHP из PPA ondrej обновится так же), затем `systemctl reload php8.4-fpm nginx`.
Если пакет требует перезагрузки — `/var/run/reboot-required`; ребут безопасен (все сервисы в автозапуске).
Обновление PHP-зависимостей проекта — локально `composer update`, коммит `composer.lock`, `deploy`.
