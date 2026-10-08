# Известные проблемы и TODO

Состояние на 2026-10-08. Отмечать сделанное и дописывать новое.

## 🔴 Безопасность

- [ ] **Админка открыта любому зарегистрированному юзеру.** `User::canAccessPanel()` проверяет только
      `isActive()`, ролей нет. Любой, кто зарегистрировался через magic link / Google на сайте, открывает `/admin`
      (сессия общая, guard `web`) и может редактировать/удалять контент и видеть юзеров.
      Решение: поле `is_admin` (миграция) или whitelist email в конфиге + проверка в `canAccessPanel()`.
- [ ] **SSH: root по паролю разрешён** (`PermitRootLogin yes`; `PasswordAuthentication yes` из
      `/etc/ssh/sshd_config.d/50-cloud-init.conf` перекрывает `no` из `60-cloudimg-settings.conf`).
      При этом на сервере лежит deploy key с правом записи в репозиторий. Решение: вход только по ключу.
- [ ] **Origin доступен напрямую по IP** (80/443 открыты всем, а не только Cloudflare) — в обход защиты CF
      (видно по сканерам `.env`/`.git` в nginx error.log). Решение: UFW allow 80/443 только с диапазонов Cloudflare
      или Authenticated Origin Pulls.
- [ ] **`POST /translate` без авторизации**, ходит в неофициальный Google endpoint от имени IP сервера
      (throttle 40/мин на IP). Злоупотребление → бан IP сервера в Google. Решение: auth или официальный API с ключом.
- [ ] Секреты продублированы в `/root/slots-tube-credentials.txt` и `/root/slots-tube-secrets/` — удалить
      после сохранения `.env` в менеджере паролей.

## 🟡 Надёжность

- [ ] **Бэкапы только локальные** (`/var/backups/slotstube` на том же диске). Гибель droplet = потеря БД и `.env`
      (медиа, включая аватарки, лежат в R2). Решение: копия дампов вне сервера (отдельный приватный R2-бакет / DO Spaces / DO Droplet Backups)
      + `.env` в менеджере паролей.
- [ ] `provision.sh` целиком на чистой машине не прогонялся (проверен по частям) — сделать тестовый прогон на
      временном droplet.
- [ ] Redis без лимита памяти и без AOF — некритично при текущих объёмах.
- [ ] Swap отсутствует (16 GB RAM, используется ~1 GB — пока не нужен).

## 🟢 Производительность / удобство

- [ ] PHP-FPM дефолтный: `pm.max_children = 5` (мало для 16 GB, при нагрузке будут очереди) — поднять до ~30–50.
- [ ] `upload_max_filesize = 2M`, `post_max_size = 8M` — крупные обложки/скриншоты не загрузятся в админке.
- [ ] Публичный домен для R2 (`cdn.slots.tube` + `AWS_URL`) убрал бы локальное зеркало `MediaMirror` целиком.
- [ ] `LOG_STACK=single` + `LOG_LEVEL=debug` — на проде лучше `daily` и `warning`.
- [ ] Cloudflare: включить «Always Use HTTPS» (сейчас http на staging отдаёт 200).

## Техдолг в коде

- [ ] Корневые `web.php` и `helpers.php` — устаревшие копии `routes/web.php` / `app/Support/helpers.php`, не используются. Удалить.
- [ ] `app/Http/Middleware/EnsureSlotTrailingSlash.php` не подключён (слэш делает nginx).
- [ ] `resources/views/welcome.blade.php`, `welcome-staging.blade.php`, `app.blade.php` — не используются роутами.
- [ ] Vite/Tailwind-заготовка (`vite.config.js`, `resources/css|js`, `package.json`) не используется — фронт статический.
- [ ] `APP_AVAILABLE_LOCALES` в .env не читается — языки зашиты в `config/app.php`.
- [ ] Сидеры зависят от Faker (dev) — нет продового сидера для первого запуска (админ создаётся `make:filament-user`).
- [ ] `CLAUDE.md` / `AGENTS.md` — заглушка Laravel Boost; `laravel/boost` в require-dev, но `boost:install` не выполнен.
- [ ] Много страниц-заглушек (`/free-slots`, `/bonuses`, `/providers`, `/by-feature`, …) — `pages/stub.blade.php`.

## Сделано

- [x] 2026-10-08 Проект переведён на git + ручной `deploy`; добавлены недостающие миграции и картинки.
- [x] 2026-10-08 `APP_DEBUG=false` на сервере.
- [x] 2026-10-08 Починен cron планировщика (раньше не мог писать лог в `/var/log` и не запускался вовсе).
- [x] 2026-10-08 Ежедневный бэкап БД + `storage/app/public`, проверено восстановление.
- [x] 2026-10-08 Ротация логов (`storage/logs/*.log`, deploy/backup логи).
- [x] 2026-10-08 Команда `media:mirror` для восстановления локального зеркала R2.
- [x] 2026-10-08 Аватарки юзеров пишутся в R2 (раньше — только локально); старые выгружаются `media:push avatars`.
- [x] 2026-10-08 Сортировка по переводимым колонкам в админке (`TranslatableSort`) — исправлено разработчиком.
