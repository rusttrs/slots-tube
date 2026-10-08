#!/usr/bin/env bash
# slots.tube — развернуть проект с нуля на чистом Ubuntu 24.04 (DigitalOcean droplet).
# Подробно и с объяснениями: docs/from-scratch.md
#
# Запуск (от root, на новом сервере). Репозиторий приватный, поэтому скрипт кладём на сервер руками:
#   scp infra/scripts/provision.sh root@NEW_IP:/root/
#   ENV_FILE=/root/slots.env DB_DUMP=/root/slotstube.dump MEDIA_TGZ=/root/storage-public.tgz \
#     bash /root/provision.sh
#
# Все входы необязательны:
#   ENV_FILE   готовый .env (из старого сервера / менеджера паролей). Без него соберётся из infra/env.server.example,
#              и секреты (R2, Resend, Google) придётся вписать руками.
#   DB_DUMP    дамп из /var/backups/slotstube/slotstube-*.dump. Без него — пустая БД (только миграции).
#   MEDIA_TGZ  архив storage-public-*.tgz (необязателен: все медиа, включая аватарки, подтянутся из R2 — media:mirror).
#
# Скрипт идемпотентен: если остановился (например, ждёт deploy key) — просто запусти ещё раз.
set -Eeuo pipefail
trap 'echo; echo "!! provision упал на строке $LINENO"' ERR

REPO="${REPO:-git@github.com:rusttrs/slots-tube.git}"
BRANCH="${BRANCH:-main}"
APP_USER="${APP_USER:-deploy}"
APP_DIR="${APP_DIR:-/var/www/slots.tube}"
PHP_VERSION="${PHP_VERSION:-8.4}"
DB_NAME="${DB_NAME:-slotstube}"
DB_USER="${DB_USER:-slotstube}"
ENV_FILE="${ENV_FILE:-}"
DB_DUMP="${DB_DUMP:-}"
MEDIA_TGZ="${MEDIA_TGZ:-}"

step() { echo; echo "==> $*"; }
as_app() { sudo -u "$APP_USER" -H "$@"; }
env_get() { grep -E "^$1=" "$2" 2>/dev/null | tail -1 | cut -d= -f2- | sed -E 's/^"(.*)"$/\1/'; }
env_set() { # env_set KEY VALUE FILE
  if grep -qE "^$1=" "$3"; then sed -i "s|^$1=.*|$1=$2|" "$3"; else echo "$1=$2" >> "$3"; fi
}

[[ $EUID -eq 0 ]] || { echo "Запусти от root"; exit 1; }
for f in "$ENV_FILE" "$DB_DUMP" "$MEDIA_TGZ"; do
  [[ -z "$f" || -f "$f" ]] || { echo "Файл не найден: $f"; exit 1; }
done
export DEBIAN_FRONTEND=noninteractive

# ---------------------------------------------------------------- 1. пакеты
step "1/12 Пакеты: nginx, PHP $PHP_VERSION, PostgreSQL 16, Redis, утилиты"
apt-get update -y
apt-get install -y curl ca-certificates gnupg software-properties-common ufw fail2ban unzip git \
  nginx redis-server postgresql postgresql-contrib
if ! apt-cache policy | grep -q ondrej/php; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update -y
fi
apt-get install -y "php${PHP_VERSION}"-{fpm,cli,common,pgsql,redis,mbstring,xml,curl,zip,gd,intl,bcmath,opcache}
if ! command -v composer >/dev/null; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
systemctl enable --now nginx "php${PHP_VERSION}-fpm" postgresql redis-server fail2ban

# ---------------------------------------------------------------- 2. фаервол
step "2/12 UFW: открыты только 22, 80, 443"
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# ---------------------------------------------------------------- 3. пользователь
step "3/12 Пользователь $APP_USER (группа www-data) и SSH deploy key"
if ! id -u "$APP_USER" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "$APP_USER"
fi
usermod -aG www-data "$APP_USER"
install -d -m 700 -o "$APP_USER" -g "$APP_USER" "/home/$APP_USER/.ssh"
if [[ ! -f "/home/$APP_USER/.ssh/id_ed25519" ]]; then
  as_app ssh-keygen -t ed25519 -N "" -C "slots-tube-server-deploy" -f "/home/$APP_USER/.ssh/id_ed25519" -q
fi
if ! grep -q github.com "/home/$APP_USER/.ssh/known_hosts" 2>/dev/null; then
  ssh-keyscan -t ed25519,rsa,ecdsa github.com 2>/dev/null >> "/home/$APP_USER/.ssh/known_hosts"
  chown "$APP_USER:$APP_USER" "/home/$APP_USER/.ssh/known_hosts"
fi
as_app git config --global user.name  >/dev/null || as_app git config --global user.name "Claude (slots.tube server)"
as_app git config --global user.email >/dev/null || as_app git config --global user.email "deploy@slots.tube"
as_app git config --global pull.ff only

if ! as_app git ls-remote "$REPO" >/dev/null 2>&1; then
  echo
  echo "!! Нет доступа к $REPO. Добавь этот ключ в GitHub → репозиторий → Settings → Deploy keys"
  echo "   (Allow write access — если сервер должен уметь пушить), затем запусти скрипт ещё раз:"
  echo
  cat "/home/$APP_USER/.ssh/id_ed25519.pub"
  exit 2
fi

# ---------------------------------------------------------------- 4. код
step "4/12 git clone → $APP_DIR"
if [[ ! -d "$APP_DIR/.git" ]]; then
  install -d -o "$APP_USER" -g www-data "$APP_DIR"
  as_app git clone --branch "$BRANCH" "$REPO" "$APP_DIR"
