# infra/ — всё серверное

Копии конфигов живого сервера (staging, DigitalOcean `188.166.21.66`). Описание — [docs/infrastructure.md](../docs/infrastructure.md).

| Файл в репо | Куда на сервере | Как применить |
|---|---|---|
| `nginx/slots.tube.conf` | `/etc/nginx/sites-available/slots.tube` (+ symlink в `sites-enabled`) | `install-configs.sh` |
| `bin/deploy` | `/usr/local/bin/deploy` | `install-configs.sh` |
| `bin/slots-backup-db` | `/usr/local/bin/slots-backup-db` | `install-configs.sh` |
| `cron/deploy.crontab` | crontab пользователя `deploy` | `install-configs.sh` |
| `cron/slots-tube-backup` | `/etc/cron.d/slots-tube-backup` | `install-configs.sh` |
| `logrotate/slots-tube` | `/etc/logrotate.d/slots-tube` | `install-configs.sh` |
| `env.server.example` | шаблон для `/var/www/slots.tube/.env` | вручную / `provision.sh` |
| `scripts/install-configs.sh` | — | `sudo bash infra/scripts/install-configs.sh` (идемпотентно) |
| `scripts/provision.sh` | — | разворот с нуля, см. [docs/from-scratch.md](../docs/from-scratch.md) |

**Правило:** поменял что-то на сервере руками → скопируй сюда и закоммить. Поменял здесь → после `deploy`
запусти `install-configs.sh` (деплой сам конфиги системы не трогает).

Чего здесь нет намеренно (секреты): `.env`, `/etc/ssl/cloudflare/origin.key`, deploy key.