fi
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true
cd "$APP_DIR"

# ---------------------------------------------------------------- 5. .env
step "5/12 .env"
if [[ ! -f .env ]]; then
  if [[ -n "$ENV_FILE" ]]; then
    install -m 640 -o "$APP_USER" -g www-data "$ENV_FILE" .env
    echo "   взят из $ENV_FILE"
  else
    install -m 640 -o "$APP_USER" -g www-data infra/env.server.example .env
    echo "   собран из infra/env.server.example — СЕКРЕТЫ НАДО ВПИСАТЬ РУКАМИ (docs/env.md)"
  fi
fi
DB_PASSWORD="$(env_get DB_PASSWORD .env)"
if [[ -z "$DB_PASSWORD" || "$DB_PASSWORD" == "null" ]]; then
  DB_PASSWORD="$(openssl rand -base64 32 | tr -d '=+/' | cut -c1-32)"
  env_set DB_PASSWORD "$DB_PASSWORD" .env
  echo "   сгенерирован DB_PASSWORD"
fi

# ---------------------------------------------------------------- 6. PostgreSQL
step "6/12 PostgreSQL: роль $DB_USER, база $DB_NAME"
if sudo -u postgres psql -tAc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1; then
  sudo -u postgres psql -qc "ALTER ROLE $DB_USER WITH LOGIN PASSWORD '$DB_PASSWORD';"
else
  sudo -u postgres psql -qc "CREATE ROLE $DB_USER WITH LOGIN PASSWORD '$DB_PASSWORD';"
fi
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1; then
  sudo -u postgres psql -qc "CREATE DATABASE $DB_NAME OWNER $DB_USER;"
fi
sudo -u postgres psql -qd "$DB_NAME" -c "GRANT ALL ON SCHEMA public TO $DB_USER;"

# ---------------------------------------------------------------- 7. зависимости
step "7/12 composer install"
as_app install -d storage/framework/{cache/data,sessions,views} storage/logs storage/app/{public,private} bootstrap/cache
chown -R "$APP_USER":www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
as_app composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
if [[ -z "$(env_get APP_KEY .env)" ]]; then
  as_app php artisan key:generate --force
  echo "   !! сгенерирован НОВЫЙ APP_KEY: старые сессии/remember-me станут недействительны."
fi

# ---------------------------------------------------------------- 8. данные БД
step "8/12 Данные БД"
TABLES=$(sudo -u postgres psql -tAd "$DB_NAME" -c "SELECT count(*) FROM pg_tables WHERE schemaname='public'")
if [[ -n "$DB_DUMP" && "$TABLES" -eq 0 ]]; then
  echo "   pg_restore из $DB_DUMP"
  sudo -u postgres pg_restore --no-owner --role="$DB_USER" -d "$DB_NAME" < "$DB_DUMP"
elif [[ -n "$DB_DUMP" ]]; then
  echo "   в базе уже есть таблицы ($TABLES) — дамп НЕ восстанавливаю (чтобы ничего не затереть)."
fi
as_app php artisan migrate --force

# ---------------------------------------------------------------- 9. медиа
step "9/12 Медиа: storage/app/public"
if [[ -n "$MEDIA_TGZ" ]]; then
  tar -xzf "$MEDIA_TGZ" -C storage/app/public
  chown -R "$APP_USER":www-data storage/app/public
fi
if [[ -n "$(env_get AWS_ACCESS_KEY_ID .env)" ]]; then
  as_app php artisan media:mirror || echo "   !! media:mirror завершился с ошибками (проверь R2-ключи в .env)"
else
  echo "   AWS_* (R2) не заданы — media:mirror пропущен"
fi

# ---------------------------------------------------------------- 10. SSL
step "10/12 SSL (Cloudflare Origin Certificate)"
if [[ ! -f /etc/ssl/cloudflare/origin.pem || ! -f /etc/ssl/cloudflare/origin.key ]]; then
  echo "   !! Нет /etc/ssl/cloudflare/origin.pem и origin.key."
  echo "      Cloudflare → slots.tube → SSL/TLS → Origin Server → Create Certificate (RSA, *.slots.tube + slots.tube),"
  echo "      сохрани в эти файлы (key — chmod 600) и запусти: bash $APP_DIR/infra/scripts/install-configs.sh"
fi

# ---------------------------------------------------------------- 11. конфиги
step "11/12 nginx / cron / logrotate / bin"
bash "$APP_DIR/infra/scripts/install-configs.sh"

# ---------------------------------------------------------------- 12. деплой
step "12/12 deploy --force (кеши, права, health-check)"
if [[ -f /etc/ssl/cloudflare/origin.pem ]]; then
  /usr/local/bin/deploy --force
else
  echo "   пропущен до установки SSL-сертификата (nginx без него не стартует с конфигом сайта)"
fi

cat <<EOF

✅ provision завершён. Что осталось (docs/from-scratch.md, «После скрипта»):
  - Cloudflare DNS: A-записи staging (и прод, когда придёт время) → IP этого сервера, Proxied
  - Google OAuth: Authorized redirect URI = APP_URL/auth/google/callback (если домен сменился)
  - Админ, если база пустая:  cd $APP_DIR && sudo -u $APP_USER php artisan make:filament-user
  - Проверка: curl -I https://staging.slots.tube/ ; вход в /admin ; страница слота с картинками
EOF
